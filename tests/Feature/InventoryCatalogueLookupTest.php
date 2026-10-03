<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\ItemCategory;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryCatalogueLookupTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('grants')]
    public function test_canonical_lookup_returns_only_owned_permitted_types_and_allowlisted_fields(string $grant): void
    {
        [$lab, $user, $items] = $this->fixture($grant);
        $items['equipment']->category->delete();
        $response = $this->getJson(route('vap-inventory.items.lookup', ['q' => 'lookup', 'lab_id' => VAPLab::factory()->create()->id]));
        if ($grant === 'none') {
            $response->assertForbidden();

            return;
        }
        $expected = $grant === 'both' ? [$items['equipment']->id, $items['material']->id] : [$items[$grant]->id];
        $response->assertOk()->assertJsonCount(count($expected));
        $this->assertSame($expected, array_column($response->json(), 'id'));
        foreach ($response->json() as $row) {
            $this->assertSame(['id', 'name', 'code', 'internal_code', 'category_id', 'inventory_type', 'unit_id', 'is_reagent'], array_keys($row));
            $this->assertTrue($grant === 'both' || $row['inventory_type'] === $grant);
        }
        $this->assertFalse(Route::has('iequipments.getReagentInventoryItem'));
        $this->assertSame($user->id, auth()->id());
        $this->assertSame($lab->id, $items['material']->lab_id);
    }

    #[DataProvider('typedRoutes')]
    public function test_legacy_lookup_context_cannot_be_broadened_by_the_query_string(string $grant, string $type, string $route): void
    {
        [, , $items] = $this->fixture($grant);
        $response = $this->getJson(route($route, ['q' => 'lookup', 'inventory_type' => $type === 'material' ? 'equipment' : 'material', 'limit' => 999999]));
        if ($grant === $type || $grant === 'both') {
            $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $items[$type]->id)->assertJsonPath('0.inventory_type', $type);
        } else {
            $response->assertForbidden();
        }
    }

    public function test_reagent_lookup_matches_current_consumption_classification_not_category_number(): void
    {
        [$lab, $user, $items] = $this->fixture('both');
        $category = ItemCategory::query()->create(['name' => 'Reagentes '.fake()->uuid(), 'inventory_type' => 'material']);
        $reagent = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Lookup genuine reagent', 'category_id' => $category->id, 'is_reagent' => true]);
        $items['equipment']->update(['is_reagent' => true]);
        $this->assertNotSame(2, $category->id);
        $this->getJson(route('iitems.getReagentInventoryItem', ['q' => 'lookup']))->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.id', $reagent->id)->assertJsonPath('0.is_reagent', true);
        $category->delete();
        $this->getJson(route('iitems.getReagentInventoryItem', ['q' => 'genuine']))->assertOk()->assertExactJson([]);
        $this->getJson(route('vap-inventory.items.lookup', ['q' => 'genuine']))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $reagent->id);
        $user->revokePermissionTo('view_iitems');
        $this->getJson(route('iitems.getReagentInventoryItem', ['q' => 'lookup']))->assertForbidden();
    }

    public function test_canonical_type_filter_can_narrow_but_not_grant_the_other_type(): void
    {
        [, , $items] = $this->fixture('equipment');
        $this->getJson(route('vap-inventory.items.lookup', ['q' => 'lookup', 'inventory_type' => 'equipment']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $items['equipment']->id);
        $this->getJson(route('vap-inventory.items.lookup', ['q' => 'lookup', 'inventory_type' => 'material']))->assertForbidden();
        $this->getJson(route('vap-inventory.items.lookup', ['q' => 'lookup', 'inventory_type' => 'unknown']))->assertUnprocessable()->assertJsonValidationErrors('inventory_type');
    }

    public function test_empty_search_does_not_enumerate_and_invalid_search_is_rejected(): void
    {
        $this->fixture('both');
        foreach (['', '   ', null] as $query) {
            $this->getJson(route('vap-inventory.items.lookup', ['q' => $query]))->assertOk()->assertExactJson([]);
        }
        foreach ([['invalid'], str_repeat('x', 101)] as $query) {
            $this->getJson(route('vap-inventory.items.lookup', ['q' => $query]))->assertUnprocessable()->assertJsonValidationErrors('q');
        }
    }

    public function test_search_is_case_insensitive_grouped_and_treats_wildcards_literally(): void
    {
        [$lab, , $items] = $this->fixture('both');
        $special = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $items['material']->category_id, 'name' => 'Literal %_\\ item', 'code' => 'Code-Finder', 'internal_code' => 'INTERNAL-FINDER']);
        foreach (['%_', '\\', ' code-finder ', 'internal-finder'] as $query) {
            $this->getJson(route('vap-inventory.items.lookup', ['q' => $query]))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $special->id);
        }
        $this->getJson(route('vap-inventory.items.lookup', ['q' => 'PEER-CODE']))->assertOk()->assertExactJson([]);
    }

    public function test_results_are_bounded_and_stably_ordered(): void
    {
        [$lab, , $items] = $this->fixture('material');
        $ids = [];
        for ($index = 0; $index < 30; $index++) {
            $ids[] = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $items['material']->category_id, 'name' => 'Same bounded result'])->id;
        }
        $response = $this->getJson(route('vap-inventory.items.lookup', ['q' => 'bounded', 'limit' => 1000]));
        $response->assertOk()->assertJsonCount(25);
        $this->assertSame(array_slice($ids, 0, 25), array_column($response->json(), 'id'));
        $this->assertSame($response->json(), $this->getJson(route('vap-inventory.items.lookup', ['q' => 'bounded']))->json());
    }

    public function test_membership_removal_and_lab_switch_do_not_leak_the_previous_catalogue(): void
    {
        [$lab, $user, $items] = $this->fixture('both');
        $other = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $other->id, 'user_id' => $user->id]);
        $peerItem = InventoryItem::query()->create(['lab_id' => $other->id, 'category_id' => $items['material']->category_id, 'name' => 'Lookup switched laboratory']);
        $this->withSession(['active_lab_id' => $other->id])->getJson(route('vap-inventory.items.lookup', ['q' => 'lookup', 'lab_id' => $lab->id]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $peerItem->id);
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->getJson(route('vap-inventory.items.lookup', ['q' => 'lookup']))->assertForbidden();
    }

    #[DataProvider('maintenancePermissions')]
    public function test_maintenance_history_requires_both_target_type_view_and_maintenance_view(string $type, bool $itemView, bool $maintenanceView, string $prefix): void
    {
        [$lab, $user, $items] = $this->fixture($itemView ? $type : ($type === 'equipment' ? 'material' : 'equipment'));
        if ($maintenanceView) {
            $user->givePermissionTo(Permission::findOrCreate('view_maintenance_tasks', 'web'));
        }
        $category = MaintenanceCategory::query()->create(['name' => 'Calibration '.fake()->uuid(), 'code' => fake()->uuid()]);
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Maintenance supplier '.fake()->uuid()]);
        $task = MaintenanceTask::query()->create(['name' => 'Private maintenance', 'equipment_id' => $items[$type]->id, 'category_id' => $category->id, 'supplier_id' => $supplier->id, 'maintenance_task_year' => now()->year, 'due_date' => now()->toDateString()]);
        $response = $this->getJson(route($prefix.'.getMaintenanceTasks', $items[$type]->id));
        if ($itemView && $maintenanceView) {
            $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $task->id);
        } else {
            $response->assertForbidden();
        }
        $foreign = InventoryItem::query()->create(['lab_id' => VAPLab::factory()->create()->id, 'category_id' => $items[$type]->category_id, 'name' => 'Peer target']);
        $this->getJson(route($prefix.'.getMaintenanceTasks', $foreign))->assertNotFound();
        $unknown = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Unclassified target']);
        $this->getJson(route($prefix.'.getMaintenanceTasks', $unknown))->assertNotFound();
    }

    /** @return array<string,array{string}> */
    public static function grants(): array
    {
        return ['material' => ['material'], 'equipment' => ['equipment'], 'both' => ['both'], 'none' => ['none']];
    }

    /** @return array<string,array{string,string,string}> */
    public static function typedRoutes(): array
    {
        $cases = [];
        foreach (array_keys(self::grants()) as $grant) {
            foreach (['material' => 'iitems.getInventoryItem', 'equipment' => 'iequipments.getInventoryItem'] as $type => $route) {
                $cases[$grant.'-'.$type] = [$grant, $type, $route];
            }
        }

        return $cases;
    }

    /** @return array<string,array{string,bool,bool,string}> */
    public static function maintenancePermissions(): array
    {
        $cases = [];
        foreach (['material', 'equipment'] as $type) {
            foreach ([false, true] as $itemView) {
                foreach ([false, true] as $maintenanceView) {
                    foreach (['iitems', 'iequipments'] as $prefix) {
                        $cases[$type.'-'.(int) $itemView.'-'.(int) $maintenanceView.'-'.$prefix] = [$type, $itemView, $maintenanceView, $prefix];
                    }
                }
            }
        }

        return $cases;
    }

    /** @return array{VAPLab,User,array{material:InventoryItem,equipment:InventoryItem}} */
    private function fixture(string $grant): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach (['material' => 'view_iitems', 'equipment' => 'view_iequipments'] as $type => $permission) {
            if ($grant === $type || $grant === 'both') {
                $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }
        $items = [];
        foreach (['material', 'equipment'] as $type) {
            $category = ItemCategory::query()->create(['name' => 'Neutral category '.fake()->uuid(), 'inventory_type' => $type]);
            $items[$type] = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Lookup '.$type, 'category_id' => $category->id, 'obs' => 'Private notes', 'standard_cost' => 123]);
            InventoryItem::query()->create(['lab_id' => VAPLab::factory()->create()->id, 'name' => 'Lookup peer '.$type, 'category_id' => $category->id, 'code' => 'PEER-CODE']);
        }
        $archived = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Lookup archived', 'category_id' => $items['material']->category_id]);
        $archived->delete();
        InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Lookup unclassified']);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $items];
    }
}
