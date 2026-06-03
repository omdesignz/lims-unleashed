<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasskeyAuthenticationTest extends TestCase
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

        $this->assertNotNull($admin, 'Expected at least one verified admin user for passkey testing.');

        return $admin;
    }

    public function test_login_page_exposes_passkey_login_routes(): void
    {
        $response = $this->get(route('login'));

        $response->assertSuccessful();
        $response->assertSee(route('passkeys.authentication_options'), false);
        $response->assertSee(route('passkeys.login'), false);
    }

    public function test_portal_login_page_exposes_portal_passkey_login_routes(): void
    {
        $response = $this->get(route('portal.login'));

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page->component('PortalAuth/Login'));
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('portal.passkeys.authentication_options'));
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('portal.passkeys.login'));
    }

    public function test_authenticated_user_can_fetch_passkey_registration_options(): void
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($this->verifiedAdmin())
            ->postJson(route('security.passkeys.registration-options'));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'rp',
            'user',
            'challenge',
            'pubKeyCredParams',
        ]);
    }

    public function test_authenticated_portal_customer_can_fetch_passkey_registration_options(): void
    {
        $warehouse = Warehouse::query()
            ->whereNotNull('customer_id')
            ->whereNotNull('email')
            ->firstOrFail();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->actingAs($warehouse, 'portal')
            ->postJson(route('portal.security.passkeys.registration-options'));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'rp',
            'user',
            'challenge',
            'pubKeyCredParams',
        ]);
    }
}
