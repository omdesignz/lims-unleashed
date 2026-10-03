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
