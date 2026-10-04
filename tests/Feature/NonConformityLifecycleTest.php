<?php

namespace Tests\Feature;

use App\Actions\SaveNonConformity;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Models\VAPNonConformityAction;
use App\Notifications\OperationalNotification;
use App\Support\NotificationTemplateService;
use App\Support\QualityModuleNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NonConformityLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    private VAPNonConformity $record;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->operator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        foreach (['view_occurrences', 'add_occurrences', 'edit_occurrences', 'delete_occurrences', 'restore_occurrences',
            'resolve_non_conformities', 'verify_non_conformities', 'close_non_conformities', 'reopen_non_conformities'] as $permission) {
            $this->operator->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->record = VAPNonConformity::create([...$this->payload(), 'lab_id' => $this->lab->id, 'reported_by_id' => $this->operator->id,
            'evidence' => '{"order_id":123}']);
        Notification::fake();
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function payload(): array
    {
        return ['nc_number' => 'NC-LIFECYCLE', 'title' => 'Deviation', 'description' => 'Observed evidence',
            'status' => 'opened', 'severity' => 'medium', 'category' => 'quality', 'reported_by' => 'Reporter', 'reported_at' => '2026-10-03 12:00:00'];
    }

    private function transition(string $action, array $overrides = []): TestResponse
    {
        return $this->postJson(route('vap_non_conformities.transition', [$this->record, $action]), [
            'request_id' => (string) Str::uuid(), 'workflow_revision' => $this->record->fresh()->workflow_revision,
            'evidence' => 'Documented '.$action, ...$overrides,
        ]);
    }

    private function close(): void
    {
        foreach (['resolve', 'verify', 'close'] as $action) {
            $this->transition($action)->assertRedirect();
        }
    }

    public function test_staged_lifecycle_records_server_actors_and_timestamps_without_overwriting_procurement_evidence(): void
    {
        $this->transition('verify')->assertUnprocessable();
        $this->transition('close')->assertUnprocessable();
        $this->transition('resolve', ['resolved_at' => '1999-01-01', 'verified_at' => '1999-01-01', 'actor_id' => 999])->assertRedirect();
        $this->assertTrue($this->record->fresh()->resolved_at->isToday());
        $this->assertNull($this->record->fresh()->verified_at);
        $this->transition('close')->assertUnprocessable();
        $this->transition('verify')->assertRedirect();
        $this->transition('close')->assertRedirect();
        $record = $this->record->fresh();
        $this->assertSame('closed', $record->status);
        $this->assertNotNull($record->closed_at);
        $this->assertSame('{"order_id":123}', $record->evidence);
        $this->assertSame(['resolve', 'verify', 'close'], array_column($record->workflow_history, 'action'));
        $this->assertSame([$this->operator->id], array_values(array_unique(array_column($record->workflow_history, 'actor_id'))));
        $this->assertSame('Deviation', $record->workflow_history[0]['snapshot']['dossier']['title']);
    }

    public function test_blank_evidence_is_rejected_but_zero_is_valid_and_terminal_status_cannot_be_authored(): void
    {
        foreach (['', '   ', null] as $evidence) {
            $this->transition('resolve', ['evidence' => $evidence])->assertUnprocessable()->assertJsonValidationErrors('evidence');
        }
        foreach (['resolved', 'closed'] as $status) {
            $this->putJson(route('vap_non_conformities.update', $this->record), [...$this->payload(), 'status' => $status])
                ->assertUnprocessable()->assertJsonValidationErrors('status');
            $this->postJson(route('vap_non_conformities.store'), [...$this->payload(), 'nc_number' => 'FORGED-'.$status, 'status' => $status])
                ->assertUnprocessable()->assertJsonValidationErrors('status');
        }
        $this->transition('resolve', ['evidence' => '0'])->assertRedirect();
        $this->assertSame('0', $this->record->fresh()->resolution_evidence);
        $this->transition('verify', ['evidence' => ' '])->assertUnprocessable();
    }

    public function test_retries_are_idempotent_and_cannot_be_reused_for_another_operation(): void
    {
        $requestId = (string) Str::uuid();
        $payload = ['request_id' => $requestId, 'workflow_revision' => 0, 'evidence' => 'Evidence'];
        $this->transition('resolve', $payload)->assertRedirect();
        $time = $this->record->fresh()->resolved_at;
        $this->travel(2)->minutes();
        $this->transition('resolve', $payload)->assertRedirect();
        $this->assertEquals($time, $this->record->fresh()->resolved_at);
        $this->assertCount(1, $this->record->fresh()->workflow_history);
        $this->transition('verify', $payload)->assertConflict();
        $this->transition('resolve', [...$payload, 'evidence' => 'Changed'])->assertConflict();
        $this->transition('verify', ['workflow_revision' => 0])->assertConflict();
    }

    public function test_closed_dossier_rejects_every_authoring_field_even_empty_values_but_accepts_observations(): void
    {
        $action = VAPNonConformityAction::create(['lab_id' => $this->lab->id, 'nc_id' => $this->record->id, 'correction' => 'Keep']);
        $this->close();
        $before = $this->record->fresh();
        foreach ([['title' => 'Changed'], ['title' => ''], ['status' => 'opened'], ['actions' => []], ['attachment_files' => []], ['nc_number' => null]] as $mutation) {
            $this->putJson(route('vap_non_conformities.update', $this->record), ['comments' => 'Allowed', ...$mutation])->assertUnprocessable();
        }
        $this->putJson(route('vap_non_conformities.update', $this->record), ['comments' => 'New observation', 'workflow_history' => []])->assertRedirect();
        $after = $this->record->fresh();
        $this->assertSame('New observation', $after->comments);
        $this->assertSame($before->title, $after->title);
        $this->assertEquals($before->closed_at, $after->closed_at);
        $this->assertCount(4, $after->workflow_history);
        $this->assertFalse($action->fresh()->trashed());
    }

    public function test_core_or_action_edits_invalidate_verification_but_comments_do_not(): void
    {
        $this->transition('resolve')->assertRedirect();
        $this->transition('verify')->assertRedirect();
        $payload = [...$this->payload(), 'status' => 'resolved', 'comments' => 'Note'];
        $this->putJson(route('vap_non_conformities.update', $this->record), $payload)->assertRedirect();
        $this->assertNotNull($this->record->fresh()->verified_at);
        $this->putJson(route('vap_non_conformities.update', $this->record), [...$payload, 'title' => 'Correction'])->assertRedirect();
        $this->assertNull($this->record->fresh()->verified_at);
        $this->assertNull($this->record->fresh()->verification_evidence);
        $this->transition('close')->assertUnprocessable();
        $this->transition('verify')->assertRedirect();
        $this->putJson(route('vap_non_conformities.update', $this->record), [...$payload, 'title' => 'Correction', 'actions' => [['correction' => 'New action']]])->assertRedirect();
        $this->assertNull($this->record->fresh()->verified_at);
        $this->putJson(route('vap_non_conformities.update', $this->record), [...$payload, 'status' => 'in_progress'])->assertUnprocessable();
    }

    public function test_reopening_requires_reason_and_preserves_previous_cycle_in_lab_private_history(): void
    {
        $this->close();
        $this->transition('reopen', ['evidence' => ''])->assertUnprocessable();
        $this->transition('reopen', ['evidence' => 'New issue'])->assertRedirect();
        $record = $this->record->fresh();
        $this->assertSame('in_progress', $record->status);
        $this->assertNull($record->closed_at);
        $this->assertNull($record->verified_at);
        $this->assertNull($record->resolution_evidence);
        $this->assertSame('Documented resolve', $record->workflow_history[0]['evidence']);
        $this->close();
        $this->assertCount(7, $this->record->fresh()->workflow_history);
        $this->get(route('vap_non_conformities.show', $this->record))->assertInertia(fn (Assert $page) => $page
            ->has('nonConformity.workflow_history', 7)->missing('nonConformity.workflow_history.0.snapshot')->missing('nonConformity.workflow_history.0.request_id'));
        $peer = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $peer->id, 'user_id' => $this->operator->id]);
        $this->withSession(['active_lab_id' => $peer->id])->get(route('vap_non_conformities.show', $this->record))->assertNotFound();
        $this->transition('reopen')->assertNotFound();
    }

    public function test_separate_permission_and_impersonation_protect_transitions(): void
    {
        $this->operator->revokePermissionTo('verify_non_conformities');
        $this->transition('resolve')->assertRedirect();
        $this->transition('verify')->assertForbidden();
        $this->withSession(['impersonate' => 999]);
        $this->transition('reopen')->assertForbidden();
        $this->assertCount(1, $this->record->fresh()->workflow_history);
    }

    public function test_archive_restore_preserves_closed_state_and_history(): void
    {
        $this->close();
        $before = $this->record->fresh();
        $this->delete(route('vap_non_conformities.destroy', $this->record))->assertRedirect();
        $this->transition('reopen')->assertNotFound();
        $this->patch(route('vap_non_conformities.restore', $this->record))->assertRedirect();
        $this->assertSame('closed', $this->record->fresh()->status);
        $this->assertEquals($before->closed_at, $this->record->fresh()->closed_at);
        $this->assertSame($before->workflow_history, $this->record->fresh()->workflow_history);
    }

    public function test_legacy_resolved_records_require_a_documented_new_cycle_without_inventing_evidence(): void
    {
        $this->record->update(['status' => 'resolved']);
        $this->transition('verify')->assertUnprocessable();
        $this->transition('close')->assertUnprocessable();
        $this->assertNull($this->record->fresh()->resolved_at);
        $this->transition('reopen')->assertRedirect();
        $this->close();
    }

    public function test_write_veto_and_authority_revocation_roll_back_state_and_audit_together(): void
    {
        $event = 'eloquent.updating: '.VAPNonConformity::class;
        Event::listen($event, fn () => false);
        try {
            $this->transition('resolve')->assertConflict();
        } finally {
            Event::forget($event);
        }
        $this->assertSame('opened', $this->record->fresh()->status);
        $this->assertNull($this->record->fresh()->workflow_history);
        $event = 'eloquent.updated: '.VAPNonConformity::class;
        Event::listen($event, fn () => DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->delete());
        try {
            $this->transition('resolve')->assertForbidden();
        } finally {
            Event::forget($event);
        }
        $this->assertSame('opened', $this->record->fresh()->status);
        $this->assertNull($this->record->fresh()->workflow_history);
        $this->assertTrue(DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->exists());
    }

    public function test_direct_actions_revalidate_current_state_and_authority(): void
    {
        $this->close();
        $this->expectException(ValidationException::class);
        app(SaveNonConformity::class)->execute($this->operator->id, $this->lab->id, $this->payload(), $this->record->id);
    }

    public function test_editing_makes_previously_open_transition_form_stale(): void
    {
        $this->putJson(route('vap_non_conformities.update', $this->record), [...$this->payload(), 'status' => 'in_progress', 'description' => 'New finding'])->assertRedirect();
        $this->transition('resolve', ['workflow_revision' => 0])->assertConflict();
        $this->assertSame('in_progress', $this->record->fresh()->status);
        $this->assertSame('opened', $this->record->fresh()->workflow_history[0]['from']);
        $this->assertSame('in_progress', $this->record->fresh()->workflow_history[0]['to']);
    }

    public function test_transition_notifications_are_distinct_and_only_reach_current_authorized_lab_members(): void
    {
        $outsider = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $outsider->givePermissionTo('view_occurrences');
        $inactive = User::factory()->create(['is_active' => false, 'email_verified_at' => now()]);
        $inactive->givePermissionTo('view_occurrences');
        $reporter = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach ([$inactive, $reporter] as $user) {
            DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id]);
        }
        $this->record->update(['reported_by_id' => $reporter->id]);
        $this->close();
        $record = $this->record->fresh();
        $notifier = app(QualityModuleNotifier::class);
        foreach ($record->workflow_history as $entry) {
            $notifier->notifyNonConformityTransition($record, $entry['request_id']);
            $notifier->notifyNonConformityTransition($record, $entry['request_id']);
        }
        Notification::assertSentToTimes($this->operator, OperationalNotification::class, 3);
        Notification::assertNotSentTo([$outsider, $inactive, $reporter], OperationalNotification::class);
        $notification = Notification::sent($this->operator, OperationalNotification::class)->first();
        $this->assertTrue($notification->shouldSend($this->operator, 'database'));
        $this->operator->revokePermissionTo('view_occurrences');
        $this->assertFalse($notification->shouldSend($this->operator, 'database'));
    }

    public function test_notification_failure_does_not_fail_committed_transition_and_identical_retry_can_deliver(): void
    {
        $notifier = $this->mock(QualityModuleNotifier::class);
        $notifier->shouldReceive('notifyNonConformityTransition')->once()->andThrow(new \RuntimeException('Notification service unavailable'));
        $notifier->shouldReceive('notifyNonConformityTransition')->once()->andReturnNull();
        $payload = ['request_id' => (string) Str::uuid(), 'workflow_revision' => 0, 'evidence' => 'Retry evidence'];
        $this->transition('resolve', $payload)->assertRedirect();
        $this->assertSame('resolved', $this->record->fresh()->status);
        $this->transition('resolve', $payload)->assertRedirect();
        $this->assertCount(1, $this->record->fresh()->workflow_history);
    }

    public function test_failed_notification_does_not_poison_deduplication_key(): void
    {
        $templates = $this->mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        $templates->shouldReceive('notify')->once()->andReturn(1);
        $notifier = new QualityModuleNotifier($templates);
        $id = (string) Str::uuid();
        try {
            $notifier->notifyNonConformityTransition($this->record, $id);
            $this->fail('Expected notification failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Queue unavailable', $exception->getMessage());
        }
        $notifier->notifyNonConformityTransition($this->record, $id);
        $notifier->notifyNonConformityTransition($this->record, $id);
    }
}
