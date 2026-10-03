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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryItemLaboratoryStockTest extends TestCase
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

    /** @return array{0: InventoryItem, 1: InventoryItemWarehouse, 2: InventoryItemWarehouse, 3: Inventory, 4: Inventory, 5: InventoryItem} */
    private function stockFixture(VAPLab $lab, VAPLab $peer): array
    {
        $category = ItemCategory::query()->create(['name' => 'Stock boundary '.fake()->uuid()]);
        $unit = InventoryUnit::query()->create(['code' => 'stk-'.fake()->unique()->numerify('######'), 'description' => 'Stock unit']);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Stock boundary item '.fake()->uuid(), 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $peerItem = InventoryItem::query()->create(['lab_id' => $peer->id, 'name' => 'Peer stock item '.fake()->uuid(), 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $localWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Local '.fake()->uuid()]);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Peer '.fake()->uuid()]);
        $localStock = Inventory::query()->create([
            'item_id' => $item->id, 'warehouse_id' => $localWarehouse->id,
            'qty_available' => 4, 'min_stock_level' => 0, 'reorder_point' => 0, 'status' => 'AVAILABLE',
        ]);
        $peerStock = Inventory::query()->create([
            'item_id' => $peerItem->id, 'warehouse_id' => $peerWarehouse->id,
            'qty_available' => 18, 'min_stock_level' => 0, 'reorder_point' => 0, 'status' => 'AVAILABLE',
        ]);

        return [$item, $localWarehouse, $peerWarehouse, $localStock, $peerStock, $peerItem];
    }

    public function test_item_pages_expose_only_stock_and_movements_from_the_active_laboratory(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $localWarehouse, $peerWarehouse, $localStock, $peerStock, $peerItem] = $this->stockFixture($lab, $peer);
        $type = InventoryTransactionType::query()->create(['code' => 'stock_in', 'name' => 'Stock in']);
        foreach ([$localStock, $peerStock] as $stock) {
            InventoryTransaction::query()->create([
                'inventory_id' => $stock->id, 'user_id' => $user->id,
                'warehouse_id' => $stock->warehouse_id, 'item_id' => $stock->item_id,
                'type_id' => $type->id, 'qty' => $stock->qty_available,
            ]);
        }

        $this->actingAs($user)->get(route('vap-inventory.items.index', ['search' => $item->name]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPInventory/Items/Index')
            ->where('items.data.0.id', $item->id)
            ->where('items.data.0.inventory_sum_qty_available', '4.0000')
            );
        $this->get(route('vap-inventory.items.create'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPInventory/Items/Create')
            ->has('warehouses', 1)
            ->where('warehouses.0.id', $localWarehouse->id)
            );
        $this->get(route('vap-inventory.items.edit', $item))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPInventory/Items/Edit')
            ->has('item.inventory', 1)
            ->where('item.inventory.0.warehouse_id', $localWarehouse->id)
            ->has('warehouses', 1)
            );
        $this->get(route('vap-inventory.items.show', $item))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPInventory/Items/Show')
            ->where('totalStock', 4)
            ->has('inventory', 1)
            ->has('recentTransactions', 1)
            ->where('inventory.0.warehouse_id', $localWarehouse->id)
            ->where('charts.compliance_pulse.series.0', 4)
            );
        $this->assertSame('18.0000', $peerStock->fresh()->qty_available);
        $this->assertSame($peer->id, $peerItem->lab_id);
        $this->assertNotEquals($localWarehouse->id, $peerWarehouse->id);
    }

    public function test_item_creation_rejects_peer_warehouses_and_audits_initial_stock(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $category = ItemCategory::query()->create(['name' => 'Opening stock '.fake()->uuid()]);
        $unit = InventoryUnit::query()->create(['code' => 'pcs-'.fake()->unique()->numerify('######'), 'description' => 'Pieces']);
        $localWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Local '.fake()->uuid()]);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Peer '.fake()->uuid()]);
        $name = 'New stock item '.fake()->uuid();
        $payload = [
            'name' => $name,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'user_id' => User::factory()->create()->id,
            'warehouses' => [[
                'id' => $peerWarehouse->id, 'qty_available' => 6,
                'min_stock_level' => 1, 'reorder_point' => 2,
            ]],
        ];

        $this->actingAs($user)->post(route('vap-inventory.items.store'), $payload)
            ->assertSessionHasErrors('warehouses.0.id');
        $this->assertSame(0, InventoryItem::query()->where('name', $name)->count());

        $payload['warehouses'][0]['id'] = $localWarehouse->id;
        $this->post(route('vap-inventory.items.store'), $payload)->assertRedirect(route('vap-inventory.items.index'));
        $item = InventoryItem::query()->where('name', $name)->firstOrFail();
        $stock = Inventory::query()->where('item_id', $item->id)->sole();
        $this->assertSame($user->id, $item->user_id);
        $this->assertSame($localWarehouse->id, $stock->warehouse_id);
        $this->assertSame('6.0000', $stock->qty_available);
        $this->assertSame(1, InventoryTransaction::query()->where('inventory_id', $stock->id)->where('reason', 'Existências iniciais')->count());
        $this->assertSame('stock_in', InventoryTransaction::query()->where('inventory_id', $stock->id)->sole()->type->code);
    }

    public function test_catalog_edit_cannot_replace_any_laboratory_stock_positions(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $localWarehouse, $peerWarehouse, $localStock, $peerStock] = $this->stockFixture($lab, $peer);

        $this->actingAs($user)->put(route('vap-inventory.items.update', $item), [
            'name' => 'Safe metadata edit', 'category_id' => $item->category_id, 'unit_id' => $item->unit_id,
            'warehouses' => [['id' => $peerWarehouse->id, 'qty_available' => 900]],
        ])->assertSessionHasErrors('warehouses');
        $this->assertSame($item->name, $item->fresh()->name);
        $this->assertSame('4.0000', $localStock->fresh()->qty_available);
        $this->assertSame('18.0000', $peerStock->fresh()->qty_available);

        $this->put(route('vap-inventory.items.update', $item), [
            'name' => 'Safe metadata edit', 'category_id' => $item->category_id, 'unit_id' => $item->unit_id,
        ])->assertRedirect(route('vap-inventory.items.show', $item));
        $this->assertSame('Safe metadata edit', $item->fresh()->name);
        $this->assertSame($localWarehouse->id, $localStock->fresh()->warehouse_id);
        $this->assertSame('18.0000', $peerStock->fresh()->qty_available);
        $this->assertSame(1, Inventory::query()->where('item_id', $item->id)->count());
    }

    public function test_document_upload_uses_the_item_edit_boundary_and_validates_file_type(): void
    {
        Storage::fake('public');
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item] = $this->stockFixture($lab, $peer);

        $this->actingAs($user)->post(route('vap-inventory.items.update', $item), [
            '_method' => 'put',
            'name' => $item->name,
            'category_id' => $item->category_id,
            'unit_id' => $item->unit_id,
            'documents' => [UploadedFile::fake()->create('script.sh', 1, 'text/plain')],
        ])->assertSessionHasErrors('documents.0');
        $this->assertSame(0, $item->getMedia('documents')->count());

        $this->post(route('vap-inventory.items.update', $item), [
            '_method' => 'put',
            'name' => $item->name,
            'category_id' => $item->category_id,
            'unit_id' => $item->unit_id,
            'documents' => [UploadedFile::fake()->create('certificate.pdf', 10, 'application/pdf')],
        ])->assertRedirect(route('vap-inventory.items.show', $item));
        $this->assertSame(1, $item->fresh()->getMedia('documents')->count());
    }

    public function test_adjustment_rejects_peer_stock_and_rolls_back_insufficient_quantity(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $localWarehouse, $peerWarehouse, $localStock, $peerStock] = $this->stockFixture($lab, $peer);
        $payload = ['warehouse_id' => $peerWarehouse->id, 'adjustment_type' => 'add', 'quantity' => 3, 'reason' => 'physical_count'];

        $this->actingAs($user)->postJson(route('vap-inventory.items.adjust-stock', $item), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
        $this->assertSame('18.0000', $peerStock->fresh()->qty_available);

        $payload['warehouse_id'] = $localWarehouse->id;
        $payload['adjustment_type'] = 'remove';
        $payload['quantity'] = 5;
        $transactionLevel = DB::transactionLevel();
        $this->postJson(route('vap-inventory.items.adjust-stock', $item), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertSame($transactionLevel, DB::transactionLevel());
        $this->assertSame('4.0000', $localStock->fresh()->qty_available);
        $this->assertSame(0, InventoryTransaction::query()->where('item_id', $item->id)->count());

        $peerBatchId = DB::table('i_inventory_batches')->insertGetId([
            'lab_id' => $peer->id,
            'inventory_id' => $peerStock->id,
            'batch_number' => 'PEER-'.fake()->uuid(),
            'qty_received' => 2,
            'qty_remaining' => 2,
        ]);
        $payload['adjustment_type'] = 'add';
        $payload['quantity'] = 3;
        $payload['batch_id'] = $peerBatchId;
        $this->postJson(route('vap-inventory.items.adjust-stock', $item), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('batch_id');
        $this->assertSame('4.0000', $localStock->fresh()->qty_available);

        unset($payload['batch_id']);
        $this->postJson(route('vap-inventory.items.adjust-stock', $item), $payload)
            ->assertOk()->assertJsonPath('old_quantity', '4.0000')->assertJsonPath('new_quantity', '7.0000');
        $this->assertSame('7.0000', $localStock->fresh()->qty_available);
        $this->assertSame('18.0000', $peerStock->fresh()->qty_available);
        $this->assertSame(1, InventoryTransaction::query()->where('item_id', $item->id)->where('warehouse_id', $localWarehouse->id)->count());
    }

    public function test_stock_count_can_set_zero_and_keeps_the_previous_balance_in_the_ledger(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        [$item, $localWarehouse, , $localStock] = $this->stockFixture($lab, $peer);

        $this->actingAs($user)->postJson(route('vap-inventory.items.adjust-stock', $item), [
            'warehouse_id' => $localWarehouse->id,
            'adjustment_type' => 'set',
            'quantity' => 0,
            'reason' => 'physical_count',
        ])->assertOk()->assertJsonPath('old_quantity', '4.0000')->assertJsonPath('new_quantity', '0.0000');

        $this->assertSame('0.0000', $localStock->fresh()->qty_available);
        $transaction = InventoryTransaction::query()->where('inventory_id', $localStock->id)->sole();
        $this->assertSame('0.0000', $transaction->qty);
        $this->assertStringContainsString('Saldo anterior: 4.0000; saldo novo: 0.0000', $transaction->notes);
    }
}
