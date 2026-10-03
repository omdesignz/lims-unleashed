<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryCatalogueReportsAccessTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('reportGrants')]
    public function test_rows_totals_charts_and_options_share_owned_type_permissions(string $report, string $grant): void
    {
        [$lab, , $items, $warehouses] = $this->fixture($grant);
        $response = $this->get(route(self::routeName($report), ['lab_id' => VAPLab::factory()->create()->id]));
        if ($grant === 'none') {
            $response->assertForbidden();

            return;
        }

        $props = $response->assertOk()->viewData('page')['props'];
        $types = $grant === 'both' ? ['material', 'equipment'] : [$grant];
        $expected = [];
        foreach ($types as $type) {
            // Low stock lists positions, the empty ones included; the other reports list items.
            $expected[] = $report === 'stock' ? $items[$type]['stock']->id : $items[$type]['item']->id;
            $expected[] = $report === 'stock' ? $items[$type]['emptyStock']->id : $items[$type]['emptyItem']->id;
        }
        $rows = $props[self::rowsKey($report)]['data'];
        $actual = array_column($rows, 'id');
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
        $categoryIds = array_column($props['categories'], 'id');
        foreach (['material', 'equipment'] as $type) {
            $this->assertSame(in_array($type, $types, true), in_array($items[$type]['item']->category_id, $categoryIds, true));
        }
        if ($report === 'calibration') {
            $this->assertSame(2 * count($types), $props['stats']['total_scheduled']);
            $this->assertSame(2 * count($types), $props['stats']['due_soon']);
        } elseif ($report === 'expiry') {
            $this->assertSame(2 * count($types), $props['stats']['total_reagents']);
            $this->assertSame(2 * count($types), $props['stats']['expiring_soon']);
            foreach ($rows as $row) {
                $this->assertSame(1, $row['warehouse_count']);
                $this->assertSame($warehouses['owned']->id, $row['inventory'][0]['warehouse_id']);
            }
        } else {
            $this->assertSame(2 * count($types), $props['stats']['total_items']);
            $this->assertSame(2 * count($types), $props['stats']['total_low_stock']);
            $this->assertSame(count($types), $props['stats']['out_of_stock']);
            $this->assertSame(count($types), $props['stats']['critical_stock']);
            $this->assertSame([count($types), count($types), 0], $props['charts']['severity_mix']['series']);
            $this->assertSame([2 * count($types)], $props['charts']['warehouse_exposure']['series']);
            $this->assertCount(2 * count($types), $props['charts']['replenishment_gap']['labels']);
            $this->assertSame('0.0000', $rows[0]['qty_available'], 'Empty positions sort first.');
            foreach ($rows as $row) {
                $this->assertSame(['id', 'item_id', 'warehouse_id', 'qty_available', 'min_stock_level', 'reorder_point', 'item', 'warehouse'], array_keys($row));
                $this->assertSame(['id', 'name', 'code', 'internal_code', 'needs_calibration', 'category'], array_keys($row['item']));
                $this->assertContains($row['qty_available'], ['0.0000', '1.2500']);
                $this->assertSame(['id', 'name', 'location'], array_keys($row['warehouse']));
            }
        }
        foreach ($props['categories'] as $category) {
            $this->assertSame(['id', 'name'], array_keys($category));
        }
        if ($report !== 'calibration') {
            $this->assertNotContains($warehouses['foreign']->id, array_column($props['warehouses'], 'id'));
        }
        $this->assertSame($lab->id, $items['material']['item']->lab_id);
    }

    #[DataProvider('reports')]
    public function test_forged_category_and_warehouse_filters_cannot_broaden_access(string $report): void
    {
        [, , $items, $warehouses] = $this->fixture('material');
        $props = $this->get(route(self::routeName($report), ['category_id' => $items['equipment']['item']->category_id]))
            ->assertOk()->viewData('page')['props'];
        $this->assertSame([], $props[self::rowsKey($report)]['data']);
        if ($report === 'stock') {
            $this->assertSame(0, $props['stats']['total_items']);
            $this->assertSame([0, 0, 0], $props['charts']['severity_mix']['series']);
            $this->assertSame([], $props['charts']['replenishment_gap']['labels']);
        } else {
            $this->assertSame(2, $props['stats'][$report === 'expiry' ? 'total_reagents' : 'total_scheduled']);
        }
        if ($report !== 'calibration') {
            $props = $this->get(route(self::routeName($report), ['warehouse_id' => $warehouses['foreign']->id]))
                ->assertOk()->viewData('page')['props'];
            $this->assertSame([], $props[self::rowsKey($report)]['data']);
        }
    }

    #[DataProvider('reports')]
    public function test_malformed_filters_and_unbounded_pagination_are_rejected(string $report): void
    {
        $this->fixture('both');
        foreach (['category_id' => [1], 'per_page' => 101, 'page' => 0, 'search' => ['bad'], 'sort_by' => 'lab_id', 'sort_direction' => 'invalid'] as $field => $value) {
            $this->getJson(route(self::routeName($report), [$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $props = $this->get(route(self::routeName($report), ['per_page' => 1]))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props[self::rowsKey($report)]['data']);
        $this->assertSame(1, $props[self::rowsKey($report)]['per_page']);
        $this->assertSame(4, $props[self::rowsKey($report)]['total']);
    }

    #[DataProvider('reports')]
    public function test_revoked_permission_membership_and_lab_switch_take_effect(string $report): void
    {
        [$lab, $user, , $warehouses] = $this->fixture('material');
        $otherLab = VAPLab::query()->findOrFail($warehouses['foreign']->lab_id);
        DB::table('lab_user')->insert(['lab_id' => $otherLab->id, 'user_id' => $user->id]);
        $props = $this->withSession(['active_lab_id' => $otherLab->id])->get(route(self::routeName($report), ['lab_id' => $lab->id]))
            ->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props[self::rowsKey($report)]['data']);
        $user->revokePermissionTo('view_iitems');
        $this->get(route(self::routeName($report)))->assertForbidden();
        $user->givePermissionTo('view_iitems');
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->get(route(self::routeName($report)))->assertForbidden();
    }

    public function test_low_stock_requires_stock_view_as_well_as_item_view(): void
    {
        [, $user] = $this->fixture('material');
        $user->revokePermissionTo('view_inventory');
        $this->get(route(self::routeName('stock')))->assertForbidden();
        $this->get(route(self::routeName('calibration')))->assertOk();
        $this->get(route(self::routeName('expiry')))->assertOk();
        $user->givePermissionTo('view_inventory');
        $user->revokePermissionTo('view_iitems');
        $this->get(route(self::routeName('stock')))->assertForbidden();
    }

    public function test_archived_calibration_category_retains_classification_and_readable_context(): void
    {
        [, , $items] = $this->fixture('equipment');
        $items['equipment']['item']->category->delete();
        $props = $this->get(route(self::routeName('calibration')))->assertOk()->viewData('page')['props'];
        $this->assertCount(2, $props['items']['data']);
        $this->assertSame($items['equipment']['item']->category_id, $props['items']['data'][0]['category']['id']);
        $this->assertSame(2, $props['stats']['total_scheduled']);
    }

    public function test_expiry_preserves_name_eligibility_and_active_category_policy(): void
    {
        [, , $items] = $this->fixture('both');
        $items['equipment']['item']->category->delete();
        $items['material']['item']->update(['is_reagent' => false]);
        $props = $this->get(route(self::routeName('expiry')))->assertOk()->viewData('page')['props'];
        $this->assertCount(2, $props['reagents']['data']);
        $this->assertSame(2, $props['stats']['total_reagents']);
        $this->assertContains($items['material']['item']->id, array_column($props['reagents']['data'], 'id'));
    }

    public function test_live_stock_on_archived_item_category_and_warehouse_remains_visible_by_type(): void
    {
        [, , $items, $warehouses] = $this->fixture('equipment');
        $items['equipment']['item']->delete();
        $items['equipment']['item']->category->delete();
        $warehouses['owned']->delete();
        $props = $this->get(route(self::routeName('stock')))->assertOk()->viewData('page')['props'];
        $this->assertCount(2, $props['inventory']['data']);
        $archived = collect($props['inventory']['data'])->firstWhere('item.id', $items['equipment']['item']->id);
        $this->assertNotNull($archived);
        $this->assertSame($warehouses['owned']->name, $archived['warehouse']['name']);
        $this->assertSame(2, $props['stats']['total_items']);
        $this->assertSame([1, 1, 0], $props['charts']['severity_mix']['series']);
        $filtered = $this->get(route(self::routeName('stock'), ['category_id' => $items['equipment']['item']->category_id]))
            ->assertOk()->viewData('page')['props'];
        $this->assertSame($props['inventory']['data'], $filtered['inventory']['data']);
        $this->assertSame($props['stats'], $filtered['stats']);
        $this->assertSame($props['charts'], $filtered['charts']);
    }

    /** @return array<string,array{string}> */
    public static function reports(): array
    {
        return ['calibration' => ['calibration'], 'expiry' => ['expiry'], 'stock' => ['stock']];
    }

    /** @return array<string,array{string,string}> */
    public static function reportGrants(): array
    {
        $cases = [];
        foreach (array_keys(self::reports()) as $report) {
            foreach (['material', 'equipment', 'both', 'none'] as $grant) {
                $cases[$report.'-'.$grant] = [$report, $grant];
            }
        }

        return $cases;
    }

    private static function routeName(string $report): string
    {
        return match ($report) {
            'calibration' => 'vap-inventory.items.calibration.schedule',
            'expiry' => 'vap-inventory.items.reagents.expiry',
            'stock' => 'vap-inventory.reports.low-stock',
        };
    }

    private static function rowsKey(string $report): string
    {
        return match ($report) {
            'calibration' => 'items',
            'expiry' => 'reagents',
            'stock' => 'inventory',
        };
    }

    /** @return array{VAPLab,User,array<string,array{item:InventoryItem,emptyItem:InventoryItem,stock:Inventory}>,array{owned:InventoryItemWarehouse,foreign:InventoryItemWarehouse}} */
    private function fixture(string $grant): array
    {
        $lab = VAPLab::factory()->create();
        $foreignLab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $user->givePermissionTo(Permission::findOrCreate('view_inventory', 'web'));
        foreach (['material' => 'view_iitems', 'equipment' => 'view_iequipments'] as $type => $permission) {
            if ($grant === $type || $grant === 'both') {
                $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }
        $warehouses = [
            'owned' => InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Owned report warehouse']),
            'foreign' => InventoryItemWarehouse::query()->create(['lab_id' => $foreignLab->id, 'name' => 'Foreign report warehouse']),
        ];
        $items = [];
        foreach (['material', 'equipment'] as $type) {
            $category = ItemCategory::query()->create(['name' => 'Reagentes '.$type.' '.fake()->uuid(), 'inventory_type' => $type]);
            $data = ['category_id' => $category->id, 'next_calibration_date' => now()->addDays(10)->toDateString(), 'reagent_expiry_date' => now()->addDays(10)->toDateString(), 'obs' => 'Private report notes', 'standard_cost' => 900];
            $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Report '.$type, ...$data]);
            $emptyItem = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Report zero '.$type, ...$data]);
            $peer = InventoryItem::query()->create(['lab_id' => $foreignLab->id, 'name' => 'Report peer '.$type, ...$data]);
            $stock = Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouses['owned']->id, 'qty_available' => '1.2500', 'min_stock_level' => 2, 'reorder_point' => 4]);
            $emptyStock = Inventory::query()->create(['item_id' => $emptyItem->id, 'warehouse_id' => $warehouses['owned']->id, 'qty_available' => 0, 'min_stock_level' => 2, 'reorder_point' => 4]);
            Inventory::query()->create(['item_id' => $peer->id, 'warehouse_id' => $warehouses['foreign']->id, 'qty_available' => 1, 'min_stock_level' => 2, 'reorder_point' => 4]);
            $items[$type] = compact('item', 'emptyItem', 'stock', 'emptyStock');
        }
        $unclassified = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Unclassified report item', 'next_calibration_date' => now()->addDays(10)->toDateString()]);
        Inventory::query()->create(['item_id' => $unclassified->id, 'warehouse_id' => $warehouses['owned']->id, 'qty_available' => 1, 'min_stock_level' => 2, 'reorder_point' => 4]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $items, $warehouses];
    }
}
