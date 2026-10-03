<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InventoryProcurementLaboratoryOwnershipTest extends TestCase
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

    public function test_peer_purchase_orders_do_not_appear_or_accept_direct_actions(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = $this->order($lab, $user);
        $foreign = $this->order($peer, $user);

        $response = $this->actingAs($user)->get(route('vap-inventory.orders.index'));
        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertSame(1, data_get($page, 'props.orders.total'));
        $this->assertSame($local->id, data_get($page, 'props.orders.data.0.id'));
        $this->assertSame(1, data_get($page, 'props.stats.total_orders'));

        $this->get(route('vap-inventory.orders.show', $foreign))->assertNotFound();
        $this->get(route('vap-inventory.orders.edit', $foreign))->assertNotFound();
        $this->get(route('vap-inventory.orders.export-pdf', $foreign))->assertNotFound();
        $this->put(route('vap-inventory.orders.update', $foreign), [])->assertNotFound();
        $this->post(route('vap-inventory.orders.receive', $foreign), [])->assertNotFound();
        $this->post(route('vap-inventory.orders.cancel', $foreign))->assertNotFound();
        $this->delete(route('vap-inventory.orders.destroy', $foreign))->assertNotFound();
        $this->get(route('iorders.show', $foreign))->assertNotFound();
        $this->get(route('rating.create', ['rateableType' => 'order', 'rateableId' => $foreign->id]))->assertNotFound();
        $this->post(route('rating.store', ['rateableType' => 'order', 'rateableId' => $foreign->id]), [])->assertNotFound();
        $this->get(route('iorders.destroy', ['recordIds' => [$local->id, $foreign->id]]))->assertNotFound();
        $this->assertFalse($local->fresh()->trashed());
        $this->assertFalse($foreign->fresh()->trashed());
    }

    public function test_procurement_lines_cannot_reference_peer_items_or_warehouses(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $order = $this->order($lab, $user);
        $department = Department::query()->create(['name' => 'Procurement '.fake()->uuid()]);
        $need = InventoryNeed::query()->create([
            'reference' => 'NEED-'.fake()->uuid(),
            'department_id' => $department->id,
            'lab_id' => $lab->id,
            'requested_by_id' => $user->id,
            'status' => 'submitted',
        ]);
        $localItem = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Local item']);
        $peerItem = InventoryItem::query()->create(['lab_id' => $peer->id, 'name' => 'Peer item']);
        $localWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Local warehouse']);
        $peerWarehouse = InventoryItemWarehouse::query()->create(['lab_id' => $peer->id, 'name' => 'Peer warehouse']);

        $this->assertConstraintFails(fn () => InventoryOrderDetail::query()->create([
            'order_id' => $order->id, 'item_id' => $peerItem->id, 'warehouse_id' => $localWarehouse->id,
        ]));
        $this->assertConstraintFails(fn () => InventoryOrderDetail::query()->create([
            'order_id' => $order->id, 'item_id' => $localItem->id, 'warehouse_id' => $peerWarehouse->id,
        ]));
        $this->assertConstraintFails(fn () => InventoryNeedItem::query()->create([
            'inventory_need_id' => $need->id, 'inventory_item_id' => $peerItem->id,
            'warehouse_id' => $localWarehouse->id, 'quantity_requested' => 1,
        ]));
        $this->assertConstraintFails(fn () => InventoryNeedItem::query()->create([
            'inventory_need_id' => $need->id, 'inventory_item_id' => $localItem->id,
            'warehouse_id' => $peerWarehouse->id, 'quantity_requested' => 1,
        ]));

        $line = InventoryOrderDetail::query()->create([
            'order_id' => $order->id, 'item_id' => $localItem->id,
            'warehouse_id' => $localWarehouse->id, 'qty' => 1,
        ]);
        $needLine = InventoryNeedItem::query()->create([
            'inventory_need_id' => $need->id, 'inventory_item_id' => $localItem->id,
            'warehouse_id' => $localWarehouse->id, 'quantity_requested' => 1,
        ]);
        $this->assertSame($lab->id, $line->lab_id);
        $this->assertSame($lab->id, $needLine->lab_id);
    }

    public function test_procurement_ownership_rollback_refuses_retained_orders(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $this->order($lab, $user);
        $migration = require database_path('migrations/2026_09_30_171135_enforce_laboratory_ownership_of_inventory_orders.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot remove procurement laboratory ownership while records are retained.');
        $migration->down();
    }

    private function order(VAPLab $lab, User $user): InventoryOrder
    {
        return InventoryOrder::query()->create([
            'lab_id' => $lab->id,
            'user_id' => $user->id,
            'order_year' => now()->format('Y'),
            'date' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
    }

    private function assertConstraintFails(callable $operation): void
    {
        try {
            DB::transaction($operation);
            $this->fail('Expected PostgreSQL to reject the cross-laboratory procurement line.');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getCode());
        }
    }
}
