<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemLocation;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryTransferLaboratoryTest extends TestCase
{
    use DatabaseTransactions;

    private function operator(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    /** @return array{0: InventoryItem, 1: InventoryItemWarehouse, 2: InventoryItemWarehouse, 3: Inventory} */
    private function stockFixture(VAPLab $lab, int $quantity = 10): array
    {
        $category = ItemCategory::query()->create(['name' => 'Transfer category '.fake()->uuid()]);
        $unit = InventoryUnit::query()->create(['code' => 'trf-'.fake()->unique()->numerify('######'), 'description' => 'Transfer unit']);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Transfer material', 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $source = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Source '.fake()->uuid()]);
        $destination = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Destination '.fake()->uuid()]);
        $stock = Inventory::query()->create([
            'item_id' => $item->id, 'warehouse_id' => $source->id, 'qty_available' => $quantity,
            'min_stock_level' => 0, 'reorder_point' => 0, 'status' => 'AVAILABLE',
        ]);

        return [$item, $source, $destination, $stock];
    }

    /** @return array<string, mixed> */
    private function transferPayload(InventoryItem $item, InventoryItemWarehouse $source, InventoryItemWarehouse $destination, int $quantity = 8): array
    {
        return [
            'item_id' => $item->id, 'source_id' => $source->id, 'destination_id' => $destination->id,
            'qty' => $quantity, 'sent_date' => today()->toDateString(), 'expected_date' => today()->addDay()->toDateString(),
        ];
    }

    public function test_warehouse_creation_and_lookup_are_owned_by_active_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $location = InventoryItemLocation::query()->create(['name' => 'Campus']);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Shared name']);

        $this->actingAs($user)->post(route('iwarehouses.store'), [
            'lab_id' => $peer->id,
            'name' => 'Shared name',
            'location_id' => ['value' => $location->id, 'label' => 'Campus'],
            'is_refrigerated' => false,
            'is_ventilated' => true,
            'has_air_exhaustion' => false,
        ])->assertRedirect();

        $warehouse = InventoryItemWarehouse::query()->where('lab_id', $lab->id)->where('name', 'Shared name')->firstOrFail();
        $this->assertSame($location->id, $warehouse->location_id);
        $this->getJson(route('iwarehouses.getInventoryItemWarehouse', ['q' => 'Shared name']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $warehouse->id);
        $this->get(route('iwarehouses.edit', $peerWarehouse))->assertNotFound();
        $this->put(route('iwarehouses.update', $peerWarehouse), ['name' => 'Changed'])->assertNotFound();
        $this->delete(route('iwarehouses.destroy'), ['recordIds' => [$warehouse->id, $peerWarehouse->id]])->assertNotFound();
        $this->assertNull($warehouse->fresh()->deleted_at);
        $this->assertSame('Shared name', $peerWarehouse->fresh()->name);
    }

    public function test_transfer_rejects_peer_warehouses_and_stock_lookups(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination, $stock] = $this->stockFixture($lab);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Peer stock']);

        $this->actingAs($user)->post(route('vap-inventory.transfers.store'),
            $this->transferPayload($item, $source, $peerWarehouse))->assertSessionHasErrors('destination_id');
        $this->getJson(route('vap-inventory.transfers.item-stock', [
            'item_id' => $item->id, 'warehouse_id' => $peerWarehouse->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->getJson(route('vap-inventory.transfers.item-stock-all', ['item_id' => $item->id]))
            ->assertOk()->assertJsonCount(2, 'warehouses')->assertJsonMissing(['id' => $peerWarehouse->id]);
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $this->assertSame(0, InventoryItemTransfer::query()->count());
    }

    public function test_transfer_reservation_and_partial_receipt_are_atomic_and_one_time(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination, $stock] = $this->stockFixture($lab);
        $payload = $this->transferPayload($item, $source, $destination);

        $this->actingAs($user)->post(route('vap-inventory.transfers.store'), $payload)->assertRedirect();
        $transfer = InventoryItemTransfer::query()->sole();
        $this->assertSame($lab->id, $transfer->lab_id);
        $this->assertSame($payload['expected_date'], $transfer->expected_date->toDateString());
        $this->assertSame('2.0000', $stock->fresh()->qty_available);
        $this->assertSame(1, InventoryTransaction::query()->where('inventory_id', $stock->id)->count());

        $this->post(route('vap-inventory.transfers.receive', $transfer), [
            'actual_qty' => 6, 'received_date' => today()->toDateString(), 'notes' => 'Six arrived',
        ])->assertRedirect();
        $this->assertSame('4.0000', $stock->fresh()->qty_available);
        $this->assertSame('6.0000', Inventory::query()->where('item_id', $item->id)->where('warehouse_id', $destination->id)->sole()->qty_available);
        $this->assertSame(3, InventoryTransaction::query()->where('item_id', $item->id)->count());

        $this->post(route('vap-inventory.transfers.receive', $transfer), [
            'actual_qty' => 6, 'received_date' => today()->toDateString(),
        ])->assertSessionHasErrors('actual_qty');
        $this->post(route('vap-inventory.transfers.cancel', $transfer))->assertSessionHasErrors('transfer');
        $this->assertSame('4.0000', $stock->fresh()->qty_available);
        $this->assertSame(3, InventoryTransaction::query()->where('item_id', $item->id)->count());
    }

    public function test_transfer_queue_counts_match_its_status_filters(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination] = $this->stockFixture($lab);

        $this->actingAs($user)->post(route('vap-inventory.transfers.store'), $this->transferPayload($item, $source, $destination, 4))->assertRedirect();
        $this->post(route('vap-inventory.transfers.store'), $this->transferPayload($item, $source, $destination, 4))->assertRedirect();
        $received = InventoryItemTransfer::query()->orderBy('id')->firstOrFail();
        $this->post(route('vap-inventory.transfers.receive', $received), [
            'actual_qty' => 4, 'received_date' => today()->toDateString(),
        ])->assertRedirect();

        $this->get(route('vap-inventory.transfers.index'))->assertInertia(fn (Assert $page) => $page
            ->component('VAPInventory/Transfers/Index')
            ->where('stats.total_transfers', 2)
            ->where('stats.pending_transfers', 1)
            ->where('stats.in_transit', 1)
            ->where('stats.received', 1));
        $this->get(route('vap-inventory.transfers.index', ['status' => 'received']))->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 1)->where('transfers.data.0.id', $received->id));
        $this->get(route('vap-inventory.transfers.index', ['status' => 'sent']))->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 1)->whereNot('transfers.data.0.id', $received->id));
    }

    public function test_cancel_returns_stock_once_and_peer_cannot_read_or_mutate_transfer(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination, $stock] = $this->stockFixture($lab);
        $this->actingAs($user)->post(route('vap-inventory.transfers.store'),
            $this->transferPayload($item, $source, $destination))->assertRedirect();
        $transfer = InventoryItemTransfer::query()->sole();

        $peerUser = $this->operator($peer);
        $this->actingAs($peerUser)->get(route('vap-inventory.transfers.show', $transfer))->assertNotFound();
        $this->post(route('vap-inventory.transfers.receive', $transfer), ['actual_qty' => 8, 'received_date' => today()->toDateString()])->assertNotFound();
        $this->post(route('vap-inventory.transfers.cancel', $transfer))->assertNotFound();
        $this->get(route('vap-inventory.transfers.index'))->assertInertia(fn (Assert $page) => $page
            ->component('VAPInventory/Transfers/Index')->where('stats.total_transfers', 0));

        $this->withSession(['active_lab_id' => $lab->id])->actingAs($user)
            ->post(route('vap-inventory.transfers.cancel', $transfer))->assertRedirect();
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $this->assertSoftDeleted('i_transfers', ['id' => $transfer->id]);
        $this->post(route('vap-inventory.transfers.cancel', $transfer))->assertNotFound();
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
    }

    public function test_bulk_transfer_rolls_back_all_rows_on_insufficient_stock(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination, $stock] = $this->stockFixture($lab);

        $this->actingAs($user)->postJson(route('vap-inventory.transfers.bulk'), [
            'transfers' => [
                $this->transferPayload($item, $source, $destination, 8),
                $this->transferPayload($item, $source, $destination, 8),
            ],
            'sent_date' => today()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('qty');

        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $this->assertSame(0, InventoryItemTransfer::query()->count());
        $this->assertSame(0, InventoryTransaction::query()->where('item_id', $item->id)->count());
    }

    public function test_revoked_laboratory_membership_denies_inventory_views(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination] = $this->stockFixture($lab);
        DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();

        $this->actingAs($user)->get(route('vap-inventory.transfers.index'))->assertForbidden();
        $this->get(route('iwarehouses.index'))->assertForbidden();
        $this->post(route('vap-inventory.transfers.store'),
            $this->transferPayload($item, $source, $destination))->assertForbidden();
    }

    public function test_stock_routes_scope_reads_and_writes_to_the_active_laboratory(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination, $stock] = $this->stockFixture($lab);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Peer warehouse']);
        $peerItem = InventoryItem::query()->create([
            'lab_id' => $peer->id, 'name' => 'Peer transfer material', 'category_id' => $item->category_id,
        ]);
        $peerStock = Inventory::query()->create([
            'item_id' => $peerItem->id, 'warehouse_id' => $peerWarehouse->id,
            'qty_available' => 99, 'min_stock_level' => 0, 'reorder_point' => 0, 'status' => 'AVAILABLE',
        ]);

        $this->actingAs($user)->get(route('inventory.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Index')->where('record.data.0.id', $stock->id)->missing('record.data.1'));
        $this->getJson(route('inventory.getInventory', ['q' => $item->name]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $stock->id);
        $this->get(route('inventory.show', $peerStock))->assertNotFound();
        $this->get(route('inventory.edit', $peerStock))->assertNotFound();
        $this->post(route('inventory.increment', $peerStock), ['qty' => 2])->assertNotFound();
        $this->post(route('inventory.decrement', $peerStock), ['qty' => 2])->assertNotFound();
        $this->post(route('inventory.store'), [
            'item_id' => $item->id, 'warehouse_id' => $peerWarehouse->id,
            'qty_available' => 1, 'min_stock_level' => 0, 'reorder_point' => 0,
        ])->assertSessionHasErrors('warehouse_id');
        $this->put(route('inventory.update', $peerStock), [
            'item_id' => $item->id, 'warehouse_id' => $peerWarehouse->id,
            'qty_available' => 1, 'min_stock_level' => 0, 'reorder_point' => 0,
        ])->assertNotFound();
        $this->delete(route('inventory.destroy'), ['recordIds' => [$stock->id, $peerStock->id]])->assertNotFound();
        $this->assertSame('99.0000', $peerStock->fresh()->qty_available);
        $this->assertNull($stock->fresh()->deleted_at);
    }

    public function test_legacy_transfer_mutations_are_retired(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination, $stock] = $this->stockFixture($lab);
        $payload = $this->transferPayload($item, $source, $destination);

        $this->actingAs($user)->get(route('itransfers.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('VAPInventory/Transfers/Index'));
        $this->post(route('itransfers.store'), $payload)->assertStatus(410);
        $this->put(route('itransfers.update', 999999), $payload)->assertStatus(410);
        $this->get(route('itransfers.destroy', ['recordIds' => [999999]]))->assertStatus(410);
        $this->get(route('itransfers.restore', ['recordIds' => [999999]]))->assertStatus(410);
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $this->assertSame(0, InventoryItemTransfer::query()->count());
    }

    public function test_active_stock_and_referenced_warehouses_cannot_be_archived(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $source, $destination, $stock] = $this->stockFixture($lab);

        $this->actingAs($user)->delete(route('inventory.destroy'), ['recordIds' => [$stock->id]])
            ->assertSessionHasErrors('recordIds');
        $this->delete(route('iwarehouses.destroy'), ['recordIds' => [$source->id, $destination->id]])
            ->assertSessionHasErrors('recordIds');
        $this->assertNull($stock->fresh()->deleted_at);
        $this->assertNull($source->fresh()->deleted_at);
        $this->assertNull($destination->fresh()->deleted_at);
    }
}
