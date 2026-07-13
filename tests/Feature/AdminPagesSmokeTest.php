<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
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
            route('archived_documents.index'),
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

    public function test_registered_controller_routes_resolve_to_existing_methods(): void
    {
        $missingRoutes = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            if (! class_exists($controller) || ! method_exists($controller, $method)) {
                $missingRoutes[] = [
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'action' => $action,
                ];
            }
        }

        $this->assertSame([], $missingRoutes, json_encode($missingRoutes, JSON_PRETTY_PRINT));
    }

    public function test_system_activity_static_endpoints_are_not_shadowed_by_the_detail_route(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->getJson(route('systemactivity.stats'))
            ->assertOk()
            ->assertJsonStructure(['total', 'today', 'yesterday', 'last_7_days', 'last_30_days']);

        $this->actingAs($user)
            ->getJson(route('systemactivity.cleanup.recommendations'))
            ->assertOk()
            ->assertJsonIsArray();
    }
}
