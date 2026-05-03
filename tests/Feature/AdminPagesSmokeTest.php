<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminPagesSmokeTest extends TestCase
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

        $this->assertNotNull($admin, 'Expected at least one verified admin user for admin page smoke testing.');

        return $admin;
    }

    public function test_verified_admin_can_open_core_admin_pages_with_filters(): void
    {
        $user = $this->verifiedAdmin();

        $checks = [
            route('users.index'),
            route('users.index', ['filter' => 'trashed']),
            route('roles.index'),
            route('roles.index', ['filter' => 'trashed']),
            route('permissions.index'),
            route('permissions.index', ['filter' => 'trashed']),
            route('samples.index'),
            route('qms.index'),
            route('supplier-assessments.index'),
            route('vap_non_conformities.index'),
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
