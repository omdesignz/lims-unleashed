<?php

namespace Tests\Feature;

use App\Actions\AdjustInventoryItemStock;
use App\Actions\ConsumeInventoryReagent;
use App\Actions\CreateInventoryItem;
use App\Actions\CreateInventoryPosition;
use App\Actions\ReverseInventoryReagentConsumption;
use App\Actions\UpdateInventoryItem;
use App\Models;
use App\Support\InventoryQuantity;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryReagentConsumptionIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('reagent_consumption_reversals')) {
            (require database_path('migrations/2026_10_02_222759_create_reagent_consumption_reversals_table.php'))->up();
        }
    }

    #[DataProvider('persistenceFaults')]
    public function test_failed_consumption_preserves_the_complete_stock_graph(string $fault): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch, $history] = $this->fixture();
        $before = $this->snapshot();
        if (str_ends_with($fault, '_veto')) {
            match ($fault) {
                'stock_veto' => Models\Inventory::updating(fn (): bool => false),
                'batch_veto' => Models\InventoryBatch::updating(fn (): bool => false),
                'type_veto' => Models\InventoryTransactionType::creating(fn (): bool => false),
                'ledger_veto' => Models\InventoryTransaction::creating(fn (): bool => false),
                'consumption_veto' => Models\ReagentConsumption::creating(fn (): bool => false),
            };
        } elseif (str_ends_with($fault, '_intent')) {
            match ($fault) {
                'ledger_intent' => Models\InventoryTransaction::creating(function (Models\InventoryTransaction $record): void {
                    $record->qty = '-9.0000';
                }),
                'consumption_intent' => Models\ReagentConsumption::creating(function (Models\ReagentConsumption $record): void {
                    $record->quantity_used = '9.0000';
                }),
                'stock_intent' => Models\Inventory::updating(function (Models\Inventory $record): void {
                    $record->qty_available = '9.0000';
                }),
            };
        } else {
            Models\ReagentConsumption::created(function (Models\ReagentConsumption $record) use ($fault, $lab, $operator, $item, $warehouse, $stock, $batch, $history): void {
                match ($fault) {
                    'late_stock' => DB::table($stock->getTable())->where('id', $stock->id)->update(['qty_available' => '99.0000']),
                    'late_batch' => DB::table($batch->getTable())->where('id', $batch->id)->update(['qty_remaining' => '9.0000']),
                    'late_item' => DB::table($item->getTable())->where('id', $item->id)->update(['name' => 'Altered reagent']),
                    'late_warehouse' => DB::table($warehouse->getTable())->where('id', $warehouse->id)->update(['deleted_at' => now()]),
                    'late_unit' => DB::table((new Models\InventoryUnit)->getTable())->where('id', $item->unit_id)->update(['description' => 'Altered unit']),
                    'late_lab' => DB::table($lab->getTable())->where('id', $lab->id)->update(['name' => 'Altered laboratory']),
                    'late_actor' => DB::table($operator->getTable())->where('id', $operator->id)->update(['name' => 'Altered operator']),
                    'late_membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                    'late_pivot' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->update(['can_manage_branding' => true]),
                    'late_verification' => DB::table($operator->getTable())->where('id', $operator->id)->update(['email_verified_at' => null]),
                    'late_ledger' => DB::table((new Models\InventoryTransaction)->getTable())->where('id', $record->inventory_transaction_id)->update(['notes' => 'Altered ledger']),
                    'late_consumption' => DB::table($record->getTable())->where('id', $record->id)->update(['remarks' => 'Altered consumption']),
                    'retained_history' => DB::table($history->getTable())->where('id', $history->id)->update(['qty' => '-9.0000']),
                    'removed_history' => DB::table($history->getTable())->where('id', $history->id)->delete(),
                    'added_batch' => Models\InventoryBatch::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
                        'batch_number' => 'Unexpected batch', 'qty_received' => '0.5000', 'qty_remaining' => '0.5000']),
                    'changed_type' => DB::table((new Models\InventoryTransactionType)->getTable())->where('code', 'consumption')->update(['name' => 'Altered type']),
                };
            });
        }
        $rejected = false;
        try {
            app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch));
        } catch (LogicException|AuthorizationException|HttpException) {
            $rejected = true;
        }
        $this->assertTrue($rejected, 'Rejected '.$fault.' must abort the complete consumption.');
        $this->assertSame($before, $this->snapshot());
    }

    public static function persistenceFaults(): array
    {
        $faults = ['stock_veto', 'batch_veto', 'type_veto', 'ledger_veto', 'consumption_veto',
            'stock_intent', 'ledger_intent', 'consumption_intent', 'late_stock', 'late_batch', 'late_item',
            'late_warehouse', 'late_unit', 'late_lab', 'late_actor', 'late_membership', 'late_pivot',
            'late_verification', 'late_ledger', 'late_consumption', 'retained_history', 'removed_history',
            'added_batch', 'changed_type'];

        return array_combine($faults, array_map(fn (string $fault): array => [$fault], $faults));
    }

    #[DataProvider('invalidPayloads')]
    public function test_direct_action_validates_before_writing(string $field, mixed $value): void
    {
        [$lab, $operator, $item, $warehouse, , $batch] = $this->fixture();
        $before = $this->snapshot();
        try {
            app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item,
                array_replace($this->payload($warehouse, $batch), [$field => $value]));
            $this->fail('Invalid consumption must fail at the action boundary.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function invalidPayloads(): array
    {
        return ['zero' => ['quantity_used', '0'], 'negative' => ['quantity_used', '-0.0001'],
            'precision' => ['quantity_used', '0.00001'], 'range' => ['quantity_used', '100000000000000'],
            'invalid' => ['quantity_used', 'invalid'], 'blank operator' => ['used_by', ''],
            'remarks' => ['remarks', str_repeat('x', 1001)], 'date' => ['date', 'invalid'],
            'warehouse' => ['warehouse_id', -1], 'batch' => ['batch_id', -1]];
    }

    #[DataProvider('staleStates')]
    public function test_current_source_eligibility_is_not_taken_from_the_bound_item(string $state): void
    {
        [$lab, $operator, $item, $warehouse, , $batch] = $this->fixture();
        match ($state) {
            'archived item' => DB::table($item->getTable())->where('id', $item->id)->update(['deleted_at' => now()]),
            'not reagent' => DB::table((new Models\ItemCategory)->getTable())->where('id', $item->category_id)->update(['name' => 'Other materials']),
            'unit removed' => DB::table($item->getTable())->where('id', $item->id)->update(['unit_id' => null]),
            'archived warehouse' => DB::table($warehouse->getTable())->where('id', $warehouse->id)->update(['deleted_at' => now()]),
        };
        $before = $this->snapshot();
        $rejected = false;
        try {
            app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch));
        } catch (ValidationException|HttpException) {
            $rejected = true;
        }
        $this->assertTrue($rejected);
        $this->assertSame($before, $this->snapshot());
    }

    public static function staleStates(): array
    {
        return array_map(fn (string $state): array => [$state], ['archived item', 'not reagent', 'unit removed', 'archived warehouse']);
    }

    #[DataProvider('revocations')]
    public function test_operator_eligibility_is_rechecked_from_persisted_state(string $state): void
    {
        [$lab, $operator, $item, $warehouse, , $batch] = $this->fixture();
        match ($state) {
            'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
            'verification' => DB::table($operator->getTable())->where('id', $operator->id)->update(['email_verified_at' => null]),
            'activation' => DB::table($operator->getTable())->where('id', $operator->id)->update(['is_active' => false]),
        };
        $before = $this->snapshot();
        try {
            app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch));
            $this->fail('An ineligible operator must not consume stock.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->snapshot());
        }
    }

    public static function revocations(): array
    {
        return [['membership'], ['verification'], ['activation']];
    }

    public function test_exact_fractional_consumption_preserves_retained_evidence_and_ignores_spoofed_fields(): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch, $history] = $this->fixture();
        $historyBefore = (array) DB::table($history->getTable())->where('id', $history->id)->first();
        $result = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item,
            $this->payload($warehouse, $batch) + ['lab_id' => 2147483647, 'user_id' => 2147483647, 'quantity' => 99]);
        $this->assertSame('9.8750', $result['new_quantity']);
        $this->assertSame('9.8750', $stock->fresh()->qty_available);
        $this->assertSame('3.3750', $batch->fresh()->qty_remaining);
        $consumption = $result['consumption'];
        $this->assertSame('0.1250', $consumption->quantity_used);
        $this->assertSame($lab->id, $consumption->lab_id);
        $this->assertSame($operator->id, $consumption->user_id);
        $this->assertSame($batch->id, $consumption->batch_id);
        $this->assertSame('-0.1250', $consumption->inventoryTransaction->qty);
        $this->assertSame($historyBefore, (array) DB::table($history->getTable())->where('id', $history->id)->first());
    }

    public function test_reversal_retains_original_evidence_and_replay_never_credits_twice(): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $sourceBefore = (array) DB::table($consumption->getTable())->where('id', $consumption->id)->first();
        $ledgerBefore = (array) DB::table('itransactions')->where('id', $consumption->inventory_transaction_id)->first();
        $reversal = app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $this->assertSame('3.5000', $batch->fresh()->qty_remaining);
        $this->assertSame('0.1250', $reversal->inventoryTransaction->qty);
        $this->assertTrue($reversal->inventoryTransaction->is_addition);
        $this->assertSame($sourceBefore, (array) DB::table($consumption->getTable())->where('id', $consumption->id)->first());
        $this->assertSame($ledgerBefore, (array) DB::table('itransactions')->where('id', $consumption->inventory_transaction_id)->first());
        $beforeReplay = $this->snapshot();
        $replayed = app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        $this->assertSame($reversal->id, $replayed->id);
        $this->assertSame($beforeReplay, $this->snapshot());
        $this->assertSame(0, Models\ReagentConsumption::query()->unreversed()->count());
        $this->assertSame(1, Models\ReagentConsumption::query()->count());
    }

    #[DataProvider('reversalFaults')]
    public function test_reversal_faults_do_not_credit_stock_or_change_retained_evidence(string $fault): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $before = $this->snapshot();
        if (str_ends_with($fault, '_veto')) {
            match ($fault) {
                'stock_veto' => Models\Inventory::updating(fn (): bool => false),
                'batch_veto' => Models\InventoryBatch::updating(fn (): bool => false),
                'type_veto' => Models\InventoryTransactionType::creating(fn (): bool => false),
                'ledger_veto' => Models\InventoryTransaction::creating(fn (): bool => false),
                'reversal_veto' => Models\ReagentConsumptionReversal::creating(fn (): bool => false),
            };
        } elseif ($fault === 'reversal_intent') {
            Models\ReagentConsumptionReversal::creating(function (Models\ReagentConsumptionReversal $record): void {
                $record->reversed_at = now()->subYear();
            });
        } elseif ($fault === 'ledger_intent') {
            Models\InventoryTransaction::creating(function (Models\InventoryTransaction $record): void {
                $record->qty = '9.0000';
            });
        } else {
            Models\ReagentConsumptionReversal::created(function (Models\ReagentConsumptionReversal $record) use ($fault, $lab, $operator, $consumption, $stock, $batch): void {
                match ($fault) {
                    'late_stock' => DB::table($stock->getTable())->where('id', $stock->id)->update(['qty_available' => '99.0000']),
                    'late_batch' => DB::table($batch->getTable())->where('id', $batch->id)->update(['qty_remaining' => '9.0000']),
                    'late_actor' => DB::table($operator->getTable())->where('id', $operator->id)->update(['name' => 'Altered operator']),
                    'late_membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->delete(),
                    'late_source' => DB::table($consumption->getTable())->where('id', $consumption->id)->update(['remarks' => 'Altered original']),
                    'late_original_ledger' => DB::table('itransactions')->where('id', $consumption->inventory_transaction_id)->update(['deleted_at' => now()]),
                    'late_reversal' => DB::table($record->getTable())->where('id', $record->id)->update(['reversed_at' => now()->subYear()]),
                    'late_movement' => DB::table('itransactions')->where('id', $record->inventory_transaction_id)->update(['qty' => '9.0000']),
                    'late_type' => DB::table((new Models\InventoryTransactionType)->getTable())->where('code', 'consumption_reversal')->update(['name' => 'Altered type']),
                };
            });
        }
        $rejected = false;
        try {
            app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        } catch (LogicException|AuthorizationException|HttpException) {
            $rejected = true;
        }
        $this->assertTrue($rejected, 'Rejected '.$fault.' must abort the complete reversal.');
        $this->assertSame($before, $this->snapshot());
    }

    public static function reversalFaults(): array
    {
        $faults = ['stock_veto', 'batch_veto', 'type_veto', 'ledger_veto', 'reversal_veto', 'reversal_intent', 'ledger_intent',
            'late_stock', 'late_batch', 'late_actor', 'late_membership', 'late_source', 'late_original_ledger',
            'late_reversal', 'late_movement', 'late_type'];

        return array_combine($faults, array_map(fn (string $fault): array => [$fault], $faults));
    }

    #[DataProvider('malformedLedger')]
    public function test_malformed_original_or_replayed_evidence_cannot_credit_stock(string $fault): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $otherOperator = str_contains($fault, 'operator') ? Models\User::factory()->create() : null;
        if (str_starts_with($fault, 'replayed ')) {
            $reversal = app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
            DB::table('itransactions')->where('id', $reversal->inventory_transaction_id)->update(
                $fault === 'replayed quantity' ? ['qty' => '9.0000'] : ['user_id' => $otherOperator->id]);
        } else {
            match ($fault) {
                'quantity' => DB::table('itransactions')->where('id', $consumption->inventory_transaction_id)->update(['qty' => '-9.0000']),
                'batch' => DB::table('itransactions')->where('id', $consumption->inventory_transaction_id)->update(['batch_id' => null]),
                'operator' => DB::table('itransactions')->where('id', $consumption->inventory_transaction_id)->update(['user_id' => $otherOperator->id]),
                'archived ledger' => DB::table('itransactions')->where('id', $consumption->inventory_transaction_id)->update(['deleted_at' => now()]),
                'missing link' => DB::table($consumption->getTable())->where('id', $consumption->id)->update(['inventory_transaction_id' => null]),
                'wrong type' => DB::table('itransactions')->where('id', $consumption->inventory_transaction_id)->update([
                    'type_id' => Models\InventoryTransactionType::query()->where('code', 'stock_in')->value('id')]),
                'duplicate link' => Models\ReagentConsumption::query()->create(array_merge($consumption->getAttributes(), ['id' => null])),
            };
        }
        $before = $this->snapshot();
        try {
            app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
            $this->fail('Malformed evidence must not be reversed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('consumption', $exception->errors());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function malformedLedger(): array
    {
        return array_map(fn (string $fault): array => [$fault], ['quantity', 'batch', 'operator', 'archived ledger', 'missing link', 'wrong type', 'duplicate link', 'replayed quantity', 'replayed operator']);
    }

    #[DataProvider('retainedReversalWriters')]
    public function test_other_checked_inventory_writers_cannot_change_retained_reversals(string $writer, bool $delete): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $reversal = app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        foreach (['add_iitems', 'edit_iitems', 'edit_inventory', 'add_inventory'] as $permission) {
            $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        if ($writer === 'position') {
            $stock->delete();
        }
        $mutate = function () use ($reversal, $delete): void {
            $query = DB::table($reversal->getTable())->where('id', $reversal->id);
            if ($delete) {
                $query->delete();
            } else {
                $query->update(['reversed_at' => now()->subYear()]);
            }
        };
        match ($writer) {
            'create' => Models\InventoryItem::created($mutate),
            'update' => Models\InventoryItem::updated($mutate),
            'adjust' => Models\Inventory::updated($mutate),
            'position' => Models\Inventory::created($mutate),
        };
        $before = $this->snapshot();
        $rejected = false;
        try {
            match ($writer) {
                'create' => app(CreateInventoryItem::class)->execute($lab->id, $operator->id,
                    ['name' => 'New reagent', 'category_id' => $item->category_id, 'unit_id' => $item->unit_id, 'warehouses' => []]),
                'update' => app(UpdateInventoryItem::class)->execute($lab->id, $operator->id, $item->id,
                    ['name' => 'Updated reagent', 'category_id' => $item->category_id, 'unit_id' => $item->unit_id]),
                'adjust' => app(AdjustInventoryItemStock::class)->execute($lab->id, $operator, $item,
                    ['warehouse_id' => $warehouse->id, 'adjustment_type' => 'add', 'quantity' => '0.1250', 'reason' => 'Checked adjustment']),
                'position' => app(CreateInventoryPosition::class)->execute($lab->id, $operator->id,
                    ['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'qty_available' => '0', 'min_stock_level' => '0', 'reorder_point' => '0']),
            };
        } catch (LogicException|ValidationException|HttpException) {
            $rejected = true;
        }
        $this->assertTrue($rejected, $writer.' must retain reversal evidence.');
        $this->assertSame($before, $this->snapshot());
    }

    public static function retainedReversalWriters(): array
    {
        $cases = [];
        foreach (['create', 'update', 'adjust', 'position'] as $writer) {
            foreach ([false, true] as $delete) {
                $cases[$writer.($delete ? '-delete' : '-change')] = [$writer, $delete];
            }
        }

        return $cases;
    }

    public function test_replay_still_requires_current_reversal_permission(): void
    {
        [$lab, $operator, $item, $warehouse, , $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        $operator->revokePermissionTo('delete_reagent_consumption');
        $before = $this->snapshot();
        try {
            app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
            $this->fail('Reversal replay must not bypass current authority.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->snapshot());
        }
    }

    public function test_archived_catalogue_and_warehouse_do_not_prevent_reversal_of_issued_consumption(): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $item->delete();
        $warehouse->delete();
        $reversal = app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $this->assertSame('3.5000', $batch->fresh()->qty_remaining);
        $this->assertSame($stock->id, $reversal->inventoryTransaction->inventory_id);
        $this->assertNotNull($item->fresh()->deleted_at);
        $this->assertNotNull($warehouse->fresh()->deleted_at);
    }

    public function test_reversal_never_credits_a_replacement_for_an_archived_original_position(): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $stock->delete();
        Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '0.0000', 'min_stock_level' => '0.0000', 'reorder_point' => '0.0000', 'status' => 'AVAILABLE']);
        $before = $this->snapshot();
        try {
            app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
            $this->fail('Replacement positions cannot inherit a reversal.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_issued_reversal_cannot_be_edited_deleted_or_removed_by_migration_rollback(): void
    {
        [$lab, $operator, $item, $warehouse, , $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $reversal = app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        $before = $this->snapshot();
        foreach (['update', 'delete', 'rollback'] as $operation) {
            try {
                match ($operation) {
                    'update' => $reversal->update(['reversed_at' => now()->subYear()]),
                    'delete' => $reversal->delete(),
                    'rollback' => (require database_path('migrations/2026_10_02_222759_create_reagent_consumption_reversals_table.php'))->down(),
                };
                $this->fail('Issued reversal evidence must survive '.$operation.'.');
            } catch (LogicException|RuntimeException) {
                $this->assertSame($before, $this->snapshot());
            }
        }
    }

    public function test_database_rejects_duplicate_and_cross_lab_reversal_evidence(): void
    {
        [$lab, $operator, $item, $warehouse, , $batch] = $this->fixture();
        $otherLab = Models\VAPLab::factory()->create();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $reversal = app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        $before = $this->snapshot();
        foreach (['duplicate', 'cross-lab'] as $fault) {
            try {
                DB::transaction(function () use ($reversal, $fault, $otherLab): void {
                    if ($fault === 'duplicate') {
                        DB::table($reversal->getTable())->insert(array_diff_key($reversal->getAttributes(), ['id' => true]));
                    } else {
                        DB::table($reversal->getTable())->where('id', $reversal->id)->update(['lab_id' => $otherLab->id]);
                    }
                });
                $this->fail('Database constraints must reject '.$fault.' evidence.');
            } catch (QueryException $exception) {
                $this->assertSame($fault === 'duplicate' ? '23505' : '23503', $exception->errorInfo[0]);
            }
            $this->assertSame($before, $this->snapshot());
        }
    }

    #[DataProvider('restorationLimits')]
    public function test_reversal_refuses_overflow_without_partial_stock_or_batch_credit(string $target): void
    {
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $record = $target === 'stock' ? $stock : $batch;
        DB::table($record->getTable())->where('id', $record->id)->update([
            $target === 'stock' ? 'qty_available' : 'qty_remaining' => InventoryQuantity::fromScaled(InventoryQuantity::MAX_SCALED)]);
        $before = $this->snapshot();
        try {
            app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
            $this->fail('Overflow must reject the complete reversal.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('consumption', $exception->errors());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public static function restorationLimits(): array
    {
        return [['stock'], ['batch']];
    }

    public function test_reversal_cannot_cross_the_active_laboratory_boundary(): void
    {
        [$lab, $operator, $item, $warehouse, , $batch] = $this->fixture();
        $otherLab = Models\VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $otherLab->id, 'user_id' => $operator->id]);
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        $before = $this->snapshot();
        try {
            app(ReverseInventoryReagentConsumption::class)->execute($otherLab->id, $operator->id, $consumption->id);
            $this->fail('Membership of both laboratories must not permit cross-context reversal.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_permissions_are_separate_and_removed_delete_path_cannot_mutate(): void
    {
        $this->freezeTime();
        [$lab, $operator, $item, $warehouse, $stock, $batch] = $this->fixture();
        $this->actingAs($operator)->withSession(['active_lab_id' => $lab->id]);
        $operator->revokePermissionTo('delete_reagent_consumption');
        $this->get(route('vap-inventory.reagents.consumption.create'))->assertOk();
        $this->postJson(route('vap-inventory.reagents.consume', $item), $this->payload($warehouse, $batch))->assertOk();
        $consumption = Models\ReagentConsumption::query()->sole();
        $before = $this->snapshot();
        $this->post(route('vap-inventory.reagents.consumption.reverse', $consumption))->assertForbidden();
        $this->delete('/vap-inventory/reagents/consumption/'.$consumption->id)->assertMethodNotAllowed();
        $this->assertSame($before, $this->snapshot());
        $operator->givePermissionTo('delete_reagent_consumption');
        $this->post(route('vap-inventory.reagents.consumption.reverse', $consumption))->assertRedirect();
        $this->assertSame('10.0000', $stock->fresh()->qty_available);
        $operator->revokePermissionTo('add_reagent_consumption');
        $before = $this->snapshot();
        $this->get(route('vap-inventory.reagents.consumption.create'))->assertForbidden();
        $this->postJson(route('vap-inventory.reagents.consume', $item), $this->payload($warehouse, $batch))->assertForbidden();
        $this->post(route('vap-inventory.reagents.consumption.store'), ['reagent_id' => $item->id] + $this->payload($warehouse, $batch))->assertForbidden();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_default_permission_registration_is_repeatable_and_grants_no_account_or_role_access(): void
    {
        $operator = Models\User::factory()->create();
        $beforeGrants = [];
        foreach (['role_has_permissions', 'model_has_permissions', 'model_has_roles'] as $table) {
            $beforeGrants[$table] = DB::table($table)->get()->map(fn (object $row): array => (array) $row)->all();
        }
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(2, Models\Permission::query()->where('guard_name', 'web')
            ->whereIn('name', ['add_reagent_consumption', 'delete_reagent_consumption'])->count());
        foreach ($beforeGrants as $table => $before) {
            $this->assertSame($before, DB::table($table)->get()->map(fn (object $row): array => (array) $row)->all());
        }
        $this->assertFalse($operator->fresh()->can('add_reagent_consumption'));
        $this->assertFalse($operator->fresh()->can('delete_reagent_consumption'));
    }

    public function test_reversed_consumption_remains_visible_but_is_excluded_from_net_reports(): void
    {
        [$lab, $operator, $item, $warehouse, , $batch] = $this->fixture();
        foreach (['view_inventory', 'view_iitems', 'view_itransactions'] as $permission) {
            $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        $consumption = app(ConsumeInventoryReagent::class)->execute($lab->id, $operator, $item, $this->payload($warehouse, $batch))['consumption'];
        app(ReverseInventoryReagentConsumption::class)->execute($lab->id, $operator->id, $consumption->id);
        $this->actingAs($operator)->withSession(['active_lab_id' => $lab->id]);
        $response = $this->get(route('vap-inventory.reagents.consumption.index'))->assertOk();
        $this->assertSame(1, data_get($response->viewData('page'), 'props.consumptions.total'));
        $this->assertSame($consumption->id, data_get($response->viewData('page'), 'props.consumptions.data.0.reversal.consumption_id'));
        $this->assertEquals(0, data_get($response->viewData('page'), 'props.stats.total_consumption'));
        $report = $this->get(route('vap-inventory.reports.consumption'))->assertOk();
        $this->assertSame(0, data_get($report->viewData('page'), 'props.consumptions.total'));
        $this->get(route('vap-inventory.reagents.consumption.show', $consumption))->assertOk();
        $movement = $this->get(route('vap-inventory.reports.stock-movement'))->assertOk();
        $this->assertEquals(0.125, data_get($movement->viewData('page'), 'props.stats.total_in'));
        $this->assertEquals(0.125, data_get($movement->viewData('page'), 'props.stats.total_out'));
        $this->assertEquals(0, data_get($movement->viewData('page'), 'props.stats.net_movement'));
        $this->assertEquals([1, 0, 1, 0], data_get($movement->viewData('page'), 'props.charts.type_mix.series'));
        $this->assertEquals([0.125], data_get($movement->viewData('page'), 'props.charts.daily_activity.series.0.data'));
        $this->assertEquals([0.125], data_get($movement->viewData('page'), 'props.charts.daily_activity.series.1.data'));
    }

    /** @return array{Models\VAPLab,Models\User,Models\InventoryItem,Models\InventoryItemWarehouse,Models\Inventory,Models\InventoryBatch,Models\InventoryTransaction} */
    private function fixture(): array
    {
        $lab = Models\VAPLab::factory()->create();
        $operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $operator->givePermissionTo(Models\Permission::findOrCreate('add_reagent_consumption', 'web'));
        $operator->givePermissionTo(Models\Permission::findOrCreate('delete_reagent_consumption', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $operator->id]);
        $category = Models\ItemCategory::query()->create(['name' => 'Reagentes '.fake()->uuid()]);
        $unit = Models\InventoryUnit::query()->create(['code' => 'cons-'.fake()->numerify('######'), 'description' => 'Millilitres']);
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Reagent '.fake()->uuid(),
            'category_id' => $category->id, 'unit_id' => $unit->id, 'is_reagent' => true]);
        $warehouse = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Consumption warehouse']);
        $stock = Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty_available' => '10.0000', 'min_stock_level' => '0.0000', 'reorder_point' => '0.0000', 'status' => 'AVAILABLE']);
        $batch = Models\InventoryBatch::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
            'batch_number' => 'Consumption batch', 'qty_received' => '3.5000', 'qty_remaining' => '3.5000']);
        $type = Models\InventoryTransactionType::query()->firstOrCreate(['code' => 'stock_in'], ['name' => 'Retained type']);
        $history = Models\InventoryTransaction::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id, 'user_id' => $operator->id,
            'warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'type_id' => $type->id, 'qty' => '0.0001', 'reason' => 'Retained evidence']);
        $history->delete();
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);

        return [$lab, $operator, $item, $warehouse, $stock, $batch, $history];
    }

    /** @return array<string,mixed> */
    private function payload(Models\InventoryItemWarehouse $warehouse, Models\InventoryBatch $batch): array
    {
        return ['warehouse_id' => (string) $warehouse->id, 'batch_id' => (string) $batch->id,
            'quantity_used' => '0.1250', 'used_by' => 'Technician', 'date' => today()->toDateString(),
            'used_at' => today()->setTime(10, 15)->format('Y-m-d H:i:s'), 'remarks' => 'Exact consumption'];
    }

    /** @return array<string,list<array<string,mixed>>> */
    private function snapshot(): array
    {
        $snapshot = [];
        foreach ([new Models\VAPLab, new Models\User, new Models\InventoryItem, new Models\InventoryItemWarehouse,
            new Models\InventoryUnit, new Models\ItemCategory, new Models\Inventory, new Models\InventoryBatch, new Models\InventoryTransaction,
            new Models\InventoryTransactionType, new Models\ReagentConsumption, new Models\ReagentConsumptionReversal] as $model) {
            $snapshot[$model->getTable()] = DB::table($model->getTable())->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }
        foreach (['lab_user', 'activity_log'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }
        $snapshot['sequence_counters'] = DB::table('sequence_counters')->orderBy('scope_hash')->get()->map(fn (object $row): array => (array) $row)->all();

        return $snapshot;
    }
}
