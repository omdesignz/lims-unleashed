<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReagentExpiryReportTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    public function test_reagent_expiry_report_exposes_fefo_filters_stats_and_stock_context(): void
    {
        $user = $this->verifiedAdmin();
        $code = 'REG-FEFO-'.uniqid();
        $category = ItemCategory::query()->create(['name' => 'Reagentes de ensaio']);
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor FEFO '.uniqid(),
        ]);
        $warehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Armazém FEFO '.uniqid(),
            'is_refrigerated' => true,
        ]);
        $reagent = InventoryItem::query()->create([
            'lab_id' => $warehouse->lab_id,
            'name' => 'Reagente rastreado FEFO',
            'code' => $code,
            'internal_code' => 'INT-'.$code,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'lot' => 'LOT-FEFO-01',
            'refrigerated' => true,
            'reagent_open_date' => now()->subDays(10)->toDateString(),
            'reagent_expiry_date' => now()->addDays(20)->toDateString(),
        ]);

        Inventory::query()->create([
            'item_id' => $reagent->id,
            'warehouse_id' => $warehouse->id,
            'qty_available' => 12,
            'min_stock_level' => 2,
            'reorder_point' => 4,
        ]);

        $this->actingAs($user)
            ->get(route('vap-inventory.items.reagents.expiry', [
                'status' => 'expiring_soon',
                'category_id' => $category->id,
                'warehouse_id' => $warehouse->id,
                'search' => $code,
                'sort_by' => 'current_stock',
                'sort_direction' => 'desc',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Reagents/ExpiryReport')
                ->where('filters.status', 'expiring_soon')
                ->where('filters.category_id', (string) $category->id)
                ->where('filters.warehouse_id', (string) $warehouse->id)
                ->where('filters.search', $code)
                ->where('filters.sort_by', 'current_stock')
                ->where('filters.sort_direction', 'desc')
                ->has('categories')
                ->has('warehouses')
                ->has('stats.expired')
                ->has('stats.expiring_soon')
                ->has('stats.expiring_30')
                ->has('stats.expiring_31_60')
                ->has('stats.expiring_61_90')
                ->has('stats.total_reagents')
                ->has('reagents.data', 1)
                ->has('reagents.data.0', fn (Assert $row) => $row
                    ->where('id', $reagent->id)
                    ->where('name', 'Reagente rastreado FEFO')
                    ->where('code', $code)
                    ->where('lot', 'LOT-FEFO-01')
                    ->where('refrigerated', true)
                    ->where('total_stock', fn ($stock): bool => (float) $stock === 12.0)
                    ->where('warehouse_count', 1)
                    ->where('is_expired', false)
                    ->where('days_to_expiry', fn ($days): bool => (int) $days >= 19 && (int) $days <= 20)
                    ->where('category.id', $category->id)
                    ->where('category.name', $category->name)
                    ->where('supplier.id', $supplier->id)
                    ->where('supplier.name', $supplier->name)
                    ->has('inventory', 1)
                    ->where('inventory.0.warehouse.id', $warehouse->id)
                    ->where('inventory.0.warehouse.name', $warehouse->name)
                    ->etc()
                )
            );
    }
}
