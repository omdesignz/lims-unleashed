<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Result;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class LaboratoryDataExportTest extends TestCase
{
    use DatabaseTransactions;

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
        $analysisUser->givePermissionTo(Permission::findByName('view_analysis'));

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
        $resultsUser->givePermissionTo(Permission::findByName('view_results'));

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

    /**
     * @return array{Analysis, Collection<int, Parameter>}
     */
    private function analysisWithMultipleParameters(): array
    {
        $analysis = Analysis::query()
            ->whereHas('profile.parameters', null, '>=', 2)
            ->with(['code', 'sample', 'profile.parameters'])
            ->firstOrFail();

        return [$analysis, $analysis->profile->parameters->values()];
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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }
}
