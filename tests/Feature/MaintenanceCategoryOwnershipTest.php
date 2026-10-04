<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceCategoryOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    private VAPLab $peer;

    private MaintenanceCategory $preset;

    private MaintenanceCategory $owned;

    private MaintenanceCategory $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->peer = VAPLab::factory()->create();
        $this->operator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        foreach (['view', 'add', 'edit', 'delete', 'restore'] as $ability) {
            $this->operator->givePermissionTo(Permission::findOrCreate($ability.'_maintenance_categories', 'web'));
        }
        foreach (['view', 'add', 'edit'] as $ability) {
            $this->operator->givePermissionTo(Permission::findOrCreate($ability.'_maintenance_tasks', 'web'));
        }
        $this->preset = MaintenanceCategory::create(['name' => 'Shared preset', 'code' => 'CAT-PRESET']);
        $this->owned = $this->category($this->lab, 'CAT-OWN', 'Local category');
        $this->other = $this->category($this->peer, 'CAT-PEER', 'Private peer category');
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function category(VAPLab $lab, string $code, string $name): MaintenanceCategory
    {
        $category = new MaintenanceCategory(['name' => $name, 'code' => $code, 'description' => 'Searchable description']);
        $category->lab_id = $lab->id;
        $category->save();

        return $category;
    }

    public function test_lists_and_all_authoring_choices_only_expose_shared_and_owned_categories(): void
    {
        $this->get(route('vap-maintenance.categories'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPMaintenance/Categories/Index')->where('stats.total', 2)->where('stats.owned', 1)
            ->where('breadcrumbs.0.title', 'Tarefas de manutenção')->where('breadcrumbs.1.title', 'Categorias')
            ->where('categories.from', 1)->where('categories.to', 2)->where('categories.total', 2)
            ->where('stats.presets', 1)->has('categories.data', 2)->where('categories.data.0.is_preset', false)
            ->where('categories.data.1.is_preset', true));
        foreach (['vap-maintenance.tasks.create', 'vap-maintenance.tasks', 'maintenancetasks.import.form'] as $route) {
            $this->get(route($route))->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has('categories', 2)->where('categories', fn ($categories) => ! collect($categories)->contains('id', $this->other->id)));
        }
        $this->getJson(route('maintenancecategories.getMaintenanceCategory', ['q' => 'category']))->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.id', $this->owned->id);
        $this->get(route('vap-maintenance.categories', ['search' => 'Searchable', 'per_page' => 1]))
            ->assertInertia(fn (Assert $page) => $page->where('stats.total', 1));
    }

    public function test_create_assigns_owner_and_rejects_forged_ownership_and_invalid_codes(): void
    {
        $this->post(route('vap-maintenance.categories.store'), ['name' => 'New category', 'code' => 'CAT-NEW'])
            ->assertRedirect(route('vap-maintenance.categories'));
        $this->assertSame($this->lab->id, MaintenanceCategory::where('code', 'CAT-NEW')->firstOrFail()->lab_id);
        foreach ([
            ['name' => 'Forged', 'code' => 'CAT-FORGE', 'lab_id' => $this->peer->id],
            ['name' => 'Bad code', 'code' => '=formula()'],
            ['name' => 'Duplicate', 'code' => $this->preset->code],
        ] as $payload) {
            $this->postJson(route('vap-maintenance.categories.store'), $payload)->assertUnprocessable();
        }
    }

    public function test_presets_and_peer_categories_are_read_only_even_with_operation_permissions(): void
    {
        foreach ([$this->preset, $this->other] as $category) {
            $this->putJson(route('vap-maintenance.categories.update', $category), ['name' => 'Changed', 'code' => $category->code])->assertNotFound();
            $this->deleteJson(route('vap-maintenance.categories.destroy', $category))->assertNotFound();
            $this->patchJson(route('vap-maintenance.categories.restore', $category))->assertNotFound();
            $this->assertFalse($category->fresh()->trashed());
        }
    }

    public function test_archive_and_restore_are_retry_safe_and_preserve_records(): void
    {
        $url = route('vap-maintenance.categories.destroy', $this->owned);
        $this->delete($url)->assertRedirect();
        $timestamp = $this->owned->fresh()->deleted_at->toISOString();
        $this->delete($url)->assertRedirect();
        $this->assertSame($timestamp, $this->owned->fresh()->deleted_at->toISOString());
        $this->get(route('vap-maintenance.categories', ['archived' => 1]))->assertInertia(fn (Assert $page) => $page
            ->has('categories.data', 1)->where('categories.data.0.deleted', true));
        $url = route('vap-maintenance.categories.restore', $this->owned);
        $this->patch($url)->assertRedirect();
        $this->patch($url)->assertRedirect();
        $this->assertFalse($this->owned->fresh()->trashed());
    }

    public function test_used_code_stays_fixed_even_when_its_task_is_archived(): void
    {
        $task = $this->task();
        $number = $task->maintenance_task_no;
        $task->delete();
        $this->putJson(route('vap-maintenance.categories.update', $this->owned), ['name' => 'Updated name', 'code' => 'CAT-CHANGED'])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame('Local category', $this->owned->fresh()->name);
        $this->put(route('vap-maintenance.categories.update', $this->owned), ['name' => 'Updated name', 'code' => $this->owned->code])
            ->assertRedirect();
        $this->assertSame($number, $task->fresh()->maintenance_task_no);
        $this->get(route('vap-maintenance.categories'))->assertInertia(fn (Assert $page) => $page
            ->where('categories.data', fn ($rows) => collect($rows)->firstWhere('id', $this->owned->id)['code_locked'] === true));
    }

    public function test_archived_category_remains_on_existing_task_but_cannot_be_used_for_new_tasks(): void
    {
        $task = $this->task();
        $this->delete(route('vap-maintenance.categories.destroy', $this->owned))->assertRedirect();
        $this->put(route('vap-maintenance.tasks.update', $task), ['obs' => 'Corrected notes'])->assertRedirect();
        $this->assertSame('Corrected notes', $task->fresh()->obs);
        $this->assertSame($this->owned->id, $task->fresh()->category->id);
        $payload = ['name' => 'New task', 'category_id' => $this->owned->id, 'equipment_id' => $task->equipment_id, 'due_date' => '2026-10-12'];
        $this->postJson(route('vap-maintenance.tasks.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $payload['category_id'] = $this->other->id;
        $this->postJson(route('vap-maintenance.tasks.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_permissions_membership_and_impersonation_are_enforced(): void
    {
        $this->operator->revokePermissionTo('edit_maintenance_categories');
        $this->putJson(route('vap-maintenance.categories.update', $this->owned), ['name' => 'Blocked', 'code' => $this->owned->code])->assertForbidden();
        $this->withSession(['impersonate' => 1])->postJson(route('vap-maintenance.categories.store'), ['name' => 'Blocked', 'code' => 'BLOCKED'])->assertForbidden();
        $this->withSession(['impersonate' => null]);
        DB::table('lab_user')->where('user_id', $this->operator->id)->delete();
        $this->get(route('vap-maintenance.categories'))->assertForbidden();
    }

    public function test_write_veto_rolls_back_and_no_legacy_get_mutates_state(): void
    {
        Event::listen('eloquent.deleting: '.MaintenanceCategory::class, fn () => false);
        $this->deleteJson(route('vap-maintenance.categories.destroy', $this->owned))->assertConflict();
        $this->assertFalse($this->owned->fresh()->trashed());
        Event::forget('eloquent.deleting: '.MaintenanceCategory::class);
        foreach (['destroy', 'restore', 'create', $this->owned->id.'/edit'] as $suffix) {
            $this->get('/maintenancecategories/'.$suffix)->assertNotFound();
        }
        $this->post('/maintenancecategories', ['name' => 'Legacy', 'code' => 'LEGACY'])->assertMethodNotAllowed();
        $this->get(route('maintenancecategories.index'))->assertRedirect(route('vap-maintenance.categories'));
    }

    public function test_invalid_list_filters_and_unavailable_lookup_authority_are_rejected(): void
    {
        foreach ([['per_page' => 101], ['archived' => 'wrong'], ['search' => str_repeat('x', 201)]] as $filters) {
            $this->getJson(route('vap-maintenance.categories', $filters))->assertUnprocessable();
        }
        $this->operator->revokePermissionTo('view_maintenance_categories');
        $this->getJson(route('maintenancecategories.getMaintenanceCategory', ['q' => 'category']))->assertForbidden();
    }

    private function task(): MaintenanceTask
    {
        $equipment = InventoryItem::create(['lab_id' => $this->lab->id, 'name' => 'Test equipment',
            'category_id' => ItemCategory::create(['name' => 'Equipment', 'inventory_type' => 'equipment'])->id]);

        return MaintenanceTask::create(['lab_id' => $this->lab->id, 'name' => 'Calibration', 'category_id' => $this->owned->id,
            'equipment_id' => $equipment->id, 'maintenance_task_year' => 2026, 'due_date' => '2026-10-12',
            'is_planned' => true, 'is_executed' => false, 'executed_by_supplier' => false]);
    }
}
