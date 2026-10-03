<?php

namespace Tests\Feature;

use App\Exports\VAPSampleEntriesTemplateExport;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabNetwork;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleDiscard;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\LaboratoryWorkflowNotifier;
use App\Support\NotificationTemplateService;
use App\Support\PersonnelQualificationGate;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LaboratorySampleAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function operator(VAPLab $lab, array $permissions = ['view_samples', 'add_samples', 'edit_samples', 'delete_samples']): User
    {
        $user = User::factory()->create(['is_active' => true]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['user_id' => $user->id, 'lab_id' => $lab->id]);

        return $user;
    }

    private function discard(VAPSampleEntry $sample, User $user, array $attributes = []): VAPSampleDiscard
    {
        return VAPSampleDiscard::create(array_merge([
            'sample_id' => $sample->id, 'lab_id' => $sample->lab_id, 'discarded_by_id' => $user->id,
            'discard_method' => 'Autoclave', 'qty' => '1', 'discarded_at' => now(),
        ], $attributes));
    }

    private function intakePayload(VAPLab $lab): array
    {
        $customer = Customer::create(['name' => 'Test customer']);
        $warehouse = Warehouse::create(['name' => fake()->unique()->company(), 'customer_id' => $customer->id]);
        $department = Department::factory()->create();

        return [
            'name' => 'Test intake', 'sample_type' => 'AGUA', 'lab_id' => $lab->id,
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'department_id' => $department->id,
        ];
    }

    public function test_bulk_and_csv_import_reject_foreign_lab_before_writing(): void
    {
        $lab = VAPLab::factory()->create();
        $data = $this->intakePayload(VAPLab::factory()->create());
        $count = VAPSampleEntry::count();
        $this->actingAs($this->operator($lab))->postJson(route('vap_samples.samples.bulk-store'), ['samples' => [$data]])
            ->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->postJson(route('vap_samples.samples.bulk-store'), ['samples' => ['entry' => $data]])
            ->assertUnprocessable()->assertJsonValidationErrors('samples');
        $csv = implode(',', array_keys($data))."\n".implode(',', array_values($data));
        $this->postJson(route('vap_samples.samples.import'), [
            'file' => UploadedFile::fake()->createWithContent('samples.csv', $csv),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertSame($count, VAPSampleEntry::count());
    }

    public function test_import_template_uses_active_lab(): void
    {
        VAPLab::factory()->create(['name' => 'Unrelated laboratory']);
        $lab = VAPLab::factory()->create(['name' => 'Selected laboratory']);
        $rows = (new VAPSampleEntriesTemplateExport($lab->id))->collection();
        $this->assertSame($lab->name, $rows->first()['Laboratório']);
    }

    public function test_batch_is_rejected_before_side_effects_when_a_later_row_targets_another_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $data = $this->intakePayload($lab) + ['code' => 'BATCH-LOCAL'];
        $this->mock(PersonnelQualificationGate::class)->shouldNotReceive('ensure');
        $this->mock(SampleEntryCollectionFlowService::class)->shouldNotReceive('sync');
        $this->mock(NotificationTemplateService::class)->shouldNotReceive('notify');
        $this->mock(LaboratoryWorkflowNotifier::class)->shouldNotReceive('notifySampleCollectionLinked');
        $count = VAPSampleEntry::count();
        $level = DB::transactionLevel();
        $this->actingAs($this->operator($lab))->postJson(route('vap_samples.samples.bulk-store'), ['samples' => [
            $data, array_merge($data, ['code' => 'BATCH-PEER', 'lab_id' => $peer->id]),
        ]])->assertUnprocessable()->assertJsonValidationErrors('samples');
        $this->assertSame($count, VAPSampleEntry::count());
        $this->assertSame($level, DB::transactionLevel());
        $csv = implode(',', array_keys($data))."\n".implode(',', array_values($data))."\n"
            .implode(',', array_values(array_merge($data, ['code' => 'BATCH-PEER', 'lab_id' => $peer->id])));
        $this->postJson(route('vap_samples.samples.import'), [
            'file' => UploadedFile::fake()->createWithContent('samples.csv', $csv),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertSame($count, VAPSampleEntry::count());
        $this->assertSame($level, DB::transactionLevel());
    }

    public function test_valid_intake_and_update_remain_available_in_active_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $data = $this->intakePayload($lab) + ['code' => 'ACCESS-TEST-CREATE'];
        $this->mock(PersonnelQualificationGate::class)->shouldReceive('ensure')->twice();
        $this->mock(SampleEntryCollectionFlowService::class)->shouldReceive('sync')->twice()->andReturnNull();
        $this->mock(NotificationTemplateService::class)->shouldReceive('notify')->once()->andReturn(1);
        $this->mock(LaboratoryWorkflowNotifier::class)->shouldReceive('notifySampleCollectionLinked')->twice();
        $this->actingAs($this->operator($lab))->post(route('vap_samples.samples.store'), $data)
            ->assertRedirect()->assertSessionHas('type', 'success');
        $sample = VAPSampleEntry::where('code', 'ACCESS-TEST-CREATE')->firstOrFail();
        $this->assertSame($lab->id, $sample->lab_id);
        $this->put(route('vap_samples.samples.update', $sample), array_merge($data, ['name' => 'Updated intake']))
            ->assertRedirect()->assertSessionHas('type', 'success');
        $this->assertSame('Updated intake', $sample->fresh()->name);
    }

    public function test_archived_samples_keep_their_discard_audit_and_monthly_counts_exclude_last_year(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $this->discard($sample, $user);
        $sample->discards()->update(['created_at' => now()->subYear()]);
        $sample->delete();
        $this->actingAs($user)->getJson(route('vap_samples.discards.stats'))->assertOk()
            ->assertJsonPath('total_discards', 1)->assertJsonPath('discards_this_month', 0);
        $csv = $this->get(route('vap_samples.discards.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($sample->code, $csv);
        $this->get(route('vap_samples.reports'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('summary.total_discards', 1)->where('discards.data.0.sample.code', $sample->code));
    }

    public function test_discard_pdf_keeps_archived_sample_lineage(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $discard = $this->discard($sample, $user);
        $sample->delete();
        $pdf = \Mockery::mock();
        $pdf->shouldReceive('output')->once()->andReturn('%PDF-test');
        \PDF::shouldReceive('loadView')->once()->withArgs(fn (string $view, array $data): bool => $view === 'PDFs.sample-discard' && $data['discard']->sample->id === $sample->id
        )->andReturn($pdf);
        $this->actingAs($user)->get(route('vap_samples.discards.pdf', $discard))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_update_rolls_back_when_downstream_workflow_fails(): void
    {
        $lab = VAPLab::factory()->create();
        $data = $this->intakePayload($lab);
        $sample = VAPSampleEntry::factory()->create($data);
        $this->mock(PersonnelQualificationGate::class)->shouldReceive('ensure')->once();
        $this->mock(SampleEntryCollectionFlowService::class)->shouldReceive('sync')->once()->andThrow(new \RuntimeException('Workflow failed'));
        $level = DB::transactionLevel();
        $this->actingAs($this->operator($lab))->withoutExceptionHandling();
        try {
            $this->put(route('vap_samples.samples.update', $sample), array_merge($data, ['name' => 'Must roll back']));
            $this->fail('Expected workflow failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Workflow failed', $exception->getMessage());
        }
        $this->assertSame('Test intake', $sample->fresh()->name);
        $this->assertSame($level, DB::transactionLevel());
    }

    public function test_collection_links_cannot_be_injected_and_untrusted_metadata_is_not_used_for_analysis_reads(): void
    {
        $lab = VAPLab::factory()->create();
        $sample = VAPSampleEntry::factory()->create([
            'lab_id' => $lab->id, 'client_submitted_info' => ['linked_sample_ids' => [987654]],
        ]);
        $this->actingAs($this->operator($lab))->postJson(route('vap_samples.samples.store'), ['collection_product_id' => 987654])
            ->assertUnprocessable()->assertJsonValidationErrors('collection_product_id');
        $this->putJson(route('vap_samples.samples.update', $sample), ['collection_product_id' => 987654])
            ->assertUnprocessable()->assertJsonValidationErrors('collection_product_id');
        $this->get(route('vap_samples.show', $sample))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('workflowSummary.linked_sample_count', 0));
    }

    public function test_administrator_without_direct_membership_cannot_access_legacy_routes(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);
        foreach (['index', 'dashboard', 'reports', 'samples.stats', 'samples.export', 'discards.recent', 'discards.stats', 'discards.export', 'samples.import-template'] as $route) {
            $this->get(route('vap_samples.'.$route))->assertForbidden();
        }
        foreach (['samples.store', 'samples.bulk-store', 'samples.import', 'discards.store'] as $route) {
            $this->postJson(route('vap_samples.'.$route), [])->assertForbidden();
        }
    }

    public function test_network_manager_and_admin_cannot_access_peer_sample_records(): void
    {
        $network = LabNetwork::factory()->create();
        $main = VAPLab::factory()->create(['network_id' => $network->id]);
        $peer = VAPLab::factory()->create(['network_id' => $network->id]);
        $network->update(['main_lab_id' => $main->id]);
        $user = $this->operator($main);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->where('user_id', $user->id)->update(['can_view_network' => true]);
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $peer->id]);
        $discard = $this->discard($sample, $user);
        $this->actingAs($user)->get(route('vap_samples.show', $sample))->assertNotFound();
        $this->get(route('vap_samples.samples.pdf', $sample))->assertNotFound();
        $this->get(route('vap_samples.discards.pdf', $discard))->assertNotFound();
        $this->putJson(route('vap_samples.samples.update', $sample), [])->assertNotFound();
        $this->patchJson(route('vap_samples.samples.internal-quality-control-decision', $sample), [])->assertNotFound();
        $this->deleteJson(route('vap_samples.samples.destroy', $sample))->assertNotFound();
        $this->assertNotSoftDeleted($sample);
    }

    public function test_read_permission_does_not_allow_mutations(): void
    {
        $lab = VAPLab::factory()->create();
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $this->actingAs($this->operator($lab, ['view_samples']));
        foreach (['samples.store', 'samples.bulk-store', 'samples.import', 'discards.store'] as $route) {
            $this->postJson(route('vap_samples.'.$route), [])->assertForbidden();
        }
        $this->putJson(route('vap_samples.samples.update', $sample), [])->assertForbidden();
        $this->patchJson(route('vap_samples.samples.internal-quality-control-decision', $sample), [])->assertForbidden();
        $this->deleteJson(route('vap_samples.samples.destroy', $sample))->assertForbidden();
    }

    public function test_membership_without_permission_does_not_allow_reads(): void
    {
        $this->actingAs($this->operator(VAPLab::factory()->create(), []))
            ->getJson(route('vap_samples.samples.stats'))->assertForbidden();
    }

    public function test_legacy_search_and_charts_stay_in_active_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $local = VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'name' => 'Water local']);
        $peer = VAPSampleEntry::factory()->create(['code' => 'Water-secret']);
        $this->actingAs($this->operator($lab))->withSession(['active_lab_id' => $peer->lab_id])
            ->get(route('vap_samples.index', ['search' => 'Water']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('samples', 1)->where('samples.0.id', $local->id)
                ->where('stats.total_samples', 1)->has('labs', 1)->where('labs.0.id', $lab->id));
    }

    public function test_reports_work_on_postgresql_and_cannot_expand_lab_scope(): void
    {
        $lab = VAPLab::factory()->create();
        VAPSampleEntry::factory()->create([
            'lab_id' => $lab->id, 'received_at' => now()->subHours(3), 'analysis_end_date' => now(), 'status' => 'COMPLETADO',
        ]);
        $peer = VAPSampleEntry::factory()->create();
        $this->actingAs($this->operator($lab))->get(route('vap_samples.reports'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('summary.total_samples', 1)
                ->where('summary.avg_turnaround_hours', 3)->has('samples.data', 1)->has('labs', 1));
        $this->get(route('vap_samples.reports', ['lab_id' => $peer->lab_id]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('summary.total_samples', 0)->has('samples.data', 0));
    }

    public function test_sample_statistics_and_exports_exclude_peer_samples(): void
    {
        $lab = VAPLab::factory()->create();
        $local = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $peer = VAPSampleEntry::factory()->create();
        $this->actingAs($this->operator($lab))->getJson(route('vap_samples.samples.stats'))
            ->assertOk()->assertJsonPath('total_samples', 1);
        $csv = $this->get(route('vap_samples.samples.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($local->code, $csv);
        $this->assertStringNotContainsString($peer->code, $csv);
        $csv = $this->get(route('vap_samples.samples.export', ['lab_id' => $peer->lab_id]))->assertOk()->streamedContent();
        $this->assertStringNotContainsString($peer->code, $csv);
    }

    public function test_lab_cannot_be_changed_through_create_or_update(): void
    {
        $lab = VAPLab::factory()->create();
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $peer = VAPLab::factory()->create();
        $this->actingAs($this->operator($lab))->postJson(route('vap_samples.samples.store'), ['lab_id' => $peer->id])
            ->assertUnprocessable()->assertJsonValidationErrors('lab_id');
        $this->putJson(route('vap_samples.samples.update', $sample), ['lab_id' => $peer->id])
            ->assertUnprocessable()->assertJsonValidationErrors('lab_id');
        $this->putJson(route('vap_samples.samples.update', $sample), ['code' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame($lab->id, $sample->fresh()->lab_id);
    }

    public function test_discard_uses_sample_ownership_and_rejects_duplicates(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'status' => 'COMPLETADO']);
        $data = ['sample_id' => $sample->id, 'discard_method' => 'Autoclave', 'qty' => '1'];
        $level = DB::transactionLevel();
        $this->actingAs($user)->post(route('vap_samples.discards.store'), $data)->assertRedirect()->assertSessionHas('type', 'success');
        $this->assertDatabaseHas('sample_discards', ['sample_id' => $sample->id, 'lab_id' => $lab->id, 'discarded_by_id' => $user->id]);
        $this->assertSame('discarded', $sample->fresh()->retention_status);
        $this->postJson(route('vap_samples.discards.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('sample_id');
        $this->assertSame(1, $sample->discards()->count());
        $this->assertSame($level, DB::transactionLevel());
    }

    public function test_invalid_discard_rolls_back_without_leaving_a_transaction_open(): void
    {
        $lab = VAPLab::factory()->create();
        $sample = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $level = DB::transactionLevel();
        $this->actingAs($this->operator($lab))->postJson(route('vap_samples.discards.store'), [
            'sample_id' => $sample->id, 'discard_method' => 'Autoclave', 'qty' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors('sample_id');
        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame(0, $sample->discards()->count());
        $this->assertNotSame('discarded', $sample->fresh()->retention_status);
    }

    public function test_cross_lab_and_deleted_samples_cannot_be_discarded(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPSampleEntry::factory()->create(['status' => 'COMPLETADO']);
        $local = VAPSampleEntry::factory()->create(['lab_id' => $lab->id, 'status' => 'COMPLETADO']);
        $this->actingAs($this->operator($lab));
        $data = ['discard_method' => 'Autoclave', 'qty' => '1'];
        $this->postJson(route('vap_samples.discards.store'), $data + ['sample_id' => $peer->id])->assertUnprocessable()->assertJsonValidationErrors('sample_id');
        $this->postJson(route('vap_samples.discards.store'), $data + ['sample_id' => $local->id, 'lab_id' => $peer->lab_id])->assertUnprocessable()->assertJsonValidationErrors('lab_id');
        $local->delete();
        $this->postJson(route('vap_samples.discards.store'), $data + ['sample_id' => $local->id])->assertUnprocessable()->assertJsonValidationErrors('sample_id');
        $this->assertSame(0, $peer->discards()->count());
    }

    public function test_discard_reads_require_both_parent_and_discard_ownership(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = VAPSampleEntry::factory()->create(['lab_id' => $lab->id]);
        $peer = VAPSampleEntry::factory()->create();
        $visible = $this->discard($local, $user);
        $this->discard($peer, $user, ['lab_id' => $lab->id]);
        $this->discard($local, $user, ['lab_id' => $peer->lab_id]);
        $this->discard($peer, $user);
        $this->actingAs($user)->getJson(route('vap_samples.discards.recent'))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $visible->id);
        $this->getJson(route('vap_samples.discards.stats'))->assertOk()->assertJsonPath('total_discards', 1);
        $csv = $this->get(route('vap_samples.discards.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($local->code, $csv);
        $this->assertStringNotContainsString($peer->code, $csv);
        $this->getJson(route('vap_samples.discards.recent', ['days' => -1]))->assertUnprocessable();
    }
}
