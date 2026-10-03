<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ItemCategory;
use App\Models\ReagentConsumption;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryAnalyticsLaboratoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_analytics_and_summary_exclude_peer_stock_and_consumption(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        $category = ItemCategory::query()->create(['name' => 'Analytics category '.fake()->uuid()]);
        $localStock = $this->stockWithConsumption($lab, 4, 2, $category->id);
        $peerStock = $this->stockWithConsumption($peer, 19, 11, $category->id);
        $type = InventoryTransactionType::query()->firstOrCreate(['code' => 'stock_in'], ['name' => 'Stock in']);

        foreach ([$localStock, $peerStock] as $stock) {
            InventoryTransaction::query()->create([
                'inventory_id' => $stock->id,
                'user_id' => $user->id,
                'warehouse_id' => $stock->warehouse_id,
                'item_id' => $stock->item_id,
                'type_id' => $type->id,
                'qty' => $stock->qty_available,
            ]);
        }

        $response = $this->actingAs($user)->get(route('vap-inventory.analytics.data'));
        $response->assertOk();
        $this->assertSame(1, $response->json('metrics.total_items'));
        $this->assertSame(4, $response->json('stockDistribution.0.quantity'));
        $this->assertSame(2, (int) $response->json('metrics.totalConsumption'));
        $this->assertCount(1, $response->json('consumptionHistory'));

        $this->get(route('vap-inventory.analytics.index'))
            ->assertOk()
            ->assertViewHas('page', fn (array $page): bool => count(data_get($page, 'props.warehouses', [])) === 1);

        $this->get(route('vap-inventory.analytics.summary'))
            ->assertOk()
            ->assertJsonPath('critical', 0)
            ->assertJsonPath('toOrder', 1);

        $movement = $this->get(route('vap-inventory.reports.stock-movement'));
        $movement->assertOk();
        $this->assertSame(1, data_get($movement->viewData('page'), 'props.transactions.total'));
        $this->assertSame(1, data_get($movement->viewData('page'), 'props.stats.total_transactions'));
        $searchedMovement = $this->get(route('vap-inventory.reports.stock-movement', ['search' => $user->name]));
        $searchedMovement->assertOk();
        $this->assertSame(1, data_get($searchedMovement->viewData('page'), 'props.transactions.total'));

        $consumption = $this->get(route('vap-inventory.reports.consumption'));
        $consumption->assertOk();
        $this->assertSame(1, data_get($consumption->viewData('page'), 'props.consumptions.total'));
        $this->assertSame(2, (int) data_get($consumption->viewData('page'), 'props.stats.total_consumption'));
        $peerName = InventoryItem::query()->findOrFail($peerStock->item_id)->name;
        $searchedConsumption = $this->get(route('vap-inventory.reports.consumption', ['search' => $peerName]));
        $searchedConsumption->assertOk();
        $this->assertSame(0, data_get($searchedConsumption->viewData('page'), 'props.consumptions.total'));

        $value = $this->get(route('vap-inventory.reports.inventory-value'));
        $value->assertOk();
        $this->assertSame(1, data_get($value->viewData('page'), 'props.inventory.total'));
        $this->assertSame(1, data_get($value->viewData('page'), 'props.stats.unique_items'));
        $this->assertSame(12.0, (float) data_get($value->viewData('page'), 'props.stats.total_value'));

        $lowStock = $this->get(route('vap-inventory.reports.low-stock'));
        $lowStock->assertOk();
        $this->assertSame(1, data_get($lowStock->viewData('page'), 'props.inventory.total'));
        $filteredLowStock = $this->get(route('vap-inventory.reports.low-stock', ['category_id' => $category->id]));
        $filteredLowStock->assertOk();
        $this->assertSame(1, data_get($filteredLowStock->viewData('page'), 'props.inventory.total'));

        $this->get(route('vap-inventory.reports.dashboard-stats'))
            ->assertOk()
            ->assertJsonPath('stats.total_items', 1)
            ->assertJsonPath('stats.total_stock_value', 12)
            ->assertJsonPath('stats.today_consumption', 2)
            ->assertJsonCount(1, 'recent_activity');

        foreach (['stock_movement', 'consumption', 'inventory_value', 'low_stock'] as $reportType) {
            $export = $this->post(route('vap-inventory.reports.export'), [
                'report_type' => $reportType,
                'format' => 'csv',
            ]);
            $export->assertOk();
            $this->assertStringNotContainsString($peerName, (string) $export->baseResponse->getContent());
        }
    }

    private function stockWithConsumption(VAPLab $lab, int $quantity, int $consumption, int $categoryId): Inventory
    {
        $item = InventoryItem::query()->create([
            'lab_id' => $lab->id,
            'name' => 'Analytics item '.fake()->uuid(),
            'is_reagent' => true,
            'category_id' => $categoryId,
            'standard_cost' => 3,
        ]);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Analytics warehouse '.fake()->uuid()]);
        $stock = Inventory::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_available' => $quantity,
            'min_stock_level' => 0,
            'reorder_point' => $quantity + 1,
            'status' => 'AVAILABLE',
        ]);
        ReagentConsumption::query()->create([
            'reagent_id' => $item->id,
            'reagent_name' => $item->name,
            'warehouse_id' => $warehouse->id,
            'quantity_used' => $consumption,
            'date' => now()->toDateString(),
            'used_at' => now(),
        ]);

        return $stock;
    }
}
