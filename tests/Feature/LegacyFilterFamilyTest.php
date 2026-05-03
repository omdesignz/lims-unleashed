<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LegacyFilterFamilyTest extends TestCase
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

        $this->assertNotNull($admin, 'Expected at least one verified admin user for legacy filter auditing.');

        return $admin;
    }

    public function test_verified_admin_can_open_next_legacy_filter_family_pages(): void
    {
        $user = $this->verifiedAdmin();

        $checks = [
            route('customers.index'),
            route('customers.index', ['filter' => 'trashed']),
            route('vehicles.index'),
            route('vehicles.index', ['filter' => 'trashed']),
            route('faqs.index'),
            route('faqs.index', ['filter' => 'trashed']),
            route('collectionreasons.index'),
            route('collectionreasons.index', ['filter' => 'trashed']),
            route('ideliveries.index'),
            route('ideliveries.index', ['filter' => 'trashed']),
            route('boards'),
            route('boards', ['filter' => 'trashed']),
            route('products.index'),
            route('products.index', ['filter' => 'trashed']),
            route('warehouses.index'),
            route('warehouses.index', ['filter' => 'trashed']),
            route('matrixes.index'),
            route('matrixes.index', ['filter' => 'trashed']),
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
