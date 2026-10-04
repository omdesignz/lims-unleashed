<?php

namespace Tests\Feature;

use App\Actions\IssueLaboratoryNotification;
use App\Models\BroadcastNotification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class NotificationIssuanceIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private User $sender;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->sender = User::factory()->create(['is_active' => true]);
        $this->recipient = User::factory()->create(['is_active' => true]);
        $this->sender->assignRole(Role::findOrCreate('admin', 'web'));
        foreach ([$this->sender, $this->recipient] as $user) {
            DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id]);
        }
        Notification::fake();
    }

    #[DataProvider('senderRevocations')]
    public function test_direct_action_requires_current_sender_authority(string $change): void
    {
        $this->revoke($this->sender, $change);
        try {
            $this->issue();
            $this->fail('An ineligible sender issued a notification.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('broadcast_notifications', 0);
            Notification::assertNothingSent();
        }
    }

    /** @return array<string, array{string}> */
    public static function senderRevocations(): array
    {
        return ['membership' => ['membership'], 'inactive' => ['inactive'], 'unverified' => ['unverified'],
            'archived' => ['archived'], 'role' => ['role'], 'lab' => ['lab']];
    }

    #[DataProvider('senderRevocations')]
    public function test_sender_revoked_during_persistence_rolls_back_before_dispatch(string $change): void
    {
        $before = $this->sender->fresh()->getRawOriginal();
        $dispatcher = BroadcastNotification::getEventDispatcher();
        BroadcastNotification::setEventDispatcher(clone $dispatcher);
        try {
            BroadcastNotification::created(fn () => $this->revoke($this->sender, $change));
            try {
                $this->issue();
                $this->fail('Revoked authority committed an issuance.');
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('broadcast_notifications', 0);
                $this->assertSame($before, $this->sender->fresh()->getRawOriginal());
                $this->assertTrue($this->sender->fresh()->hasRole('admin'));
                $this->assertDatabaseHas('lab_user', ['lab_id' => $this->lab->id, 'user_id' => $this->sender->id]);
                $this->assertModelExists($this->lab);
                Notification::assertNothingSent();
            }
        } finally {
            BroadcastNotification::setEventDispatcher($dispatcher);
        }
    }

    #[DataProvider('recipientRevocations')]
    public function test_late_recipient_changes_reject_the_entire_frozen_audience(string $change): void
    {
        $before = $this->recipient->fresh()->getRawOriginal();
        $dispatcher = BroadcastNotification::getEventDispatcher();
        BroadcastNotification::setEventDispatcher(clone $dispatcher);
        try {
            BroadcastNotification::created(fn () => $this->revoke($this->recipient, $change));
            try {
                $this->issue(['recipients' => [$this->sender->id, $this->recipient->id]]);
                $this->fail('Changed audience was silently reduced.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('recipients', $exception->errors());
                $this->assertDatabaseCount('broadcast_notifications', 0);
                $this->assertSame($before, $this->recipient->fresh()->getRawOriginal());
                $this->assertDatabaseHas('lab_user', ['lab_id' => $this->lab->id, 'user_id' => $this->recipient->id]);
                Notification::assertNothingSent();
            }
        } finally {
            BroadcastNotification::setEventDispatcher($dispatcher);
        }
    }

    /** @return array<string, array{string}> */
    public static function recipientRevocations(): array
    {
        return ['membership' => ['membership'], 'inactive' => ['inactive'], 'unverified' => ['unverified'], 'archived' => ['archived']];
    }

    #[DataProvider('writeFailures')]
    public function test_vetoed_or_altered_issuance_is_never_dispatched(string $failure): void
    {
        $peerLab = VAPLab::factory()->create();
        $dispatcher = BroadcastNotification::getEventDispatcher();
        BroadcastNotification::setEventDispatcher(clone $dispatcher);
        try {
            if (in_array($failure, ['saving', 'creating'], true)) {
                BroadcastNotification::{$failure}(fn (): bool => false);
            } else {
                BroadcastNotification::created(function (BroadcastNotification $notification) use ($failure, $peerLab): void {
                    $changes = match ($failure) {
                        'title' => ['title' => 'Unexpected title'],
                        'count' => ['recipient_count' => 42],
                        'sender' => ['sender_id' => $this->recipient->id],
                        'owner' => ['lab_id' => $peerLab->id],
                    };
                    DB::table('broadcast_notifications')->where('id', $notification->id)->update($changes);
                });
            }
            try {
                $this->issue();
                $this->fail('A failed issuance was accepted.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
                $this->assertDatabaseCount('broadcast_notifications', 0);
                Notification::assertNothingSent();
            }
        } finally {
            BroadcastNotification::setEventDispatcher($dispatcher);
        }
    }

    /** @return array<string, array{string}> */
    public static function writeFailures(): array
    {
        return ['saving' => ['saving'], 'creating' => ['creating'], 'title' => ['title'], 'count' => ['count'], 'sender' => ['sender'], 'owner' => ['owner']];
    }

    #[DataProvider('invalidDrafts')]
    public function test_action_validates_its_own_input(string $field, mixed $value, string $error): void
    {
        try {
            $this->issue([$field => $value]);
            $this->fail('Invalid direct-action input accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($error, $exception->errors());
            $this->assertDatabaseCount('broadcast_notifications', 0);
            Notification::assertNothingSent();
        }
    }

    /** @return array<string, array{string, mixed, string}> */
    public static function invalidDrafts(): array
    {
        return ['empty title' => ['title', '', 'title'], 'missing recipient' => ['recipients', [PHP_INT_MAX], 'recipients.0'],
            'schedule' => ['schedule_send', true, 'scheduled_at'], 'forged lab' => ['lab_id', 1, 'lab_id'],
            'forged sender' => ['sender_id', 1, 'sender_id'], 'forged count' => ['recipient_count', 100, 'recipient_count']];
    }

    public function test_action_uses_explicit_laboratory_instead_of_session_context(): void
    {
        request()->setLaravelSession(app('session')->driver());
        $this->withSession(['active_lab_id' => VAPLab::factory()->create()->id]);
        $result = $this->issue();
        $this->assertSame($this->lab->id, $result['notification']->lab_id);
        $this->assertSame($this->sender->id, $result['notification']->sender_id);
        $this->assertSame(1, $result['notification']->recipient_count);
    }

    public function test_impersonation_cannot_issue_an_administrative_notification(): void
    {
        request()->setLaravelSession(app('session')->driver());
        $this->withSession(['impersonate' => $this->sender->id]);
        try {
            $this->issue();
            $this->fail('Impersonation issued an administrative message.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertDatabaseCount('broadcast_notifications', 0);
            Notification::assertNothingSent();
        }
    }

    private function revoke(User $user, string $change): void
    {
        match ($change) {
            'membership' => DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $user->id)->delete(),
            'inactive' => DB::table('users')->where('id', $user->id)->update(['is_active' => false]),
            'unverified' => DB::table('users')->where('id', $user->id)->update(['email_verified_at' => null]),
            'archived' => DB::table('users')->where('id', $user->id)->update(['deleted_at' => now()]),
            'role' => $user->removeRole('admin'),
            'lab' => $this->lab->delete(),
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array{notification: BroadcastNotification, dispatch_failed: bool}
     */
    private function issue(array $changes = []): array
    {
        return app(IssueLaboratoryNotification::class)->execute($this->sender->id, $this->lab->id, [
            'title' => 'Local message', 'message' => 'Fixture-only notification.', 'type' => 'info', 'priority' => 'normal',
            'recipient_type' => 'specific', 'recipients' => [$this->recipient->id], ...$changes,
        ]);
    }
}
