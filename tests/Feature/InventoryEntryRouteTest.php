<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryEntryRouteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_entry_redirects_to_the_existing_editor_without_granting_stock_read(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['add_inventory']);
        $this->stock($lab);
        $destination = route('inventory.index', ['create' => 1]);
        $this->get(route('inventory.create'))->assertRedirect($destination);
        $this->get($destination)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Index')->where('canView', false)->where('openCreate', true)
            ->where('initialRecord', null)->has('record.data', 0)->where('record.meta.total', 0));
        $this->get(route('inventory.index'))->assertForbidden();
        $this->getJson(route('vap-inventory.items.lookup', ['q' => 'Item']))->assertForbidden();
        $this->getJson(route('iwarehouses.getInventoryItemWarehouse', ['q' => 'Warehouse']))->assertForbidden();
    }

    public function test_edit_only_entry_exposes_only_the_requested_position_and_preserves_stock_identity(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['edit_inventory']);
        $stock = $this->stock($lab);
        $this->stock($lab);
        $destination = route('inventory.index', ['edit' => $stock->id]);
        $this->get(route('inventory.edit', $stock))->assertRedirect($destination);
        $this->get($destination)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Index')->where('canView', false)->where('openCreate', false)
            ->where('initialRecord.data.id', $stock->id)->where('initialRecord.data.inventory_type', 'material')
            ->has('record.data', 0)->where('record.meta.total', 0));
        $this->putJson(route('inventory.update', $stock), ['item_id' => $stock->item_id, 'warehouse_id' => $stock->warehouse_id,
            'min_stock_level' => '0.2500', 'reorder_point' => '0.7500'])->assertRedirect();
        $this->assertSame('0.2500', $stock->fresh()->min_stock_level);
        $this->assertSame('2.0000', $stock->fresh()->qty_available);
        $this->get(route('inventory.index'))->assertForbidden();
        $this->get(route('inventory.show', $stock))->assertForbidden();
        $this->get(route('inventory.create'))->assertForbidden();
    }

    public function test_list_read_permission_does_not_authorize_editor_contexts(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['view_inventory']);
        $stock = $this->stock($lab);
        $this->get(route('inventory.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('canView', true)->where('openCreate', false)->where('initialRecord', null)->has('record.data', 1));
        $this->get(route('inventory.index', ['create' => 1]))->assertForbidden();
        $this->get(route('inventory.index', ['edit' => $stock->id]))->assertForbidden();
        $this->get(route('inventory.create'))->assertForbidden();
        $this->get(route('inventory.edit', $stock))->assertForbidden();
    }

    public function test_creation_with_existing_selector_permissions_uses_the_canonical_write(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['add_inventory', 'view_iitems', 'view_iwarehouses']);
        $existing = $this->stock($lab);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'New warehouse '.fake()->uuid()]);
        $this->get(route('inventory.index', ['create' => 1]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Index')->where('openCreate', true)->has('record.data', 0));
        $this->getJson(route('vap-inventory.items.lookup', ['q' => $existing->item->name]))->assertOk()->assertJsonPath('0.id', $existing->item_id);
        $this->getJson(route('iwarehouses.getInventoryItemWarehouse', ['q' => $warehouse->name]))->assertOk()->assertJsonPath('0.id', $warehouse->id);
        $this->postJson(route('inventory.store'), ['item_id' => $existing->item_id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '0.1250', 'min_stock_level' => '0.0100', 'reorder_point' => '0.0250'])->assertRedirect();
        $created = Inventory::query()->where('item_id', $existing->item_id)->where('warehouse_id', $warehouse->id)->sole();
        $this->assertSame($lab->id, $created->lab_id);
        $this->assertSame('0.1250', $created->qty_available);
        $this->assertSame('2.0000', $existing->fresh()->qty_available);
        $this->get(route('inventory.index'))->assertForbidden();
    }

    public function test_add_permission_cannot_open_edit_intent(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['add_inventory']);
        $stock = $this->stock($lab);
        $this->get(route('inventory.edit', $stock))->assertForbidden();
        $this->get(route('inventory.index', ['edit' => $stock->id]))->assertForbidden();
    }

    public function test_peer_archived_and_missing_positions_are_not_edit_contexts(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['edit_inventory']);
        $peer = $this->stock(VAPLab::factory()->create());
        $archived = $this->stock($lab);
        $archived->delete();
        foreach ([$peer->id, $archived->id, PHP_INT_MAX] as $id) {
            $this->get(route('inventory.edit', $id))->assertNotFound();
            $this->get(route('inventory.index', ['edit' => $id]))->assertNotFound();
        }
    }

    #[DataProvider('invalidContexts')]
    public function test_invalid_editor_intent_is_rejected_without_a_database_write(array $query): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['view_inventory', 'add_inventory', 'edit_inventory']);
        $before = Inventory::query()->count();
        $this->call('GET', route('inventory.index'), $query, server: ['HTTP_ACCEPT' => 'application/json'])->assertUnprocessable();
        $this->assertSame($before, Inventory::query()->count());
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidContexts(): array
    {
        $cases = [];
        foreach (['null' => null, 'zero' => 0, 'negative' => -1, 'true' => true, 'false' => false, 'float' => 1.0,
            'fractional' => 1.5, 'decimal' => '1.0', 'exponent' => '1e2', 'text' => 'abc', 'overflow' => PHP_INT_MAX.'0', 'array' => [1]] as $label => $id) {
            $cases[$label] = [['edit' => $id]];
        }

        return $cases + ['create-text' => [['create' => 'yes']], 'create-array' => [['create' => [1]]],
            'conflicting-intents' => [['create' => 1, 'edit' => 1]], 'search-array' => [['search' => ['x']]],
            'filter-array' => [['filter' => ['trashed']]]];
    }

    public function test_legacy_edit_route_cannot_replace_its_path_identity_with_query_parameters(): void
    {
        $lab = VAPLab::factory()->create();
        $this->operator($lab, ['edit_inventory']);
        $stock = $this->stock($lab);
        $peer = $this->stock(VAPLab::factory()->create());
        $this->get(route('inventory.edit', ['inventory' => $stock->id, 'edit' => $peer->id, 'create' => 1]))
            ->assertRedirect(route('inventory.index', ['edit' => $stock->id]));
        foreach (['abc', '1.5', '0', '-1'] as $id) {
            $this->getJson(route('inventory.edit', $id))->assertNotFound();
        }
    }

    public function test_removed_membership_cannot_open_an_authoring_context(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['add_inventory', 'edit_inventory']);
        $stock = $this->stock($lab);
        DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
        $this->get(route('inventory.index', ['create' => 1]))->assertForbidden();
        $this->get(route('inventory.index', ['edit' => $stock->id]))->assertForbidden();
    }

    /** @param list<string> $permissions */
    private function operator(VAPLab $lab, array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    private function stock(VAPLab $lab): Inventory
    {
        $category = ItemCategory::query()->create(['name' => 'Category '.fake()->uuid(), 'inventory_type' => 'material']);
        $unit = InventoryUnit::query()->create(['code' => fake()->uuid(), 'description' => 'Units']);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Item '.fake()->uuid(), 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Warehouse '.fake()->uuid()]);

        return Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '2.0000', 'min_stock_level' => '0.1250', 'reorder_point' => '0.5000']);
    }
}
