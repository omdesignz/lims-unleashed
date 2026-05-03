<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use Tests\TestCase;

class LandingAndSettingsTest extends TestCase
{
    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for settings testing.');

        return $admin;
    }

    public function test_guest_can_open_public_landing_page(): void
    {
        $response = $this->get(route('landing'));

        $response->assertSuccessful();
    }

    public function test_authenticated_user_is_redirected_from_public_landing_to_dashboard(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())->get(route('landing'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_update_general_settings_and_signing_configuration(): void
    {
        $user = $this->verifiedAdmin();
        /** @var GeneralSettings $settings */
        $settings = app(GeneralSettings::class);
        $original = [
            'app_name' => $settings->app_name,
            'app_version' => $settings->app_version,
            'app_slogan' => $settings->app_slogan,
            'app_contact' => $settings->app_contact,
            'app_email' => $settings->app_email,
            'app_primary_color' => $settings->app_primary_color,
            'app_secondary_color' => $settings->app_secondary_color,
            'app_accent_color' => $settings->app_accent_color,
            'app_theme_preset' => $settings->app_theme_preset,
            'app_operation_mode' => $settings->app_operation_mode,
            'app_agt_valid_name' => $settings->app_agt_valid_name,
            'app_agt_validation_number' => $settings->app_agt_validation_number,
            'app_public_key' => $settings->app_public_key,
            'app_private_key' => $settings->app_private_key,
            'app_client_name' => $settings->app_client_name,
            'app_client_lab_name' => $settings->app_client_lab_name,
            'app_client_lab_director' => $settings->app_client_lab_director,
        ];

        $payload = [
            'app_name' => 'LIMS Unleashed QA',
            'app_version' => '13.0.0',
            'app_slogan' => 'ISO-ready laboratory operations',
            'app_contact' => '244900000000',
            'app_email' => 'qa@lims-unleashed.test',
            'app_primary_color' => '#0f172a',
            'app_secondary_color' => '#111827',
            'app_accent_color' => '#14b8a6',
            'app_theme_preset' => 'executive',
            'app_operation_mode' => 'hybrid',
            'app_agt_valid_name' => 'Autoridade de Teste',
            'app_agt_validation_number' => 'VAL-2026-001',
            'app_public_key' => '-----BEGIN PUBLIC KEY-----example-----END PUBLIC KEY-----',
            'app_private_key' => '-----BEGIN PRIVATE KEY-----example-----END PRIVATE KEY-----',
            'app_client_name' => 'Cliente QA',
            'app_client_lab_name' => 'Laboratório QA',
            'app_client_lab_director' => 'Direção Técnica QA',
        ];

        try {
            $response = $this->actingAs($user)->post(route('generalsettings.update'), $payload);

            $response->assertRedirect();

            $settings = app(GeneralSettings::class);

            $this->assertSame($payload['app_name'], $settings->app_name);
            $this->assertSame($payload['app_primary_color'], $settings->app_primary_color);
            $this->assertSame($payload['app_secondary_color'], $settings->app_secondary_color);
            $this->assertSame($payload['app_accent_color'], $settings->app_accent_color);
            $this->assertSame($payload['app_theme_preset'], $settings->app_theme_preset);
            $this->assertSame($payload['app_operation_mode'], $settings->app_operation_mode);
            $this->assertSame($payload['app_agt_validation_number'], $settings->app_agt_validation_number);
            $this->assertSame($payload['app_public_key'], $settings->app_public_key);
            $this->assertSame($payload['app_private_key'], $settings->app_private_key);
            $this->assertSame($payload['app_client_lab_name'], $settings->app_client_lab_name);
        } finally {
            $restoredSettings = app(GeneralSettings::class);
            $restoredSettings->fill($original);
            $restoredSettings->save();
        }
    }

    public function test_admin_can_open_settings_page_after_modernization(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())->get(route('generalsettings.index'));

        $response->assertSuccessful();
    }
}
