<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryTransferShowTest extends TestCase
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

    public function test_inventory_transfer_show_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->firstOrFail();
        $source = InventoryItemWarehouse::query()->firstOrFail();
        $destination = InventoryItemWarehouse::query()->whereKeyNot($source->id)->firstOrFail();

        Inventory::query()->updateOrCreate(
            ['item_id' => $item->id, 'warehouse_id' => $source->id],
            [
                'qty_available' => 25,
                'min_stock_level' => 5,
                'reorder_point' => 10,
                'category_id' => $item->category_id,
                'status' => 'AVAILABLE',
                'name' => 'AVAILABLE',
            ]
        );

        Inventory::query()->updateOrCreate(
            ['item_id' => $item->id, 'warehouse_id' => $destination->id],
            [
                'qty_available' => 4,
                'min_stock_level' => 0,
                'reorder_point' => 0,
                'category_id' => $item->category_id,
                'status' => 'AVAILABLE',
                'name' => 'AVAILABLE',
            ]
        );

        $transfer = InventoryItemTransfer::query()->create([
            'item_id' => $item->id,
            'source_id' => $source->id,
            'destination_id' => $destination->id,
            'qty' => 8,
            'sent_date' => now()->subDay()->toDateString(),
            'obs' => 'Transferência para teste visual',
        ]);

        $this->actingAs($user)
            ->get(route('vap-inventory.transfers.show', $transfer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Transfers/Show')
                ->where('transfer.id', $transfer->id)
                ->where('charts.quantity_flow.labels.0', 'Quantidade transferida')
                ->where('charts.timing_pressure.labels.0', 'Dias em curso')
                ->where('charts.execution_pulse.labels.0', 'Gap destino')
            );
    }
}
