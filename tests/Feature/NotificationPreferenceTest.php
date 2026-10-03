<?php

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\Role;
use App\Models\User;
use App\Support\NotificationChannelResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_user_can_open_and_update_notification_preferences(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('notification-preferences.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/NotificationPreferences')
                ->has('preferences', 8));

        $this->actingAs($user)
            ->put(route('notification-preferences.update'), [
                'preferences' => [[
                    'category' => 'laboratory',
                    'database_enabled' => true,
                    'broadcast_enabled' => false,
                    'mail_enabled' => false,
                    'quiet_hours_start' => '20:00',
                    'quiet_hours_end' => '06:00',
                    'timezone' => 'Africa/Luanda',
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHas('toast.variant', 'success');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'category' => 'laboratory',
            'broadcast_enabled' => false,
            'mail_enabled' => false,
        ]);
    }

    public function test_channel_resolver_honors_opt_outs_and_quiet_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 22:30:00', 'Africa/Luanda'));
        $user = $this->verifiedAdmin();
        NotificationPreference::query()->updateOrCreate([
            'user_id' => $user->id,
            'category' => 'inventory',
        ], [
            'database_enabled' => true,
            'broadcast_enabled' => true,
            'mail_enabled' => true,
            'quiet_hours_start' => '20:00',
            'quiet_hours_end' => '06:00',
            'timezone' => 'Africa/Luanda',
        ]);

        $channels = app(NotificationChannelResolver::class)->resolve(
            $user,
            'inventory',
            'normal',
            ['database', 'broadcast', 'mail']
        );

        $this->assertSame(['database'], $channels);
    }

    public function test_urgent_alerts_bypass_quiet_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 22:30:00', 'Africa/Luanda'));
        $user = $this->verifiedAdmin();
        NotificationPreference::query()->updateOrCreate([
            'user_id' => $user->id,
            'category' => 'quality',
        ], [
            'database_enabled' => false,
            'broadcast_enabled' => false,
            'mail_enabled' => false,
            'quiet_hours_start' => '20:00',
            'quiet_hours_end' => '06:00',
            'timezone' => 'Africa/Luanda',
        ]);

        $channels = app(NotificationChannelResolver::class)->resolve(
            $user,
            'quality',
            'urgent',
            ['database', 'broadcast', 'mail']
        );

        $this->assertSame(['database', 'broadcast', 'mail'], $channels);
    }

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }
}
