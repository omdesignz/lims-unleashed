<?php

namespace Tests\Feature;

use App\Actions\SaveNonConformity;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Models\VAPNonConformityAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NonConformityAuthoringTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    private VAPNonConformity $record;

    private VAPNonConformityAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->operator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        foreach (['view', 'add', 'edit', 'delete', 'restore'] as $ability) {
            $this->operator->givePermissionTo(Permission::findOrCreate($ability.'_occurrences', 'web'));
        }
        $this->record = VAPNonConformity::create([...$this->payload(), 'lab_id' => $this->lab->id, 'reported_by_id' => $this->operator->id]);
        $this->action = VAPNonConformityAction::create(['lab_id' => $this->lab->id, 'nc_id' => $this->record->id, 'correction' => 'Original correction']);
        Notification::fake();
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function payload(): array
    {
        return ['nc_number' => 'NC-AUTHORING-001', 'title' => 'Deviation', 'description' => 'Observed evidence',
            'status' => 'opened', 'severity' => 'medium', 'category' => 'quality', 'reported_by' => 'Reporter', 'reported_at' => '2026-10-03 12:00:00'];
    }

    public function test_new_assignments_require_an_active_verified_unarchived_member_of_the_owning_lab(): void
    {
        foreach (['inactive', 'unverified', 'archived', 'foreign'] as $state) {
            $assignee = User::factory()->create(['is_active' => $state !== 'inactive',
                'email_verified_at' => $state === 'unverified' ? null : now()]);
            DB::table('lab_user')->insert(['lab_id' => $state === 'foreign' ? VAPLab::factory()->create()->id : $this->lab->id,
                'user_id' => $assignee->id]);
            if ($state === 'archived') {
                $assignee->delete();
            }
            $before = VAPNonConformity::count();
            $this->postJson(route('vap_non_conformities.store'), [...$this->payload(),
                'nc_number' => 'NC-INELIGIBLE-'.$state, 'assigned_to_id' => $assignee->id])
                ->assertUnprocessable()->assertJsonValidationErrors('assigned_to_id');
            $this->assertSame($before, VAPNonConformity::count());
        }
        $assignee = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $assignee->id]);
        $this->postJson(route('vap_non_conformities.store'), [...$this->payload(),
            'nc_number' => 'NC-ELIGIBLE', 'assigned_to_id' => $assignee->id])->assertRedirect();
        $this->assertSame($assignee->id, VAPNonConformity::where('lab_id', $this->lab->id)->where('nc_number', 'NC-ELIGIBLE')->sole()->assigned_to_id);
    }

    public function test_number_suggestions_skip_taken_numbers_including_archived_records_without_changing_format(): void
    {
        $this->record->forceFill(['created_at' => now()->subYear()])->saveQuietly();
        $taken = VAPNonConformity::create([...$this->payload(), 'lab_id' => $this->lab->id,
            'nc_number' => 'NC-'.now()->format('Ym').'-0002']);
        $taken->delete();
        $this->assertSame('NC-'.now()->format('Ym').'-0003', (new VAPNonConformity)->generateNcNumber($this->lab->id));
    }

    public function test_metadata_correction_preserves_an_existing_assignee_after_membership_is_removed_without_allowing_new_assignment(): void
    {
        $assignee = User::factory()->create(['is_active' => false, 'email_verified_at' => null]);
        $this->record->forceFill(['assigned_to_id' => $assignee->id])->saveQuietly();
        $this->putJson(route('vap_non_conformities.update', $this->record), [...$this->payload(),
            'assigned_to_id' => $assignee->id, 'comments' => 'Metadata correction'])->assertRedirect();
        $this->assertSame($assignee->id, $this->record->fresh()->assigned_to_id);
        $this->assertSame('Metadata correction', $this->record->fresh()->comments);
        $this->postJson(route('vap_non_conformities.store'), [...$this->payload(),
            'nc_number' => 'NC-REASSIGNMENT', 'assigned_to_id' => $assignee->id])
            ->assertUnprocessable()->assertJsonValidationErrors('assigned_to_id');
    }

    public function test_direct_authoring_rechecks_assignee_eligibility_before_any_write(): void
    {
        $assignee = User::factory()->create(['is_active' => false, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $assignee->id]);
        $before = [VAPNonConformity::count(), VAPNonConformityAction::count()];
        try {
            app(SaveNonConformity::class)->execute($this->operator->id, $this->lab->id, [...$this->payload(),
                'nc_number' => 'NC-DIRECT-INELIGIBLE', 'assigned_to_id' => $assignee->id,
                'actions' => [['correction' => 'Must not persist']]]);
            $this->fail('An ineligible assignee was accepted by direct authoring.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('assigned_to_id', $exception->errors());
        }
        $this->assertSame($before, [VAPNonConformity::count(), VAPNonConformityAction::count()]);
    }

    public function test_duplicate_numbers_are_rejected_without_parent_or_action_writes_but_other_labs_can_reuse_the_label(): void
    {
        $before = [VAPNonConformity::count(), VAPNonConformityAction::count()];
        $this->postJson(route('vap_non_conformities.store'), [...$this->payload(), 'actions' => [['correction' => 'Must not persist']]])
            ->assertUnprocessable()->assertJsonValidationErrors('nc_number');
        $this->assertSame($before, [VAPNonConformity::count(), VAPNonConformityAction::count()]);
        $otherLab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $otherLab->id, 'user_id' => $this->operator->id]);
        $this->withSession(['active_lab_id' => $otherLab->id]);
        $this->postJson(route('vap_non_conformities.store'), $this->payload())->assertRedirect();
        $this->assertSame(2, VAPNonConformity::where('nc_number', $this->record->nc_number)->count());
    }

    public function test_nested_actions_reject_unvalidated_ownership_and_approval_fields(): void
    {
        foreach (['nc_id' => $this->record->id, 'lab_id' => $this->lab->id, 'approved_at' => now()->toDateTimeString(),
            'was_effective' => true, 'assigned_to_id' => $this->operator->id, 'evidence' => 'Forged evidence'] as $key => $value) {
            $this->putJson(route('vap_non_conformities.update', $this->record), [...$this->payload(),
                'actions' => [['id' => $this->action->id, 'correction' => 'Changed', $key => $value]],
            ])->assertUnprocessable()->assertJsonValidationErrors('actions.0');
        }
        $this->assertSame('Original correction', $this->action->fresh()->correction);
        $this->assertNull($this->action->fresh()->approved_at);
    }

    public function test_edit_payload_preserves_local_datetime_values_and_parent_archive_keeps_private_media(): void
    {
        Storage::fake('local');
        $media = $this->record->addMediaFromString('Private evidence')->usingFileName('evidence.txt')->toMediaCollection('attachments');
        $this->action->update(['due_at' => '2026-10-09 15:45:00']);
        $this->get(route('vap_non_conformities.edit', $this->record))->assertInertia(fn (Assert $page) => $page
            ->where('nonConformity.reported_at', '2026-10-03T12:00:00')
            ->where('nonConformity.actions.0.due_at', '2026-10-09T15:45:00'));
        $this->delete(route('vap_non_conformities.destroy', $this->record))->assertRedirect();
        $this->get(route('vap_non_conformities.attachments.show', [$this->record, $media]))->assertOk();
        $this->assertModelExists($media);
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_duplicate_foreign_and_archived_action_ids_cannot_be_reconciled(): void
    {
        $row = ['id' => $this->action->id, 'correction' => 'Changed'];
        $this->putJson(route('vap_non_conformities.update', $this->record), [...$this->payload(), 'actions' => [$row, $row]])
            ->assertUnprocessable();
        $other = VAPNonConformity::create([...$this->payload(), 'nc_number' => 'NC-OTHER', 'lab_id' => $this->lab->id]);
        $this->putJson(route('vap_non_conformities.update', $other), [...$this->payload(), 'nc_number' => 'NC-OTHER', 'actions' => [$row]])
            ->assertNotFound();
        $this->action->delete();
        $this->putJson(route('vap_non_conformities.update', $this->record), [...$this->payload(), 'actions' => [$row]])
            ->assertNotFound();
    }

    public function test_removed_actions_remain_in_read_only_history_and_retries_preserve_archive_timestamp(): void
    {
        $url = route('vap_non_conformities.update', $this->record);
        $payload = [...$this->payload(), 'actions' => []];
        $this->put($url, $payload)->assertRedirect();
        $archived = $this->action->fresh();
        $this->assertTrue($archived->trashed());
        $this->put($url, $payload)->assertRedirect();
        $this->assertEquals($archived->deleted_at, $this->action->fresh()->deleted_at);
        $this->get(route('vap_non_conformities.show', $this->record))->assertInertia(fn (Assert $page) => $page
            ->has('nonConformity.actions', 1)->where('nonConformity.actions.0.correction', 'Original correction')
            ->where('nonConformity.actions.0.deleted_at', fn ($value) => filled($value)));
        $this->get(route('vap_non_conformities.edit', $this->record))->assertInertia(fn (Assert $page) => $page->has('nonConformity.actions', 0));
    }

    public function test_parent_archive_restore_preserves_action_archive_states_and_is_retry_safe(): void
    {
        $this->action->delete();
        $active = VAPNonConformityAction::create(['lab_id' => $this->lab->id, 'nc_id' => $this->record->id, 'correction' => 'Still active']);
        $url = route('vap_non_conformities.destroy', $this->record);
        $this->delete($url)->assertRedirect();
        $timestamp = $this->record->fresh()->deleted_at;
        $this->delete($url)->assertRedirect();
        $this->assertEquals($timestamp, $this->record->fresh()->deleted_at);
        $this->get(route('vap_non_conformities.index'))->assertInertia(fn (Assert $page) => $page->where('nonConformities.total', 0));
        $this->get(route('vap_non_conformities.index', ['archived' => 1]))->assertInertia(fn (Assert $page) => $page->where('nonConformities.total', 1));
        $this->get(route('vap_non_conformities.edit', $this->record))->assertNotFound();
        $this->putJson(route('vap_non_conformities.update', $this->record), $this->payload())->assertNotFound();
        $restore = route('vap_non_conformities.restore', $this->record);
        $this->patch($restore)->assertRedirect();
        $this->patch($restore)->assertRedirect();
        $this->assertFalse($this->record->fresh()->trashed());
        $this->assertTrue($this->action->fresh()->trashed());
        $this->assertFalse($active->fresh()->trashed());
    }

    public function test_multipart_empty_action_list_archives_actions_without_treating_metadata_only_as_removal(): void
    {
        $this->post(route('vap_non_conformities.update', $this->record), [...$this->payload(), '_method' => 'put', 'actions_present' => '1'])
            ->assertRedirect();
        $this->assertTrue($this->action->fresh()->trashed());
    }

    public function test_write_veto_rolls_back_parent_and_actions(): void
    {
        $event = 'eloquent.deleting: '.VAPNonConformityAction::class;
        Event::listen($event, fn () => false);
        try {
            $this->putJson(route('vap_non_conformities.update', $this->record), [...$this->payload(), 'title' => 'Must roll back', 'actions' => []])
                ->assertConflict();
        } finally {
            Event::forget($event);
        }
        $this->assertSame('Deviation', $this->record->fresh()->title);
        $this->assertFalse($this->action->fresh()->trashed());

        $event = 'eloquent.updating: '.VAPNonConformity::class;
        Event::listen($event, fn () => false);
        try {
            $this->putJson(route('vap_non_conformities.update', $this->record), [...$this->payload(), 'title' => 'Rejected',
                'actions' => [['id' => $this->action->id, 'correction' => 'Rejected']]])->assertConflict();
        } finally {
            Event::forget($event);
        }
        $this->assertSame('Original correction', $this->action->fresh()->correction);
    }

    public function test_revoked_permissions_and_impersonation_cannot_mutate_records(): void
    {
        $this->operator->revokePermissionTo('restore_occurrences');
        $this->patchJson(route('vap_non_conformities.restore', $this->record))->assertForbidden();
        $this->withSession(['impersonate' => 999])->putJson(route('vap_non_conformities.update', $this->record), $this->payload())->assertForbidden();
        $this->deleteJson(route('vap_non_conformities.destroy', $this->record))->assertForbidden();
        $this->assertFalse($this->record->fresh()->trashed());
    }

    public function test_writer_rechecks_current_membership_inside_transaction(): void
    {
        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->delete();
        $this->expectException(AuthorizationException::class);
        app(SaveNonConformity::class)->execute($this->operator->id, $this->lab->id, $this->payload(), $this->record->id);
    }
}
