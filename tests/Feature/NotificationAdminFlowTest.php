<?php

namespace Tests\Feature;

use App\Models\BroadcastNotification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\GlobalNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationAdminFlowTest extends TestCase
{
    use DatabaseTransactions;

    private VAPLab $lab;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $admin->id]);

        return $admin;
    }

    public function test_creation_lists_only_eligible_local_recipients_without_hiding_historical_members(): void
    {
        $admin = $this->verifiedAdmin();
        $admin->forceFill(['last_login_at' => now(), 'created_at' => now()->subMonth()])->save();
        $eligible = User::factory()->create(['is_active' => true, 'last_login_at' => now()]);
        $inactive = User::factory()->create(['is_active' => false, 'last_login_at' => now()]);
        $unverified = User::factory()->unverified()->create(['is_active' => true]);
        $archived = User::factory()->create(['is_active' => true]);
        foreach ([$eligible, $inactive, $unverified, $archived] as $user) {
            DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id]);
        }
        $archived->delete();
        $inactive->assignRole(Role::findOrCreate('admin', 'web'));
        $peerLab = VAPLab::factory()->create();
        $peer = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['lab_id' => $peerLab->id, 'user_id' => $peer->id]);
        $oldNotice = $inactive->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => GlobalNotification::class,
            'data' => ['lab_id' => $this->lab->id, 'title' => 'Historical local notice'],
        ]);
        $eligible->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => GlobalNotification::class,
            'data' => ['lab_id' => $this->lab->id, 'title' => 'Unread local notice'],
        ]);
        $eligible->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => GlobalNotification::class,
            'data' => ['lab_id' => $peerLab->id, 'title' => 'Unread peer notice'],
        ]);

        $this->actingAs($admin)->withSession(['active_lab_id' => $this->lab->id])
            ->get(route('admin.notifications.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users', 2)
                ->where('users', fn ($users): bool => collect($users)->pluck('id')->sort()->values()->all() === collect([$admin->id, $eligible->id])->sort()->values()->all())
                ->where('users', fn ($users): bool => collect($users)->firstWhere('id', $eligible->id)['unread_count'] === 1)
                ->has('userGroups', 4)
                ->where('userGroups.0.count', 2)->where('userGroups.1.count', 2)
                ->where('userGroups.2.count', 1)->where('userGroups.3.count', 1));

        $this->get(route('admin.notifications.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('users', fn ($users): bool => collect($users)->contains('id', $inactive->id))
                ->where('notifications.data', fn ($notices): bool => collect($notices)->contains('id', $oldNotice->id)));
        $this->get(route('admin.notifications.show', $oldNotice->id))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('notification.user_name', $inactive->name));
    }

    #[DataProvider('eligibleAudienceModes')]
    public function test_all_and_group_queue_counts_exclude_ineligible_members(string $mode, string $group): void
    {
        $admin = $this->verifiedAdmin();
        $admin->update(['last_login_at' => now()]);
        $inactive = User::factory()->create(['is_active' => false, 'last_login_at' => now()]);
        $unverified = User::factory()->unverified()->create(['is_active' => true, 'last_login_at' => now()]);
        foreach ([$inactive, $unverified] as $user) {
            $user->assignRole(Role::findOrCreate('admin', 'web'));
            DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $user->id]);
        }
        Notification::fake();

        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'title' => 'Eligible audience', 'message' => 'Queue only eligible members.', 'type' => 'info', 'priority' => 'normal',
            'recipient_type' => $mode, 'group' => $group, 'recipients' => [],
        ])->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('toast.title', 'Notificação colocada na fila');

        Notification::assertSentTo($admin, GlobalNotification::class);
        Notification::assertNotSentTo([$inactive, $unverified], GlobalNotification::class);
        Notification::assertCount(1);
        $this->assertDatabaseHas('broadcast_notifications', ['lab_id' => $this->lab->id, 'recipient_count' => 1]);
    }

    /** @return array<string, array{string, string}> */
    public static function eligibleAudienceModes(): array
    {
        return ['all' => ['all', 'all'], 'all group' => ['group', 'all'], 'recent' => ['group', 'active'],
            'new' => ['group', 'new'], 'admins' => ['group', 'admins']];
    }

    #[DataProvider('invalidRecipientStates')]
    public function test_specific_ineligible_recipients_reject_the_whole_submission(string $state): void
    {
        $admin = $this->verifiedAdmin();
        $invalid = User::factory()->create(['is_active' => $state !== 'inactive', 'email_verified_at' => $state === 'unverified' ? null : now()]);
        if ($state !== 'foreign') {
            DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $invalid->id]);
        }
        if ($state === 'archived') {
            $invalid->delete();
        }
        Notification::fake();
        $invalidId = match ($state) {
            'missing' => PHP_INT_MAX,
            'duplicate' => $admin->id,
            default => $invalid->id,
        };
        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'title' => 'Invalid audience', 'message' => 'Must not partially send.', 'type' => 'info', 'priority' => 'normal',
            'recipient_type' => 'specific', 'recipients' => [$admin->id, $invalidId],
        ])->assertSessionHasErrors('recipients.1');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('broadcast_notifications', 0);
    }

    /** @return array<string, array{string}> */
    public static function invalidRecipientStates(): array
    {
        return array_combine($states = ['inactive', 'unverified', 'archived', 'foreign', 'missing', 'duplicate'], array_map(fn (string $state): array => [$state], $states));
    }

    public function test_retired_unverified_group_is_rejected_without_falling_back_to_everyone(): void
    {
        $admin = $this->verifiedAdmin();
        Notification::fake();
        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'title' => 'Invalid group', 'message' => 'Must not send.', 'type' => 'info', 'priority' => 'normal',
            'recipient_type' => 'group', 'group' => 'unverified', 'recipients' => [],
        ])->assertSessionHasErrors('group');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('broadcast_notifications', 0);
    }

    public function test_admin_can_send_immediate_notification_to_specific_user(): void
    {
        $admin = $this->verifiedAdmin();
        $recipient = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $recipient->id]);
        Notification::fake();

        $response = $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'title' => 'Smoke notification',
            'message' => 'Immediate notification smoke flow.',
            'type' => 'info',
            'priority' => 'normal',
            'recipient_type' => 'specific',
            'recipients' => [$recipient->id],
            'schedule_send' => false,
        ]);

        $response->assertRedirect(route('admin.notifications.index'));

        Notification::assertSentTo(
            $recipient,
            GlobalNotification::class,
            fn (GlobalNotification $notification): bool => $notification->type === 'info'
                && $notification->priority === 'normal'
                && $notification->labId === $this->lab->id
                && $notification->toDatabase($recipient)['lab_id'] === $this->lab->id
        );

        $this->assertDatabaseHas('broadcast_notifications', [
            'lab_id' => $this->lab->id,
            'sender_id' => $admin->id,
            'recipient_count' => 1,
        ]);
        $broadcast = BroadcastNotification::query()->firstOrFail();
        $broadcast->lab_id = VAPLab::factory()->create()->id;
        $this->expectException(LogicException::class);
        $broadcast->save();
    }

    public function test_admin_cannot_use_unimplemented_notification_scheduling_path(): void
    {
        $admin = $this->verifiedAdmin();
        $recipient = User::factory()->create(['is_active' => true]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $recipient->id]);
        Notification::fake();

        $response = $this->actingAs($admin)
            ->from(route('admin.notifications.create'))
            ->post(route('admin.notifications.store'), [
                'title' => 'Scheduled notification',
                'message' => 'This should fail safely.',
                'type' => 'warning',
                'priority' => 'high',
                'recipient_type' => 'specific',
                'recipients' => [$recipient->id],
                'schedule_send' => true,
                'scheduled_at' => now()->addHour()->toDateTimeString(),
            ]);

        $response->assertRedirect(route('admin.notifications.create'));
        $response->assertSessionHasErrors('scheduled_at');

        Notification::assertNothingSent();
    }

    public function test_admin_cannot_schedule_without_a_date(): void
    {
        $admin = $this->verifiedAdmin();
        Notification::fake();

        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'title' => 'Scheduled notification',
            'message' => 'Must not send.',
            'type' => 'warning',
            'priority' => 'high',
            'recipient_type' => 'all',
            'schedule_send' => true,
        ])->assertSessionHasErrors('scheduled_at');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('broadcast_notifications', 0);
    }

    public function test_admin_cannot_claim_an_unimplemented_expiration_policy(): void
    {
        $admin = $this->verifiedAdmin();
        Notification::fake();

        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'title' => 'Expiring notification',
            'message' => 'Must not promise expiry.',
            'type' => 'info',
            'priority' => 'normal',
            'recipient_type' => 'all',
            'expires_at' => now()->addDay()->toDateTimeString(),
        ])->assertSessionHasErrors('expires_at');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('broadcast_notifications', 0);
    }

    public function test_broadcast_recipients_and_group_counts_are_limited_to_the_active_laboratory(): void
    {
        $admin = $this->verifiedAdmin();
        $local = User::factory()->create(['is_active' => true]);
        $peer = User::factory()->create(['is_active' => true]);
        $peerLab = VAPLab::factory()->create();
        DB::table('lab_user')->insert([
            ['lab_id' => $this->lab->id, 'user_id' => $local->id],
            ['lab_id' => $peerLab->id, 'user_id' => $peer->id],
        ]);
        Notification::fake();

        $this->actingAs($admin)->withSession(['active_lab_id' => $this->lab->id])
            ->get(route('admin.notifications.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users', 2)
                ->where('userGroups.0.count', 2));

        $this->post(route('admin.notifications.store'), [
            'title' => 'Local broadcast',
            'message' => 'Only local members',
            'type' => 'info',
            'priority' => 'normal',
            'recipient_type' => 'all',
        ])->assertRedirect(route('admin.notifications.index'));

        Notification::assertSentTo($admin, GlobalNotification::class);
        Notification::assertSentTo($local, GlobalNotification::class);
        Notification::assertNotSentTo($peer, GlobalNotification::class);
        $this->assertSame(2, BroadcastNotification::query()->where('lab_id', $this->lab->id)->firstOrFail()->recipient_count);
    }

    public function test_specific_recipients_from_another_laboratory_are_rejected_atomically(): void
    {
        $admin = $this->verifiedAdmin();
        $local = User::factory()->create(['is_active' => true]);
        $peer = User::factory()->create(['is_active' => true]);
        $peerLab = VAPLab::factory()->create();
        DB::table('lab_user')->insert([
            ['lab_id' => $this->lab->id, 'user_id' => $local->id],
            ['lab_id' => $peerLab->id, 'user_id' => $peer->id],
        ]);
        Notification::fake();

        $this->actingAs($admin)->withSession(['active_lab_id' => $this->lab->id])
            ->post(route('admin.notifications.store'), [
                'title' => 'Mixed broadcast',
                'message' => 'Must not send',
                'type' => 'warning',
                'priority' => 'high',
                'recipient_type' => 'specific',
                'recipients' => [$local->id, $peer->id],
            ])->assertSessionHasErrors('recipients.1');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('broadcast_notifications', 0);
    }

    public function test_admin_feed_and_analytics_only_include_active_laboratory_notices(): void
    {
        $admin = $this->verifiedAdmin();
        $peerLab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $peerLab->id, 'user_id' => $admin->id]);

        $localNotice = $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => GlobalNotification::class,
            'data' => ['lab_id' => $this->lab->id, 'title' => 'Local broadcast'],
        ]);
        $peerNotice = $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => GlobalNotification::class,
            'data' => ['lab_id' => $peerLab->id, 'title' => 'Peer broadcast'],
        ]);
        $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => GlobalNotification::class,
            'data' => ['title' => 'Personal system notice'],
        ]);

        $this->actingAs($admin)->withSession(['active_lab_id' => $this->lab->id]);
        $this->get(route('admin.notifications.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('notifications.data', 1)
                ->where('notifications.data.0.id', $localNotice->id));
        $this->get(route('admin.notifications.show', $peerNotice->id))->assertNotFound();
        $this->get(route('admin.notifications.dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.total', 1));
        $this->get(route('admin.notifications.analytics'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.total_sent', 1));
        $this->get(route('notifications.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('notifications', 3));
        $export = $this->get(route('admin.notifications.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Local broadcast', $export);
        $this->assertStringNotContainsString('Peer broadcast', $export);
        $this->assertStringNotContainsString('Personal system notice', $export);
    }
}
