<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StockMovementReportTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $admin;
    }

    public function test_stock_movement_report_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('vap-inventory.reports.stock-movement', ['view' => 'summary']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Reports/StockMovement')
                ->has('charts.direction_breakdown.labels')
                ->has('charts.direction_breakdown.series')
                ->has('charts.type_mix.labels')
                ->has('charts.type_mix.series')
                ->has('charts.daily_activity.labels')
                ->has('charts.daily_activity.series')
            );
    }

    public function test_stock_movement_report_preserves_fractional_quantities_on_postgresql(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Stock movement fixture',
            'category_id' => ItemCategory::query()->create(['name' => 'Stock movement material', 'inventory_type' => 'material'])->id,
            'code' => fake()->unique()->bothify('SM-######'),
        ]);
        $warehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Stock movement warehouse',
        ]);
        $inventory = Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id]);

        foreach (['stock_in' => '2.50', 'stock_out' => '1.25'] as $typeCode => $quantity) {
            $type = InventoryTransactionType::query()->create(['name' => $typeCode, 'code' => $typeCode]);
            InventoryTransaction::query()->create([
                'inventory_id' => $inventory->id,
                'user_id' => $user->id,
                'warehouse_id' => $warehouse->id,
                'item_id' => $item->id,
                'type_id' => $type->id,
                'qty' => $quantity,
            ]);
        }

        $this->actingAs($user)
            ->get(route('vap-inventory.reports.stock-movement', ['view' => 'summary']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Reports/StockMovement')
                ->where('stats.total_in', 2.5)
                ->where('stats.total_out', 1.25)
                ->where('stats.net_movement', 1.25)
                ->where('charts.direction_breakdown.series.0.data', [2.5, 1.25, 1.25])
                ->where('charts.daily_activity.series.0.data.0', 2.5)
                ->where('charts.daily_activity.series.1.data.0', 1.25)
            );
    }
}
