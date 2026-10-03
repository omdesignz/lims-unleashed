<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPagesSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $admin->givePermissionTo(Permission::findOrCreate('view_samples', 'web'));
        $laboratory = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $laboratory->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $laboratory->id]);

        return $admin;
    }

    #[DataProvider('coreAdminPages')]
    public function test_verified_admin_can_open_core_admin_page(string $routeName, array $parameters): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)->get(route($routeName, $parameters))->assertOk();
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function coreAdminPages(): array
    {
        return [
            'users' => ['users.index', []],
            'trashed users' => ['users.index', ['filter' => 'trashed']],
            'roles' => ['roles.index', []],
            'trashed roles' => ['roles.index', ['filter' => 'trashed']],
            'permissions' => ['permissions.index', []],
            'trashed permissions' => ['permissions.index', ['filter' => 'trashed']],
            'archived documents' => ['archived_documents.index', []],
            'samples' => ['samples.index', []],
            'quality management' => ['qms.index', []],
            'supplier assessments' => ['supplier-assessments.index', []],
            'non-conformities' => ['vap_non_conformities.index', []],
        ];
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
        DB::table('activity_log')->insert([
            'log_name' => 'admin-smoke',
            'description' => 'Hour aggregation regression',
            'created_at' => now()->startOfDay()->addHours(3),
            'updated_at' => now()->startOfDay()->addHours(3),
        ]);

        $this->actingAs($user)
            ->getJson(route('systemactivity.stats'))
            ->assertOk()
            ->assertJsonStructure(['total', 'today', 'yesterday', 'last_7_days', 'last_30_days', 'by_hour'])
            ->assertJsonPath('by_hour.03:00', fn (int $count): bool => $count >= 1);

        $this->actingAs($user)
            ->getJson(route('systemactivity.cleanup.recommendations'))
            ->assertOk()
            ->assertJsonIsArray();
    }
}
