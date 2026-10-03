<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventorySupplierAssessment;
use App\Models\InventoryUnit;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseOrderIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_blocked_supplier_does_not_leave_store_transaction_open(): void
    {
        $fixture = $this->createFixture();
        $this->suspendSupplier($fixture['supplier'], $fixture['user']);
        $transactionLevel = DB::transactionLevel();

        try {
            $this->from(route('vap-inventory.orders.create'))
                ->actingAs($fixture['user'])
                ->post(route('vap-inventory.orders.store'), $this->orderPayload($fixture))
                ->assertRedirect(route('vap-inventory.orders.create'))
                ->assertSessionHas('error');

            $this->assertSame($transactionLevel, DB::transactionLevel());
            $this->assertSame(0, InventoryOrder::query()->where('supplier_id', $fixture['supplier']->id)->count());
        } finally {
            while (DB::transactionLevel() > $transactionLevel) {
                DB::rollBack();
            }
        }
    }

    public function test_blocked_supplier_does_not_leave_update_transaction_open(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $this->suspendSupplier($fixture['supplier'], $fixture['user']);
        $transactionLevel = DB::transactionLevel();

        try {
            $this->from(route('vap-inventory.orders.edit', $order))
                ->actingAs($fixture['user'])
                ->put(route('vap-inventory.orders.update', $order), $this->orderPayload($fixture, $line->id))
                ->assertRedirect(route('vap-inventory.orders.edit', $order))
                ->assertSessionHas('error');

            $this->assertSame($transactionLevel, DB::transactionLevel());
            $this->assertSame('2.0000', $line->fresh()->qty);
        } finally {
            while (DB::transactionLevel() > $transactionLevel) {
                DB::rollBack();
            }
        }
    }

    public function test_order_update_rejects_line_owned_by_another_order(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $ownLine = $this->createLine($fixture, $order);
        $otherOrder = $this->createOrder($fixture);
        $otherLine = $this->createLine($fixture, $otherOrder);

        $this->from(route('vap-inventory.orders.edit', $order))
            ->actingAs($fixture['user'])
            ->put(route('vap-inventory.orders.update', $order), $this->orderPayload($fixture, $otherLine->id))
            ->assertRedirect(route('vap-inventory.orders.edit', $order))
            ->assertSessionHasErrors('order_items.0.id');

        $this->assertSame('2.0000', $ownLine->fresh()->qty);
        $this->assertSame('2.0000', $otherLine->fresh()->qty);
        $this->assertNull($ownLine->fresh()->deleted_at);
    }

    public function test_order_update_rejects_duplicate_line_ids(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $payload = $this->orderPayload($fixture, $line->id);
        $payload['order_items'][] = $payload['order_items'][0];

        $this->from(route('vap-inventory.orders.edit', $order))
            ->actingAs($fixture['user'])
            ->put(route('vap-inventory.orders.update', $order), $payload)
            ->assertRedirect(route('vap-inventory.orders.edit', $order))
            ->assertSessionHasErrors(['order_items.0.id', 'order_items.1.id']);

        $this->assertSame('2.0000', $line->fresh()->qty);
    }

    public function test_order_update_rejects_malformed_line_id_before_database_lookup(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $payload = $this->orderPayload($fixture);
        $payload['order_items'][0]['id'] = 'not-an-id';

        $this->from(route('vap-inventory.orders.edit', $order))
            ->actingAs($fixture['user'])
            ->put(route('vap-inventory.orders.update', $order), $payload)
            ->assertRedirect(route('vap-inventory.orders.edit', $order))
            ->assertSessionHasErrors('order_items.0.id');

        $this->assertSame('2.0000', $line->fresh()->qty);
        $this->assertNull($line->fresh()->deleted_at);
    }

    public function test_store_rejects_price_precision_beyond_storage_scale(): void
    {
        $fixture = $this->createFixture();
        $payload = $this->orderPayload($fixture);
        $payload['order_items'][0]['unit_price'] = '0.33335';

        $this->from(route('vap-inventory.orders.create'))
            ->actingAs($fixture['user'])
            ->post(route('vap-inventory.orders.store'), $payload)
            ->assertRedirect(route('vap-inventory.orders.create'))
            ->assertSessionHasErrors('order_items.0.unit_price');

        $this->assertSame(0, InventoryOrder::query()->where('supplier_id', $fixture['supplier']->id)->count());
    }

    public function test_store_total_matches_persisted_line_total(): void
    {
        $fixture = $this->createFixture();
        $payload = $this->orderPayload($fixture);
        $payload['order_items'][0]['qty'] = 3;
        $payload['order_items'][0]['unit_price'] = '12.3456';

        $this->actingAs($fixture['user'])
            ->post(route('vap-inventory.orders.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $order = InventoryOrder::query()->where('supplier_id', $fixture['supplier']->id)->firstOrFail();
        $this->assertSame('37.0368', $order->total_amount);
        $this->assertSame($order->total_amount, $order->items()->firstOrFail()->total_price);
    }

    public function test_update_total_matches_remaining_persisted_lines(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $retainedLine = $this->createLine($fixture, $order);
        $removedLine = $this->createLine($fixture, $order);

        $this->actingAs($fixture['user'])
            ->put(route('vap-inventory.orders.update', $order), $this->orderPayload($fixture, $retainedLine->id))
            ->assertRedirect(route('vap-inventory.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame('37.0368', $order->fresh()->total_amount);
        $this->assertSame($order->fresh()->total_amount, $retainedLine->fresh()->total_price);
        $this->assertNotNull($removedLine->fresh()->deleted_at);
    }

    public function test_fractional_order_quantity_and_receipts_preserve_four_decimal_balances(): void
    {
        $fixture = $this->createFixture();
        $payload = $this->orderPayload($fixture);
        $payload['order_items'][0]['qty'] = '1.1250';
        $payload['order_items'][0]['unit_price'] = '12.0000';

        $this->actingAs($fixture['user'])->post(route('vap-inventory.orders.store'), $payload)
            ->assertSessionHas('success');
        $order = InventoryOrder::query()->where('supplier_id', $fixture['supplier']->id)->sole();
        $line = $order->items()->sole();
        $this->assertSame('1.1250', $line->qty);
        $this->assertSame('13.5000', $line->total_price);

        $order->update(['status' => 'ORDERED']);
        $receipt = [
            'items' => [['id' => $line->id, 'received_qty' => '0.1250']],
            'receive_date' => today()->toDateString(),
        ];
        $this->post(route('vap-inventory.orders.receive', $order), $receipt)
            ->assertSessionHas('success');
        $this->assertSame('0.1250', $line->fresh()->received_qty);
        $stock = Inventory::query()->where('item_id', $fixture['item']->id)
            ->where('warehouse_id', $fixture['warehouse']->id)->sole();
        $this->assertSame('0.1250', $stock->qty_available);

        $receipt['items'][0]['received_qty'] = '1.0001';
        $this->post(route('vap-inventory.orders.receive', $order), $receipt)
            ->assertSessionHasErrors('items');
        $this->assertSame('0.1250', $line->fresh()->received_qty);
        $this->assertSame('0.1250', $stock->fresh()->qty_available);

        $receipt['items'][0]['received_qty'] = '1.0000';
        $this->post(route('vap-inventory.orders.receive', $order), $receipt)
            ->assertSessionHas('success');
        $this->assertSame('1.1250', $line->fresh()->received_qty);
        $this->assertSame('1.1250', $stock->fresh()->qty_available);
    }

    public function test_pending_order_can_be_cancelled_with_its_lines(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);

        $this->actingAs($fixture['user'])->post(route('vap-inventory.orders.cancel', $order))
            ->assertRedirect(route('vap-inventory.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame('CANCELLED', $order->fresh()->status->value);
        $this->assertSame('CANCELLED', $line->fresh()->status->value);
    }

    public function test_order_with_fractional_receipt_cannot_be_cancelled(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $order->update(['status' => 'ORDERED']);
        $line = $this->createLine($fixture, $order);
        $line->update(['received_qty' => '0.0001']);

        $this->actingAs($fixture['user'])->post(route('vap-inventory.orders.cancel', $order))
            ->assertRedirect(route('vap-inventory.orders.show', $order))
            ->assertSessionHas('error');

        $this->assertSame('ORDERED', $order->fresh()->status->value);
        $this->assertSame('PENDING', $line->fresh()->status->value);
    }

    /**
     * @return array{user: User, supplier: InventoryItemSupplier, item: InventoryItem, warehouse: InventoryItemWarehouse}
     */
    private function createFixture(): array
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);
        $unit = InventoryUnit::query()->create(['code' => 'g-'.fake()->numerify('######'), 'description' => 'Grams']);

        return [
            'user' => $user,
            'supplier' => InventoryItemSupplier::query()->create(['name' => 'Integrity supplier', 'currency' => 'AOA']),
            'item' => InventoryItem::query()->create([
                'lab_id' => $lab->id,
                'name' => 'Integrity item',
                'code' => fake()->unique()->bothify('IO-######'),
                'unit_id' => $unit->id,
            ]),
            'warehouse' => InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Integrity warehouse']),
        ];
    }

    /**
     * @param  array{user: User, supplier: InventoryItemSupplier, item: InventoryItem, warehouse: InventoryItemWarehouse}  $fixture
     */
    private function createOrder(array $fixture): InventoryOrder
    {
        return InventoryOrder::query()->create([
            'lab_id' => $fixture['item']->lab_id,
            'date' => now()->toDateString(),
            'user_id' => $fixture['user']->id,
            'supplier_id' => $fixture['supplier']->id,
            'order_year' => now()->format('Y'),
            'status' => 'PENDING',
            'currency' => 'AOA',
        ]);
    }

    /**
     * @param  array{user: User, supplier: InventoryItemSupplier, item: InventoryItem, warehouse: InventoryItemWarehouse}  $fixture
     */
    private function createLine(array $fixture, InventoryOrder $order): InventoryOrderDetail
    {
        return InventoryOrderDetail::query()->create([
            'order_id' => $order->id,
            'item_id' => $fixture['item']->id,
            'warehouse_id' => $fixture['warehouse']->id,
            'qty' => 2,
            'unit_price' => '10.0000',
            'status' => 'PENDING',
            'currency' => 'AOA',
        ]);
    }

    private function suspendSupplier(InventoryItemSupplier $supplier, User $user): void
    {
        InventorySupplierAssessment::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $user->id,
            'assessment_date' => now()->toDateString(),
            'status' => 'suspended',
            'risk_level' => 'high',
            'total_score' => 35,
            'delivery_score' => 2,
            'quality_score' => 2,
            'compliance_score' => 2,
            'responsiveness_score' => 1,
            'approved_supplier' => false,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array{user: User, supplier: InventoryItemSupplier, item: InventoryItem, warehouse: InventoryItemWarehouse}  $fixture
     * @return array<string, mixed>
     */
    private function orderPayload(array $fixture, ?int $lineId = null): array
    {
        return [
            'supplier_id' => $fixture['supplier']->id,
            'date' => now()->toDateString(),
            'status' => 'PENDING',
            'currency' => 'AOA',
            'order_items' => [[
                ...($lineId !== null ? ['id' => $lineId] : []),
                'item_id' => $fixture['item']->id,
                'qty' => 3,
                'warehouse_id' => $fixture['warehouse']->id,
                'expected_date' => now()->addWeek()->toDateString(),
                'unit_price' => '12.3456',
                'status' => 'PENDING',
            ]],
        ];
    }
}
