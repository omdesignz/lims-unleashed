<?php

namespace Tests\Feature;

use App\Actions\ManageInventoryTransfers;
use App\Models;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryTransferIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('persistenceFaults')]
    public function test_transfer_faults_roll_back_balances_root_and_ledger(string $stage, string $fault): void
    {
        if ($fault === 'late_timestamp') {
            $this->assertSame('lims_unleashed_test', DB::connection()->getDatabaseName());
            DB::statement('ALTER TABLE "inventory" ALTER COLUMN "updated_at" TYPE timestamp(6) without time zone');
        }
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $action = app(ManageInventoryTransfers::class);
        $payload = $this->payload($item, $source, $destination);
        $transfer = in_array($stage, ['receive', 'cancel'], true) ? $action->create($lab->id, $operator, $payload) : null;
        $type = Models\InventoryTransactionType::query()->firstOrCreate(['code' => 'stock_in'], ['name' => 'Entrada']);
        $retained = Models\InventoryTransaction::query()->create(['lab_id' => $lab->id, 'inventory_id' => $stock->id,
            'user_id' => $operator->id, 'warehouse_id' => $source->id, 'item_id' => $item->id,
            'type_id' => $type->id, 'qty' => '0.0001', 'reason' => 'Retained evidence']);
        $before = $this->snapshot($lab->id);
        if ($fault === 'stock_veto') {
            Models\Inventory::updating(fn (): bool => false);
        } elseif ($fault === 'transfer_veto') {
            $event = match ($stage) {
                'create', 'bulk' => 'creating', 'receive' => 'updating', 'cancel' => 'deleting'
            };
            Models\InventoryItemTransfer::{$event}(fn (): bool => false);
        } elseif ($fault === 'ledger_veto') {
            Models\InventoryTransaction::creating(fn (): bool => false);
        } elseif ($fault === 'type_veto') {
            Models\InventoryTransactionType::creating(fn (): bool => false);
        } elseif ($fault === 'ledger_quantity') {
            Models\InventoryTransaction::creating(function (Models\InventoryTransaction $record): void {
                $record->qty = '9.0000';
            });
        } else {
            Models\InventoryTransaction::created(function (Models\InventoryTransaction $record) use ($fault, $stock, $transfer, $operator, $retained): void {
                if ($fault === 'late_stock') {
                    DB::table($stock->getTable())->where('id', $stock->id)->update(['qty_available' => '99.0000']);
                } elseif ($fault === 'late_transfer') {
                    DB::table((new Models\InventoryItemTransfer)->getTable())->where('id', $transfer?->id ?? Models\InventoryItemTransfer::query()->latest('id')->value('id'))
                        ->update(['qty' => '9.0000']);
                } elseif ($fault === 'late_authority') {
                    DB::table('lab_user')->where('user_id', $operator->id)->delete();
                } elseif ($fault === 'late_batch') {
                    Models\InventoryBatch::query()->firstOrCreate(['inventory_id' => $stock->id, 'batch_number' => 'Unexpected'],
                        ['lab_id' => $stock->lab_id, 'qty_received' => '0.5000', 'qty_remaining' => '0.5000']);
                } elseif ($fault === 'late_timestamp') {
                    DB::table($stock->getTable())->where('id', $stock->id)->update(['updated_at' => DB::raw("updated_at + interval '1 microsecond'")]);
                } else {
                    DB::table($retained->getTable())->where('id', $retained->id)->update(['qty' => '9.0000']);
                }
            });
        }
        $rejected = false;
        try {
            match ($stage) {
                'create' => $action->create($lab->id, $operator, $payload),
                'bulk' => $action->createMany($lab->id, $operator, [$payload, $payload], today()->toDateString(), null),
                'receive' => $action->receive($lab->id, $operator, $transfer, '2.0001', today()->toDateString(), 'Received evidence'),
                'cancel' => $action->cancel($lab->id, $operator, $transfer, 'Cancelled evidence'),
            };
        } catch (LogicException|AuthorizationException $exception) {
            if ($exception instanceof LogicException) {
                $this->assertMatchesRegularExpression('/Inventory transfer evidence|Transfer cancellation/', $exception->getMessage());
            }
            $rejected = true;
        }
        $this->assertTrue($rejected, 'The transfer must reject '.$stage.'/'.$fault.' before commit.');
        $this->assertSame($before, $this->snapshot($lab->id));
        $this->assertTrue(DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $operator->id)->exists());
    }

    public static function persistenceFaults(): array
    {
        $cases = [];
        foreach (['create', 'bulk', 'receive', 'cancel'] as $stage) {
            foreach (['stock_veto', 'transfer_veto', 'ledger_veto', 'type_veto', 'ledger_quantity', 'late_stock', 'late_transfer', 'late_authority', 'late_history', 'late_batch', 'late_timestamp'] as $fault) {
                $cases[$stage.'-'.$fault] = [$stage, $fault];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidReceiptQuantities')]
    public function test_action_rejects_invalid_receipt_quantities_without_relying_on_http_validation(string $quantity): void
    {
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $action = app(ManageInventoryTransfers::class);
        $transfer = $action->create($lab->id, $operator, $this->payload($item, $source, $destination));
        $before = $this->snapshot($lab->id);
        try {
            $action->receive($lab->id, $operator, $transfer, $quantity, today()->toDateString(), null);
            $this->fail('Invalid receipt must be rejected by the canonical action.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actual_qty', $exception->errors());
        }
        $this->assertSame($before, $this->snapshot($lab->id));
    }

    public static function invalidReceiptQuantities(): array
    {
        return ['zero' => ['0'], 'negative' => ['-0.0001'], 'excess precision' => ['0.00001'], 'out of range' => ['100000000000000']];
    }

    public function test_unrelated_shared_transaction_type_creation_does_not_invalidate_a_transfer(): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        Models\InventoryTransaction::created(function (): void {
            Models\InventoryTransactionType::query()->firstOrCreate(['code' => 'stock_in'], ['name' => 'Entrada']);
        });
        $transfer = app(ManageInventoryTransfers::class)->create($lab->id, $operator, $this->payload($item, $source, $destination));
        $this->assertModelExists($transfer);
        $this->assertSame('6.8766', $stock->fresh()->qty_available);
        $this->assertSame(1, Models\InventoryTransaction::query()->where('lab_id', $lab->id)->count());
        $this->assertSame(['stock_in', 'stock_out'], Models\InventoryTransactionType::query()->whereIn('code', ['stock_in', 'stock_out'])->orderBy('code')->pluck('code')->all());
    }

    #[DataProvider('terminalStages')]
    public function test_archived_issued_item_can_settle_its_existing_transfer_but_cannot_issue_another(string $stage): void
    {
        [$lab, $operator, $item, $source, $destination, $stock] = $this->fixture();
        $action = app(ManageInventoryTransfers::class);
        $transfer = $action->create($lab->id, $operator, $this->payload($item, $source, $destination));
        $item->delete();
        $archived = $item->fresh()->getAttributes();
        $before = $this->snapshot($lab->id);
        try {
            $action->create($lab->id, $operator, $this->payload($item, $source, $destination));
            $this->fail('Archived material cannot issue a new transfer.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('item_id', $exception->errors());
        }
        $this->assertSame($before, $this->snapshot($lab->id));
        if ($stage === 'receive') {
            $action->receive($lab->id, $operator, $transfer, '2.0001', today()->toDateString(), null);
            $this->assertSame('7.9999', $stock->fresh()->qty_available);
            $this->assertSame('2.0001', Models\Inventory::query()->where('item_id', $item->id)->where('warehouse_id', $destination->id)->sole()->qty_available);
            $this->assertNotNull($transfer->fresh()->received_date);
        } else {
            $action->cancel($lab->id, $operator, $transfer, null);
            $this->assertSame('10.0000', $stock->fresh()->qty_available);
            $this->assertTrue($transfer->fresh()->trashed());
        }
        $this->assertSame($archived, $item->fresh()->getAttributes());
    }

    public static function terminalStages(): array
    {
        return ['receipt' => ['receive'], 'cancellation' => ['cancel']];
    }

    public function test_subsecond_change_to_generated_cancellation_evidence_rolls_back(): void
    {
        $this->assertSame('lims_unleashed_test', DB::connection()->getDatabaseName());
        DB::statement('ALTER TABLE "i_transfers" ALTER COLUMN "deleted_at" TYPE timestamp(6) without time zone');
        [$lab, $operator, $item, $source, $destination] = $this->fixture();
        $action = app(ManageInventoryTransfers::class);
        $transfer = $action->create($lab->id, $operator, $this->payload($item, $source, $destination));
        $before = $this->snapshot($lab->id);
        Models\InventoryItemTransfer::deleted(function (Models\InventoryItemTransfer $record): void {
            DB::table($record->getTable())->where('id', $record->id)->update(['deleted_at' => DB::raw("deleted_at + interval '1 microsecond'")]);
        });
        try {
            $action->cancel($lab->id, $operator, $transfer, 'Cancellation evidence');
            $this->fail('Altered generated evidence must not commit.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('Inventory transfer evidence changed', $exception->getMessage());
        }
        $this->assertSame($before, $this->snapshot($lab->id));
    }

    /** @return array{Models\VAPLab, Models\User, Models\InventoryItem, Models\InventoryItemWarehouse, Models\InventoryItemWarehouse, Models\Inventory} */
    private function fixture(): array
    {
        $lab = Models\VAPLab::factory()->create();
        $operator = Models\User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['add_itransfers', 'edit_itransfers', 'delete_itransfers'] as $permission) {
            $operator->givePermissionTo(Models\Permission::findOrCreate($permission, 'web'));
        }
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $operator->id]);
        $category = Models\ItemCategory::query()->create(['name' => 'Transfer '.fake()->uuid()]);
        $unit = Models\InventoryUnit::query()->create(['code' => 'tr-'.fake()->numerify('######'), 'description' => 'Millilitres']);
        $item = Models\InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Transfer material', 'category_id' => $category->id, 'unit_id' => $unit->id]);
        $source = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Source']);
        $destination = Models\InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Destination']);
        $stock = Models\Inventory::query()->create(['lab_id' => $lab->id, 'item_id' => $item->id, 'warehouse_id' => $source->id,
            'qty_available' => '10.0000', 'min_stock_level' => '0.0000', 'reorder_point' => '0.0000', 'status' => 'AVAILABLE']);
        Event::fake([fn (string $event): bool => str_starts_with($event, 'App\\Events\\')]);

        return [$lab, $operator, $item, $source, $destination, $stock];
    }

    private function payload(Models\InventoryItem $item, Models\InventoryItemWarehouse $source, Models\InventoryItemWarehouse $destination): array
    {
        return ['item_id' => $item->id, 'source_id' => $source->id, 'destination_id' => $destination->id,
            'qty' => '3.1234', 'sent_date' => today()->toDateString(), 'obs' => 'Transfer evidence'];
    }

    private function snapshot(int $labId): array
    {
        $snapshot = [];
        foreach ([new Models\Inventory, new Models\InventoryItemTransfer, new Models\InventoryTransaction, new Models\InventoryBatch] as $model) {
            $snapshot[$model->getTable()] = DB::table($model->getTable())->where('lab_id', $labId)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
        }

        return $snapshot;
    }
}
