<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InventoryLedgerQuantityMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ledger_quantity_replays_as_numeric_with_four_decimal_places(): void
    {
        $migration = require database_path('migrations/2026_09_30_194335_enforce_fractional_inventory_ledger_quantities.php');
        $migration->down();

        try {
            $this->assertSame('character varying', $this->columnType());
        } finally {
            $migration->up();
        }

        $this->assertSame('numeric', $this->columnType());
        $this->assertSame(4, $this->numericScale());
    }

    public function test_ledger_quantity_cannot_be_downgraded_while_transactions_are_retained(): void
    {
        $lab = VAPLab::factory()->create();
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Ledger quantity item']);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Ledger quantity warehouse']);
        $stock = Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '0.0001']);
        $type = InventoryTransactionType::query()->firstOrCreate(['code' => 'stock_in'], ['name' => 'Stock in']);
        $user = User::factory()->create();
        InventoryTransaction::query()->create([
            'inventory_id' => $stock->id,
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'type_id' => $type->id,
            'user_id' => $user->id,
            'qty' => '0.0001',
        ]);

        $migration = require database_path('migrations/2026_09_30_194335_enforce_fractional_inventory_ledger_quantities.php');
        $this->expectException(RuntimeException::class);
        $migration->down();
    }

    private function columnType(): string
    {
        return DB::selectOne('SELECT data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?', ['itransactions', 'qty'])->data_type;
    }

    private function numericScale(): int
    {
        return (int) DB::selectOne('SELECT numeric_scale FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?', ['itransactions', 'qty'])->numeric_scale;
    }
}
