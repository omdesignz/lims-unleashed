<?php

namespace Tests\Feature;

use App\Models;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LaboratoryAnalysisMutationTest extends TestCase
{
    use DatabaseTransactions;

    private Models\User $operator;

    private Models\VAPLab $lab;

    private Models\VAPLab $peerLab;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $network = Models\LabNetwork::query()->create(['name' => 'Analysis mutation network '.Str::uuid()]);
        $this->lab = Models\VAPLab::factory()->create(['network_id' => $network->id]);
        $this->peerLab = Models\VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $this->lab->id]);
        $this->operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['view_analysis', 'add_analysis', 'edit_analysis', 'delete_analysis', 'restore_analysis', 'insert_results'] as $permission) {
            $this->operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id, 'can_view_network' => true]);
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        Notification::fake();
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    public function test_manual_creation_and_identity_update_routes_are_retired(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $payload = collect(['department_id', 'sample_id', 'profile_id', 'type_id', 'cl_id'])
            ->mapWithKeys(fn (string $field): array => [$field => ['value' => $peer['analysis']->{$field}]])->all();
        $before = Models\Analysis::query()->orderBy('id')->get()->map->getAttributes()->all();

        $this->get(route('analysis.create'))->assertRedirect(route('vap_samples.index'));
        $this->postJson('/analysis', $payload)->assertStatus(405);
        $this->putJson('/analysis/'.$local['analysis']->id, $payload)->assertStatus(404);
        $this->assertSame($before, Models\Analysis::query()->orderBy('id')->get()->map->getAttributes()->all());
    }

    public function test_get_requests_cannot_archive_or_restore_analysis(): void
    {
        $local = $this->fixture($this->lab);
        $this->get(route('analysis.destroy', ['recordIds' => [$local['analysis']->id]]))->assertStatus(405);
        $this->assertFalse($local['analysis']->fresh()->trashed());
        $local['analysis']->delete();
        $this->get(route('analysis.restore', ['recordIds' => [$local['analysis']->id]]))->assertStatus(405);
        $this->assertTrue(Models\Analysis::withTrashed()->findOrFail($local['analysis']->id)->trashed());
    }

    public function test_mixed_lab_batches_fail_atomically_and_restore_requires_the_same_owner(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $this->postJson(route('analysis.destroy'), ['recordIds' => [$local['analysis']->id, $peer['analysis']->id]])->assertNotFound();
        $this->assertFalse($local['analysis']->fresh()->trashed());
        $this->assertFalse($peer['analysis']->fresh()->trashed());

        $this->post(route('analysis.destroy'), ['recordIds' => [$local['analysis']->id]])->assertRedirect();
        $this->assertTrue(Models\Analysis::withTrashed()->findOrFail($local['analysis']->id)->trashed());
        $this->postJson(route('analysis.restore'), ['recordIds' => [$local['analysis']->id, $peer['analysis']->id]])->assertNotFound();
        $this->assertTrue(Models\Analysis::withTrashed()->findOrFail($local['analysis']->id)->trashed());
        $this->post(route('analysis.restore'), ['recordIds' => [$local['analysis']->id]])->assertRedirect();
        $this->assertFalse($local['analysis']->fresh()->trashed());
    }

    public function test_archive_replay_preserves_issued_identity_children_and_result_evidence(): void
    {
        $local = $this->fixture($this->lab);
        $result = Models\Result::query()->create([
            'sample_id' => $local['sample']->id, 'parameter_id' => $local['parameter']->id,
            'profile_id' => $local['profile']->id, 'code_id' => $local['code']->id,
            'collection_id' => $local['accession']->id, 'product_id' => $local['product']->id,
            'resultable_id' => $local['analysis']->id, 'resultable_type' => $local['analysis']->getMorphClass(),
            'inserted_value' => '0', 'verified_value' => '0', 'approved_value' => '0',
            'inserted_date' => now()->subDays(2), 'verified_date' => now()->subDay(), 'approved_date' => now(),
            'extra_data' => ['analysis_mutation_evidence' => 'Retain issued result'],
        ]);
        $before = collect([$local['entry'], $local['accession'], $local['code'], $local['sample'], $result])
            ->map(fn (Models\VAPSampleEntry|Models\CollectionProduct|Models\LabCode|Models\Sample|Models\Result $record): array => $record->fresh()->getAttributes())->all();
        $identity = $local['analysis']->fresh()->only(['sample_id', 'profile_id', 'cl_id', 'product_id', 'department_id', 'type_id', 'col_date', 'entry_date', 'init_date', 'end_date', 'status']);
        $ids = ['recordIds' => [$local['analysis']->id]];

        $this->post(route('analysis.destroy'), $ids)->assertRedirect();
        $archived = Models\Analysis::withTrashed()->findOrFail($local['analysis']->id)->getAttributes();
        $this->post(route('analysis.destroy'), $ids)->assertRedirect();
        $this->assertSame($archived, Models\Analysis::withTrashed()->findOrFail($local['analysis']->id)->getAttributes());
        $this->assertSame(1, $this->auditCount($local['analysis']));
        $this->post(route('analysis.restore'), $ids)->assertRedirect();
        $restored = $local['analysis']->fresh()->getAttributes();
        $this->post(route('analysis.restore'), $ids)->assertRedirect();
        $this->assertSame($restored, $local['analysis']->fresh()->getAttributes());
        $this->assertSame(2, $this->auditCount($local['analysis']));
        $this->assertSame($identity, $local['analysis']->fresh()->only(array_keys($identity)));
        $this->assertSame($before, collect([$local['entry'], $local['accession'], $local['code'], $local['sample'], $result])
            ->map(fn (Models\VAPSampleEntry|Models\CollectionProduct|Models\LabCode|Models\Sample|Models\Result $record): array => $record->fresh()->getAttributes())->all());
    }

    #[DataProvider('accessRevocations')]
    public function test_mutations_recheck_current_lab_access_and_permission(string $change): void
    {
        $local = $this->fixture($this->lab);
        match ($change) {
            'membership' => DB::table('lab_user')->where('user_id', $this->operator->id)->delete(),
            'inactive user' => DB::table('users')->where('id', $this->operator->id)->update(['is_active' => false]),
            'unverified user' => DB::table('users')->where('id', $this->operator->id)->update(['email_verified_at' => null]),
            'archived lab' => DB::table('labs')->where('id', $this->lab->id)->update(['deleted_at' => now()]),
            'permission' => $this->operator->revokePermissionTo(['delete_analysis', 'restore_analysis']),
        };

        $this->postJson(route('analysis.destroy'), ['recordIds' => [$local['analysis']->id]])->assertForbidden();
        $this->assertFalse($local['analysis']->fresh()->trashed());
        $local['analysis']->delete();
        $this->postJson(route('analysis.restore'), ['recordIds' => [$local['analysis']->id]])->assertForbidden();
        $this->assertTrue(Models\Analysis::withTrashed()->findOrFail($local['analysis']->id)->trashed());
    }

    /** @return array<string, array{string}> */
    public static function accessRevocations(): array
    {
        return collect(['membership', 'inactive user', 'unverified user', 'archived lab', 'permission'])
            ->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    #[DataProvider('invalidBatches')]
    public function test_invalid_ids_fail_validation_before_queries(mixed $ids): void
    {
        foreach (['analysis.destroy', 'analysis.restore'] as $route) {
            $this->postJson(route($route), ['recordIds' => $ids])->assertUnprocessable();
        }
    }

    /** @return array<string, array{mixed}> */
    public static function invalidBatches(): array
    {
        return [
            'empty' => [[]], 'null' => [null], 'scalar' => ['7'], 'zero' => [[0]], 'negative' => [[-1]],
            'missing id' => [[null]], 'duplicate' => [[7, '7']], 'invalid id' => [['not-an-id']],
            'decimal' => [[1.5]], 'overflow' => [['9223372036854775808']], 'oversized batch' => [range(1, 501)],
        ];
    }

    #[DataProvider('archivedLineage')]
    public function test_archived_ancestors_block_archive_and_restore(string $subject): void
    {
        $local = $this->fixture($this->lab);
        $model = $local[$subject];
        DB::table($model->getTable())->where('id', $model->id)->update(['deleted_at' => now()]);
        $this->postJson(route('analysis.destroy'), ['recordIds' => [$local['analysis']->id]])->assertNotFound();
        $this->assertFalse($local['analysis']->fresh()->trashed());
        $local['analysis']->delete();
        $this->postJson(route('analysis.restore'), ['recordIds' => [$local['analysis']->id]])->assertNotFound();
        $this->assertTrue(Models\Analysis::withTrashed()->findOrFail($local['analysis']->id)->trashed());
    }

    /** @return array<string, array{string}> */
    public static function archivedLineage(): array
    {
        return collect(['entry', 'accession', 'code', 'sample'])->mapWithKeys(fn (string $subject): array => [$subject => [$subject]])->all();
    }

    public function test_forged_switch_and_code_lineage_do_not_authorize_mutations(): void
    {
        $local = $this->fixture($this->lab);
        $peer = $this->fixture($this->peerLab);
        $this->withSession(['active_lab_id' => $this->peerLab->id])
            ->postJson(route('analysis.destroy'), ['recordIds' => [$peer['analysis']->id]])->assertNotFound();
        $this->assertFalse($peer['analysis']->fresh()->trashed());
        DB::table('analysis')->where('id', $local['analysis']->id)->update(['cl_id' => $peer['code']->id]);
        $this->postJson(route('analysis.destroy'), ['recordIds' => [$local['analysis']->id]])->assertNotFound();
        $this->assertFalse($local['analysis']->fresh()->trashed());
    }

    #[DataProvider('archiveOperations')]
    public function test_batch_failure_rolls_back_earlier_mutations_and_activity(bool $archived): void
    {
        $first = $this->fixture($this->lab);
        $last = $this->fixture($this->lab);
        if (! $archived) {
            $first['analysis']->delete();
            $last['analysis']->delete();
        }
        $event = $archived ? 'deleting' : 'restoring';
        Models\Analysis::{$event}(function (Models\Analysis $analysis) use ($last): void {
            if ($analysis->id === $last['analysis']->id) {
                throw new \RuntimeException('Injected analysis mutation failure');
            }
        });
        $this->postJson(route($archived ? 'analysis.destroy' : 'analysis.restore'), ['recordIds' => [$last['analysis']->id, $first['analysis']->id]])->assertStatus(500);
        $this->assertSame(! $archived, Models\Analysis::withTrashed()->findOrFail($first['analysis']->id)->trashed());
        $this->assertSame(! $archived, Models\Analysis::withTrashed()->findOrFail($last['analysis']->id)->trashed());
        $this->assertSame(0, $this->auditCount($first['analysis']));
        $this->assertSame(0, $this->auditCount($last['analysis']));
    }

    /** @return array<string, array{bool}> */
    public static function archiveOperations(): array
    {
        return ['archive' => [true], 'restore' => [false]];
    }

    #[DataProvider('archiveOperations')]
    public function test_model_cancellation_rolls_back_the_batch_instead_of_reporting_success(bool $archived): void
    {
        $first = $this->fixture($this->lab);
        $last = $this->fixture($this->lab);
        if (! $archived) {
            $first['analysis']->delete();
            $last['analysis']->delete();
        }
        $event = $archived ? 'deleting' : 'restoring';
        Models\Analysis::{$event}(fn (Models\Analysis $analysis): bool => $analysis->id !== $last['analysis']->id);

        $this->postJson(route($archived ? 'analysis.destroy' : 'analysis.restore'), ['recordIds' => [$last['analysis']->id, $first['analysis']->id]])->assertConflict();
        $this->assertSame(! $archived, Models\Analysis::withTrashed()->findOrFail($first['analysis']->id)->trashed());
        $this->assertSame(! $archived, Models\Analysis::withTrashed()->findOrFail($last['analysis']->id)->trashed());
        $this->assertSame(0, $this->auditCount($first['analysis']));
        $this->assertSame(0, $this->auditCount($last['analysis']));
    }

    private function auditCount(Models\Analysis $analysis): int
    {
        return DB::table('activity_log')->where('subject_type', $analysis->getMorphClass())->where('subject_id', $analysis->id)->count();
    }

    /** @return array<string, Model> */
    private function fixture(Models\VAPLab $lab): array
    {
        $customer = Models\Customer::query()->create(['name' => 'Analysis mutation customer '.Str::uuid()]);
        $warehouse = Models\Warehouse::query()->create(['name' => 'Analysis mutation site '.Str::uuid(), 'customer_id' => $customer->id]);
        $department = Models\Department::factory()->create();
        $category = Models\AnalysisCategory::query()->create(['name' => 'Analysis mutation category '.Str::uuid(), 'code' => Str::uuid(), 'department_id' => $department->id]);
        $matrix = Models\Matrix::query()->create(['code' => Str::uuid(), 'description' => 'Analysis mutation matrix']);
        $profile = Models\Profile::query()->create(['name' => 'Analysis mutation profile', 'code' => Str::uuid(), 'category_id' => $category->id]);
        $matrix->profiles()->attach($profile);
        $parameter = Models\Parameter::query()->create(['name' => 'Analysis mutation parameter', 'code' => Str::uuid(), 'active' => true]);
        $profile->parameters()->attach($parameter);
        $product = Models\Product::query()->create(['name' => 'Analysis mutation product', 'matrix_id' => $matrix->id]);
        $entry = Models\VAPSampleEntry::factory()->create([
            'code' => null, 'lab_id' => $lab->id, 'department_id' => $department->id,
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'client_submitted_info' => ['collection_type' => 'direct', 'product_id' => $product->id, 'requested_profile_ids' => [$profile->id]],
        ]);
        $accession = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $code = $accession->code;
        $sample = $code->samples()->firstOrFail();
        $analysis = Models\Analysis::query()->where('sample_id', $sample->id)->firstOrFail();

        return compact('customer', 'warehouse', 'department', 'category', 'matrix', 'profile', 'parameter', 'product', 'entry', 'accession', 'code', 'sample', 'analysis');
    }
}
