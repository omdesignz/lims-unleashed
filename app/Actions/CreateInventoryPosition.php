<?php

namespace App\Actions;

use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\InventoryQuantity;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class CreateInventoryPosition
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access, private readonly AdjustInventoryItemStock $adjustStock) {}

    /** @param array{item_id:int,warehouse_id:int,qty_available?:string|int|float,min_stock_level:string|int|float,reorder_point:string|int|float} $data */
    public function execute(int $labId, int $userId, array $data): Inventory
    {
        $data = Validator::make($data, [
            'item_id' => ['required', 'integer', 'min:1'], 'warehouse_id' => ['required', 'integer', 'min:1'],
            'qty_available' => ['sometimes', 'numeric', 'decimal:0,4', 'min:0'],
            'min_stock_level' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'reorder_point' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
        ])->validate();
        $data['item_id'] = (int) $data['item_id'];
        $data['warehouse_id'] = (int) $data['warehouse_id'];
        foreach (['qty_available', 'min_stock_level', 'reorder_point'] as $field) {
            try {
                $data[$field] = InventoryQuantity::fromScaled(InventoryQuantity::toScaled($data[$field] ?? '0'));
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([$field => 'Indique uma quantidade válida com até quatro casas decimais.']);
            }
        }

        return DB::transaction(function () use ($labId, $userId, $data): Inventory {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
            abort_unless($lab, 403);
            $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
            $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
            $itemRow = InventoryItem::query()->where('lab_id', $labId)->whereKey($data['item_id'])->lockForUpdate()->toBase()->first();
            $warehouse = InventoryItemWarehouse::query()->where('lab_id', $labId)->whereKey($data['warehouse_id'])->lockForUpdate()->toBase()->first();
            $positions = $this->positions($data['item_id'], $data['warehouse_id']);
            $references = $this->references($data['item_id'], $data['warehouse_id'], array_keys($positions));
            $operator = $this->access->operator($userId, $labId, 'add_inventory');
            abort_unless($itemRow && $warehouse, 404);
            if ($itemRow->unit_id === null) {
                throw ValidationException::withMessages(['item_id' => 'Defina a unidade do item antes de criar existências.']);
            }
            if (Inventory::query()->where('item_id', $data['item_id'])->where('warehouse_id', $data['warehouse_id'])->toBase()->exists()) {
                throw ValidationException::withMessages(['item_id' => 'Já existe uma posição activa para este item e armazém.']);
            }
            $inventory = new Inventory([
                'lab_id' => $labId, 'item_id' => $data['item_id'], 'warehouse_id' => $data['warehouse_id'],
                'qty_available' => '0.0000', 'min_stock_level' => $data['min_stock_level'],
                'reorder_point' => $data['reorder_point'], 'status' => 'AVAILABLE',
            ]);
            $inventory->deleted_at = null;
            $inventory->setCreatedAt($inventory->freshTimestamp());
            $inventory->setUpdatedAt($inventory->created_at);
            $intended = clone $inventory;
            if (! Inventory::withoutTimestamps(fn (): bool => $inventory->save()) || ! $inventory->exists || ! $inventory->id) {
                throw new LogicException('Inventory position was not persisted.');
            }
            $intended->id = $inventory->id;
            $intended->exists = true;
            $this->assertPosition($intended);
            if (isset($positions[$inventory->id])) {
                throw new LogicException('Inventory position identity was reused.');
            }
            $positionIds = [...array_keys($positions), $inventory->id];
            $typeEvidence = null;
            if ($this->references($data['item_id'], $data['warehouse_id'], $positionIds, $references) !== $references) {
                throw new LogicException('Inventory position creation changed retained or child evidence.');
            }
            if (InventoryQuantity::compare($data['qty_available'], '0') > 0) {
                $item = new InventoryItem;
                $item->setRawAttributes((array) $itemRow, true);
                $item->exists = true;
                $this->adjustStock->execute($labId, $operator, $item, [
                    'warehouse_id' => $data['warehouse_id'], 'adjustment_type' => 'add',
                    'quantity' => $data['qty_available'], 'reason' => 'Existências iniciais',
                ], 'add_inventory', $inventory->id);
                $intended->qty_available = $data['qty_available'];
                $intended->updated_at = DB::table($inventory->getTable())->where('id', $inventory->id)->value('updated_at');
                $storedReferences = $this->references($data['item_id'], $data['warehouse_id'], $positionIds, $references);
                $newLedgers = array_diff_key($storedReferences[InventoryTransaction::class], $references[InventoryTransaction::class]);
                if (count($newLedgers) !== 1 || (int) reset($newLedgers)['inventory_id'] !== $inventory->id) {
                    throw new LogicException('Inventory opening must persist exactly one intended ledger.');
                }
                $references[InventoryTransaction::class] += $newLedgers;
                ksort($references[InventoryTransaction::class]);
                $typeEvidence = InventoryTransactionType::withTrashed()->whereKey(reset($newLedgers)['type_id'])
                    ->lockForUpdate()->toBase()->first();
                if (! $typeEvidence) {
                    throw new LogicException('Inventory opening type evidence disappeared.');
                }
            }
            $this->access->operator($userId, $labId, 'add_inventory');
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            if ((array) VAPLab::withTrashed()->whereKey($labId)->toBase()->first() !== (array) $lab
                || (array) User::withTrashed()->whereKey($userId)->toBase()->first() !== (array) $actor
                || (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->first() !== (array) $membership
                || (array) InventoryItem::withTrashed()->whereKey($itemRow->id)->toBase()->first() !== (array) $itemRow
                || (array) InventoryItemWarehouse::withTrashed()->whereKey($warehouse->id)->toBase()->first() !== (array) $warehouse) {
                throw new LogicException('Inventory position authority or ownership evidence changed.');
            }
            $this->assertPosition($intended);
            $storedPositions = $this->positions($data['item_id'], $data['warehouse_id'], array_keys($positions));
            unset($storedPositions[$inventory->id]);
            if ($storedPositions !== $positions
                || $this->references($data['item_id'], $data['warehouse_id'], $positionIds, $references) !== $references
                || ($typeEvidence !== null && (array) InventoryTransactionType::withTrashed()->whereKey($typeEvidence->id)->toBase()->first() !== (array) $typeEvidence)) {
                throw new LogicException('Inventory position retained evidence changed.');
            }
            $intended->wasRecentlyCreated = true;
            $intended->syncOriginal();

            return $intended;
        });
    }

    /** @param list<int> $retainedIds @return array<int,array<string,mixed>> */
    private function positions(int $itemId, int $warehouseId, array $retainedIds = []): array
    {
        return Inventory::withTrashed()->where(fn (Builder $query): Builder => $query
            ->where(fn (Builder $query): Builder => $query->where('item_id', $itemId)->where('warehouse_id', $warehouseId))
            ->orWhereIn('id', $retainedIds))->orderBy('id')->lockForUpdate()->toBase()->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
    }

    /** @param list<int> $positionIds
     * @param  array<class-string,array<int,array<string,mixed>>>  $retained
     * @return array<class-string,array<int,array<string,mixed>>>
     */
    private function references(int $itemId, int $warehouseId, array $positionIds, array $retained = []): array
    {
        $references = [];
        foreach ([InventoryBatch::class, InventoryItemTransfer::class, InventoryTransaction::class, ReagentConsumption::class, ReagentConsumptionReversal::class] as $class) {
            $references[$class] = (new $class)->newQueryWithoutScopes()->where(function (Builder $query) use ($class, $itemId, $warehouseId, $positionIds, $retained): void {
                if ($class === InventoryItemTransfer::class) {
                    $query->where('item_id', $itemId)->where(fn (Builder $query): Builder => $query
                        ->where('source_id', $warehouseId)->orWhere('destination_id', $warehouseId));
                } elseif ($class === ReagentConsumptionReversal::class) {
                    $query->whereIn('consumption_id', ReagentConsumption::query()->where('reagent_id', $itemId)->where('warehouse_id', $warehouseId)->select('id'));
                } elseif ($class === ReagentConsumption::class) {
                    $query->where('reagent_id', $itemId)->where('warehouse_id', $warehouseId);
                } else {
                    $query->whereIn('inventory_id', $positionIds);
                }
                $query->orWhereIn('id', array_keys($retained[$class] ?? []));
            })->orderBy('id')->lockForUpdate()->toBase()->get()
                ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
        }

        return $references;
    }

    private function assertPosition(Inventory $intended): void
    {
        $row = Inventory::withTrashed()->whereKey($intended->id)->toBase()->first();
        if (! $row) {
            throw new LogicException('Inventory position disappeared.');
        }
        $stored = new Inventory;
        $stored->setRawAttributes((array) $row, true);
        foreach (array_keys($intended->getAttributes()) as $key) {
            $expected = $intended->getAttribute($key);
            $actual = $stored->getAttribute($key);
            if ($expected instanceof DateTimeInterface && $actual instanceof DateTimeInterface) {
                $expected = $expected->format('Y-m-d H:i:s.uP');
                $actual = $actual->format('Y-m-d H:i:s.uP');
            }
            if ($expected !== $actual) {
                throw new LogicException('Inventory position evidence changed: '.$key.'.');
            }
        }
    }
}
