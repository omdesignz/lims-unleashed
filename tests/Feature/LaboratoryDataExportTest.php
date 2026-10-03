<?php

namespace Tests\Feature;

use App\Actions\PrepareSampleEntryPayload;
use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabCode;
use App\Models\LabNetwork;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Result;
use App\Models\Role;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class LaboratoryDataExportTest extends TestCase
{
    use DatabaseTransactions;

    private ?User $admin = null;

    private ?VAPLab $lab = null;

    public function test_pending_worksheet_includes_only_parameters_without_an_inserted_result(): void
    {
        [$analysis, $parameters] = $this->analysisWithMultipleParameters();

        $analysis->update(['end_date' => null]);
        Result::query()->where('sample_id', $analysis->sample_id)->update([
            'inserted_date' => null,
            'verified_date' => null,
            'approved_date' => null,
        ]);

        $insertedParameter = $parameters->first();
        Result::query()->create([
            'sample_id' => $analysis->sample_id,
            'code_id' => $analysis->cl_id,
            'parameter_id' => $insertedParameter->id,
            'profile_id' => $analysis->profile_id,
            'parameter_label' => $insertedParameter->name,
            'code_label' => $analysis->code?->code,
            'inserted_value' => '12.5',
            'inserted_date' => now(),
        ]);

        $response = $this->actingAs($this->verifiedAdmin())
            ->get(route('analysis.data-exports.index', [
                'view' => 'pending',
                'search' => $analysis->sample->code,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Analysis/DataExports')
                ->where('filters.view', 'pending')
                ->where('canViewPending', true)
                ->where('canViewAudit', true)
            );

        $rows = collect(data_get($response->viewData('page'), 'props.records.data', []));

        $this->assertNotEmpty($rows);
        $this->assertFalse($rows->contains('parameter_id', $insertedParameter->id));
        $this->assertTrue($rows->contains('parameter_id', $parameters->skip(1)->first()->id));
        $this->assertSame($rows->count(), $rows->pluck('parameter_id')->unique()->count());
    }

    public function test_pending_worksheet_uses_issued_scope_after_catalogue_changes(): void
    {
        [$analysis, $parameters] = $this->analysisWithMultipleParameters();
        $first = $parameters->first();
        $issuedName = $first->name;
        $analysis->profile->parameters()->detach($first->id);
        $first->update(['name' => 'Renamed after issuance']);
        $extra = Parameter::query()->create(['name' => 'Added after issuance', 'code' => 'LATE-'.Str::upper(Str::random(8))]);
        $analysis->profile->parameters()->attach($extra);

        $response = $this->actingAs($this->verifiedAdmin())
            ->get(route('analysis.data-exports.index', [
                'view' => 'pending',
                'search' => $analysis->sample->code,
                'per_page' => 100,
            ]))->assertOk();

        $rows = collect(data_get($response->viewData('page'), 'props.records.data', []));
        $this->assertEqualsCanonicalizing($parameters->pluck('id')->all(), $rows->pluck('parameter_id')->all());
        $this->assertSame($issuedName, $rows->firstWhere('parameter_id', $first->id)['parameter']);
    }

    public function test_audit_register_classifies_inserted_verified_and_approved_results(): void
    {
        [$analysis, $parameters] = $this->analysisWithMultipleParameters();
        $analysis->replicate()->save();
        $results = collect([
            $this->createResultAtStage($analysis, $parameters->first(), 'inserted'),
            $this->createResultAtStage($analysis, $parameters->first(), 'verified'),
            $this->createResultAtStage($analysis, $parameters->first(), 'approved'),
        ]);

        $response = $this->actingAs($this->verifiedAdmin())
            ->get(route('analysis.data-exports.index', [
                'view' => 'audit',
                'search' => 'AUDIT-'.$analysis->id,
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Analysis/DataExports')
                ->where('filters.view', 'audit')
                ->where('summary.inserted', 1)
                ->where('summary.verified', 1)
                ->where('summary.approved', 1)
            );

        $rows = collect(data_get($response->viewData('page'), 'props.records.data', []))
            ->whereIn('result_id', $results->pluck('id'));

        $this->assertCount(3, $rows);
        $this->assertSame(3, $rows->pluck('result_id')->unique()->count());
        $this->assertEqualsCanonicalizing(['inserted', 'verified', 'approved'], $rows->pluck('stage')->all());
        $this->assertEqualsCanonicalizing(['Inserido', 'Verificado', 'Aprovado'], $rows->pluck('stage_label')->all());
    }

    public function test_views_and_downloads_enforce_dataset_permissions(): void
    {
        $analysisUser = User::factory()->create(['is_active' => true]);
        $analysisUser->givePermissionTo(Permission::findOrCreate('view_analysis', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->lab()->id, 'user_id' => $analysisUser->id]);

        $this->actingAs($analysisUser)
            ->get(route('analysis.data-exports.index', ['view' => 'pending']))
            ->assertOk();
        $this->actingAs($analysisUser)
            ->get(route('analysis.data-exports.index', ['view' => 'audit']))
            ->assertForbidden();
        $this->actingAs($analysisUser)
            ->get(route('analysis.data-exports.download', ['view' => 'audit']))
            ->assertForbidden();

        $resultsUser = User::factory()->create(['is_active' => true]);
        $resultsUser->givePermissionTo(Permission::findOrCreate('view_results', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->lab()->id, 'user_id' => $resultsUser->id]);

        $this->actingAs($resultsUser)
            ->get(route('analysis.data-exports.index', ['view' => 'audit']))
            ->assertOk();
        $this->actingAs($resultsUser)
            ->get(route('analysis.data-exports.index', ['view' => 'pending']))
            ->assertForbidden();
        $this->actingAs($resultsUser)
            ->get(route('analysis.data-exports.download', ['view' => 'pending']))
            ->assertForbidden();
    }

    public function test_pending_and_audit_workbooks_contain_the_filtered_rows(): void
    {
        [$analysis, $parameters] = $this->analysisWithMultipleParameters();
        $analysis->update(['end_date' => null]);
        Result::query()->where('sample_id', $analysis->sample_id)->update(['inserted_date' => null]);

        $auditResult = $this->createResultAtStage($analysis, $parameters->first(), 'approved', '=AUDIT-FORMULA');
        $this->travelTo(now()->startOfMinute());
        $timestamp = now()->format('Ymd-His');

        $pendingResponse = $this->actingAs($this->verifiedAdmin())
            ->get(route('analysis.data-exports.download', [
                'view' => 'pending',
                'search' => $analysis->sample->code,
            ]))
            ->assertOk()
            ->assertDownload("folha-analises-pendentes-{$timestamp}.xlsx");

        $pendingSheet = $this->worksheetFromResponse($pendingResponse->baseResponse);
        $this->assertSame('Código laboratorial', $pendingSheet->getCell('A1')->getValue());
        $this->assertSame('Análise / parâmetro', $pendingSheet->getCell('I1')->getValue());
        $this->assertTrue(collect($pendingSheet->toArray())->contains(fn (array $row): bool => in_array($analysis->sample->code, $row, true)));

        $auditResponse = $this->actingAs($this->verifiedAdmin())
            ->get(route('analysis.data-exports.download', [
                'view' => 'audit',
                'stage' => 'approved',
                'search' => 'AUDIT-'.$analysis->id,
            ]))
            ->assertOk()
            ->assertDownload("auditoria-resultados-{$timestamp}.xlsx");

        $auditSheet = $this->worksheetFromResponse($auditResponse->baseResponse);
        $this->assertSame('ID do resultado', $auditSheet->getCell('A1')->getValue());
        $this->assertSame('Estado actual', $auditSheet->getCell('W1')->getValue());
        $auditRow = collect($auditSheet->toArray())
            ->first(fn (array $row): bool => (int) $row[0] === $auditResult->id);

        $this->assertNotNull($auditRow);
        $this->assertSame("'=AUDIT-FORMULA", $auditRow[19]);
        $this->assertSame('Aprovado', $auditRow[22]);
    }

    public function test_filter_validation_and_export_indexes_are_present(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->from(route('analysis.data-exports.index'))
            ->get(route('analysis.data-exports.index', [
                'view' => 'audit',
                'date_from' => '2026-07-15',
                'date_to' => '2026-07-14',
            ]))
            ->assertRedirect(route('analysis.data-exports.index'))
            ->assertSessionHasErrors('date_to');

        $analysisIndexes = collect(Schema::getIndexes('analysis'))->pluck('name');
        $resultIndexes = collect(Schema::getIndexes('results'))->pluck('name');

        $this->assertContains('analysis_export_queue_index', $analysisIndexes);
        $this->assertContains('analysis_export_col_date_index', $analysisIndexes);
        $this->assertContains('results_export_insertion_index', $resultIndexes);
        $this->assertContains('results_export_approval_index', $resultIndexes);
    }

    public function test_main_lab_network_visibility_does_not_expose_another_labs_analysis_exports(): void
    {
        $admin = $this->verifiedAdmin();
        $network = LabNetwork::query()->create(['name' => 'Export isolation network']);
        $this->lab()->update(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $this->lab()->id]);
        DB::table('lab_user')->where('lab_id', $this->lab()->id)
            ->where('user_id', $admin->id)
            ->update(['can_view_network' => true]);
        $otherLab = VAPLab::factory()->create(['network_id' => $network->id]);

        [$localAnalysis, $localParameters] = $this->analysisWithMultipleParameters();
        [$otherAnalysis, $otherParameters] = $this->analysisWithMultipleParameters($otherLab);
        $localResult = $this->createResultAtStage($localAnalysis, $localParameters->first(), 'approved');
        $otherResult = $this->createResultAtStage($otherAnalysis, $otherParameters->first(), 'approved');

        $pendingResponse = $this->actingAs($admin)
            ->get(route('analysis.data-exports.index', ['view' => 'pending']))
            ->assertOk();
        $pendingIds = collect(data_get($pendingResponse->viewData('page'), 'props.records.data', []))->pluck('analysis_id');
        $this->assertContains($localAnalysis->id, $pendingIds);
        $this->assertNotContains($otherAnalysis->id, $pendingIds);

        $auditResponse = $this->actingAs($admin)
            ->get(route('analysis.data-exports.index', ['view' => 'audit']))
            ->assertOk();
        $auditIds = collect(data_get($auditResponse->viewData('page'), 'props.records.data', []))->pluck('result_id');
        $this->assertContains($localResult->id, $auditIds);
        $this->assertNotContains($otherResult->id, $auditIds);
        $this->assertSame(1, data_get($auditResponse->viewData('page'), 'props.summary.total'));

        $this->actingAs($admin)
            ->get(route('analysis.data-exports.index', [
                'view' => 'pending',
                'search' => $otherAnalysis->sample->code,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('records.data', 0));
        $this->actingAs($admin)
            ->get(route('analysis.data-exports.index', [
                'view' => 'audit',
                'search' => 'AUDIT-'.$otherAnalysis->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('records.data', 0));
        $this->actingAs($admin)
            ->get(route('analysis.data-exports.index', [
                'view' => 'pending',
                'department_id' => $localAnalysis->department_id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('summary.tasks', 1));
        $this->actingAs($admin)
            ->get(route('analysis.data-exports.index', [
                'view' => 'audit',
                'department_id' => $localAnalysis->department_id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('summary.total', 1));

        $pendingWorkbook = $this->worksheetFromResponse($this->actingAs($admin)
            ->get(route('analysis.data-exports.download', ['view' => 'pending']))
            ->assertOk()->baseResponse)->toArray();
        $this->assertTrue(collect($pendingWorkbook)->contains(fn (array $row): bool => in_array($localAnalysis->sample->code, $row, true)));
        $this->assertFalse(collect($pendingWorkbook)->contains(fn (array $row): bool => in_array($otherAnalysis->sample->code, $row, true)));

        $auditWorkbook = $this->worksheetFromResponse($this->actingAs($admin)
            ->get(route('analysis.data-exports.download', ['view' => 'audit']))
            ->assertOk()->baseResponse)->toArray();
        $this->assertTrue(collect($auditWorkbook)->contains(fn (array $row): bool => (int) $row[0] === $localResult->id));
        $this->assertFalse(collect($auditWorkbook)->contains(fn (array $row): bool => (int) $row[0] === $otherResult->id));

        $this->actingAs($admin)
            ->withSession(['active_lab_id' => $otherLab->id])
            ->get(route('analysis.data-exports.index', ['view' => 'audit']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.total', 1)
                ->where('records.data.0.result_id', $localResult->id));

        VAPSampleEntry::query()
            ->where('collection_product_id', $localAnalysis->code->collection_id)
            ->firstOrFail()
            ->delete();

        $this->actingAs($admin)
            ->get(route('analysis.data-exports.index', ['view' => 'pending']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('summary.tasks', 0));
        $this->actingAs($admin)
            ->get(route('analysis.data-exports.index', ['view' => 'audit']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('summary.total', 0));
    }

    public function test_ambiguous_collection_ownership_is_excluded_even_when_foreign_link_is_archived(): void
    {
        [$analysis, $parameters] = $this->analysisWithMultipleParameters();
        $this->createResultAtStage($analysis, $parameters->first(), 'approved');
        DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        $foreignLink = VAPSampleEntry::factory()->create([
            'collection_product_id' => $analysis->code->collection_id,
        ]);
        $foreignLink->delete();

        foreach (['pending', 'audit'] as $view) {
            $this->actingAs($this->verifiedAdmin())
                ->get(route('analysis.data-exports.index', ['view' => $view]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->has('records.data', 0));
            $sheet = $this->worksheetFromResponse($this->get(route('analysis.data-exports.download', ['view' => $view]))
                ->assertOk()->baseResponse);
            $this->assertSame(1, $sheet->getHighestDataRow());
        }
    }

    /**
     * @return array{Analysis, Collection<int, Parameter>}
     */
    private function analysisWithMultipleParameters(?VAPLab $lab = null): array
    {
        $lab ??= $this->lab();
        $customer = Customer::query()->create(['name' => 'Export customer '.Str::random(8)]);
        $department = Department::factory()->create();
        $category = AnalysisCategory::query()->create([
            'name' => 'Export category '.Str::random(8),
            'department_id' => $department->id,
        ]);
        $profile = Profile::query()->create([
            'name' => 'Export profile '.Str::random(8),
            'code' => 'EXPORT-PROFILE-'.Str::upper(Str::random(8)),
            'category_id' => $category->id,
        ]);
        $matrix = Matrix::query()->create(['code' => 'EXPORT-MATRIX-'.Str::upper(Str::random(8))]);
        $matrix->profiles()->attach($profile);
        $product = Product::query()->create(['name' => 'Export product '.Str::random(8), 'matrix_id' => $matrix->id]);
        $parameters = collect([1, 2])->map(function (int $index) use ($profile): Parameter {
            $parameter = Parameter::query()->create([
                'name' => 'Export parameter '.$index.' '.Str::random(8),
                'code' => 'EXPORT-PARAMETER-'.Str::upper(Str::random(8)),
                'active' => true,
            ]);
            $profile->parameters()->attach($parameter->id);

            return $parameter;
        });
        $collectionProduct = CollectionProduct::query()->create(['customer_id' => $customer->id, 'product_id' => $product->id]);
        $intakePayload = app(PrepareSampleEntryPayload::class)->execute([
            'lab_id' => $lab->id,
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'client_submitted_info' => ['request_origin' => 'internal', 'product_id' => $product->id, 'requested_profile_ids' => [$profile->id]],
        ], null);
        VAPSampleEntry::factory()->create([
            ...$intakePayload,
            'lab_id' => $lab->id,
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'collection_product_id' => $collectionProduct->id,
        ]);
        $code = LabCode::query()->create([
            'collection_id' => $collectionProduct->id,
            'cl_month' => now()->format('y/m'),
        ]);
        $sample = Sample::query()->create([
            'cl_id' => $code->id,
            'sample_month' => now()->format('y/m'),
        ]);
        $analysis = Analysis::query()->create([
            'department_id' => $department->id,
            'sample_id' => $sample->id,
            'profile_id' => $profile->id,
            'type_id' => $category->id,
            'cl_id' => $code->id,
            'entry_date' => now()->toDateString(),
        ])->load(['code', 'sample', 'profile.parameters']);

        return [$analysis, $parameters];
    }

    private function createResultAtStage(Analysis $analysis, object $parameter, string $stage, ?string $approvedValue = null): Result
    {
        $now = now();
        $insertedValue = 'AUDIT-'.$analysis->id.'-'.$stage;

        return Result::query()->create([
            'sample_id' => $analysis->sample_id,
            'code_id' => $analysis->cl_id,
            'parameter_id' => $parameter->id,
            'profile_id' => $analysis->profile_id,
            'parameter_label' => 'AUDIT-'.$analysis->id.'-'.$parameter->name,
            'code_label' => $analysis->code?->code,
            'inserted_value' => $insertedValue,
            'inserted_by' => 'Analista de teste',
            'inserted_date' => $now->copy()->subHours(2),
            'verified_value' => in_array($stage, ['verified', 'approved'], true) ? $insertedValue : null,
            'verified_by' => in_array($stage, ['verified', 'approved'], true) ? 'Verificador de teste' : null,
            'verified_date' => in_array($stage, ['verified', 'approved'], true) ? $now->copy()->subHour() : null,
            'approved_value' => $stage === 'approved' ? ($approvedValue ?? $insertedValue) : null,
            'approved_by' => $stage === 'approved' ? 'Aprovador de teste' : null,
            'approved_date' => $stage === 'approved' ? $now : null,
        ]);
    }

    private function worksheetFromResponse(BinaryFileResponse $response): Worksheet
    {
        $spreadsheet = IOFactory::load($response->getFile()->getPathname());

        return $spreadsheet->getActiveSheet();
    }

    private function verifiedAdmin(): User
    {
        if ($this->admin instanceof User) {
            return $this->admin;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->admin->id]);

        return $this->admin;
    }

    private function lab(): VAPLab
    {
        $this->verifiedAdmin();

        return $this->lab;
    }
}
