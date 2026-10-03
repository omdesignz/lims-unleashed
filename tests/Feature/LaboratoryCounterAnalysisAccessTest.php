<?php

namespace Tests\Feature;

use App\Actions\RequestLaboratoryCounterAnalysis;
use App\Jobs\RegisterCounterAnalysis;
use App\Models\Analysis;
use App\Models\AnalysisCategory;
use App\Models\CollectionProduct;
use App\Models\CounterAnalysis;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabCode;
use App\Models\LabNetwork;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\Result;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LaboratoryCounterAnalysisAccessTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private VAPLab $peerLab;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $network = LabNetwork::query()->create(['name' => 'Counter-analysis test network']);
        $this->lab = VAPLab::factory()->create(['network_id' => $network->id]);
        $this->peerLab = VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $this->lab->id]);
        $this->operator = $this->member($this->lab);
        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)
            ->update(['can_view_network' => true]);
        Notification::fake();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
    }

    public function test_request_captures_active_lab_and_rejects_peer_or_unknown_result_ids(): void
    {
        [$result] = $this->fixture($this->lab);
        [$foreign] = $this->fixture($this->peerLab);
        Bus::fake([RegisterCounterAnalysis::class]);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
            ->post(route('counteranalysis.store'), ['result_id' => $foreign->id])->assertSessionHasErrors('result_id');
        $this->postJson(route('counteranalysis.store'), ['result_id' => 0])->assertUnprocessable()->assertJsonValidationErrors('result_id');
        Bus::assertNotDispatched(RegisterCounterAnalysis::class);

        $this->post(route('counteranalysis.store'), ['result_id' => $result->id])->assertRedirect()->assertSessionHasNoErrors();
        Bus::assertDispatched(RegisterCounterAnalysis::class, fn (RegisterCounterAnalysis $job): bool => $job->result_id === $result->id
            && $job->user_id === $this->operator->id && $job->lab_id === $this->lab->id && $job->afterCommit);
        $this->post(route('counteranalysis.store'), ['result_id' => $result->id])->assertRedirect();
        Bus::assertDispatchedTimes(RegisterCounterAnalysis::class, 1);
    }

    public function test_registration_uses_canonical_source_and_is_idempotent_including_archived_counter(): void
    {
        [$result, $analysis, $entry, $product] = $this->fixture($this->lab);
        [$foreign] = $this->fixture($this->peerLab);
        $result->update(['code_id' => $foreign->code_id, 'collection_id' => $foreign->collection_id]);
        $action = app(RequestLaboratoryCounterAnalysis::class);
        $counter = $action->execute($this->lab->id, $result->id, $this->operator->id);

        $this->assertSame($product->id, $counter->code->collection_id);
        $this->assertSame($analysis->id, $counter->analysis_id);
        $this->assertSame($result->sample_id, data_get($counter->extra_data, 'source_sample_id'));
        $this->assertNotSame($result->sample_id, $counter->sample_id);
        $this->assertTrue($result->fresh()->requested_counter_analysis);
        $this->assertTrue(app(LaboratoryWorkflowOwnership::class)->counterAnalysesForLaboratory($this->lab->id)->whereKey($counter->id)->exists());
        $this->assertFalse(app(LaboratoryWorkflowOwnership::class)->counterAnalysesForLaboratory($this->peerLab->id)->whereKey($counter->id)->exists());
        $this->assertSame($counter->id, $action->execute($this->lab->id, $result->id, $this->operator->id)->id);
        $counter->update(['end_date' => now(), 'status' => true]);
        $result->update(['requested_counter_analysis' => false]);
        $this->assertSame($counter->id, $action->execute($this->lab->id, $result->id, $this->operator->id)->id);
        $this->assertFalse($result->fresh()->requested_counter_analysis);
        $counter->delete();
        $this->assertSame($counter->id, $action->execute($this->lab->id, $result->id, $this->operator->id)->id);
        $this->assertFalse($result->fresh()->requested_counter_analysis);
        $this->assertSame(1, CounterAnalysis::withTrashed()->where('result_id', $result->id)->count());
        $this->assertSoftDeleted($counter);
        Notification::assertNothingSent();
    }

    public function test_worker_lab_is_fixed_after_session_switch(): void
    {
        [$result] = $this->fixture($this->lab);
        DB::table('lab_user')->insert(['user_id' => $this->operator->id, 'lab_id' => $this->peerLab->id]);
        $job = new RegisterCounterAnalysis($result->id, $this->operator->id, $this->lab->id);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->peerLab->id]);
        $job->handle(app(RequestLaboratoryCounterAnalysis::class));

        $counter = CounterAnalysis::query()->where('result_id', $result->id)->firstOrFail();
        $this->assertSame($this->lab->id, app(LaboratoryWorkflowOwnership::class)->resolve(['counter_analysis_id' => $counter->id])?->lab_id);
    }

    public function test_legacy_serialized_job_without_lab_context_fails_closed(): void
    {
        [$result] = $this->fixture($this->lab);
        $legacy = new RegisterCounterAnalysis($result->id, $this->operator->id, $this->lab->id);
        unset($legacy->lab_id);
        $job = unserialize(serialize($legacy));
        $this->assertSame(0, $job->lab_id);
        $before = $this->counts();
        $this->assertRejected(fn () => $job->handle(app(RequestLaboratoryCounterAnalysis::class)));
        $this->assertSame($before, $this->counts());
    }

    public function test_failed_registration_rolls_back_records_flags_and_sequence_reservations(): void
    {
        [$result] = $this->fixture($this->lab);
        $before = $this->counts();
        $counters = DB::table('sequence_counters')->orderBy('scope_hash')->get()->toArray();
        $level = DB::transactionLevel();
        CounterAnalysis::creating(function (): void {
            throw new RuntimeException('Simulated counter-analysis persistence failure.');
        });

        try {
            $this->counter($result, $this->operator, $this->lab);
            $this->fail('Expected simulated persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated counter-analysis persistence failure.', $exception->getMessage());
        }

        $this->assertSame($before, $this->counts());
        $this->assertEquals($counters, DB::table('sequence_counters')->orderBy('scope_hash')->get()->toArray());
        $this->assertSame($level, DB::transactionLevel());
        $this->assertFalse($result->fresh()->requested_counter_analysis);
        Notification::assertNothingSent();
        Event::assertNothingDispatched();
    }

    #[DataProvider('invalidQueuedSources')]
    public function test_worker_rejects_stale_actor_or_ownership_without_any_mutation(string $change): void
    {
        [$result, $analysis, $entry, $product] = $this->fixture($this->lab);
        [$foreign] = $this->fixture($this->peerLab);
        $job = new RegisterCounterAnalysis($result->id, $this->operator->id, $this->lab->id);
        if (in_array($change, ['archived_foreign_link', 'archived_unassigned_link'], true)) {
            DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        }

        match ($change) {
            'removed_member' => DB::table('lab_user')->where('user_id', $this->operator->id)->delete(),
            'inactive_actor' => $this->operator->update(['is_active' => false]),
            'unverified_actor' => $this->operator->update(['email_verified_at' => null]),
            'deleted_actor' => $this->operator->delete(),
            'revoked_permission' => $this->operator->revokePermissionTo('add_counter_analysis'),
            'deleted_lab' => $this->lab->delete(),
            'deleted_entry' => $entry->delete(),
            'deleted_product' => DB::table('collection_product')->where('id', $product->id)->update(['deleted_at' => now()]),
            'deleted_code' => $result->sample->collection->delete(),
            'deleted_sample' => DB::table('samples')->where('id', $result->sample_id)->update(['deleted_at' => now()]),
            'deleted_analysis' => $analysis->delete(),
            'deleted_result' => $result->delete(),
            'wrong_analysis_code' => $analysis->update(['cl_id' => $foreign->code_id]),
            'foreign_result' => $job->result_id = $foreign->id,
            'wrong_lab' => $job->lab_id = $this->peerLab->id,
            'missing_lab_context' => $job->lab_id = 0,
            'customer_mismatch' => $product->update(['customer_id' => $foreign->collection->customer_id]),
            'archived_foreign_link' => VAPSampleEntry::factory()->create([
                'lab_id' => $this->peerLab->id, 'customer_id' => $entry->customer_id, 'collection_product_id' => $product->id,
            ])->delete(),
            'archived_unassigned_link' => DB::table('sample_entries')->insert([
                'name' => 'Unassigned legacy sample', 'code' => 'UNASSIGNED-'.$product->id, 'collection_product_id' => $product->id,
                'lab_id' => null, 'deleted_at' => now(),
            ]),
        };
        $before = $this->counts();
        $this->assertRejected(fn () => $job->handle(app(RequestLaboratoryCounterAnalysis::class)));
        $this->assertSame($before, $this->counts());
        $this->assertFalse(Result::withTrashed()->findOrFail($result->id)->requested_counter_analysis);
        Notification::assertNothingSent();
        Event::assertNothingDispatched();
    }

    /** @return array<string, array{string}> */
    public static function invalidQueuedSources(): array
    {
        return collect(['removed_member', 'inactive_actor', 'unverified_actor', 'deleted_actor', 'revoked_permission', 'deleted_lab',
            'deleted_entry', 'deleted_product', 'deleted_code', 'deleted_sample', 'deleted_analysis', 'deleted_result', 'wrong_analysis_code',
            'foreign_result', 'wrong_lab', 'missing_lab_context', 'customer_mismatch', 'archived_foreign_link', 'archived_unassigned_link'])
            ->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    public function test_peer_records_are_hidden_in_list_lookup_and_direct_routes(): void
    {
        [$localResult] = $this->fixture($this->lab);
        [$foreignResult] = $this->fixture($this->peerLab);
        $local = $this->counter($localResult, $this->operator, $this->lab);
        $foreign = $this->counter($foreignResult, $this->member($this->peerLab), $this->peerLab);
        $foreign->profile->update(['name' => 'Foreign-search-needle']);

        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
            ->get(route('counteranalysis.index'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->has('record.data', 1)->where('record.data.0.id', $local->id));
        $this->getJson(route('counteranalysis.getAnalysis'))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local->id);
        $this->getJson(route('counteranalysis.getAnalysis', ['q' => 'Foreign-search-needle']))->assertOk()->assertExactJson([]);
        $this->get(route('counteranalysis.edit', $foreign->id))->assertNotFound();
        $this->putJson(route('counteranalysis.update', $foreign->id), $this->metadata($foreign))->assertNotFound();
        $this->get(route('counteranalysis.edit', $local->id))->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('Analysis/ResultsWorkflow')->where('record.id', $local->id));
    }

    public function test_metadata_update_preserves_lineage_and_accepts_scalar_or_selector_ids(): void
    {
        [$result] = $this->fixture($this->lab);
        [$foreignResult] = $this->fixture($this->peerLab);
        $counter = $this->counter($result, $this->operator, $this->lab);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
            ->put(route('counteranalysis.update', $counter->id), $this->metadata($counter) + ['col_date' => '2026-09-20'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('2026-09-20', $counter->fresh()->col_date);

        $payload = collect($this->metadata($counter))->map(fn (int $id): array => ['value' => $id])->all();
        $this->put(route('counteranalysis.update', $counter->id), $payload)->assertRedirect()->assertSessionHasNoErrors();
        foreach (['sample_id' => $foreignResult->sample_id, 'result_id' => $foreignResult->id, 'cl_id' => $foreignResult->code_id,
            'profile_id' => $foreignResult->profile_id, 'parameter_id' => $foreignResult->parameter_id,
            'department_id' => $foreignResult->sample->analysis->department_id, 'type_id' => $foreignResult->sample->analysis->type_id] as $field => $id) {
            $this->putJson(route('counteranalysis.update', $counter->id), array_replace($this->metadata($counter), [$field => $id]))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertSame($this->metadata($counter), $this->metadata($counter->fresh()));
    }

    public function test_bulk_delete_and_restore_preflight_every_id(): void
    {
        [$localResult] = $this->fixture($this->lab);
        [$foreignResult] = $this->fixture($this->peerLab);
        $local = $this->counter($localResult, $this->operator, $this->lab);
        $foreign = $this->counter($foreignResult, $this->member($this->peerLab), $this->peerLab);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id])
            ->post(route('counteranalysis.destroy'), ['recordIds' => [$local->id, $foreign->id]])->assertNotFound();
        $this->assertNotSoftDeleted($local);
        $this->assertNotSoftDeleted($foreign);
        $this->post(route('counteranalysis.destroy'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->assertSoftDeleted($local);
        $foreign->delete();
        $this->post(route('counteranalysis.restore'), ['recordIds' => [$local->id, $foreign->id]])->assertNotFound();
        $this->assertSoftDeleted($local);
        $this->assertSoftDeleted($foreign);
        $this->post(route('counteranalysis.restore'), ['recordIds' => [$local->id]])->assertRedirect();
        $this->assertNotSoftDeleted($local);
        $this->assertSoftDeleted($foreign);
    }

    public function test_missing_permission_cannot_request_or_lookup_counter_analysis(): void
    {
        [$result] = $this->fixture($this->lab);
        $this->operator->revokePermissionTo(['add_counter_analysis', 'view_counter_analysis']);
        Bus::fake([RegisterCounterAnalysis::class]);
        $this->actingAs($this->operator)->postJson(route('counteranalysis.store'), ['result_id' => $result->id])->assertForbidden();
        $this->getJson(route('counteranalysis.getAnalysis'))->assertForbidden();
        Bus::assertNotDispatched(RegisterCounterAnalysis::class);
    }

    public function test_orphan_request_flag_does_not_prevent_repair_dispatch(): void
    {
        [$result] = $this->fixture($this->lab);
        $result->update(['requested_counter_analysis' => true]);
        Bus::fake([RegisterCounterAnalysis::class]);
        $this->actingAs($this->operator)->post(route('counteranalysis.store'), ['result_id' => $result->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        Bus::assertDispatchedTimes(RegisterCounterAnalysis::class, 1);
    }

    public function test_completed_counter_cannot_be_modified_by_direct_put(): void
    {
        [$result] = $this->fixture($this->lab);
        $counter = $this->counter($result, $this->operator, $this->lab);
        $counter->update(['end_date' => now(), 'status' => true]);
        $before = $counter->fresh()->getAttributes();
        $this->actingAs($this->operator)->putJson(route('counteranalysis.update', $counter->id),
            $this->metadata($counter) + ['col_date' => '2026-01-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('analysis');
        $this->assertSame($before, $counter->fresh()->getAttributes());
    }

    public function test_get_requests_cannot_delete_or_restore_counter_analysis(): void
    {
        [$result] = $this->fixture($this->lab);
        $counter = $this->counter($result, $this->operator, $this->lab);
        $this->actingAs($this->operator)->get(route('counteranalysis.destroy', ['recordIds' => [$counter->id]]))->assertStatus(405);
        $this->assertNotSoftDeleted($counter);
        $counter->delete();
        $this->get(route('counteranalysis.restore', ['recordIds' => [$counter->id]]))->assertStatus(405);
        $this->assertSoftDeleted($counter);
    }

    public function test_counter_result_data_and_submission_reject_peer_sample_ids(): void
    {
        [$foreignResult] = $this->fixture($this->peerLab);
        $foreign = $this->counter($foreignResult, $this->member($this->peerLab), $this->peerLab);
        Bus::fake();

        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->peerLab->id]);
        foreach (['analyze', 'verify', 'approve'] as $action) {
            $this->getJson(route('results.getCounterAnalysisDefaultResultsData', [
                'action' => $action, 'sample_id' => $foreign->sample_id,
            ]))->assertNotFound();
        }
        $this->postJson(route('results.storeCounterAnalysisResults'), [
            'sample_id' => $foreign->sample_id, 'results' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('sample_id');
        Bus::assertNothingDispatched();
    }

    public function test_reading_completed_results_does_not_mutate_counter_analysis(): void
    {
        [$result] = $this->fixture($this->lab);
        $counter = $this->counter($result, $this->operator, $this->lab);
        Result::query()->create(['sample_id' => $counter->sample_id, 'code_id' => $counter->cl_id,
            'parameter_id' => $counter->parameter_id, 'profile_id' => $counter->profile_id,
            'inserted_date' => now(), 'verified_date' => now(), 'approved_date' => now()]);

        $this->actingAs($this->operator)->get(route('counteranalysis.edit', $counter->id))->assertRedirect(route('counteranalysis.index'));
        $this->assertNull($counter->fresh()->end_date);
        $this->assertFalse($counter->fresh()->status);
    }

    #[DataProvider('invalidCounterLineage')]
    public function test_invalid_counter_lineage_is_hidden_from_http_reads_and_bulk_mutations(string $change): void
    {
        [$result, $analysis, $entry, $product] = $this->fixture($this->lab);
        [$foreignResult, $foreignAnalysis] = $this->fixture($this->peerLab);
        $counter = $this->counter($result, $this->operator, $this->lab);
        if (str_starts_with($change, 'same_lab_')) {
            [$otherResult, $otherAnalysis, , $otherProduct] = $this->fixture($this->lab);
        }
        if ($change === 'archived_foreign_link') {
            DB::statement('SET CONSTRAINTS sample_entries_collection_product_unique DEFERRED');
        }

        match ($change) {
            'wrong_sample_code' => DB::table('samples')->where('id', $counter->sample_id)->update(['cl_id' => $foreignResult->code_id]),
            'wrong_source_result' => $counter->update(['result_id' => $foreignResult->id]),
            'wrong_source_analysis' => $counter->update(['analysis_id' => $foreignAnalysis->id]),
            'wrong_source_analysis_code' => $analysis->update(['cl_id' => $foreignResult->code_id]),
            'deleted_counter_code' => $counter->code->delete(),
            'deleted_source_analysis' => $analysis->delete(),
            'deleted_entry' => $entry->delete(),
            'same_lab_wrong_result' => $counter->update(['result_id' => $otherResult->id]),
            'same_lab_wrong_analysis' => $counter->update(['analysis_id' => $otherAnalysis->id]),
            'same_lab_wrong_product' => $counter->code->update(['collection_id' => $otherProduct->id]),
            'customer_mismatch' => $product->update(['customer_id' => $foreignResult->collection->customer_id]),
            'archived_foreign_link' => VAPSampleEntry::factory()->create([
                'lab_id' => $this->peerLab->id, 'customer_id' => $entry->customer_id, 'collection_product_id' => $product->id,
            ])->delete(),
        };
        $before = $counter->fresh()->getAttributes();
        $this->actingAs($this->operator)->get(route('counteranalysis.index'))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->has('record.data', 0));
        $this->getJson(route('counteranalysis.getAnalysis'))->assertOk()->assertExactJson([]);
        $this->get(route('counteranalysis.edit', $counter->id))->assertNotFound();
        $this->post(route('counteranalysis.destroy'), ['recordIds' => [$counter->id]])->assertNotFound();
        $this->getJson(route('results.getCounterAnalysisDefaultResultsData', [
            'action' => 'verify', 'sample_id' => $counter->sample_id,
        ]))->assertNotFound();
        $this->postJson(route('results.storeCounterAnalysisResults'), [
            'sample_id' => $counter->sample_id, 'results' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('sample_id');
        $this->assertSame($before, $counter->fresh()->getAttributes());
    }

    /** @return array<string, array{string}> */
    public static function invalidCounterLineage(): array
    {
        return collect(['wrong_sample_code', 'wrong_source_result', 'wrong_source_analysis', 'wrong_source_analysis_code',
            'deleted_counter_code', 'deleted_source_analysis', 'deleted_entry', 'customer_mismatch', 'archived_foreign_link',
            'same_lab_wrong_result', 'same_lab_wrong_analysis', 'same_lab_wrong_product'])
            ->mapWithKeys(fn (string $change): array => [$change => [$change]])->all();
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true]);
        foreach (['view_counter_analysis', 'add_counter_analysis', 'edit_counter_analysis', 'delete_counter_analysis', 'restore_counter_analysis', 'insert_results', 'view_results'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    /** @return array{Result, Analysis, VAPSampleEntry, CollectionProduct} */
    private function fixture(VAPLab $lab): array
    {
        $customer = Customer::query()->create(['name' => 'Counter-analysis customer']);
        $product = CollectionProduct::query()->create(['customer_id' => $customer->id]);
        $entry = VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'customer_id' => $customer->id, 'collection_product_id' => $product->id]);
        $department = Department::factory()->create();
        $category = AnalysisCategory::query()->create(['name' => fake()->unique()->bothify('Counter category ######'), 'code' => fake()->unique()->bothify('C-######'), 'department_id' => $department->id]);
        $profile = Profile::query()->create(['name' => fake()->unique()->bothify('Counter profile ######'), 'code' => fake()->unique()->bothify('P-######'), 'category_id' => $category->id]);
        $parameter = Parameter::query()->create(['name' => fake()->unique()->bothify('Counter parameter ######'), 'code' => fake()->unique()->bothify('PARAM-######'), 'active' => true]);
        $profile->parameters()->attach($parameter->id);
        $code = LabCode::query()->create(['collection_id' => $product->id, 'cl_month' => now()->format('y/m')]);
        $sample = Sample::query()->create(['cl_id' => $code->id, 'sample_month' => now()->format('y/m')]);
        $analysis = Analysis::query()->create(['cl_id' => $code->id, 'sample_id' => $sample->id, 'profile_id' => $profile->id,
            'department_id' => $department->id, 'type_id' => $category->id]);
        $result = Result::query()->create(['sample_id' => $sample->id, 'code_id' => $code->id, 'collection_id' => $product->id,
            'parameter_id' => $parameter->id, 'profile_id' => $profile->id, 'inserted_value' => '1.5', 'inserted_date' => now()]);

        return [$result, $analysis, $entry, $product];
    }

    private function counter(Result $result, User $operator, VAPLab $lab): CounterAnalysis
    {
        return app(RequestLaboratoryCounterAnalysis::class)->execute($lab->id, $result->id, $operator->id);
    }

    /** @return array<string, int> */
    private function metadata(CounterAnalysis $counter): array
    {
        return $counter->only(['sample_id', 'result_id', 'cl_id', 'profile_id', 'parameter_id', 'department_id', 'type_id']);
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return ['counters' => CounterAnalysis::withTrashed()->count(), 'samples' => Sample::withTrashed()->count(),
            'codes' => LabCode::withTrashed()->count(), 'results' => Result::withTrashed()->count()];
    }

    private function assertRejected(callable $attempt): void
    {
        try {
            $attempt();
            $this->fail('Expected inaccessible laboratory mutation to fail closed.');
        } catch (AuthorizationException|ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }
}
