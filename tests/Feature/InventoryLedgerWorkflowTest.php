<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryLedgerWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    private function inventoryPosition(User $user): Inventory
    {
        $category = ItemCategory::query()->create(['name' => 'Consumíveis de ensaio']);
        $unit = InventoryUnit::query()->create(['code' => fake()->uuid(), 'description' => 'Millilitres']);
        $item = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Ledger item', 'category_id' => $category->id, 'unit_id' => $unit->id,
        ]);
        $warehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Ledger warehouse',
        ]);

        return Inventory::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_available' => 10,
        ]);
    }

    public function test_direct_ledger_creation_is_retired_in_favor_of_controlled_stock_actions(): void
    {
        $admin = $this->verifiedAdmin();
        $inventory = $this->inventoryPosition($admin);
        $this->actingAs($admin)->post(route('itransactions.store'), [
            'inventory_id' => $inventory->id,
            'qty' => 3,
        ])->assertStatus(410);
        $this->get(route('itransactions.create'))->assertStatus(410);
        $this->assertDatabaseCount('itransactions', 0);
    }

    public function test_direct_ledger_mutations_cannot_rewrite_existing_evidence(): void
    {
        $admin = $this->verifiedAdmin();
        $inventory = $this->inventoryPosition($admin);
        $type = InventoryTransactionType::query()->create(['name' => 'Stock adjustment', 'code' => 'stock_adjustment_add']);
        $transaction = InventoryTransaction::query()->create([
            'inventory_id' => $inventory->id,
            'type_id' => $type->id,
            'qty' => 1,
            'user_id' => $admin->id,
            'item_id' => $inventory->item_id,
            'warehouse_id' => $inventory->warehouse_id,
        ]);

        $this->actingAs($admin)->put(route('itransactions.update', $transaction), [
            'inventory_id' => $inventory->id,
            'type_id' => $type->id,
            'qty' => 4,
        ])->assertStatus(410);
        $this->get(route('itransactions.edit', $transaction))->assertStatus(410);
        $this->get(route('itransactions.destroy', ['recordIds' => [$transaction->id]]))->assertStatus(410);
        $this->get(route('itransactions.restore', ['recordIds' => [$transaction->id]]))->assertStatus(410);
        $this->assertDatabaseHas('itransactions', [
            'id' => $transaction->id,
            'inventory_id' => $inventory->id,
            'qty' => 1,
            'user_id' => $admin->id,
            'item_id' => $inventory->item_id,
            'warehouse_id' => $inventory->warehouse_id,
        ]);
    }

    public function test_ledger_register_and_details_are_private_to_the_active_laboratory(): void
    {
        $admin = $this->verifiedAdmin();
        $local = $this->inventoryPosition($admin);
        $peer = VAPLab::factory()->create();
        $peerItem = InventoryItem::query()->create(['lab_id' => $peer->id, 'name' => 'Peer secret reagent']);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Peer warehouse']);
        $peerStock = Inventory::query()->create([
            'item_id' => $peerItem->id, 'warehouse_id' => $peerWarehouse->id, 'qty_available' => 9,
        ]);
        $type = InventoryTransactionType::query()->create(['name' => 'Stock in', 'code' => 'stock_in']);
        $localTransaction = InventoryTransaction::query()->create([
            'inventory_id' => $local->id, 'item_id' => $local->item_id,
            'warehouse_id' => $local->warehouse_id, 'type_id' => $type->id,
            'user_id' => $admin->id, 'qty' => 2,
        ]);
        $peerTransaction = InventoryTransaction::query()->create([
            'inventory_id' => $peerStock->id, 'item_id' => $peerItem->id,
            'warehouse_id' => $peerWarehouse->id, 'type_id' => $type->id,
            'user_id' => $admin->id, 'qty' => 9,
        ]);

        $this->actingAs($admin)->get(route('itransactions.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('InventoryTransactions/Index')
            ->has('record.data', 1)
            ->where('record.data.0.id', $localTransaction->id)
            ->missing('record.data.0.links'));
        $this->get(route('itransactions.index', ['search' => 'Peer secret']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 0));
        $this->get(route('itransactions.show', $peerTransaction))->assertNotFound();
        $this->get(route('itransactions.show', $localTransaction))->assertOk();

        $local->item->delete();
        $this->get(route('itransactions.show', $localTransaction))->assertOk();
    }

    public function test_legacy_reagent_writes_and_dashboard_are_retired(): void
    {
        $admin = $this->verifiedAdmin();

        $this->actingAs($admin)->get(route('reagent-consumption.index'))
            ->assertRedirect(route('vap-inventory.reagents.consumption.index'));
        $this->post(route('reagent-consumption.store'), [])->assertStatus(410);
        $this->post(route('reagent-consumption.storeBatch'), [])->assertStatus(410);
        $this->get(route('reagent-consumption.consumptionLogs', 1))->assertStatus(410);
        $this->get(route('reagent-dashboard.index'))->assertStatus(410);
        $this->assertDatabaseCount('reagent_consumption', 0);
    }

    public function test_stock_decrement_cannot_reduce_the_balance_below_zero(): void
    {
        $admin = $this->verifiedAdmin();
        $inventory = $this->inventoryPosition($admin);
        $inventory->update(['qty_available' => 2]);

        $response = $this->actingAs($admin)->post(route('inventory.decrement', $inventory), [
            'qty' => 3,
        ]);

        $response->assertSessionHasErrors(['qty' => 'As existências não podem ficar com quantidade negativa.']);
        $this->assertSame('2.0000', $inventory->refresh()->qty_available);
        $this->assertDatabaseCount('itransactions', 0);
    }

    public function test_user_without_inventory_edit_permission_cannot_adjust_stock(): void
    {
        $admin = $this->verifiedAdmin();
        $user = User::factory()->create(['is_active' => true]);
        $inventory = $this->inventoryPosition($admin);
        $originalQuantity = $inventory->qty_available;

        $this->actingAs($user)
            ->post(route('inventory.increment', $inventory), ['qty' => 1])
            ->assertForbidden();

        $this->assertSame($originalQuantity, $inventory->refresh()->qty_available);
    }
}
