<?php

namespace Tests\Feature;

use App\Actions\PrepareSampleEntryPayload;
use App\Models;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LaboratoryAnalysisReadAccessTest extends TestCase
{
    use DatabaseTransactions;

    private Models\User $operator;

    private Models\VAPLab $lab;

    private Models\VAPLab $peerLab;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $network = Models\LabNetwork::query()->create(['name' => 'Analysis read network '.Str::uuid()]);
        $this->lab = Models\VAPLab::factory()->create(['network_id' => $network->id]);
        $this->peerLab = Models\VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $this->lab->id]);
        $this->operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['view_analysis', 'edit_analysis', 'insert_results', 'view_worksheets'] as $permission) {
            $this->operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id, 'can_view_network' => true]);
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        Notification::fake();
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_lists_lookup_and_result_workflow_exclude_peer_operational_records(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $peer['profile']->update(['name' => 'Foreign analytical scope needle']);

        $this->get(route('analysis.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('Analysis/Index')->has('record.data', 1)->where('record.data.0.id', $local['analysis']->id));
        $this->get(route('analysis.index', ['filter' => ['globalFilter' => 'Foreign analytical scope needle']]))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
        $this->getJson(route('analysis.getAnalysis', ['q' => '']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local['analysis']->id);
        $this->getJson(route('analysis.getAnalysis', ['q' => 'Foreign analytical scope needle']))
            ->assertOk()->assertExactJson([]);
        $this->get(route('analysis.edit', $peer['analysis']->id))->assertNotFound();
        $this->get(route('analysis.edit', $local['analysis']->id))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('Analysis/ResultsWorkflow')
                ->where('record.id', $local['analysis']->id)->where('record.sample_entry.id', $local['entry']->id));
    }

    public function test_forged_lab_switch_does_not_grant_network_operational_access(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $this->withSession(['active_lab_id' => $this->peerLab->id])
            ->get(route('analysis.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $local['analysis']->id));
        $this->get(route('analysis.edit', $peer['analysis']->id))->assertNotFound();
    }

    public function test_explicit_lab_membership_and_switch_do_not_merge_private_lists(): void
    {
        $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        DB::table('lab_user')->insert(['lab_id' => $this->peerLab->id, 'user_id' => $this->operator->id]);

        $this->withSession(['active_lab_id' => $this->peerLab->id])->get(route('analysis.index'))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 1)
            ->where('record.data.0.id', $peer['analysis']->id));
        $this->get(route('analysis.edit', $peer['analysis']->id))->assertOk();
    }

    public function test_search_matches_real_identifiers_and_catalog_names_case_insensitively(): void
    {
        $local = $this->fixture($this->lab);
        $this->fixture($this->peerLab);
        $local['profile']->update(['name' => 'Local Analytical Scope Needle']);

        foreach (['local analytical scope needle', strtolower($local['code']->code), strtolower($local['sample']->code), $local['analysis']->entry_date] as $search) {
            $this->get(route('analysis.index', ['filter' => ['globalFilter' => $search]]))
                ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 1)
                ->where('record.data.0.id', $local['analysis']->id));
            $this->getJson(route('analysis.getAnalysis', ['q' => $search]))
                ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local['analysis']->id);
        }
    }

    public function test_archive_filters_do_not_remove_the_lab_ownership_boundary(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $local['analysis']->delete();
        $peer['analysis']->delete();

        foreach (['with', 'only'] as $filter) {
            $this->get(route('analysis.index', ['filter' => ['trashed' => $filter]]))
                ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 1)
                ->where('record.data.0.id', $local['analysis']->id));
        }
        $this->getJson(route('analysis.getAnalysis', ['q' => '']))->assertOk()->assertExactJson([]);
        $this->get(route('analysis.edit', $local['analysis']->id))->assertNotFound();
    }

    public function test_lookup_rejects_non_string_and_oversized_queries_before_matching(): void
    {
        foreach ([['unexpected'], str_repeat('x', 256), ['nested' => ['query' => 'needle']]] as $query) {
            $this->getJson(route('analysis.getAnalysis', ['q' => $query]))
                ->assertUnprocessable()->assertJsonValidationErrors('q');
        }
        $this->getJson(route('analysis.getAnalysis', ['q' => '7']))->assertOk();
    }

    #[DataProvider('relatedSorts')]
    public function test_related_name_sorting_is_postgresql_safe_and_stays_lab_private(string $relation, bool $descending): void
    {
        $first = $this->fixture($this->lab);
        $last = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $first[$relation]->update(['name' => 'B analysis sort '.Str::uuid()]);
        $last[$relation]->update(['name' => 'Z analysis sort '.Str::uuid()]);
        $peer[$relation]->update(['name' => 'A foreign analysis sort '.Str::uuid()]);
        $expected = $descending ? [$last['analysis']->id, $first['analysis']->id] : [$first['analysis']->id, $last['analysis']->id];

        $this->get(route('analysis.index', ['sort' => ($descending ? '-' : '').$relation.'.name']))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 2)
            ->where('record.data.0.id', $expected[0])->where('record.data.1.id', $expected[1]));
    }

    /** @return array<string, array{string, bool}> */
    public static function relatedSorts(): array
    {
        return [
            'department ascending' => ['department', false], 'department descending' => ['department', true],
            'profile ascending' => ['profile', false], 'profile descending' => ['profile', true],
        ];
    }

    #[DataProvider('readAccessRevocations')]
    public function test_reads_require_current_active_verified_membership_and_permission(string $change): void
    {
        $local = $this->fixture($this->lab);
        match ($change) {
            'membership' => DB::table('lab_user')->where('user_id', $this->operator->id)->delete(),
            'inactive user' => DB::table('users')->where('id', $this->operator->id)->update(['is_active' => false]),
            'unverified user' => DB::table('users')->where('id', $this->operator->id)->update(['email_verified_at' => null]),
            'permission' => $this->operator->revokePermissionTo('view_analysis'),
        };

        $this->getJson(route('analysis.index'))->assertForbidden();
        $this->getJson(route('analysis.getAnalysis', ['q' => '']))->assertForbidden();
        if ($change !== 'permission') {
            $this->getJson(route('analysis.edit', $local['analysis']->id))->assertForbidden();
        }
    }

    /** @return array<string, array{string}> */
    public static function readAccessRevocations(): array
    {
        return collect(['membership', 'inactive user', 'unverified user', 'permission'])
            ->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    #[DataProvider('archivedSources')]
    public function test_archived_lineage_is_absent_from_lists_lookup_and_workflow(string $subject): void
    {
        $local = $this->fixture($this->lab);
        $model = $local[$subject];
        DB::table($model->getTable())->where('id', $model->id)->update(['deleted_at' => now()]);

        $this->get(route('analysis.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
        $this->getJson(route('analysis.getAnalysis', ['q' => '']))->assertOk()->assertExactJson([]);
        $this->get(route('analysis.edit', $local['analysis']->id))->assertNotFound();
    }

    /** @return array<string, array{string}> */
    public static function archivedSources(): array
    {
        return collect(['entry', 'accession', 'code', 'sample', 'analysis'])
            ->mapWithKeys(fn (string $subject): array => [$subject => [$subject]])->all();
    }

    public function test_archived_foreign_ownership_conflict_fails_closed(): void
    {
        $local = $this->fixture($this->lab);
        DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        Models\VAPSampleEntry::factory()->create([
            'lab_id' => $this->peerLab->id, 'collection_product_id' => $local['accession']->id,
            'customer_id' => $local['entry']->customer_id, 'deleted_at' => now(),
        ]);

        $this->get(route('analysis.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
        $this->get(route('analysis.edit', $local['analysis']->id))->assertNotFound();
    }

    public function test_reading_already_approved_results_does_not_complete_or_modify_analysis(): void
    {
        $local = $this->fixture($this->lab);
        Models\Result::query()->create([
            'sample_id' => $local['sample']->id, 'parameter_id' => $local['parameter']->id,
            'profile_id' => $local['profile']->id, 'code_id' => $local['code']->id,
            'collection_id' => $local['accession']->id, 'product_id' => $local['product']->id,
            'resultable_id' => $local['analysis']->id, 'resultable_type' => $local['analysis']->getMorphClass(),
            'inserted_value' => '0', 'inserted_date' => now()->subDays(2),
            'verified_value' => '0', 'verified_date' => now()->subDay(),
            'approved_value' => '0', 'approved_date' => now(),
        ]);
        $before = $local['analysis']->fresh()->getAttributes();

        $this->get(route('analysis.edit', $local['analysis']->id))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->where('action', 'completed'));
        $this->assertSame($before, $local['analysis']->fresh()->getAttributes());
    }

    public function test_result_workflow_counts_issued_parameters_after_catalogue_changes(): void
    {
        $local = $this->fixture($this->lab);
        $local['profile']->parameters()->detach($local['parameter']->id);
        $added = Models\Parameter::query()->create(['name' => 'Late parameter', 'code' => Str::uuid()]);
        $local['profile']->parameters()->attach($added);

        $this->get(route('analysis.edit', $local['analysis']->id))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('expected_parameters_count', 1)
                ->where('scope_audit.expected_count', 1)
                ->where('scope_audit.expected_parameters.0.id', $local['parameter']->id)
                ->where('scope_audit.scope_drift.reception_only', [])
                ->where('scope_audit.scope_drift.profile_only', []));
    }

    public function test_result_workflow_ignores_results_from_another_analysis_on_the_same_specimen(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        Models\Result::query()->create([
            'sample_id' => $local['sample']->id,
            'code_id' => $local['code']->id,
            'profile_id' => $peer['profile']->id,
            'parameter_id' => $peer['parameter']->id,
            'parameter_label' => 'Foreign result secret',
            'resultable_id' => $peer['analysis']->id,
            'resultable_type' => $peer['analysis']->getMorphClass(),
            'inserted_date' => now(),
            'inserted_value' => '9',
        ]);

        $this->get(route('analysis.edit', $local['analysis']->id))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('action', 'analyze')
                ->where('actual_results_count', 0)
                ->where('scope_audit.existing_results', [])
                ->missing('Foreign result secret'));
    }

    public function test_worksheet_brief_rejects_conflicting_lineage_and_uses_the_canonical_match(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        Models\Worksheet::query()->create([
            'name' => 'Foreign worksheet secret', 'user_id' => $this->operator->id,
            'lab_id' => $this->lab->id,
            'worksheets' => ['analysis_id' => $local['analysis']->id, 'collection_product_id' => $peer['accession']->id],
        ]);

        $this->get(route('analysis.edit', $local['analysis']->id))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->where('worksheet_brief.exists', false)
                ->missing('worksheet_brief.name')->missing('worksheet_brief.id'));

        $worksheet = Models\Worksheet::query()->create([
            'name' => 'Canonical analysis worksheet', 'user_id' => $this->operator->id,
            'lab_id' => $this->lab->id, 'analysis_id' => $local['analysis']->id,
            'worksheets' => ['analysis_id' => $local['analysis']->id, 'collection_product_id' => $local['accession']->id,
                'sample_id' => $local['sample']->id, 'profile_id' => $local['profile']->id, 'generated_from' => 'analysis_scope'],
        ]);
        $this->get(route('analysis.edit', $local['analysis']->id))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->where('worksheet_brief.exists', true)
                ->where('worksheet_brief.id', $worksheet->id)->where('worksheet_brief.name', $worksheet->name));
    }

    /** @return array<string, Model> */
    private function fixture(Models\VAPLab $lab): array
    {
        $customer = Models\Customer::query()->create(['name' => 'Analysis read customer '.Str::uuid()]);
        $warehouse = Models\Warehouse::query()->create(['name' => 'Analysis read site '.Str::uuid(), 'customer_id' => $customer->id]);
        $department = Models\Department::factory()->create();
        $category = Models\AnalysisCategory::query()->create(['name' => 'Analysis read category '.Str::uuid(), 'code' => Str::uuid(), 'department_id' => $department->id]);
        $matrix = Models\Matrix::query()->create(['code' => Str::uuid(), 'description' => 'Analysis read matrix']);
        $profile = Models\Profile::query()->create(['name' => 'Analysis read profile', 'code' => Str::uuid(), 'category_id' => $category->id]);
        $matrix->profiles()->attach($profile);
        $parameter = Models\Parameter::query()->create(['name' => 'Analysis read parameter', 'code' => Str::uuid(), 'active' => true]);
        $profile->parameters()->attach($parameter);
        $product = Models\Product::query()->create(['name' => 'Analysis read product', 'matrix_id' => $matrix->id]);
        $intakePayload = app(PrepareSampleEntryPayload::class)->execute([
            'lab_id' => $lab->id,
            'department_id' => $department->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'client_submitted_info' => ['collection_type' => 'direct', 'product_id' => $product->id, 'requested_profile_ids' => [$profile->id]],
        ], null);
        $entry = Models\VAPSampleEntry::factory()->create([
            ...$intakePayload,
            'code' => null, 'lab_id' => $lab->id, 'department_id' => $department->id,
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
        ]);
        $accession = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $code = $accession->code;
        $sample = $code->samples()->firstOrFail();
        $analysis = Models\Analysis::query()->where('sample_id', $sample->id)->firstOrFail();

        return compact('customer', 'warehouse', 'department', 'category', 'matrix', 'profile', 'parameter', 'product', 'entry', 'accession', 'code', 'sample', 'analysis');
    }
}
