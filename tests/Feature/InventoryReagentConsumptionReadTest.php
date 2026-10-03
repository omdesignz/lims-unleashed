<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryReagentConsumptionReadTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('grants')]
    public function test_register_rows_summaries_and_options_follow_catalogue_kinds(string $grant): void
    {
        [, , $data] = $this->fixture($grant);
        $response = $this->get(route('vap-inventory.reagents.consumption.index'));
        if ($grant === 'none') {
            $response->assertForbidden();

            return;
        }
        $types = $grant === 'both' ? ['material', 'equipment'] : [$grant];
        $props = $response->assertOk()->viewData('page')['props'];
        $this->assertCount(count($types), $props['consumptions']['data']);
        $this->assertSame(0.125 * count($types), (float) $props['stats']['total_consumption']);
        $this->assertSame(count($types), (int) $props['stats']['total_uses']);
        $this->assertSame(0.125 * count($types), (float) $props['stats']['avg_daily_consumption']);
        foreach (['summaryByItem', 'summaryByUser', 'items', 'users'] as $key) {
            $this->assertCount(count($types), $props[$key]);
        }
        foreach ($props['consumptions']['data'] as $row) {
            $this->assertContains($row['item']['id'], array_map(fn (string $type): int => $data[$type]['item']->id, $types));
            $this->assertSame(['id', 'name'], array_keys($row['user']));
            $this->assertArrayNotHasKey('obs', $row['item']);
            $this->assertArrayNotHasKey('standard_cost', $row['item']);
            $this->assertArrayNotHasKey('lab_id', $row);
        }
        foreach ($props['users'] as $user) {
            $this->assertSame(['id', 'name'], array_keys($user));
        }
    }

    #[DataProvider('grants')]
    public function test_detail_cannot_bypass_kind_or_laboratory_access(string $grant): void
    {
        [, , $data] = $this->fixture($grant);
        foreach (['material', 'equipment'] as $kind) {
            $response = $this->get(route('vap-inventory.reagents.consumption.show', $data[$kind]['consumption']));
            if ($grant === 'none') {
                $response->assertForbidden();
            } elseif ($grant !== 'both' && $grant !== $kind) {
                $response->assertNotFound();
            } else {
                $row = $response->assertOk()->viewData('page')['props']['consumption'];
                $this->assertSame($data[$kind]['item']->id, $row['item']['id']);
                $this->assertFalse($row['item']['is_archived']);
                $this->assertSame($data[$kind]['unit']->code, $row['item']['unit']['code']);
                $this->assertSame('1.2500', $row['item']['inventory'][0]['qty_available']);
                $this->assertSame(['warehouse_id', 'qty_available'], array_keys($row['item']['inventory'][0]));
                $this->assertSame(['id', 'name'], array_keys($row['user']));
                $this->assertArrayNotHasKey('obs', $row['item']);
                $this->assertArrayNotHasKey('standard_cost', $row['item']);
            }
            if ($grant !== 'none') {
                $this->get(route('vap-inventory.reagents.consumption.show', $data[$kind]['peer']))->assertNotFound();
            }
        }
    }

    public function test_read_operation_is_independent_of_consumption_write_permissions(): void
    {
        [, $user, $data] = $this->fixture('both');
        $user->revokePermissionTo('view_inventory');
        $this->get(route('vap-inventory.reagents.consumption.index'))->assertForbidden();
        $this->get(route('vap-inventory.reagents.consumption.show', $data['material']['consumption']))->assertForbidden();
    }

    public function test_all_filters_apply_to_net_statistics_as_well_as_rows(): void
    {
        [, , $data] = $this->fixture('material');
        foreach (['item_id' => $data['equipment']['item']->id, 'warehouse_id' => $data['equipment']['warehouse']->id, 'user_id' => $data['equipment']['author']->id, 'search' => 'not-present'] as $key => $value) {
            $props = $this->get(route('vap-inventory.reagents.consumption.index', [$key => $value]))->assertOk()->viewData('page')['props'];
            foreach (['consumptions.data', 'summaryByItem', 'summaryByDate', 'summaryByUser', 'users'] as $path) {
                $this->assertSame([], data_get($props, $path));
            }
            $this->assertSame(0.0, (float) $props['stats']['total_consumption']);
            $this->assertSame(0.0, (float) $props['stats']['avg_daily_consumption']);
        }
    }

    public function test_register_filters_are_validated_and_pagination_is_bounded(): void
    {
        $this->fixture('both');
        foreach (['item_id' => [1], 'search' => ['bad'], 'sort_by' => 'password', 'sort_direction' => 'bad', 'per_page' => 101, 'page' => 0, 'date_from' => 'bad'] as $key => $value) {
            $this->getJson(route('vap-inventory.reagents.consumption.index', [$key => $value]))->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $this->getJson(route('vap-inventory.reagents.consumption.index', ['date_from' => '2020-01-02', 'date_to' => '2020-01-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('date_to');
        $props = $this->get(route('vap-inventory.reagents.consumption.index', ['sort_by' => 'quantity_used', 'per_page' => 1]))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props['consumptions']['data']);
        $this->assertSame(2, $props['consumptions']['total']);
    }

    public function test_selected_daily_average_and_peak_day_use_the_filtered_consumption_totals(): void
    {
        [$lab, , $data] = $this->fixture('material');
        $record = $data['material'];
        ReagentConsumption::query()->create([
            'lab_id' => $lab->id, 'reagent_id' => $record['item']->id, 'reagent_name' => $record['item']->name,
            'warehouse_id' => $record['warehouse']->id, 'user_id' => $record['author']->id,
            'used_by' => $record['author']->name, 'quantity_used' => '1.5000', 'date' => today()->subDay(), 'used_at' => now()->subDay(),
        ]);
        $props = $this->get(route('vap-inventory.reagents.consumption.index', [
            'date_from' => today()->subDays(2)->toDateString(), 'date_to' => today()->toDateString(),
        ]))->assertOk()->viewData('page')['props'];
        $this->assertSame(1.625, (float) $props['stats']['total_consumption']);
        $this->assertSame(0.5417, (float) $props['stats']['avg_daily_consumption']);
        $this->assertSame(2, (int) $props['stats']['total_uses']);
        $this->assertSame(1.5, (float) $props['stats']['peak_consumption_day']['total_consumption']);
        $this->assertSame(today()->subDay()->toJSON(), $props['stats']['peak_consumption_day']['date']);
    }

    public function test_retained_reversed_consumption_is_readable_without_inflating_net_totals(): void
    {
        [$lab, , $data] = $this->fixture('material');
        $record = $data['material'];
        $movement = InventoryTransaction::query()->create([
            'lab_id' => $lab->id, 'inventory_id' => $record['stock']->id, 'item_id' => $record['item']->id,
            'warehouse_id' => $record['warehouse']->id, 'user_id' => $record['author']->id,
            'type_id' => InventoryTransactionType::query()->firstOrCreate(['code' => 'consumption_reversal'], ['name' => 'Reversal'])->id, 'qty' => '0.1250',
        ]);
        ReagentConsumptionReversal::factory()->forConsumption($record['consumption'], $movement)->create(['user_id' => $record['author']->id]);
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Retained supplier', 'address' => 'Private supplier address']);
        $record['item']->update(['supplier_id' => $supplier->id]);
        $supplier->delete();
        $record['unit']->delete();
        $record['item']->delete();
        $record['item']->category->delete();
        $record['warehouse']->delete();
        $record['author']->delete();
        $props = $this->get(route('vap-inventory.reagents.consumption.index'))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props['consumptions']['data']);
        $row = $props['consumptions']['data'][0];
        $this->assertSame($record['consumption']->id, $row['reversal']['consumption_id']);
        $this->assertSame(['id', 'name'], array_keys($row['reversal']['user']));
        $this->assertSame($record['author']->name, $props['users'][0]['name']);
        $this->assertSame(0.0, (float) $props['stats']['total_consumption']);
        $this->assertSame(0, (int) $props['stats']['total_uses']);
        $row = $this->get(route('vap-inventory.reagents.consumption.show', $record['consumption']))->assertOk()->viewData('page')['props']['consumption'];
        $this->assertSame($record['item']->name, $row['item']['name']);
        $this->assertTrue($row['item']['is_archived']);
        $this->assertSame('1.2500', $row['item']['inventory'][0]['qty_available']);
        $this->assertSame($record['author']->name, $row['reversal']['user']['name']);
        $this->assertSame($record['unit']->code, $row['item']['unit']['code']);
        $this->assertSame(['id' => $supplier->id, 'name' => $supplier->name], $row['item']['supplier']);
    }

    public function test_create_choices_are_minimal_owned_and_default_to_the_signed_in_operator(): void
    {
        [, $user, $data] = $this->fixture('both');
        $props = $this->get(route('vap-inventory.reagents.consumption.create'))->assertOk()->viewData('page')['props'];
        $this->assertSame([['id' => $user->id, 'name' => $user->name]], $props['users']);
        $this->assertSame(route('vap-inventory.reagents.consumption.index'), $props['backUrl']);
        $this->assertCount(2, $props['reagents']);
        $this->assertCount(2, $props['warehouses']);
        foreach ($props['reagents'] as $row) {
            foreach (['obs', 'standard_cost', 'lab_id'] as $key) {
                $this->assertArrayNotHasKey($key, $row);
            }
            $this->assertSame('1.2500', $row['inventory'][0]['qty_available']);
            $this->assertSame(['warehouse_id', 'qty_available'], array_keys($row['inventory'][0]));
            $this->assertNotNull($row['unit']['code']);
        }
        $data['material']['item']->delete();
        $props = $this->get(route('vap-inventory.reagents.consumption.create'))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props['reagents']);
    }

    public function test_current_permissions_membership_and_lab_switch_are_rechecked(): void
    {
        [$lab, $user, $data] = $this->fixture('material');
        $peerLab = $data['material']['peer']->lab_id;
        DB::table('lab_user')->insert(['lab_id' => $peerLab, 'user_id' => $user->id]);
        $props = $this->withSession(['active_lab_id' => $peerLab])->get(route('vap-inventory.reagents.consumption.index', ['lab_id' => $lab->id]))->assertOk()->viewData('page')['props'];
        $this->assertCount(1, $props['consumptions']['data']);
        $this->assertSame($data['material']['peer']->id, $props['consumptions']['data'][0]['id']);
        $user->revokePermissionTo('view_iitems');
        $this->get(route('vap-inventory.reagents.consumption.index'))->assertForbidden();
        $user->givePermissionTo('view_iitems');
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->get(route('vap-inventory.reagents.consumption.index'))->assertForbidden();
        $this->get(route('vap-inventory.reagents.consumption.show', $data['material']['peer']))->assertForbidden();
    }

    #[DataProvider('grants')]
    public function test_workbench_stock_alerts_do_not_reveal_unreadable_catalogue_kinds(string $grant): void
    {
        [, $user] = $this->fixture($grant);
        $props = $this->get(route('dashboard'))->assertOk()->viewData('page')['props'];
        $this->assertSame(match ($grant) {
            'both' => 2, 'none' => 0, default => 1
        }, $props['stockAlerts']);
        $user->revokePermissionTo('view_inventory');
        $props = $this->get(route('dashboard'))->assertOk()->viewData('page')['props'];
        $this->assertSame(0, $props['stockAlerts']);
    }

    public function test_write_only_operators_land_on_an_authorized_page_without_receiving_read_grants(): void
    {
        [$lab, $user, $data] = $this->fixture('none');
        $user->revokePermissionTo('view_inventory');
        $props = $this->get(route('vap-inventory.reagents.consumption.create'))->assertOk()->viewData('page')['props'];
        $this->assertSame(route('dashboard'), $props['backUrl']);
        $this->get($props['backUrl'])->assertOk();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);
        $this->post(route('vap-inventory.reagents.consumption.store'), [
            'reagent_id' => $data['material']['item']->id, 'warehouse_id' => $data['material']['warehouse']->id,
            'quantity_used' => '0.1250', 'used_by' => 'Actual technician', 'date' => today()->toDateString(),
        ])->assertRedirect(route('vap-inventory.reagents.consumption.create'));
        $consumption = ReagentConsumption::query()->where('lab_id', $lab->id)->where('user_id', $user->id)->sole();
        $user->revokePermissionTo('add_reagent_consumption');
        $this->post(route('vap-inventory.reagents.consumption.reverse', $consumption))->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('vap-inventory.reagents.consumption.index'))->assertForbidden();
        $this->assertFalse($user->can('view_inventory'));
        $this->assertFalse($user->can('view_iitems'));
        $this->assertFalse($user->can('view_iequipments'));
        $this->assertSame('1.2500', $data['material']['stock']->fresh()->qty_available);
    }

    /** @return array<string,array{string}> */
    public static function grants(): array
    {
        return ['material' => ['material'], 'equipment' => ['equipment'], 'both' => ['both'], 'none' => ['none']];
    }

    /** @return array{VAPLab,User,array<string,array{item:InventoryItem,warehouse:InventoryItemWarehouse,stock:Inventory,consumption:ReagentConsumption,peer:ReagentConsumption,unit:InventoryUnit,author:User}>} */
    private function fixture(string $grant): array
    {
        $lab = VAPLab::factory()->create();
        $peerLab = VAPLab::factory()->create();
        $user = User::factory()->create(['name' => 'Signed-in operator', 'is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $permissions = ['view_inventory', 'add_reagent_consumption', 'delete_reagent_consumption'];
        foreach ($grant === 'both' ? ['material', 'equipment'] : ($grant === 'none' ? [] : [$grant]) as $kind) {
            $permissions[] = $kind === 'material' ? 'view_iitems' : 'view_iequipments';
        }
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $data = [];
        foreach (['material', 'equipment'] as $kind) {
            $author = User::factory()->create(['name' => 'Other author '.$kind]);
            $category = ItemCategory::query()->create(['name' => 'Reagentes read '.$kind.' '.fake()->uuid(), 'inventory_type' => $kind]);
            $unit = InventoryUnit::query()->create(['code' => 'read-'.fake()->uuid(), 'description' => 'Read unit']);
            $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Local reagent '.$kind, 'code' => 'READ-'.$kind, 'category_id' => $category->id, 'unit_id' => $unit->id, 'standard_cost' => 10, 'obs' => 'Private catalogue notes']);
            $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Local reagent warehouse '.$kind]);
            $stock = Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '1.2500', 'reorder_point' => 2]);
            $consumption = ReagentConsumption::query()->create(['reagent_id' => $item->id, 'reagent_name' => $item->name, 'warehouse_id' => $warehouse->id, 'user_id' => $author->id, 'used_by' => $author->name, 'quantity_used' => '0.1250', 'date' => today(), 'used_at' => now()]);
            $peerItem = InventoryItem::query()->create(['lab_id' => $peerLab->id, 'name' => 'Peer reagent '.$kind, 'category_id' => $category->id, 'unit_id' => $unit->id]);
            $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peerLab->id, 'name' => 'Peer reagent warehouse '.$kind]);
            Inventory::query()->create(['item_id' => $peerItem->id, 'warehouse_id' => $peerWarehouse->id, 'qty_available' => 9, 'reorder_point' => 10]);
            $peer = ReagentConsumption::query()->create(['reagent_id' => $peerItem->id, 'reagent_name' => $peerItem->name, 'warehouse_id' => $peerWarehouse->id, 'user_id' => $author->id, 'used_by' => $author->name, 'quantity_used' => 9, 'date' => today(), 'used_at' => now()]);
            $data[$kind] = compact('item', 'warehouse', 'stock', 'consumption', 'peer', 'unit', 'author');
        }
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        return [$lab, $user, $data];
    }
}
