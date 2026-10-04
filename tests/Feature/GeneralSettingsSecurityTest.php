<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GeneralSettingsSecurityTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('readers')]
    public function test_settings_responses_never_include_the_private_key(bool $admin): void
    {
        $user = $this->user($admin);
        $settings = app(GeneralSettings::class);
        $settings->app_private_key = 'FICTIONAL-PRIVATE-KEY-DO-NOT-EXPOSE';
        $settings->save();
        $this->actingAs($user);
        $version = app(HandleInertiaRequests::class)->version(request());
        foreach ([[], ['X-Inertia' => 'true', 'X-Inertia-Version' => $version]] as $headers) {
            $response = $this->get(route('generalsettings.index'), $headers)->assertSuccessful();
            $response->assertDontSee('FICTIONAL-PRIVATE-KEY-DO-NOT-EXPOSE', false);
            if ($headers === []) {
                $response->assertInertia(fn (Assert $page) => $page->component('SystemSettings/Index')
                    ->missing('settings.app_private_key')
                    ->where('securitySummary.private_key_configured', true)
                    ->where('canEdit', $admin));
            } else {
                $response->assertJsonPath('component', 'SystemSettings/Index')
                    ->assertJsonMissingPath('props.settings.app_private_key')
                    ->assertJsonPath('props.securitySummary.private_key_configured', true)
                    ->assertJsonPath('props.canEdit', $admin);
            }
        }
    }

    /** @return array<string,array{bool}> */
    public static function readers(): array
    {
        return ['administrator' => [true], 'read-only viewer' => [false]];
    }

    public function test_ordinary_and_blank_secret_saves_preserve_the_existing_key(): void
    {
        $this->actingAs($this->user(true));
        $settings = app(GeneralSettings::class);
        $settings->app_private_key = 'FICTIONAL-RETAINED-KEY';
        $settings->save();
        foreach ([[], ['app_private_key' => null], ['app_private_key' => ''], ['app_private_key' => '   ']] as $secret) {
            $this->post(route('generalsettings.update'), ['settings_revision' => (new GeneralSettings)->revision(), 'app_slogan' => 'Configuration security check', ...$secret])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('FICTIONAL-RETAINED-KEY', app(GeneralSettings::class)->app_private_key);
        }
        $this->post(route('generalsettings.update'), ['settings_revision' => (new GeneralSettings)->revision(), 'app_private_key' => 'FICTIONAL-REPLACEMENT-KEY'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('FICTIONAL-REPLACEMENT-KEY', app(GeneralSettings::class)->app_private_key);
    }

    public function test_invalid_save_neither_changes_settings_nor_flashes_submitted_private_key(): void
    {
        $this->actingAs($this->user(true));
        $settings = app(GeneralSettings::class);
        $before = $settings->toArray();
        $this->post(route('generalsettings.update'), [
            'app_private_key' => 'FICTIONAL-REJECTED-SECRET', 'app_primary_color' => 'invalid',
        ])->assertSessionHasErrors('app_primary_color')->assertSessionMissing('_old_input.app_private_key');
        $this->assertSame('invalid', session('_old_input.app_primary_color'));
        $this->assertSame($before, app(GeneralSettings::class)->toArray());
        $this->assertStringNotContainsString('FICTIONAL-REJECTED-SECRET', json_encode(session()->all()));
    }

    public function test_view_permission_does_not_allow_writes(): void
    {
        $this->actingAs($this->user(false));
        $before = app(GeneralSettings::class)->toArray();
        $this->post(route('generalsettings.update'), ['app_name' => 'Unauthorized change'])->assertForbidden();
        $this->assertSame($before, app(GeneralSettings::class)->toArray());
    }

    private function user(bool $admin): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        if ($admin) {
            $user->assignRole(Role::findOrCreate('admin', 'web'));
        } else {
            $user->givePermissionTo(Permission::findOrCreate('view_settings', 'web'));
        }

        return $user;
    }
}
