<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\GlobalNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationAdminFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for notification workflow testing.');

        return $admin;
    }

    public function test_admin_can_send_immediate_notification_to_specific_user(): void
    {
        Notification::fake();

        $admin = $this->verifiedAdmin();
        $recipient = User::query()
            ->whereKeyNot($admin->id)
            ->firstOrFail();

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

        Notification::assertSentTo($recipient, GlobalNotification::class);
    }

    public function test_admin_cannot_use_unimplemented_notification_scheduling_path(): void
    {
        Notification::fake();

        $admin = $this->verifiedAdmin();
        $recipient = User::query()
            ->whereKeyNot($admin->id)
            ->firstOrFail();

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
}
