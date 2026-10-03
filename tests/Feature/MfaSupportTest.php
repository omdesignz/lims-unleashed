<?php

namespace Tests\Feature;

use App\Actions\Fortify\DisableTwoFactorAuthentication;
use App\Actions\Fortify\EnableTwoFactorAuthentication;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MfaSupportTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

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
        $customer = Customer::query()->create(['name' => 'MFA portal customer '.fake()->uuid()]);
        $warehouse = Warehouse::query()->create([
            'name' => 'MFA portal site',
            'customer_id' => $customer->id,
            'email' => 'mfa-portal-'.fake()->uuid().'@lims-unleashed.test',
        ]);

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
