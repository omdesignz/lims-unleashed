<?php

namespace Tests\Feature;

use App\Actions\AdjustInventoryItemStock;
use App\Actions\CreateInventoryPosition;
use App\Models;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryStockAdjustmentIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('persistenceFaults')]
    public function test_failed_adjustment_preserves_balances_and_ledger(string $fault): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $before = $this->snapshot();
        if (in_array($fault, ['stock_veto', 'batch_veto', 'ledger_veto', 'type_veto'], true)) {
            match ($fault) {
                'stock_veto' => Models\Inventory::updating(fn (): bool => false),
                'batch_veto' => Models\InventoryBatch::updating(fn (): bool => false),
                'ledger_veto' => Models\InventoryTransaction::creating(fn (): bool => false),
                'type_veto' => Models\InventoryTransactionType::creating(fn (): bool => false),
            };
        } elseif ($fault === 'ledger_intent') {
            Models\InventoryTransaction::creating(function (Models\InventoryTransaction $record): void {
                $record->qty = '9.0000';
            });
        } else {
            Models\InventoryTransaction::created(function (Models\InventoryTransaction $record) use ($fault, $lab, $operator, $item, $warehouse, $stock, $batch): void {
                match ($fault) {
                    'late_stock' => DB::table($stock->getTable())->where('id', $stock->id)->update(['qty_available' => '99.0000']),
                    'late_batch' => DB::table($batch->getTable())->where('id', $batch->id)->update(['qty_remaining' => '9.0000']),
                    'late_item' => DB::table($item->getTable())->where('id', $item->id)->update(['name' => 'Altered material']),
                    'late_warehouse' => DB::table($warehouse->getTable())->where('id', $warehouse->id)->update(['name' => 'Altered warehouse']),
                    'late_lab' => DB::table($lab->getTable())->where('id', $lab->id)->update(['name' => 'Altered laboratory']),
                    'late_actor' => DB::table($operator->getTable())->where('id', $operator->id)->update(['name' => 'Altered operator']),
                    'late_membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                    'late_pivot' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->update(['can_manage_branding' => true]),
                    'late_verification' => DB::table($operator->getTable())->where('id', $operator->id)->update(['email_verified_at' => null]),
                    'late_ledger' => DB::table($record->getTable())->where('id', $record->id)->update(['notes' => 'Altered movement']),
                    'retained_history' => DB::table($record->getTable())->where('reason', 'Retained evidence')->update(['qty' => '9.0000']),
                    'moved_history' => DB::table($record->getTable())->where('reason', 'Retained evidence')->delete(),
                    'added_batch' => Models\InventoryBatch::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
                        'batch_number' => 'Unexpected batch', 'qty_received' => '0.5000', 'qty_remaining' => '0.5000']),
                    'changed_type' => DB::table((new Models\InventoryTransactionType)->getTable())->where('id', $record->type_id)->update(['name' => 'Altered type']),
                };
            });
        }
        $rejected = false;
        try {
            app(AdjustInventoryItemStock::class)->execute($lab->id, $operator, $item, [
                'warehouse_id' => $warehouse->id, 'batch_id' => $batch->id,
                'adjustment_type' => 'add', 'quantity' => '0.1250', 'reason' => 'Physical count',
            ]);
        } catch (LogicException|AuthorizationException) {
            $rejected = true;
        }
        $this->assertTrue($rejected, 'Rejected '.$fault.' must abort the complete stock adjustment.');
        $this->assertSame($before, $this->snapshot());
    }

    public static function persistenceFaults(): array
    {
        $faults = ['stock_veto', 'batch_veto', 'ledger_veto', 'type_veto', 'ledger_intent', 'late_stock', 'late_batch',
            'late_item', 'late_warehouse', 'late_lab', 'late_actor', 'late_membership', 'late_pivot', 'late_verification',
            'late_ledger', 'retained_history', 'moved_history', 'added_batch', 'changed_type'];

        return array_combine($faults, array_map(fn (string $fault): array => [$fault], $faults));
    }

    #[DataProvider('invalidQuantities')]
    public function test_direct_action_validates_quantity_before_writing(string $type, string $quantity): void
    {
        [$lab, $operator, $item, $warehouse] = $this->fixture();
        $before = $this->snapshot();
        try {
            app(AdjustInventoryItemStock::class)->execute($lab->id, $operator, $item, [
                'warehouse_id' => $warehouse->id, 'adjustment_type' => $type, 'quantity' => $quantity, 'reason' => 'Invalid count',
            ]);
            $this->fail('Invalid adjustment must fail at the action boundary.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function invalidQuantities(): array
    {
        return ['zero add' => ['add', '0'], 'zero remove' => ['remove', '0'], 'negative' => ['set', '-0.0001'],
            'precision' => ['add', '0.00001'], 'range' => ['set', '100000000000000'], 'invalid' => ['add', 'invalid']];
    }

    #[DataProvider('revocations')]
    public function test_action_uses_fresh_authority_instead_of_passed_operator(string $revocation): void
    {
        [$lab, $operator, $item, $warehouse] = $this->fixture();
        $operator->can('edit_inventory');
        match ($revocation) {
            'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
            'verification' => DB::table($operator->getTable())->where('id', $operator->id)->update(['email_verified_at' => null]),
            'activation' => DB::table($operator->getTable())->where('id', $operator->id)->update(['is_active' => false]),
            'permission' => $operator->revokePermissionTo('edit_inventory'),
        };
        $before = $this->snapshot();
        try {
            app(AdjustInventoryItemStock::class)->execute($lab->id, $operator, $item, [
                'warehouse_id' => $warehouse->id, 'adjustment_type' => 'add', 'quantity' => '0.1250', 'reason' => 'Physical count',
            ]);
            $this->fail('Revoked authority must not adjust stock.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->snapshot());
        }
    }

    public static function revocations(): array
    {
        return ['membership' => ['membership'], 'verification' => ['verification'], 'activation' => ['activation'], 'permission' => ['permission']];
    }

    public function test_quick_adjustment_cannot_modify_a_replacement_position(): void
    {
        [$lab, $operator, $item, $warehouse, $stock] = $this->fixture();
        $stock->delete();
        Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '2.0000', 'status' => 'AVAILABLE']);
        $before = $this->snapshot();
        try {
            app(AdjustInventoryItemStock::class)->execute($lab->id, $operator, $item, [
                'warehouse_id' => $warehouse->id, 'adjustment_type' => 'add', 'quantity' => '0.1250', 'reason' => 'Physical count',
            ], expectedInventoryId: $stock->id);
            $this->fail('A quick adjustment must retain its original position identity.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('quickRoutes')]
    public function test_quick_route_rejects_archived_item_without_a_null_argument_failure(string $route): void
    {
        [$lab, $operator, $item, , $stock] = $this->fixture();
        $item->delete();
        $this->actingAs($operator)->withSession(['active_lab_id' => $lab->id])
            ->post(route($route, $stock), ['qty' => '0.1250'])->assertNotFound();
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $this->assertSame(1, Models\InventoryTransaction::query()->where('inventory_id', $stock->id)->count());
    }

    public static function quickRoutes(): array
    {
        return ['increment' => ['inventory.increment'], 'decrement' => ['inventory.decrement']];
    }

    #[DataProvider('staleItemStates')]
    public function test_action_rereads_the_item_instead_of_trusting_its_bound_state(bool $archived): void
    {
        [$lab, $operator, $item, $warehouse] = $this->fixture();
        DB::table($item->getTable())->where('id', $item->id)->update($archived ? ['deleted_at' => now()] : ['unit_id' => null]);
        $before = $this->snapshot();
        try {
            app(AdjustInventoryItemStock::class)->execute($lab->id, $operator, $item, [
                'warehouse_id' => $warehouse->id, 'adjustment_type' => 'add', 'quantity' => '0.1250', 'reason' => 'Physical count',
            ]);
            $this->fail('An ineligible current item must not issue an adjustment.');
        } catch (ValidationException $exception) {
            $this->assertFalse($archived);
            $this->assertArrayHasKey('unit_id', $exception->errors());
        } catch (HttpException $exception) {
            $this->assertTrue($archived);
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function staleItemStates(): array
    {
        return ['unit cleared' => [false], 'archived' => [true]];
    }

    #[DataProvider('openingQuantities')]
    public function test_opening_stock_preserves_add_only_permission_and_zero_behavior(string $quantity, bool $stringIds = false): void
    {
        [$lab, $operator, $item] = $this->fixture();
        $operator->revokePermissionTo('edit_inventory');
        $operator->givePermissionTo(Models\Permission::findOrCreate('add_inventory', 'web'));
        $warehouse = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'New position']);
        $position = app(CreateInventoryPosition::class)->execute($lab->id, $operator->id, [
            'item_id' => $stringIds ? (string) $item->id : $item->id,
            'warehouse_id' => $stringIds ? (string) $warehouse->id : $warehouse->id, 'qty_available' => $quantity,
            'min_stock_level' => '0.1250', 'reorder_point' => '0.5000',
        ]);
        $this->assertModelExists($position);
        $this->assertFalse($position->isDirty());
        $this->assertTrue($position->wasRecentlyCreated);
        $this->assertSame($quantity, $position->qty_available);
        $this->assertSame($quantity, $position->fresh()->qty_available);
        $this->assertSame($quantity === '0.0000' ? 0 : 1, Models\InventoryTransaction::query()->where('inventory_id', $position->id)->count());
        $this->assertSame('0.1250', $position->fresh()->min_stock_level);
    }

    public static function openingQuantities(): array
    {
        return ['zero' => ['0.0000'], 'fraction' => ['0.1250'], 'numeric string IDs' => ['0.1250', true]];
    }

    public function test_opening_keeps_required_type_evidence_after_the_outer_final_operator_read(): void
    {
        [$lab, $operator, $item] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('add_inventory', 'web'));
        $warehouse = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Type evidence position']);
        $before = $this->snapshot();
        $reads = 0;
        Models\User::retrieved(function (Models\User $user) use ($operator, &$reads): void {
            if ($user->id === $operator->id && ++$reads === 4) {
                DB::table((new Models\InventoryTransactionType)->getTable())->where('code', 'stock_adjustment_add')
                    ->update(['name' => 'Altered after nested verification']);
            }
        });
        $rejected = false;
        try {
            app(CreateInventoryPosition::class)->execute($lab->id, $operator->id, [
                'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '0.1250',
                'min_stock_level' => '0.1250', 'reorder_point' => '0.5000',
            ]);
        } catch (LogicException) {
            $rejected = true;
        }
        $this->assertSame(4, $reads);
        $this->assertTrue($rejected, 'The outer final read must not replace already verified type evidence.');
        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('openingFaults')]
    public function test_opening_stock_rolls_back_checked_position_and_authority_faults(string $quantity, string $fault): void
    {
        [$lab, $operator, $item] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('add_inventory', 'web'));
        $warehouse = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Rejected new position']);
        $before = $this->snapshot();
        if ($fault === 'veto') {
            Models\Inventory::creating(fn (): bool => false);
        } else {
            Models\Inventory::created(function (Models\Inventory $record) use ($lab, $operator, $fault): void {
                match ($fault) {
                    'quantity' => DB::table($record->getTable())->where('id', $record->id)->update(['qty_available' => '9.0000']),
                    'authority' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                };
            });
        }
        $rejected = false;
        try {
            app(CreateInventoryPosition::class)->execute($lab->id, $operator->id, [
                'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => $quantity,
                'min_stock_level' => '0.1250', 'reorder_point' => '0.5000',
            ]);
        } catch (LogicException|AuthorizationException) {
            $rejected = true;
        }
        $this->assertTrue($rejected);
        $this->assertSame($before, $this->snapshot());
    }

    public static function openingFaults(): array
    {
        $cases = [];
        foreach (['0.0000', '0.1250'] as $quantity) {
            foreach (['veto', 'quantity', 'authority'] as $fault) {
                $cases[$quantity.'-'.$fault] = [$quantity, $fault];
            }
        }

        return $cases;
    }

    #[DataProvider('openingGraphFaults')]
    public function test_opening_graph_rejects_unexpected_children_and_retained_evidence_changes(string $quantity, string $fault): void
    {
        [$lab, $operator, $item, $warehouse, $stock] = $this->fixture();
        $operator->givePermissionTo(Models\Permission::findOrCreate('add_inventory', 'web'));
        $stock->delete();
        $type = Models\InventoryTransactionType::query()->where('code', 'stock_in')->firstOrFail();
        $before = $this->snapshot();
        Models\Inventory::created(function (Models\Inventory $record) use ($fault, $stock, $operator, $type): void {
            match ($fault) {
                'batch' => Models\InventoryBatch::query()->create(['lab_id' => $record->lab_id, 'inventory_id' => $record->id,
                    'batch_number' => 'Unexpected opening batch', 'qty_received' => '0.1250', 'qty_remaining' => '0.1250']),
                'ledger' => Models\InventoryTransaction::query()->create(['lab_id' => $record->lab_id, 'inventory_id' => $record->id,
                    'warehouse_id' => $record->warehouse_id, 'item_id' => $record->item_id, 'type_id' => $type->id,
                    'user_id' => $operator->id, 'qty' => '0.1250', 'reason' => 'Unexpected opening ledger']),
                'retained stock' => DB::table($stock->getTable())->where('id', $stock->id)->update(['qty_available' => '99.0000']),
                'retained ledger' => DB::table((new Models\InventoryTransaction)->getTable())->where('reason', 'Retained evidence')->update(['qty' => '9.0000']),
            };
        });
        $rejected = false;
        try {
            app(CreateInventoryPosition::class)->execute($lab->id, $operator->id, [
                'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => $quantity,
                'min_stock_level' => '0.1250', 'reorder_point' => '0.5000',
            ]);
        } catch (LogicException) {
            $rejected = true;
        }
        $this->assertTrue($rejected, 'The opening graph must reject '.$fault.' for '.$quantity.'.');
        $this->assertSame($before, $this->snapshot());
    }

    public static function openingGraphFaults(): array
    {
        $cases = [];
        foreach (['0.0000', '0.1250'] as $quantity) {
            foreach (['batch', 'ledger', 'retained stock', 'retained ledger'] as $fault) {
                $cases[$quantity.'-'.$fault] = [$quantity, $fault];
            }
        }

        return $cases;
    }

    /** @return array{Models\VAPLab,Models\User,Models\InventoryItem,Models\InventoryItemWarehouse,Models\Inventory,Models\InventoryBatch} */
    private function fixture(): array
    {
        $lab = Models\VAPLab::factory()->create();
        $operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $operator->givePermissionTo(Models\Permission::findOrCreate('edit_inventory', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $operator->id]);
        $category = Models\ItemCategory::query()->create(['name' => 'Adjustment '.fake()->uuid()]);
        $unit = Models\InventoryUnit::query()->create(['code' => 'adj-'.fake()->numerify('######'), 'description' => 'Millilitres']);
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Adjustment material', 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $warehouse = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Adjustment warehouse']);
        $stock = Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '10.0000', 'min_stock_level' => '0.0000', 'reorder_point' => '0.0000', 'status' => 'AVAILABLE']);
        $batch = Models\InventoryBatch::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
            'batch_number' => 'Adjustment batch', 'qty_received' => '3.5000', 'qty_remaining' => '3.5000']);
        $type = Models\InventoryTransactionType::query()->firstOrCreate(['code' => 'stock_in'], ['name' => 'Retained type']);
        Models\InventoryTransaction::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id, 'user_id' => $operator->id,
            'warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'type_id' => $type->id, 'qty' => '0.0001', 'reason' => 'Retained evidence']);
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);

        return [$lab, $operator, $item, $warehouse, $stock, $batch];
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach ([new Models\VAPLab, new Models\User, new Models\InventoryItem, new Models\InventoryItemWarehouse,
            new Models\Inventory, new Models\InventoryBatch, new Models\InventoryTransaction, new Models\InventoryTransactionType] as $model) {
            $snapshot[$model->getTable()] = DB::table($model->getTable())->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }
        $snapshot['lab_user'] = DB::table('lab_user')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();

        return $snapshot;
    }
}
