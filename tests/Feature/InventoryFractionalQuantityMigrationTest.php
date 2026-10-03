<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InventoryFractionalQuantityMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fractional_columns_replay_on_an_empty_database(): void
    {
        $migration = require database_path('migrations/2026_09_30_183610_support_fractional_inventory_quantities.php');
        $migration->down();

        try {
            $this->assertSame(2, $this->numericScale('reagent_consumption', 'quantity_used'));
            $this->assertSame(0, $this->numericScale('inventory', 'qty_available'));
        } finally {
            $migration->up();
        }

        $this->assertSame(4, $this->numericScale('inventory', 'qty_available'));
        $this->assertSame(4, $this->numericScale('inventory', 'min_stock_level'));
        $this->assertSame(4, $this->numericScale('i_transfers', 'qty'));
        $this->assertSame(4, $this->numericScale('reagent_consumption', 'quantity_used'));
    }

    public function test_fractional_columns_cannot_be_removed_while_stock_is_retained(): void
    {
        $lab = VAPLab::factory()->create();
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Fractional migration item']);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Fractional migration warehouse']);
        Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '0.0001']);

        $migration = require database_path('migrations/2026_09_30_183610_support_fractional_inventory_quantities.php');
        $this->expectException(RuntimeException::class);
        $migration->down();
    }

    private function numericScale(string $table, string $column): ?int
    {
        $result = DB::selectOne('SELECT numeric_scale FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?', [$table, $column]);

        return $result?->numeric_scale === null ? null : (int) $result->numeric_scale;
    }
}
