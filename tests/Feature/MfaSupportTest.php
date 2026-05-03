<?php

namespace Tests\Feature;

use App\Actions\Fortify\DisableTwoFactorAuthentication;
use App\Actions\Fortify\EnableTwoFactorAuthentication;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MfaSupportTest extends TestCase
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

        $this->assertNotNull($admin, 'Expected at least one verified admin user for MFA testing.');

        return $admin;
    }

    public function test_enabling_two_factor_does_not_mark_internal_user_as_confirmed_until_challenge_is_completed(): void
    {
        $user = $this->verifiedAdmin();

        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        app(DisableTwoFactorAuthentication::class)($user);
        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_portal_customer_model_supports_two_factor_fields(): void
    {
        $warehouse = Warehouse::query()->whereNotNull('email')->firstOrFail();

        app(EnableTwoFactorAuthentication::class)($warehouse);
        $warehouse->refresh();

        $this->assertNotNull($warehouse->two_factor_secret);
        $this->assertNull($warehouse->two_factor_confirmed_at);

        app(DisableTwoFactorAuthentication::class)($warehouse);
        $warehouse->refresh();

        $this->assertNull($warehouse->two_factor_secret);
        $this->assertNull($warehouse->two_factor_confirmed_at);
    }
}
