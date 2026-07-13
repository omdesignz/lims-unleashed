<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryLedgerWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    private function inventoryPosition(): Inventory
    {
        return Inventory::query()
            ->whereHas('item')
            ->whereHas('warehouse')
            ->firstOrFail();
    }

    public function test_transaction_creation_derives_traceability_fields_from_the_selected_stock_position(): void
    {
        $admin = $this->verifiedAdmin();
        $inventory = $this->inventoryPosition();
        $type = InventoryTransactionType::query()->whereNull('deleted_at')->firstOrFail();
        $forgedUser = User::factory()->create();
        $forgedItem = InventoryItem::query()->whereKeyNot($inventory->item_id)->first() ?? $inventory->item;
        $forgedWarehouse = InventoryItemWarehouse::query()->whereKeyNot($inventory->warehouse_id)->first() ?? $inventory->warehouse;

        $response = $this->actingAs($admin)->post(route('itransactions.store'), [
            'inventory_id' => ['value' => $inventory->id],
            'type_id' => ['value' => $type->id],
            'qty' => 3,
            'user_id' => $forgedUser->id,
            'item_id' => $forgedItem->id,
            'warehouse_id' => $forgedWarehouse->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('itransactions', [
            'inventory_id' => $inventory->id,
            'type_id' => $type->id,
            'qty' => 3,
            'user_id' => $admin->id,
            'item_id' => $inventory->item_id,
            'warehouse_id' => $inventory->warehouse_id,
        ]);
    }

    public function test_transaction_update_accepts_scalar_identifiers_and_preserves_position_integrity(): void
    {
        $admin = $this->verifiedAdmin();
        $inventory = $this->inventoryPosition();
        $type = InventoryTransactionType::query()->whereNull('deleted_at')->firstOrFail();
        $transaction = InventoryTransaction::query()->create([
            'inventory_id' => $inventory->id,
            'type_id' => $type->id,
            'qty' => 1,
            'user_id' => $admin->id,
            'item_id' => $inventory->item_id,
            'warehouse_id' => $inventory->warehouse_id,
        ]);

        $response = $this->actingAs($admin)->put(route('itransactions.update', $transaction), [
            'inventory_id' => $inventory->id,
            'type_id' => $type->id,
            'qty' => 4,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('itransactions', [
            'id' => $transaction->id,
            'inventory_id' => $inventory->id,
            'qty' => 4,
            'user_id' => $admin->id,
            'item_id' => $inventory->item_id,
            'warehouse_id' => $inventory->warehouse_id,
        ]);
    }

    public function test_stock_decrement_cannot_reduce_the_balance_below_zero(): void
    {
        $admin = $this->verifiedAdmin();
        $inventory = $this->inventoryPosition();
        $inventory->update(['qty_available' => 2]);

        $response = $this->actingAs($admin)->post(route('inventory.decrement', $inventory), [
            'qty' => 3,
        ]);

        $response->assertSessionHasErrors('qty');
        $this->assertSame(2, $inventory->refresh()->qty_available);
    }

    public function test_user_without_inventory_edit_permission_cannot_adjust_stock(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $inventory = $this->inventoryPosition();
        $originalQuantity = $inventory->qty_available;

        $this->actingAs($user)
            ->post(route('inventory.increment', $inventory), ['qty' => 1])
            ->assertForbidden();

        $this->assertSame($originalQuantity, $inventory->refresh()->qty_available);
    }
}
