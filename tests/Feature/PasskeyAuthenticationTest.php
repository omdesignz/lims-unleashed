<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
