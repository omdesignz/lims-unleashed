<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventorySupplierAssessment;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\InventoryUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PurchaseOrderIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_receiving_requires_its_operation_permission_and_optional_nonconformity_permission(): void
    {
        $fixture = $this->createFixture();
        $fixture['user']->removeRole('admin');
        $order = $this->createOrder($fixture);
        $order->update(['status' => 'ORDERED']);
        $line = $this->createLine($fixture, $order);
        $payload = ['request_id' => (string) Str::uuid(), 'items' => [['id' => $line->id, 'received_qty' => '0.1250']],
            'receive_date' => today()->toDateString()];
        $this->actingAs($fixture['user'])->postJson(route('vap-inventory.orders.receive', $order), $payload)->assertForbidden();
        $fixture['user']->givePermissionTo(Permission::findOrCreate('edit_iorders', 'web'));
        $withDossier = [...$payload, 'register_non_conformity' => true, 'non_conformity_title' => 'Fictional deviation',
            'non_conformity_description' => 'Permission boundary verification'];
        $this->postJson(route('vap-inventory.orders.receive', $order), $withDossier)->assertForbidden();
        $this->assertSame('0.0000', $line->fresh()->received_qty);
        $this->assertNull($order->fresh()->receipt_history);
        $this->assertFalse(Inventory::where('item_id', $fixture['item']->id)->exists());
        $this->assertFalse(InventoryTransaction::where('item_id', $fixture['item']->id)->exists());
        $fixture['user']->givePermissionTo(Permission::findOrCreate('add_occurrences', 'web'));
        $this->postJson(route('vap-inventory.orders.receive', $order), $withDossier)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('0.1250', $line->fresh()->received_qty);
        $this->assertNotNull($order->fresh()->receipt_history[0]['non_conformity_id']);
    }

    #[DataProvider('receiptAuthorityRevocations')]
    public function test_receiving_rechecks_current_authority_and_rolls_back_all_effects(string $revocation): void
    {
        $fixture = $this->createFixture();
        $fixture['user']->removeRole('admin');
        $fixture['user']->givePermissionTo(Permission::findOrCreate('edit_iorders', 'web'));
        $fixture['user']->givePermissionTo(Permission::findOrCreate('add_occurrences', 'web'));
        $order = $this->createOrder($fixture);
        $order->update(['status' => 'ORDERED']);
        $line = $this->createLine($fixture, $order);
        $dossierCount = VAPNonConformity::withTrashed()->where('lab_id', $order->lab_id)->count();
        $this->actingAs($fixture['user']);
        $event = 'eloquent.created: '.InventoryTransaction::class;
        Event::listen($event, function () use ($revocation, $fixture, $order): void {
            match ($revocation) {
                'permission' => $fixture['user']->revokePermissionTo('edit_iorders'),
                'nonconformity_permission' => $fixture['user']->revokePermissionTo('add_occurrences'),
                'membership' => DB::table('lab_user')->where('lab_id', $order->lab_id)->where('user_id', $fixture['user']->id)->delete(),
                'activation' => User::whereKey($fixture['user']->id)->update(['is_active' => false]),
                'verification' => User::whereKey($fixture['user']->id)->update(['email_verified_at' => null]),
                'archive' => User::whereKey($fixture['user']->id)->update(['deleted_at' => now()]),
            };
        });
        try {
            $this->postJson(route('vap-inventory.orders.receive', $order), ['request_id' => (string) Str::uuid(),
                'items' => [['id' => $line->id, 'received_qty' => '0.1250']], 'receive_date' => today()->toDateString(),
                ...($revocation === 'nonconformity_permission' ? ['register_non_conformity' => true,
                    'non_conformity_title' => 'Fictional deviation', 'non_conformity_description' => 'Must roll back'] : [])])->assertForbidden();
        } finally {
            Event::forget($event);
        }
        $this->assertSame('0.0000', $line->fresh()->received_qty);
        $this->assertNull($order->fresh()->receipt_history);
        $this->assertFalse(Inventory::where('item_id', $fixture['item']->id)->exists());
        $this->assertFalse(InventoryTransaction::where('item_id', $fixture['item']->id)->exists());
        $this->assertSame($dossierCount, VAPNonConformity::withTrashed()->where('lab_id', $order->lab_id)->count());
    }

    public static function receiptAuthorityRevocations(): array
    {
        return array_combine(['permission', 'nonconformity_permission', 'membership', 'activation', 'verification', 'archive'],
            array_map(fn (string $state): array => [$state], ['permission', 'nonconformity_permission', 'membership', 'activation', 'verification', 'archive']));
    }

    public function test_edit_payload_retains_saved_warehouse_label_without_exposing_foreign_warehouses(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $this->createLine($fixture, $order);

        $this->actingAs($fixture['user'])
            ->get(route('vap-inventory.orders.edit', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Orders/Edit')
                ->where('order.items.0.warehouse.id', $fixture['warehouse']->id)
                ->where('order.items.0.warehouse.name', $fixture['warehouse']->name)
                ->missing('order.items.0.warehouse.obs'));

        $foreignWarehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => VAPLab::factory()->create()->id,
            'name' => 'Foreign laboratory warehouse',
        ]);
        $this->get(route('vap-inventory.orders.edit', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPInventory/Orders/Edit')
                ->where('warehouses', fn ($warehouses) => ! collect($warehouses)->contains('id', $foreignWarehouse->id))
                ->where('order.items.0.warehouse.id', $fixture['warehouse']->id));
    }

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
            'request_id' => (string) Str::uuid(),
            'receive_date' => today()->toDateString(),
        ];
        $this->post(route('vap-inventory.orders.receive', $order), $receipt)
            ->assertSessionHas('success');
        $this->assertSame('0.1250', $line->fresh()->received_qty);
        $stock = Inventory::query()->where('item_id', $fixture['item']->id)
            ->where('warehouse_id', $fixture['warehouse']->id)->sole();
        $this->assertSame('0.1250', $stock->qty_available);

        $receipt['items'][0]['received_qty'] = '1.0001';
        $receipt['request_id'] = (string) Str::uuid();
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

    public function test_receiving_an_unordered_purchase_returns_field_errors_without_changing_stock(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $before = $line->fresh()->getRawOriginal();

        $this->actingAs($fixture['user'])
            ->from(route('vap-inventory.orders.show', $order))
            ->post(route('vap-inventory.orders.receive', $order), [
                'items' => [['id' => $line->id, 'received_qty' => '0.1250']],
                'request_id' => (string) Str::uuid(),
                'receive_date' => today()->toDateString(),
            ])->assertRedirect(route('vap-inventory.orders.show', $order))->assertSessionHasErrors('items');

        $this->assertSame($before, $line->fresh()->getRawOriginal());
        $this->assertFalse(Inventory::where('item_id', $fixture['item']->id)->exists());
    }

    /** @return array<string, array{string}> */
    public static function archivedReceiptReferences(): array
    {
        return ['material' => ['item'], 'warehouse' => ['warehouse'], 'missing unit' => ['unit']];
    }

    #[DataProvider('archivedReceiptReferences')]
    public function test_receipt_rejects_archived_references_without_stock_or_ledger_changes(string $reference): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $order->update(['status' => 'ORDERED']);
        if ($reference === 'unit') {
            $fixture['item']->update(['unit_id' => null]);
        } else {
            $fixture[$reference]->delete();
        }
        $before = $line->fresh()->getRawOriginal();
        $movementCount = InventoryTransaction::count();

        $this->actingAs($fixture['user'])->from(route('vap-inventory.orders.show', $order))
            ->post(route('vap-inventory.orders.receive', $order), [
                'items' => [['id' => $line->id, 'received_qty' => '0.1250']],
                'request_id' => (string) Str::uuid(),
                'receive_date' => today()->toDateString(),
            ])->assertSessionHasErrors('items')->assertSessionMissing('success');

        $this->assertSame($before, $line->fresh()->getRawOriginal());
        $this->assertSame('ORDERED', $order->fresh()->status->value);
        $this->assertFalse(Inventory::where('item_id', $fixture['item']->id)->exists());
        $this->assertSame($movementCount, InventoryTransaction::count());
    }

    /** @return array<string, array{class-string<\Throwable>}> */
    public static function receiptWriteFailures(): array
    {
        return ['exception' => [\RuntimeException::class], 'php error' => [\TypeError::class]];
    }

    #[DataProvider('receiptWriteFailures')]
    public function test_receiving_write_exception_is_reported_and_returns_errors_after_rollback(string $failureClass): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $order->update(['status' => 'ORDERED']);
        $before = $line->fresh()->getRawOriginal();
        $stockCount = Inventory::count();
        $movementCount = InventoryTransaction::count();
        $transactionLevel = DB::transactionLevel();
        Exceptions::fake();
        $dispatcher = InventoryOrderDetail::getEventDispatcher();
        InventoryOrderDetail::setEventDispatcher(clone $dispatcher);
        InventoryOrderDetail::saving(function (InventoryOrderDetail $record) use ($line, $failureClass): void {
            if ($record->id === $line->id) {
                throw new $failureClass('Receipt write failure');
            }
        });

        try {
            $this->actingAs($fixture['user'])->from(route('vap-inventory.orders.show', $order))
                ->post(route('vap-inventory.orders.receive', $order), [
                    'items' => [['id' => $line->id, 'received_qty' => '0.1250']],
                    'request_id' => (string) Str::uuid(),
                    'receive_date' => today()->toDateString(),
                ])->assertSessionHasErrors('items')->assertSessionMissing('success');
        } finally {
            InventoryOrderDetail::setEventDispatcher($dispatcher);
        }

        Exceptions::assertReported($failureClass);
        $this->assertSame($transactionLevel, DB::transactionLevel());
        $this->assertSame($before, $line->fresh()->getRawOriginal());
        $this->assertSame($stockCount, Inventory::count());
        $this->assertSame($movementCount, InventoryTransaction::count());
    }

    public function test_mixed_receipt_validates_all_destinations_before_attempting_stock_writes(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $validLine = $this->createLine($fixture, $order);
        $unavailableWarehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => $fixture['item']->lab_id,
            'name' => 'Archived receipt destination',
        ]);
        $invalidLine = $this->createLine([...$fixture, 'warehouse' => $unavailableWarehouse], $order);
        $order->update(['status' => 'ORDERED']);
        $unavailableWarehouse->delete();
        $stockWrites = 0;
        $dispatcher = Inventory::getEventDispatcher();
        Inventory::setEventDispatcher(clone $dispatcher);
        Inventory::creating(function () use (&$stockWrites): void {
            $stockWrites++;
        });

        try {
            $this->actingAs($fixture['user'])->post(route('vap-inventory.orders.receive', $order), [
                'items' => [
                    ['id' => $validLine->id, 'received_qty' => '0.1250'],
                    ['id' => $invalidLine->id, 'received_qty' => '0.1250'],
                ],
                'request_id' => (string) Str::uuid(),
                'receive_date' => today()->toDateString(),
            ])->assertSessionHasErrors('items')->assertSessionMissing('success');
        } finally {
            Inventory::setEventDispatcher($dispatcher);
        }

        $this->assertSame(0, $stockWrites);
        $this->assertSame('0.0000', $validLine->fresh()->received_qty);
        $this->assertSame('0.0000', $invalidLine->fresh()->received_qty);
        $this->assertSame('ORDERED', $order->fresh()->status->value);
    }

    /** @return array<string, array{class-string, string, bool}> */
    public static function receiptWriteVetoes(): array
    {
        return [
            'new stock' => [Inventory::class, 'creating', false],
            'existing stock increment' => [Inventory::class, 'updating', true],
            'ledger' => [InventoryTransaction::class, 'creating', true],
            'received line' => [InventoryOrderDetail::class, 'saving', true],
            'purchase price' => [InventoryItem::class, 'updating', true],
            'order status' => [InventoryOrder::class, 'updating', true],
            'nonconformity' => [VAPNonConformity::class, 'creating', true],
        ];
    }

    #[DataProvider('receiptWriteVetoes')]
    public function test_receipt_write_veto_rolls_back_all_receiving_state(string $modelClass, string $event, bool $existingStock): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $order->update(['status' => 'ORDERED']);
        $stock = $existingStock ? Inventory::query()->create([
            'item_id' => $fixture['item']->id,
            'warehouse_id' => $fixture['warehouse']->id,
            'qty_available' => '3.0000',
            'status' => 'AVAILABLE',
        ]) : null;
        $beforeOrder = $order->fresh()->getRawOriginal();
        $beforeLine = $line->fresh()->getRawOriginal();
        $beforeItem = $fixture['item']->fresh()->getRawOriginal();
        $beforeStock = $stock?->fresh()->getRawOriginal();
        $stockCount = Inventory::count();
        $movementCount = InventoryTransaction::count();
        $nonConformityCount = VAPNonConformity::count();
        Exceptions::fake();
        $dispatcher = $modelClass::getEventDispatcher();
        $modelClass::setEventDispatcher(clone $dispatcher);
        $modelClass::{$event}(fn (): bool => false);

        try {
            $this->actingAs($fixture['user'])->from(route('vap-inventory.orders.show', $order))
                ->post(route('vap-inventory.orders.receive', $order), [
                    'items' => [['id' => $line->id, 'received_qty' => '0.1250', 'unit_price' => '0']],
                    'request_id' => (string) Str::uuid(),
                    'receive_date' => today()->toDateString(),
                    'register_non_conformity' => $modelClass === VAPNonConformity::class,
                    'non_conformity_title' => 'Receipt deviation',
                    'non_conformity_description' => 'Demonstration evidence',
                ])->assertSessionHasErrors('items')->assertSessionMissing('success');
        } finally {
            $modelClass::setEventDispatcher($dispatcher);
        }

        Exceptions::assertReported(\RuntimeException::class);
        $this->assertSame($beforeOrder, $order->fresh()->getRawOriginal());
        $this->assertSame($beforeLine, $line->fresh()->getRawOriginal());
        $this->assertSame($beforeItem, $fixture['item']->fresh()->getRawOriginal());
        $this->assertSame($beforeStock, $stock?->fresh()->getRawOriginal());
        $this->assertSame($stockCount, Inventory::count());
        $this->assertSame($movementCount, InventoryTransaction::count());
        $this->assertSame($nonConformityCount, VAPNonConformity::count());
    }

    public function test_zero_receipt_price_is_persisted_without_replacing_agreed_order_price(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $order->update(['status' => 'ORDERED']);

        $this->actingAs($fixture['user'])->post(route('vap-inventory.orders.receive', $order), [
            'items' => [['id' => $line->id, 'received_qty' => '0.1250', 'unit_price' => '0']],
            'request_id' => (string) Str::uuid(),
            'receive_date' => today()->toDateString(),
        ])->assertSessionHas('success');

        $this->assertEquals(0, $fixture['item']->fresh()->last_purchase_price);
        $this->assertSame('10.0000', $line->fresh()->unit_price);
        $movement = InventoryTransaction::where('item_id', $fixture['item']->id)->sole();
        $this->assertStringContainsString('preço unitário de 0 AOA (total: 0)', $movement->notes);
        $this->assertSame('0.1250', $line->fresh()->received_qty);
    }

    /** @return array<string, array{string}> */
    public static function replayQuantities(): array
    {
        return ['partial' => ['0.1250'], 'complete' => ['2.0000']];
    }

    #[DataProvider('replayQuantities')]
    public function test_identical_receipt_retry_never_repeats_stock_and_changed_payload_is_rejected(string $quantity): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $order->update(['status' => 'ORDERED']);
        $payload = [
            'request_id' => (string) Str::uuid(),
            'items' => [['id' => $line->id, 'received_qty' => $quantity, 'unit_price' => '0']],
            'receive_date' => today()->toDateString(),
        ];
        $this->actingAs($fixture['user'])->post(route('vap-inventory.orders.receive', $order), $payload)
            ->assertSessionHas('success');
        $beforeOrder = $order->fresh()->getRawOriginal();
        $beforeLine = $line->fresh()->getRawOriginal();
        $stock = Inventory::where('item_id', $fixture['item']->id)->sole();
        $beforeStock = $stock->getRawOriginal();
        $movementCount = InventoryTransaction::count();

        $this->post(route('vap-inventory.orders.receive', $order), $payload)->assertSessionHas('success');
        $payload['items'][0]['unit_price'] = '1';
        $this->post(route('vap-inventory.orders.receive', $order), $payload)->assertSessionHasErrors('request_id');

        $this->assertSame($beforeOrder, $order->fresh()->getRawOriginal());
        $this->assertSame($beforeLine, $line->fresh()->getRawOriginal());
        $this->assertSame($beforeStock, $stock->fresh()->getRawOriginal());
        $this->assertSame($movementCount, InventoryTransaction::count());
        $this->assertCount(1, $order->fresh()->receipt_history);
        $this->assertArrayNotHasKey('receipt_history', $order->fresh()->toArray());
    }

    public function test_receipt_requires_uuid_and_history_write_failure_rolls_back_stock(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $order->update(['status' => 'ORDERED']);
        $payload = [
            'items' => [['id' => $line->id, 'received_qty' => '0.1250']],
            'receive_date' => today()->toDateString(),
        ];
        $this->actingAs($fixture['user'])->post(route('vap-inventory.orders.receive', $order), $payload)
            ->assertSessionHasErrors('request_id');
        $payload['request_id'] = (string) Str::uuid();
        new Inventory;
        new InventoryTransaction;
        new InventoryTransactionType;
        $dispatcher = InventoryOrder::getEventDispatcher();
        InventoryOrder::setEventDispatcher(clone $dispatcher);
        InventoryOrder::saving(fn (InventoryOrder $record): bool => ! $record->isDirty('receipt_history'));
        Exceptions::fake();
        try {
            $this->post(route('vap-inventory.orders.receive', $order), $payload)->assertSessionHasErrors('items');
        } finally {
            InventoryOrder::setEventDispatcher($dispatcher);
        }
        $this->assertNull($order->fresh()->receipt_history);
        $this->assertSame('0.0000', $line->fresh()->received_qty);
        $this->assertFalse(Inventory::where('item_id', $fixture['item']->id)->exists());
        $this->assertFalse(InventoryTransaction::where('item_id', $fixture['item']->id)->exists());
        $response = $this->post(route('vap-inventory.orders.receive', $order), $payload);
        $this->assertCount(1, Exceptions::reported(), collect(Exceptions::reported())->map(fn (\Throwable $exception): string => $exception->getMessage())->implode("\n"));
        $response->assertSessionHas('success');
        $this->assertSame('0.1250', $line->fresh()->received_qty);
    }

    public function test_receipt_replay_does_not_duplicate_nonconformity_and_cannot_be_claimed_by_another_actor(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $line = $this->createLine($fixture, $order);
        $order->update(['status' => 'ORDERED']);
        $payload = [
            'request_id' => (string) Str::uuid(),
            'items' => [['id' => $line->id, 'received_qty' => '0.1250']],
            'receive_date' => today()->toDateString(),
            'register_non_conformity' => true,
            'non_conformity_title' => 'Replay evidence fixture',
            'non_conformity_description' => 'Retain one receiving deviation.',
        ];
        $this->actingAs($fixture['user'])->post(route('vap-inventory.orders.receive', $order), $payload)
            ->assertSessionHas('success');
        $history = $order->fresh()->receipt_history;
        $nonConformity = VAPNonConformity::findOrFail($history[0]['non_conformity_id']);
        $before = $nonConformity->getRawOriginal();
        $nonConformityCount = VAPNonConformity::count();
        $movementCount = InventoryTransaction::count();

        $this->post(route('vap-inventory.orders.receive', $order), $payload)->assertSessionHas('success');
        $otherUser = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $otherUser->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $order->lab_id, 'user_id' => $otherUser->id]);
        $this->actingAs($otherUser)->post(route('vap-inventory.orders.receive', $order), $payload)
            ->assertSessionHasErrors('request_id');

        $this->assertSame($history, $order->fresh()->receipt_history);
        $this->assertSame($before, $nonConformity->fresh()->getRawOriginal());
        $this->assertSame($nonConformityCount, VAPNonConformity::count());
        $this->assertSame($movementCount, InventoryTransaction::count());
        $this->assertSame('0.1250', $line->fresh()->received_qty);
    }

    public function test_receipt_history_migration_round_trip_preserves_existing_order_fields(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $before = $order->fresh()->getRawOriginal();
        unset($before['receipt_history']);
        $migration = require database_path('migrations/2026_10_04_063925_add_receipt_history_to_inventory_orders.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('i_orders', 'receipt_history'));
        $this->assertSame($before, $order->fresh()->getRawOriginal());
        $migration->up();
        $this->assertTrue(Schema::hasColumn('i_orders', 'receipt_history'));
        $this->assertNull($order->fresh()->receipt_history);
        $after = $order->fresh()->getRawOriginal();
        unset($after['receipt_history']);
        $this->assertSame($before, $after);
    }

    public function test_receipt_history_migration_refuses_to_remove_replay_evidence(): void
    {
        $fixture = $this->createFixture();
        $order = $this->createOrder($fixture);
        $order->receipt_history = [['request_id' => (string) Str::uuid()]];
        $order->save();
        $before = $order->fresh()->getRawOriginal();
        $migration = require database_path('migrations/2026_10_04_063925_add_receipt_history_to_inventory_orders.php');

        try {
            $migration->down();
            $this->fail('Receipt evidence must prevent rollback.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('forward migration', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('i_orders', 'receipt_history'));
        $this->assertSame($before, $order->fresh()->getRawOriginal());
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
