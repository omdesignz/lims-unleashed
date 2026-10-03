<?php

namespace App\Actions;

use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\User;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\InventoryQuantity;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class ManageInventoryTransfers
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param array{item_id: int, source_id: int, destination_id: int, qty: string|int|float, sent_date?: ?string, expected_date?: ?string, obs?: ?string} $data */
    public function create(int $labId, User $operator, array $data): InventoryItemTransfer
    {
        return $this->createMany($labId, $operator, [$data + ['obs' => null]], $data['sent_date'] ?? null, $data['expected_date'] ?? null)[0];
    }

    /**
     * @param  array<int, array{item_id: int, source_id: int, destination_id: int, qty: string|int|float}>  $rows
     * @return array<int, InventoryItemTransfer>
     */
    public function createMany(int $labId, User $operator, array $rows, ?string $sentDate, ?string $expectedDate): array
    {
        return DB::transaction(function () use ($labId, $operator, $rows, $sentDate, $expectedDate): array {
            $operator = $this->access->operator($operator->id, $labId, 'add_itransfers');
            if ($rows === [] || count($rows) > 100) {
                throw ValidationException::withMessages(['transfers' => 'Indique entre uma e cem transferências.']);
            }
            [$scope, $evidence] = $this->lockEvidence($labId, $rows, ['stock_out']);
            $transfers = [];

            foreach ($rows as $row) {
                $transfers[] = $this->createWithinTransaction($labId, $operator, $row + [
                    'sent_date' => $sentDate,
                    'expected_date' => $expectedDate,
                    'obs' => 'Transferência em lote',
                ], $evidence);
            }
            $this->access->operator($operator->id, $labId, 'add_itransfers');
            $this->assertEvidence($scope, $evidence);

            return $transfers;
        });
    }

    public function receive(int $labId, User $operator, InventoryItemTransfer $transfer, string|int|float $actualQty, string $receivedDate, ?string $notes): InventoryItemTransfer
    {
        return DB::transaction(function () use ($labId, $operator, $transfer, $actualQty, $receivedDate, $notes): InventoryItemTransfer {
            $operator = $this->access->operator($operator->id, $labId, 'edit_itransfers');
            $transfer = $this->lockedTransfer($labId, $transfer->id);
            $actualQty = $this->positiveQuantity($actualQty, 'actual_qty');
            if ($transfer->received_date !== null || $transfer->sent_date === null || InventoryQuantity::compare($actualQty, $transfer->qty) > 0) {
                throw ValidationException::withMessages(['actual_qty' => 'Esta transferência não pode ser recepcionada novamente.']);
            }

            $difference = InventoryQuantity::subtract($transfer->qty, $actualQty);
            $typeCodes = InventoryQuantity::compare($difference, '0') > 0 ? ['stock_in', 'stock_adjustment_add'] : ['stock_in'];
            [$scope, $evidence] = $this->lockEvidence($labId, [$transfer->getAttributes()], $typeCodes, false);
            $destinationInventory = $this->stock($evidence, $transfer->item_id, $transfer->destination_id);
            if ($destinationInventory === null) {
                $destinationInventory = new Inventory(['lab_id' => $labId, 'item_id' => $transfer->item_id,
                    'warehouse_id' => $transfer->destination_id, 'qty_available' => '0.0000', 'min_stock_level' => '0.0000',
                    'reorder_point' => '0.0000', 'status' => 'AVAILABLE']);
                $destinationInventory->deleted_at = null;
            }
            $this->changeStock($destinationInventory, $actualQty, $evidence);
            $scope['inventory_ids'][] = $destinationInventory->id;
            $this->recordTransaction($destinationInventory, $operator, 'stock_in', $actualQty,
                'Transfer from '.$evidence[InventoryItemWarehouse::class][$transfer->source_id]->name, $notes, $evidence);

            if (InventoryQuantity::compare($difference, '0') > 0) {
                $sourceInventory = $this->stock($evidence, $transfer->item_id, $transfer->source_id);
                if ($sourceInventory === null) {
                    throw new LogicException('Transfer source stock is unavailable.');
                }
                $this->changeStock($sourceInventory, $difference, $evidence);
                $this->recordTransaction($sourceInventory, $operator, 'stock_adjustment_add', $difference,
                    'Transfer quantity difference', 'Expected: '.$transfer->qty.', received: '.$actualQty, $evidence);
            }

            $transfer->fill([
                'received_date' => $receivedDate,
                'obs' => trim(($transfer->obs ? $transfer->obs."\n" : '').'Recepcionado: '.$actualQty.($notes ? ' — '.$notes : '')),
            ]);
            $this->saveEvidence($transfer, $evidence);
            $this->access->operator($operator->id, $labId, 'edit_itransfers');
            $this->assertEvidence($scope, $evidence);

            return $transfer;
        });
    }

    public function cancel(int $labId, User $operator, InventoryItemTransfer $transfer, ?string $notes): void
    {
        DB::transaction(function () use ($labId, $operator, $transfer, $notes): void {
            $operator = $this->access->operator($operator->id, $labId, 'delete_itransfers');
            $transfer = $this->lockedTransfer($labId, $transfer->id);
            if ($transfer->received_date !== null) {
                throw ValidationException::withMessages(['transfer' => 'Não é possível cancelar uma transferência recepcionada.']);
            }

            [$scope, $evidence] = $this->lockEvidence($labId, [$transfer->getAttributes()], ['stock_adjustment_add'], false);
            $sourceInventory = $this->stock($evidence, $transfer->item_id, $transfer->source_id);
            if ($sourceInventory === null) {
                throw new LogicException('Transfer source stock is unavailable.');
            }
            $this->changeStock($sourceInventory, $transfer->qty, $evidence);
            $this->recordTransaction($sourceInventory, $operator, 'stock_adjustment_add', $transfer->qty,
                'Transferência cancelada', $notes ?: 'Transferência n.º '.$transfer->id.' cancelada', $evidence);
            $transfer->fill(['obs' => trim(($transfer->obs ? $transfer->obs."\n" : '').'CANCELADA: '.($notes ?: 'Sem motivo indicado'))]);
            $this->saveEvidence($transfer, $evidence);
            $intended = clone $evidence[InventoryItemTransfer::class][$transfer->id];
            $started = now()->startOfSecond();
            if (! InventoryItemTransfer::withoutTimestamps(fn (): ?bool => $transfer->delete())) {
                throw new LogicException('Transfer cancellation was not persisted.');
            }
            $stored = $this->fromRow(InventoryItemTransfer::class, DB::table($transfer->getTable())->where('id', $intended->id)->first());
            if (! $stored?->deleted_at || $stored->deleted_at->lt($started) || $stored->deleted_at->gt(now())) {
                throw new LogicException('Transfer cancellation evidence changed.');
            }
            $intended->deleted_at = $transfer->getRawOriginal('deleted_at');
            $evidence[InventoryItemTransfer::class][$intended->id] = $intended;
            $this->access->operator($operator->id, $labId, 'delete_itransfers');
            $this->assertEvidence($scope, $evidence);
        });
    }

    /** @param array{item_id: int, source_id: int, destination_id: int, qty: string|int|float, sent_date?: ?string, expected_date?: ?string, obs?: ?string} $data */
    private function createWithinTransaction(int $labId, User $operator, array $data, array &$evidence): InventoryItemTransfer
    {
        if (! $evidence[InventoryItem::class][$data['item_id']]->unit_id) {
            throw ValidationException::withMessages(['item_id' => 'Defina a unidade do item antes de transferir existências.']);
        }

        $sourceInventory = $this->stock($evidence, $data['item_id'], $data['source_id']);
        $quantity = $this->positiveQuantity($data['qty'], 'qty');
        if ($sourceInventory === null || InventoryQuantity::compare($sourceInventory->qty_available, $quantity) < 0) {
            throw ValidationException::withMessages(['qty' => 'Existências insuficientes no armazém de origem.']);
        }
        if (InventoryQuantity::compare(
            InventoryQuantity::subtract($sourceInventory->qty_available, $quantity),
            $evidence[InventoryBatch::class]->where('inventory_id', $sourceInventory->id)->reduce(
                fn (string $sum, InventoryBatch $batch): string => InventoryQuantity::add($sum, $batch->qty_remaining), '0.0000')
        ) < 0) {
            throw ValidationException::withMessages(['qty' => 'A quantidade livre não cobre esta transferência; existem lotes com saldo reservado.']);
        }

        $transfer = new InventoryItemTransfer([
            'lab_id' => $labId,
            'item_id' => $data['item_id'],
            'source_id' => $data['source_id'],
            'destination_id' => $data['destination_id'],
            'qty' => $quantity,
            'sent_date' => $data['sent_date'] ?? now()->toDateString(),
            'received_date' => null,
            'expected_date' => $data['expected_date'] ?? null,
            'obs' => $data['obs'] ?? null,
        ]);
        $transfer->deleted_at = null;
        $this->saveEvidence($transfer, $evidence);
        $this->changeStock($sourceInventory, '-'.$quantity, $evidence);
        $this->recordTransaction($sourceInventory, $operator, 'stock_out', $quantity,
            'Transfer to '.$evidence[InventoryItemWarehouse::class][$data['destination_id']]->name, $data['obs'] ?? null, $evidence);

        return $transfer;
    }

    private function recordTransaction(Inventory $inventory, User $operator, string $typeCode, string|int|float $quantity, string $reason, ?string $notes, array &$evidence): void
    {
        $type = $evidence[InventoryTransactionType::class]->firstWhere('code', $typeCode);
        if (! $type) {
            $type = new InventoryTransactionType(['code' => $typeCode, 'name' => ucfirst(str_replace('_', ' ', $typeCode)),
                'description' => 'Tipo de transacção de inventário registado automaticamente.']);
            $type->deleted_at = null;
            try {
                DB::transaction(fn () => $this->saveEvidence($type, $evidence));
            } catch (UniqueConstraintViolationException $exception) {
                $type = $this->fromRow(InventoryTransactionType::class,
                    InventoryTransactionType::query()->where('code', $typeCode)->lockForUpdate()->toBase()->first());
                if (! $type) {
                    throw $exception;
                }
                $evidence[InventoryTransactionType::class][$type->id] = $type;
            }
        }
        if ($type->trashed()) {
            throw ValidationException::withMessages(['transfer' => 'O tipo de movimento necessário está arquivado.']);
        }
        $transaction = new InventoryTransaction([
            'lab_id' => $inventory->lab_id,
            'inventory_id' => $inventory->id,
            'user_id' => $operator->id,
            'warehouse_id' => $inventory->warehouse_id,
            'item_id' => $inventory->item_id,
            'type_id' => $type->id,
            'qty' => $quantity,
            'reason' => $reason,
            'notes' => $notes,
            'batch_id' => null,
        ]);
        $transaction->deleted_at = null;
        $this->saveEvidence($transaction, $evidence);
    }

    /** @param list<array<string,mixed>> $rows @param list<string> $typeCodes @return array{array<string,list<int|string>>, array<class-string<Model>,Collection<int,Model>>} */
    private function lockEvidence(int $labId, array $rows, array $typeCodes, bool $requireLiveItem = true): array
    {
        $scope = ['item_ids' => collect($rows)->pluck('item_id')->unique()->sort()->values()->all(),
            'warehouse_ids' => collect($rows)->flatMap(fn (array $row): array => [$row['source_id'], $row['destination_id']])->unique()->sort()->values()->all(),
            'inventory_ids' => [], 'type_codes' => $typeCodes];
        $evidence = [];
        foreach ([InventoryItem::class, InventoryItemWarehouse::class, Inventory::class, InventoryItemTransfer::class,
            InventoryBatch::class, InventoryTransaction::class, InventoryTransactionType::class] as $class) {
            $evidence[$class] = $this->evidenceQuery($class, $scope)->orderBy('id')->lockForUpdate()->toBase()->get()
                ->map(fn (object $row): Model => $this->fromRow($class, $row))->keyBy('id');
            if ($class === Inventory::class) {
                $scope['inventory_ids'] = $evidence[$class]->keys()->all();
            }
        }
        foreach ($rows as $row) {
            if ($row['source_id'] === $row['destination_id']
                || ! $evidence[InventoryItemWarehouse::class]->has($row['source_id'])
                || ! $evidence[InventoryItemWarehouse::class]->has($row['destination_id'])
                || $evidence[InventoryItemWarehouse::class][$row['source_id']]->lab_id !== $labId
                || $evidence[InventoryItemWarehouse::class][$row['destination_id']]->lab_id !== $labId
                || $evidence[InventoryItemWarehouse::class][$row['source_id']]->trashed()
                || $evidence[InventoryItemWarehouse::class][$row['destination_id']]->trashed()) {
                throw ValidationException::withMessages(['destination_id' => 'Escolha dois armazéns diferentes do laboratório activo.']);
            }
            $item = $evidence[InventoryItem::class]->get($row['item_id']);
            if (! $item || $item->lab_id !== $labId || ($requireLiveItem && $item->trashed()) || ! $item->unit_id) {
                throw ValidationException::withMessages(['item_id' => 'Defina a unidade de um item activo deste laboratório antes de transferir existências.']);
            }
        }

        return [$scope, $evidence];
    }

    /** @param class-string<Model> $class @param array<string,list<int|string>> $scope */
    private function evidenceQuery(string $class, array $scope): Builder
    {
        $query = (new $class)->newQueryWithoutScopes();

        return match ($class) {
            InventoryItem::class => $query->whereKey($scope['item_ids']),
            InventoryItemWarehouse::class => $query->whereKey($scope['warehouse_ids']),
            Inventory::class, InventoryTransaction::class => $query->whereIn('item_id', $scope['item_ids'])->whereIn('warehouse_id', $scope['warehouse_ids']),
            InventoryItemTransfer::class => $query->whereIn('item_id', $scope['item_ids'])->where(fn (Builder $query): Builder => $query
                ->whereIn('source_id', $scope['warehouse_ids'])->orWhereIn('destination_id', $scope['warehouse_ids'])),
            InventoryBatch::class => $query->whereIn('inventory_id', $scope['inventory_ids']),
            InventoryTransactionType::class => $query->whereIn('code', $scope['type_codes']),
        };
    }

    /** @param array<class-string<Model>,Collection<int,Model>> $evidence */
    private function stock(array $evidence, int $itemId, int $warehouseId): ?Inventory
    {
        $stock = $evidence[Inventory::class]->first(fn (Inventory $stock): bool => $stock->item_id === $itemId && $stock->warehouse_id === $warehouseId && ! $stock->trashed());

        return $stock === null ? null : clone $stock;
    }

    private function changeStock(Inventory $stock, string $difference, array &$evidence): void
    {
        $quantity = InventoryQuantity::toScaled($stock->qty_available) + InventoryQuantity::toScaled($difference);
        if ($quantity < 0 || $quantity > InventoryQuantity::MAX_SCALED) {
            throw ValidationException::withMessages(['qty' => 'O saldo das existências excede os limites permitidos.']);
        }
        $stock->qty_available = InventoryQuantity::fromScaled($quantity);
        $this->saveEvidence($stock, $evidence);
    }

    private function positiveQuantity(string|int|float $quantity, string $field): string
    {
        try {
            $scaled = InventoryQuantity::toScaled($quantity);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([$field => 'Indique uma quantidade válida com até quatro casas decimais.']);
        }
        if ($scaled <= 0) {
            throw ValidationException::withMessages([$field => 'Indique uma quantidade positiva.']);
        }

        return InventoryQuantity::fromScaled($scaled);
    }

    private function lockedTransfer(int $labId, int $id): InventoryItemTransfer
    {
        $row = InventoryItemTransfer::query()->where('lab_id', $labId)->whereKey($id)->lockForUpdate()->toBase()->first();
        if (! $row) {
            abort(404);
        }

        return $this->fromRow(InventoryItemTransfer::class, $row);
    }

    /** @param class-string<Model> $class */
    private function fromRow(string $class, ?object $row): ?Model
    {
        if ($row === null) {
            return null;
        }
        $model = new $class;
        $model->setRawAttributes((array) $row, true);
        $model->exists = true;

        return $model;
    }

    /** @param array<class-string<Model>,Collection<int,Model>> $evidence */
    private function saveEvidence(Model $record, array &$evidence): void
    {
        $record->updated_at = now();
        if (! $record->exists) {
            $record->created_at = $record->updated_at;
        }
        $intended = clone $record;
        if (! $record::withoutTimestamps(fn (): bool => $record->save()) || ! $record->exists || ! $record->getKey()) {
            throw new LogicException('Inventory transfer evidence was not persisted.');
        }
        $intended->setAttribute($record->getKeyName(), $record->getKey());
        $intended->exists = true;
        $stored = $this->fromRow($record::class, DB::table($record->getTable())->where($record->getKeyName(), $record->getKey())->first());
        $this->assertRecord($intended, $stored);
        $evidence[$record::class][$record->getKey()] = $intended;
    }

    /** @param array<string,list<int|string>> $scope @param array<class-string<Model>,Collection<int,Model>> $evidence */
    private function assertEvidence(array $scope, array $evidence): void
    {
        foreach ($evidence as $class => $expected) {
            $stored = (new $class)->newQueryWithoutScopes()->where(fn (Builder $query): Builder => $query
                ->whereIn('id', $this->evidenceQuery($class, $scope)->select('id'))->orWhereIn('id', $expected->keys()->all()))
                ->orderBy('id')->toBase()->get()->map(fn (object $row): Model => $this->fromRow($class, $row))->keyBy('id');
            if ($stored->keys()->sort()->values()->all() !== $expected->keys()->sort()->values()->all()) {
                throw new LogicException('Inventory transfer evidence set changed.');
            }
            foreach ($expected as $id => $intended) {
                $this->assertRecord($intended, $stored->get($id));
            }
        }
    }

    private function assertRecord(Model $intended, ?Model $stored): void
    {
        if ($stored === null) {
            throw new LogicException('Inventory transfer evidence disappeared.');
        }
        foreach (array_keys($intended->getAttributes()) as $key) {
            $expected = $intended->getAttribute($key);
            $actual = $stored->getAttribute($key);
            if ($expected instanceof DateTimeInterface && $actual instanceof DateTimeInterface) {
                $expected = $expected->format('Y-m-d H:i:s.uP');
                $actual = $actual->format('Y-m-d H:i:s.uP');
            }
            if ($expected !== $actual) {
                throw new LogicException('Inventory transfer evidence changed: '.$intended->getTable().'.'.$key.'.');
            }
        }
    }
}
