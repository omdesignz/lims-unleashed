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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LaboratorySpecimenReadAccessTest extends TestCase
{
    use DatabaseTransactions;

    private Models\User $operator;

    private Models\VAPLab $lab;

    private Models\VAPLab $peerLab;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        Notification::fake();
        $network = Models\LabNetwork::query()->create(['name' => 'Specimen network '.Str::uuid()]);
        $this->lab = Models\VAPLab::factory()->create(['network_id' => $network->id]);
        $this->peerLab = Models\VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $this->lab->id]);
        $this->operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $this->operator->givePermissionTo(Models\Permission::findOrCreate('view_samples', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id, 'can_view_network' => true]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_specimen_list_and_lookup_do_not_reveal_network_peer_records(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $this->get(route('samples.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('Samples/Index')->has('record.data', 1)->where('record.data.0.id', $local['sample']->id)
            ->where('record.meta.total', 1));
        $this->getJson(route('samples.getCode'))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local['sample']->id);
        $this->getJson(route('samples.getCode', ['q' => $peer['sample']->code]))->assertOk()->assertExactJson([]);
    }

    public function test_resource_links_point_to_canonical_intake_without_ghost_mutation_actions(): void
    {
        $local = $this->fixture($this->lab);
        $this->get(route('samples.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->where('record.data.0.collection', $local['code']->code)
            ->where('record.data.0.links.sample_entry_show_path', route('vap_samples.show', $local['entry']->id))
            ->missing('record.data.0.links.edit_path')->missing('record.data.0.links.delete_path')->missing('record.data.0.links.restore_path')
            ->where('createAction', false)->where('entrypoint.create_sample_url', route('vap_samples.index')));

        foreach (['create', 'store', 'edit', 'update', 'destroy', 'restore'] as $action) {
            $this->assertFalse(Route::has('samples.'.$action));
        }

        foreach (['create', 'destroy', 'restore', $local['sample']->id.'/edit'] as $path) {
            $this->get('/samples/'.$path)->assertNotFound();
        }

        $this->postJson(route('samples.index'), ['cl_id' => $local['code']->id])->assertStatus(405);
        $this->putJson('/samples/'.$local['sample']->id, ['cl_id' => null])->assertNotFound();
        $this->assertSame($local['code']->id, $local['sample']->fresh()->cl_id);
    }

    public function test_active_laboratory_switch_uses_one_direct_membership_not_the_network_union(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        DB::table('lab_user')->insert(['lab_id' => $this->peerLab->id, 'user_id' => $this->operator->id]);
        $this->getJson(route('samples.getCode'))->assertJsonCount(1)->assertJsonPath('0.id', $local['sample']->id);
        $this->withSession(['active_lab_id' => $this->peerLab->id]);
        $this->getJson(route('samples.getCode'))->assertJsonCount(1)->assertJsonPath('0.id', $peer['sample']->id);
        $this->get(route('samples.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $peer['sample']->id));
    }

    public function test_forged_active_lab_does_not_turn_summary_access_into_private_specimen_access(): void
    {
        $local = $this->fixture($this->lab);
        $this->fixture($this->peerLab);
        $this->withSession(['active_lab_id' => $this->peerLab->id]);
        $this->get(route('samples.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $local['sample']->id));
        $this->getJson(route('samples.getCode'))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local['sample']->id);
    }

    public function test_archived_register_filters_never_reveal_a_peers_archived_specimen(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $local['sample']->delete();
        $peer['sample']->delete();
        foreach (['only', 'with'] as $trashed) {
            $this->get(route('samples.index', ['filter' => ['trashed' => $trashed]]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
                ->has('record.data', 1)->where('record.data.0.id', $local['sample']->id)->where('record.data.0.deleted', true));
        }
    }

    #[DataProvider('revocations')]
    public function test_read_authority_is_fresh_after_revocation(string $revocation): void
    {
        $this->fixture($this->lab);
        $this->get(route('samples.index'))->assertOk();
        $this->getJson(route('samples.getCode'))->assertOk();

        match ($revocation) {
            'membership' => DB::table('lab_user')->where('user_id', $this->operator->id)->delete(),
            'permission' => $this->operator->revokePermissionTo('view_samples'),
            'inactive' => DB::table('users')->where('id', $this->operator->id)->update(['is_active' => false]),
            'unverified' => DB::table('users')->where('id', $this->operator->id)->update(['email_verified_at' => null]),
            'laboratory' => DB::table('labs')->where('id', $this->lab->id)->update(['deleted_at' => now()]),
        };

        $this->getJson(route('samples.index'))->assertForbidden();
        $this->getJson(route('samples.getCode'))->assertForbidden();
    }

    public static function revocations(): array
    {
        return array_combine(['membership', 'permission', 'inactive', 'unverified', 'laboratory'],
            array_map(fn (string $value): array => [$value], ['membership', 'permission', 'inactive', 'unverified', 'laboratory']));
    }

    #[DataProvider('unreadableLineage')]
    public function test_archived_unassigned_or_inconsistent_lineage_is_not_readable(string $change): void
    {
        $fixture = $this->fixture($this->lab);
        if (in_array($change, ['entry', 'accession', 'code', 'collection', 'subject'], true)) {
            $model = $fixture[$change];
            DB::table($model->getTable())->where('id', $model->id)->update(['deleted_at' => now()]);
        } elseif ($change === 'unassigned') {
            DB::table('sample_entries')->where('id', $fixture['entry']->id)->update(['lab_id' => null]);
        } elseif ($change === 'customer') {
            $customer = Models\Customer::query()->create(['name' => 'Other specimen customer '.Str::uuid()]);
            DB::table('collections')->where('id', $fixture['collection']->id)->update(['customer_id' => $customer->id]);
        } elseif ($change === 'site') {
            DB::table('collections')->where('id', $fixture['collection']->id)->update(['warehouse_id' => null]);
        } elseif ($change === 'subject_type') {
            DB::table('collections')->where('id', $fixture['collection']->id)->update(['collectionable_type' => 'unknown']);
        }

        $this->get(route('samples.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0)->where('record.meta.total', 0));
        $this->getJson(route('samples.getCode'))->assertOk()->assertExactJson([]);
    }

    public static function unreadableLineage(): array
    {
        $changes = ['entry', 'accession', 'code', 'collection', 'subject', 'unassigned', 'customer', 'site', 'subject_type'];

        return array_combine($changes, array_map(fn (string $change): array => [$change], $changes));
    }

    public function test_programmed_intake_specimens_use_the_same_private_register(): void
    {
        $local = $this->fixture($this->lab, 'programmed');
        $this->fixture($this->peerLab, 'programmed');
        $this->get(route('samples.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $local['sample']->id));
        $this->getJson(route('samples.getCode'))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local['sample']->id);
    }

    public function test_parameter_filters_use_issued_scope_after_catalogue_changes(): void
    {
        $local = $this->fixture($this->lab);
        $added = Models\Parameter::query()->create(['name' => 'New catalogue parameter', 'code' => Str::uuid(), 'active' => true]);
        $local['profile']->parameters()->sync([$added->id]);

        foreach ([['parameters' => [$local['parameter']->id]], ['filter' => ['parameters' => (string) $local['parameter']->id]]] as $query) {
            $this->get(route('samples.index', $query))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
                ->has('record.data', 1)->where('record.data.0.id', $local['sample']->id)->where('parameters.0.value', $local['parameter']->id));
        }

        $this->get(route('samples.index', ['parameters' => [$added->id]]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
        $this->assertTrue(Models\Sample::query()->whereKey($local['sample']->id)->byParameters([$local['parameter']->id])->exists());
        $this->assertFalse(Models\Sample::query()->whereKey($local['sample']->id)->byParameters([$added->id])->exists());
    }

    public function test_parameter_filter_matches_the_specimens_own_profile_and_accession(): void
    {
        $local = $this->fixture($this->lab);
        $other = $this->fixture($this->lab);
        $info = $local['entry']->client_submitted_info;
        $info['required_parameters'][0]['profile_ids'] = [$other['profile']->id];
        DB::table('sample_entries')->where('id', $local['entry']->id)->update(['client_submitted_info' => json_encode($info, JSON_THROW_ON_ERROR)]);
        $this->get(route('samples.index', ['parameters' => [$local['parameter']->id]]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));

        $info['required_parameters'][0]['profile_ids'] = [$local['profile']->id];
        DB::table('sample_entries')->where('id', $local['entry']->id)->update(['client_submitted_info' => json_encode($info, JSON_THROW_ON_ERROR)]);
        DB::table('analysis')->where('id', $local['analysis']->id)->update(['cl_id' => $other['code']->id]);
        $this->get(route('samples.index', ['filter' => ['parameters' => [$local['parameter']->id]]]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
    }

    public function test_missing_issued_parameter_scope_is_not_fabricated_from_the_catalogue(): void
    {
        $local = $this->fixture($this->lab);
        $info = $local['entry']->client_submitted_info;
        unset($info['required_parameters']);
        DB::table('sample_entries')->where('id', $local['entry']->id)->update(['client_submitted_info' => json_encode($info, JSON_THROW_ON_ERROR)]);
        $this->get(route('samples.index', ['parameters' => [$local['parameter']->id]]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
    }

    #[DataProvider('invalidQueries')]
    public function test_malformed_queries_return_validation_errors(array $query, string $field): void
    {
        foreach (['samples.index', 'samples.getCode'] as $route) {
            $this->getJson(route($route, $query))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public static function invalidQueries(): array
    {
        return [
            'small page size' => [['per_page' => 0], 'per_page'],
            'large page size' => [['per_page' => 101], 'per_page'],
            'decimal page size' => [['per_page' => '1.5'], 'per_page'],
            'page size array' => [['per_page' => [10]], 'per_page'],
            'invalid page' => [['page' => 0], 'page'],
            'overflow page' => [['page' => '999999999999999999999999'], 'page'],
            'nested parameters' => [['parameters' => [[1]]], 'parameters'],
            'parameter options' => [['parameters' => [['value' => 1]]], 'parameters'],
            'sparse parameters' => [['parameters' => [2 => 1]], 'parameters'],
            'decimal parameters' => [['parameters' => ['1.5']], 'parameters'],
            'scientific parameters' => [['parameters' => ['1e2']], 'parameters'],
            'overflow parameters' => [['parameters' => ['999999999999999999999999']], 'parameters'],
            'negative parameters' => [['parameters' => [-1]], 'parameters'],
            'zero parameters' => [['parameters' => [0]], 'parameters'],
            'empty comma parameter' => [['parameters' => '1,,2'], 'parameters'],
            'parameter limit' => [['parameters' => range(1, 101)], 'parameters'],
            'nested filter parameters' => [['filter' => ['parameters' => [[1]]]], 'filter.parameters'],
            'decimal filter parameters' => [['filter' => ['parameters' => '1.5']], 'filter.parameters'],
            'overflow filter parameters' => [['filter' => ['parameters' => '999999999999999999999999']], 'filter.parameters'],
            'filter limit' => [['filter' => ['parameters' => range(1, 101)]], 'filter.parameters'],
            'query array' => [['q' => ['code']], 'q'],
            'long query' => [['q' => str_repeat('q', 256)], 'q'],
            'global query array' => [['globalFilter' => ['code']], 'globalFilter'],
            'global filter array' => [['filter' => ['globalFilter' => ['code']]], 'filter.globalFilter'],
            'collection filter array' => [['filter' => ['collection.code' => ['code']]], 'filter.collection.code'],
            'code filter array' => [['filter' => ['code' => ['code']]], 'filter.code'],
            'invalid date filter' => [['filter' => ['created_at' => 'not-a-date']], 'filter.created_at'],
            'sort array' => [['sort' => ['code']], 'sort'],
            'filter string' => [['filter' => 'code'], 'filter'],
            'foreign laboratory filter' => [['filter' => ['lab_id' => 1]], 'filter'],
            'includes string' => [['includes' => 'analysis'], 'includes'],
        ];
    }

    #[DataProvider('sorts')]
    public function test_owned_register_can_sort_sample_and_accession_codes_without_postgresql_errors(string $sort): void
    {
        $local = $this->fixture($this->lab);
        $this->fixture($this->peerLab);
        $this->get(route('samples.index', ['sort' => $sort]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $local['sample']->id));
    }

    public static function sorts(): array
    {
        $sorts = ['collection.code', '-collection.code', 'created_at', '-created_at', 'code', '-code'];

        return array_combine($sorts, array_map(fn (string $sort): array => [$sort], $sorts));
    }

    public function test_global_and_date_filters_stay_grouped_inside_laboratory_ownership(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        foreach ([$local['sample']->code, $local['code']->code] as $search) {
            foreach ([['globalFilter' => $search], ['filter' => ['globalFilter' => $search]]] as $query) {
                $this->get(route('samples.index', $query))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
                    ->has('record.data', 1)->where('record.data.0.id', $local['sample']->id));
            }
        }

        $this->get(route('samples.index', ['filter' => ['globalFilter' => $peer['code']->code]]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
        $this->get(route('samples.index', ['filter' => ['created_at' => $local['sample']->created_at->toDateString()]]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $local['sample']->id));
        foreach (['%', '_', 'true', 'false', 'missing,code'] as $search) {
            $this->get(route('samples.index', ['filter' => ['globalFilter' => $search]]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
            $this->getJson(route('samples.getCode', ['q' => $search]))->assertOk()->assertExactJson([]);
        }
    }

    public function test_soft_archiving_a_specimen_preserves_analyses_and_result_evidence(): void
    {
        $local = $this->fixture($this->lab);
        $result = Models\Result::query()->create([
            'sample_id' => $local['sample']->id, 'parameter_id' => $local['parameter']->id, 'profile_id' => $local['profile']->id,
            'product_id' => $local['product']->id, 'collection_id' => $local['accession']->id, 'code_id' => $local['code']->id,
            'resultable_type' => 'analysis', 'resultable_id' => $local['analysis']->id, 'inserted_value' => 0,
        ]);
        $beforeResult = $result->refresh()->getAttributes();
        $beforeAnalysis = $local['analysis']->refresh()->getAttributes();
        $local['sample']->delete();
        $this->assertSame($beforeResult, $result->fresh()->getAttributes());
        $this->assertSame($beforeAnalysis, $local['analysis']->fresh()->getAttributes());
        $this->getJson(route('samples.getCode'))->assertOk()->assertExactJson([]);
        $this->get(route('samples.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
        $this->get(route('samples.index', ['filter' => ['trashed' => 'only']]))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $local['sample']->id)->where('record.data.0.deleted', true));
    }

    /** @return array<string, Model> */
    private function fixture(Models\VAPLab $lab, string $collectionType = 'direct'): array
    {
        $customer = Models\Customer::query()->create(['name' => 'Specimen customer '.Str::uuid()]);
        $warehouse = Models\Warehouse::query()->create(['name' => 'Specimen site '.Str::uuid(), 'customer_id' => $customer->id]);
        $department = Models\Department::factory()->create();
        $category = Models\AnalysisCategory::query()->create(['name' => 'Specimen category '.Str::uuid(), 'code' => Str::uuid(), 'department_id' => $department->id]);
        $matrix = Models\Matrix::query()->create(['code' => Str::uuid(), 'description' => 'Specimen matrix']);
        $profile = Models\Profile::query()->create(['name' => 'Specimen profile', 'code' => Str::uuid(), 'category_id' => $category->id]);
        $matrix->profiles()->attach($profile);
        $parameter = Models\Parameter::query()->create(['name' => 'Specimen parameter', 'code' => Str::uuid(), 'active' => true]);
        $profile->parameters()->attach($parameter);
        $product = Models\Product::query()->create(['name' => 'Specimen product', 'matrix_id' => $matrix->id]);
        $payload = app(PrepareSampleEntryPayload::class)->execute([
            'lab_id' => $lab->id, 'department_id' => $department->id, 'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'client_submitted_info' => ['product_id' => $product->id, 'requested_profile_ids' => [$profile->id], 'collection_type' => $collectionType],
        ], null);
        $entry = Models\VAPSampleEntry::factory()->create($payload);
        $accession = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $collection = $accession->collection;
        $subject = $collection->collectionable;
        $code = $accession->code;
        $sample = $code->samples()->firstOrFail();
        $analysis = $sample->analysis;

        return compact('customer', 'warehouse', 'department', 'category', 'matrix', 'profile', 'parameter', 'product', 'entry', 'accession', 'collection', 'subject', 'code', 'sample', 'analysis');
    }
}
