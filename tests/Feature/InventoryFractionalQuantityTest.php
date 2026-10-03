<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\ReagentConsumption;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryFractionalQuantityTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{item: InventoryItem, source: InventoryItemWarehouse, destination: InventoryItemWarehouse, stock: Inventory} */
    private function reagentStock(VAPLab $lab): array
    {
        $category = ItemCategory::query()->create(['name' => 'Reagentes '.fake()->uuid()]);
        $unit = InventoryUnit::query()->create(['code' => 'mL-'.fake()->numerify('######'), 'description' => 'Millilitres']);
        $item = InventoryItem::query()->create([
            'lab_id' => $lab->id,
            'name' => 'Fractional reagent '.fake()->uuid(),
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_reagent' => true,
        ]);
        $source = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Source '.fake()->uuid()]);
        $destination = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Destination '.fake()->uuid()]);
        $stock = Inventory::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $source->id,
            'qty_available' => '4.0000',
            'min_stock_level' => '0.1250',
            'reorder_point' => '0.5000',
            'status' => 'AVAILABLE',
        ]);

        return compact('item', 'source', 'destination', 'stock');
    }

    private function operator(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    public function test_four_decimal_adjustment_and_transfer_remain_exact(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        ['item' => $item, 'source' => $source, 'destination' => $destination, 'stock' => $stock] = $this->reagentStock($lab);

        $this->actingAs($user)->postJson(route('vap-inventory.items.adjust-stock', $item), [
            'warehouse_id' => $source->id,
            'adjustment_type' => 'add',
            'quantity' => '0.0001',
            'reason' => 'physical_count',
        ])->assertOk()->assertJsonPath('old_quantity', '4.0000')->assertJsonPath('new_quantity', '4.0001');
        $this->assertSame('4.0001', $stock->fresh()->qty_available);

        $this->postJson(route('vap-inventory.items.adjust-stock', $item), [
            'warehouse_id' => $source->id,
            'adjustment_type' => 'remove',
            'quantity' => '0.00001',
            'reason' => 'physical_count',
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertSame('4.0001', $stock->fresh()->qty_available);

        $this->post(route('vap-inventory.transfers.store'), [
            'item_id' => $item->id,
            'source_id' => $source->id,
            'destination_id' => $destination->id,
            'qty' => '1.2345',
            'sent_date' => today()->toDateString(),
        ])->assertRedirect();
        $transfer = InventoryItemTransfer::query()->sole();
        $this->assertSame('1.2345', $transfer->qty);
        $this->assertSame('2.7656', $stock->fresh()->qty_available);

        $this->post(route('vap-inventory.transfers.receive', $transfer), [
            'actual_qty' => '1.0001',
            'received_date' => today()->toDateString(),
        ])->assertRedirect();
        $this->assertSame('3.0000', $stock->fresh()->qty_available);
        $this->assertSame('1.0001', Inventory::query()->where('item_id', $item->id)->where('warehouse_id', $destination->id)->sole()->qty_available);
    }

    public function test_reagent_consumption_updates_the_exact_batch_and_can_be_reversed(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        ['item' => $item, 'source' => $source, 'destination' => $destination, 'stock' => $stock] = $this->reagentStock($lab);
        $batch = InventoryBatch::query()->create([
            'inventory_id' => $stock->id,
            'batch_number' => 'B-'.fake()->numerify('######'),
            'qty_received' => '1.5000',
            'qty_remaining' => '1.5000',
        ]);
        $otherStock = Inventory::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $destination->id,
            'qty_available' => '1.0000',
            'status' => 'AVAILABLE',
        ]);
        $otherBatch = InventoryBatch::query()->create([
            'inventory_id' => $otherStock->id,
            'batch_number' => 'B-'.fake()->numerify('######'),
            'qty_received' => '1.0000',
            'qty_remaining' => '1.0000',
        ]);

        $payload = [
            'warehouse_id' => $source->id,
            'quantity_used' => '0.1250',
            'used_by' => 'Technician',
            'batch_id' => $otherBatch->id,
            'date' => today()->toDateString(),
            'used_at' => today()->setTime(10, 15)->format('Y-m-d\TH:i:s'),
        ];
        $this->actingAs($user)->postJson(route('vap-inventory.reagents.consume', $item), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('batch_id');
        $this->assertSame('4.0000', $stock->fresh()->qty_available);
        $this->assertDatabaseCount('reagent_consumption', 0);

        $payload['batch_id'] = $batch->id;
        $payload['quantity_used'] = '0.00001';
        $this->postJson(route('vap-inventory.reagents.consume', $item), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('quantity_used');

        $payload['quantity_used'] = '0.1250';
        $this->postJson(route('vap-inventory.reagents.consume', $item), $payload)
            ->assertOk()->assertJsonPath('new_quantity', '3.8750');
        $consumption = ReagentConsumption::query()->sole();
        $this->assertSame('0.1250', $consumption->quantity_used);
        $this->assertSame('10:15', $consumption->used_at->format('H:i'));
        $this->assertSame('1.3750', $batch->fresh()->qty_remaining);
        $this->assertSame('-0.1250', InventoryTransaction::query()->whereKey($consumption->inventory_transaction_id)->sole()->qty);

        $this->post(route('vap-inventory.reagents.consumption.reverse', $consumption))
            ->assertRedirect(route('vap-inventory.reagents.consumption.index'));
        $this->assertSame('4.0000', $stock->fresh()->qty_available);
        $this->assertSame('1.5000', $batch->fresh()->qty_remaining);
        $this->assertModelExists($consumption);
        $this->assertNull($consumption->inventoryTransaction->deleted_at);
        $this->assertSame('0.1250', $consumption->reversal->inventoryTransaction->qty);
        $this->assertTrue($consumption->reversal->inventoryTransaction->is_addition);
    }

    public function test_reagent_consumption_form_accepts_the_smallest_supported_fraction(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        ['item' => $item, 'source' => $source, 'stock' => $stock] = $this->reagentStock($lab);

        $this->actingAs($user)->post(route('vap-inventory.reagents.consumption.store'), [
            'reagent_id' => $item->id,
            'warehouse_id' => $source->id,
            'quantity_used' => '0.0001',
            'used_by' => 'Technician',
            'date' => today()->toDateString(),
        ])->assertRedirect(route('vap-inventory.reagents.consumption.index'));

        $this->assertSame('3.9999', $stock->fresh()->qty_available);
        $this->assertSame('0.0001', ReagentConsumption::query()->sole()->quantity_used);
    }

    public function test_batch_adjustments_move_both_balances_and_unbatched_moves_cannot_spend_batch_stock(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        ['item' => $item, 'source' => $source, 'destination' => $destination, 'stock' => $stock] = $this->reagentStock($lab);
        $batch = InventoryBatch::query()->create([
            'inventory_id' => $stock->id,
            'batch_number' => 'B-'.fake()->numerify('######'),
            'qty_received' => '3.5000',
            'qty_remaining' => '3.5000',
        ]);

        $this->actingAs($user)->postJson(route('vap-inventory.items.adjust-stock', $item), [
            'warehouse_id' => $source->id,
            'adjustment_type' => 'remove',
            'quantity' => '0.5001',
            'reason' => 'physical_count',
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson(route('vap-inventory.reagents.consume', $item), [
            'warehouse_id' => $source->id,
            'quantity_used' => '0.5001',
            'used_by' => 'Technician',
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity_used');
        $this->post(route('vap-inventory.transfers.store'), [
            'item_id' => $item->id,
            'source_id' => $source->id,
            'destination_id' => $destination->id,
            'qty' => '0.5001',
            'sent_date' => today()->toDateString(),
        ])->assertSessionHasErrors('qty');
        $this->assertSame('4.0000', $stock->fresh()->qty_available);
        $this->assertSame('3.5000', $batch->fresh()->qty_remaining);

        $this->postJson(route('vap-inventory.items.adjust-stock', $item), [
            'warehouse_id' => $source->id,
            'batch_id' => $batch->id,
            'adjustment_type' => 'add',
            'quantity' => '0.1250',
            'reason' => 'physical_count',
        ])->assertOk()->assertJsonPath('new_quantity', '4.1250');
        $this->assertSame('3.6250', $batch->fresh()->qty_remaining);

        $this->postJson(route('vap-inventory.items.adjust-stock', $item), [
            'warehouse_id' => $source->id,
            'batch_id' => $batch->id,
            'adjustment_type' => 'set',
            'quantity' => '2.5000',
            'reason' => 'physical_count',
        ])->assertOk()->assertJsonPath('new_quantity', '3.0000');
        $this->assertSame('2.5000', $batch->fresh()->qty_remaining);

        $this->postJson(route('vap-inventory.items.adjust-stock', $item), [
            'warehouse_id' => $source->id,
            'adjustment_type' => 'remove',
            'quantity' => '0.5000',
            'reason' => 'physical_count',
        ])->assertOk()->assertJsonPath('new_quantity', '2.5000');
        $this->assertSame('2.5000', $batch->fresh()->qty_remaining);
    }

    public function test_canonical_item_creation_requires_a_unit_when_opening_stock(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $category = ItemCategory::query()->create(['name' => 'Stock category '.fake()->uuid()]);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Stock room '.fake()->uuid()]);

        $this->actingAs($user)->post(route('vap-inventory.items.store'), [
            'name' => 'Unmeasured item',
            'category_id' => $category->id,
            'warehouses' => [['id' => $warehouse->id, 'qty_available' => '0.0001']],
        ])->assertSessionHasErrors('unit_id');

        $this->assertDatabaseMissing('i_items', ['lab_id' => $lab->id, 'name' => 'Unmeasured item']);
    }

    public function test_issued_stock_locks_the_items_unit(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        ['item' => $item] = $this->reagentStock($lab);
        $replacement = InventoryUnit::query()->create(['code' => 'g-'.fake()->numerify('######'), 'description' => 'Grams']);

        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), [
            'name' => $item->name,
            'category_id' => $item->category_id,
            'unit_id' => $replacement->id,
        ])->assertSessionHasErrors('unit_id');

        $this->assertSame($item->unit_id, $item->fresh()->unit_id);
    }

    public function test_fractional_stock_thresholds_distinguish_critical_and_reorder_levels(): void
    {
        $lab = VAPLab::factory()->create();
        ['stock' => $stock] = $this->reagentStock($lab);

        $stock->update(['qty_available' => '0.2500']);
        $this->assertSame('low_stock', $stock->fresh()->stock_status);

        $stock->update(['qty_available' => '0.1000']);
        $this->assertSame('critical_stock', $stock->fresh()->stock_status);

        $stock->update(['qty_available' => '0.0000']);
        $this->assertSame('out_of_stock', $stock->fresh()->stock_status);
    }
}
