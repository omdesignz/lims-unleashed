<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryTransferShowTest extends TestCase
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

    public function test_inventory_transfer_show_exposes_chart_payloads(): void
    {
        $user = $this->verifiedAdmin();
        $labId = (int) DB::table('lab_user')->where('user_id', $user->id)->value('lab_id');
        $category = ItemCategory::query()->create(['name' => 'Consumíveis de ensaio']);
        $item = InventoryItem::query()->create([
            'lab_id' => $labId,
            'name' => 'Transfer item',
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
        $source = InventoryItemWarehouse::query()->create(['lab_id' => $labId, 'name' => 'Source warehouse']);
        $destination = InventoryItemWarehouse::query()->create(['lab_id' => $labId, 'name' => 'Destination warehouse']);

        Inventory::query()->updateOrCreate(
            ['item_id' => $item->id, 'warehouse_id' => $source->id],
            [
                'qty_available' => 25,
                'min_stock_level' => 5,
                'reorder_point' => 10,
                'status' => 'AVAILABLE',
            ]
        );

        Inventory::query()->updateOrCreate(
            ['item_id' => $item->id, 'warehouse_id' => $destination->id],
            [
                'qty_available' => 4,
                'min_stock_level' => 0,
                'reorder_point' => 0,
                'status' => 'AVAILABLE',
            ]
        );

        $transfer = InventoryItemTransfer::query()->create([
            'lab_id' => $labId,
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
