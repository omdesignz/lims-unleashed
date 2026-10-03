<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\VAPLab;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InventoryOwnershipMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_database_rejects_unowned_and_cross_lab_inventory_positions_and_transfers(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Constraint material']);
        $source = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Source']);
        $destination = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Destination']);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Peer']);
        Inventory::query()->create(['item_id' => $item->id, 'warehouse_id' => $source->id, 'qty_available' => 1]);

        $this->assertConstraintFails(fn () => DB::table('i_warehouses')->insert(['name' => 'Unowned']));
        $this->assertConstraintFails(fn () => DB::table('inventory')->insert([
            'item_id' => $item->id, 'warehouse_id' => $source->id, 'qty_available' => 2,
        ]));
        $this->assertConstraintFails(fn () => DB::table('inventory')->insert([
            'item_id' => $item->id, 'warehouse_id' => $destination->id, 'qty_available' => -1,
        ]));
        $this->assertConstraintFails(fn () => DB::table('i_transfers')->insert([
            'lab_id' => $lab->id, 'item_id' => $item->id,
            'source_id' => $source->id, 'destination_id' => $peerWarehouse->id, 'qty' => 1,
        ]));
        $this->assertConstraintFails(fn () => DB::table('i_transfers')->insert([
            'lab_id' => $lab->id, 'item_id' => $item->id,
            'source_id' => $source->id, 'destination_id' => $source->id, 'qty' => 1,
        ]));

        $this->assertDatabaseCount('i_transfers', 0);
        $this->assertSame('1.0000', Inventory::query()->where('warehouse_id', $source->id)->sole()->qty_available);

        $migration = require database_path('migrations/2026_09_30_124053_enforce_laboratory_ownership_of_inventory_warehouses_and_transfers.php');
        try {
            $migration->down();
            $this->fail('Ownership must not be removed while warehouse and stock records remain.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('retained', $exception->getMessage());
        }
    }

    private function assertConstraintFails(callable $operation): void
    {
        try {
            DB::transaction($operation);
            $this->fail('Expected PostgreSQL to reject the invalid inventory record.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getCode());
        }
    }
}
