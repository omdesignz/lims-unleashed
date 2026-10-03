<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransactionType;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryBatchLaboratoryBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    private function batch(VAPLab $lab, string $name): InventoryBatch
    {
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => $name]);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => $name.' warehouse']);
        $stock = Inventory::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_available' => 6,
        ]);

        return InventoryBatch::query()->create([
            'inventory_id' => $stock->id,
            'batch_number' => 'LOT-'.fake()->unique()->numerify('####'),
            'qty_received' => 6,
            'qty_remaining' => 6,
        ]);
    }

    public function test_lookup_and_labels_are_private_to_the_active_laboratory(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $localBatch = $this->batch($lab, 'Local material');
        $peerBatch = $this->batch($peer, 'Peer material');
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        $this->actingAs($user)->getJson(route('inventory.batches.lookup', $localBatch))
            ->assertOk()->assertJsonPath('item_name', 'Local material');
        $this->getJson(route('inventory.batches.lookup', $peerBatch))->assertNotFound();
        $this->get(route('printBatchLabels', ['ids' => (string) $peerBatch->id]))->assertNotFound();
        $this->get(route('printBatchLabels', ['ids' => $localBatch->id.','.$peerBatch->id]))->assertNotFound();
        $this->get(route('printBatchLabels', ['ids' => (string) $localBatch->id]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_uncontrolled_mobile_consumption_and_audit_cannot_change_stock(): void
    {
        $lab = VAPLab::factory()->create();
        $batch = $this->batch($lab, 'Controlled batch');
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        $this->actingAs($user)->postJson(route('inventory.batches.mobile-action'), [
            'batch_id' => $batch->id, 'qty' => 2, 'type' => 'consumption',
        ])->assertStatus(410);
        $this->postJson(route('inventory.batches.audit'), [
            'batch_id' => $batch->id, 'physical_qty' => 2, 'system_qty' => 6,
        ])->assertStatus(410);

        $this->assertSame(6.0, (float) $batch->fresh()->qty_remaining);
        $this->assertSame('6.0000', $batch->inventory->fresh()->qty_available);
        $this->assertDatabaseCount('itransactions', 0);
        $this->assertDatabaseCount('reagent_consumption', 0);
    }

    public function test_database_rejects_cross_laboratory_batch_ledger_and_consumption_links(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $batch = $this->batch($lab, 'Local reagent');
        $peerBatch = $this->batch($peer, 'Peer reagent');
        $stock = $batch->inventory;
        $type = InventoryTransactionType::query()->create(['name' => 'Stock in', 'code' => 'stock_in']);
        $user = User::factory()->create();

        $this->assertSame($lab->id, $batch->lab_id);
        $this->assertConstraintFails(fn () => DB::table('i_inventory_batches')->insert([
            'lab_id' => $lab->id, 'inventory_id' => $peerBatch->inventory_id,
            'batch_number' => 'INVALID', 'qty_received' => 1, 'qty_remaining' => 1,
        ]));
        $this->assertConstraintFails(fn () => DB::table('itransactions')->insert([
            'lab_id' => $lab->id, 'inventory_id' => $stock->id,
            'item_id' => $stock->item_id, 'warehouse_id' => $stock->warehouse_id,
            'batch_id' => $peerBatch->id, 'type_id' => $type->id,
            'user_id' => $user->id, 'qty' => '1',
        ]));
        $this->assertConstraintFails(fn () => DB::table('reagent_consumption')->insert([
            'lab_id' => $lab->id, 'reagent_id' => $peerBatch->inventory->item_id,
            'warehouse_id' => $stock->warehouse_id,
            'date' => now()->toDateString(), 'reagent_name' => 'Peer reagent',
            'quantity_used' => 1, 'used_at' => now(),
        ]));

        $migration = require database_path('migrations/2026_09_30_182255_enforce_laboratory_ownership_of_inventory_ledger.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('while records are retained');
        $migration->down();
    }

    private function assertConstraintFails(callable $operation): void
    {
        try {
            DB::transaction($operation);
            $this->fail('Expected PostgreSQL to reject a cross-laboratory inventory link.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getCode());
        }
    }
}
