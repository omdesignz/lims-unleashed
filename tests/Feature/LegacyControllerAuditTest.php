<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LegacyControllerAuditTest extends TestCase
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

        $this->assertNotNull($admin, 'Expected at least one verified admin user for the legacy controller audit.');

        return $admin;
    }

    public function test_verified_admin_can_open_legacy_master_data_pages(): void
    {
        $user = $this->verifiedAdmin();

        $checks = [
            route('countries.index'),
            route('paymentcategories.index'),
            route('standards.index'),
            route('paidservices.index'),
            route('itypes.index'),
            route('itypes.create'),
            route('iorders.index'),
            route('phytosanitary_products.index'),
            route('phytosanitary_products.create'),
            route('users.create'),
            route('units.create'),
            route('iunits.index'),
            route('iunits.create'),
        ];

        $failures = [];

        foreach ($checks as $url) {
            $response = $this->actingAs($user)->get($url);

            if (! $response->isSuccessful()) {
                $failures[] = sprintf(
                    'Expected [%s] to load successfully, got HTTP %d.',
                    $url,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }
}
