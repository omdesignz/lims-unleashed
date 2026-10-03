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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class AdjustInventoryItemStock
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /**
     * @param  array{warehouse_id: int, adjustment_type: string, quantity: string|int|float, reason: string, notes?: ?string, batch_id?: ?int}  $data
     * @return array{old_quantity: string, new_quantity: string}
     */
    public function execute(int $labId, User $operator, InventoryItem $item, array $data, string $permission = 'edit_inventory', ?int $expectedInventoryId = null): array
    {
        if (! in_array($permission, ['edit_inventory', 'add_inventory'], true)) {
            throw new LogicException('Unsupported stock adjustment permission.');
        }
        $data = Validator::make($data, [
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'adjustment_type' => ['required', 'in:add,remove,set'],
            'quantity' => ['required', 'numeric', 'decimal:0,4', 'min:'.(($data['adjustment_type'] ?? null) === 'set' ? '0' : '0.0001')],
            'reason' => ['required', 'string', 'max:255'], 'notes' => ['nullable', 'string'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
        ])->validate();
        try {
            $quantityScaled = InventoryQuantity::toScaled($data['quantity']);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['quantity' => 'Indique uma quantidade válida com até quatro casas decimais.']);
        }

        return DB::transaction(function () use ($labId, $operator, $item, $data, $quantityScaled, $permission, $expectedInventoryId): array {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $userId = $operator->id;
            $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
            abort_unless($lab, 403);
            $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
            $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
            $scope = ['item_id' => $item->id, 'warehouse_id' => (int) $data['warehouse_id'], 'inventory_ids' => [],
                'type_code' => 'stock_adjustment_'.$data['adjustment_type']];
            $evidence = [];
            foreach ([InventoryItem::class, InventoryItemWarehouse::class, Inventory::class, InventoryBatch::class,
                InventoryItemTransfer::class, InventoryTransaction::class, ReagentConsumption::class, ReagentConsumptionReversal::class, InventoryTransactionType::class] as $class) {
                $evidence[$class] = $this->evidenceQuery($class, $scope)->orderBy('id')->lockForUpdate()->toBase()->get()
                    ->map(fn (object $row): Model => $this->fromRow($class, $row))->keyBy('id');
                if ($class === Inventory::class) {
                    $scope['inventory_ids'] = $evidence[$class]->keys()->all();
                }
            }
            $operator = $this->access->operator($userId, $labId, $permission);
            $item = $evidence[InventoryItem::class]->get($scope['item_id']);
            $warehouse = $evidence[InventoryItemWarehouse::class]->get($scope['warehouse_id']);
            abort_unless($item && (int) $item->lab_id === $labId && ! $item->trashed()
                && $warehouse && (int) $warehouse->lab_id === $labId && ! $warehouse->trashed(), 404);
            if ($item->unit_id === null) {
                throw ValidationException::withMessages(['unit_id' => 'Defina a unidade do item antes de ajustar existências.']);
            }
            $inventory = $evidence[Inventory::class]->first(fn (Inventory $record): bool => ! $record->trashed());
            abort_unless($inventory && (int) $inventory->lab_id === $labId
                && ($expectedInventoryId === null || $inventory->id === $expectedInventoryId), 404);
            $inventory = clone $inventory;

            $batch = null;
            if (isset($data['batch_id'])) {
                $batch = $evidence[InventoryBatch::class]->get((int) $data['batch_id']);
                if ($batch === null || (int) $batch->lab_id !== $labId || $batch->inventory_id !== $inventory->id) {
                    throw ValidationException::withMessages(['batch_id' => 'O lote não pertence às existências seleccionadas.']);
                }
                $batch = clone $batch;
            }

            $oldQuantityScaled = InventoryQuantity::toScaled($inventory->qty_available);
            $oldBatchQuantityScaled = $batch === null ? null : InventoryQuantity::toScaled($batch->qty_remaining);
            $adjustedQuantityScaled = match ($data['adjustment_type']) {
                'add' => ($oldBatchQuantityScaled ?? $oldQuantityScaled) + $quantityScaled,
                'remove' => ($oldBatchQuantityScaled ?? $oldQuantityScaled) - $quantityScaled,
                'set' => $quantityScaled,
            };
            $newQuantityScaled = $batch === null
                ? $adjustedQuantityScaled
                : $oldQuantityScaled + $adjustedQuantityScaled - $oldBatchQuantityScaled;

            if ($adjustedQuantityScaled < 0 || $newQuantityScaled < 0) {
                throw ValidationException::withMessages(['quantity' => 'As existências não podem ficar com quantidade negativa.']);
            }
            if ($adjustedQuantityScaled > InventoryQuantity::MAX_SCALED || $newQuantityScaled > InventoryQuantity::MAX_SCALED) {
                throw ValidationException::withMessages(['quantity' => 'A quantidade excede o limite permitido.']);
            }

            if ($batch === null) {
                $reservedQuantityScaled = InventoryQuantity::toScaled(
                    $evidence[InventoryBatch::class]->where('inventory_id', $inventory->id)->reduce(
                        fn (string $sum, InventoryBatch $record): string => InventoryQuantity::add($sum, $record->qty_remaining), '0.0000')
                );
                if ($newQuantityScaled < $reservedQuantityScaled) {
                    throw ValidationException::withMessages(['quantity' => 'A quantidade livre não cobre este ajuste; existem lotes com saldo reservado.']);
                }
            }

            $oldQuantity = InventoryQuantity::fromScaled($oldQuantityScaled);
            $quantity = InventoryQuantity::fromScaled($quantityScaled);
            $newQuantity = InventoryQuantity::fromScaled($newQuantityScaled);
            $inventory->qty_available = $newQuantity;
            $this->saveEvidence($inventory, $evidence);
            if ($batch !== null) {
                $batch->qty_remaining = InventoryQuantity::fromScaled($adjustedQuantityScaled);
                $this->saveEvidence($batch, $evidence);
            }

            $transactionType = $evidence[InventoryTransactionType::class]->first();
            if ($transactionType === null) {
                $transactionType = new InventoryTransactionType([
                    'code' => $scope['type_code'],
                    'name' => match ($data['adjustment_type']) {
                        'add' => 'Adição às existências',
                        'remove' => 'Remoção das existências',
                        'set' => 'Rectificação das existências',
                    },
                    'description' => 'Tipo de ajuste de existências registado automaticamente.',
                ]);
                $transactionType->deleted_at = null;
                try {
                    DB::transaction(fn () => $this->saveEvidence($transactionType, $evidence));
                } catch (UniqueConstraintViolationException $exception) {
                    $row = InventoryTransactionType::query()->where('code', $scope['type_code'])->lockForUpdate()->toBase()->first();
                    if (! $row) {
                        throw $exception;
                    }
                    $transactionType = $this->fromRow(InventoryTransactionType::class, $row);
                    $evidence[InventoryTransactionType::class][$transactionType->id] = $transactionType;
                }
            }
            if ($transactionType->trashed()) {
                throw ValidationException::withMessages(['quantity' => 'O tipo de movimento necessário está arquivado.']);
            }

            $transaction = new InventoryTransaction([
                'lab_id' => $labId,
                'inventory_id' => $inventory->id,
                'user_id' => $operator->id,
                'warehouse_id' => $inventory->warehouse_id,
                'item_id' => $item->id,
                'type_id' => $transactionType->id,
                'batch_id' => $batch?->id,
                'qty' => $quantity,
                'notes' => $data['adjustment_type'] === 'set'
                    ? trim(($data['notes'] ?? '').($batch === null
                        ? ' Saldo anterior: '.$oldQuantity.'; saldo novo: '.$newQuantity.'.'
                        : ' Lote anterior: '.InventoryQuantity::fromScaled($oldBatchQuantityScaled).'; lote novo: '.InventoryQuantity::fromScaled($adjustedQuantityScaled).'; existências anteriores: '.$oldQuantity.'; existências novas: '.$newQuantity.'.'))
                    : ($data['notes'] ?? null),
                'reason' => $data['reason'],
            ]);
            $transaction->deleted_at = null;
            $this->saveEvidence($transaction, $evidence);
            $this->access->operator($userId, $labId, $permission);
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            if ((array) VAPLab::withTrashed()->whereKey($labId)->toBase()->first() !== (array) $lab
                || (array) User::withTrashed()->whereKey($userId)->toBase()->first() !== (array) $actor
                || (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->first() !== (array) $membership) {
                throw new LogicException('Stock adjustment authority evidence changed.');
            }
            $this->assertEvidence($scope, $evidence);

            return ['old_quantity' => $oldQuantity, 'new_quantity' => $newQuantity];
        });
    }

    /** @param class-string<Model> $class @param array{item_id:int,warehouse_id:int,inventory_ids:list<int>,type_code:string} $scope */
    private function evidenceQuery(string $class, array $scope): Builder
    {
        $query = (new $class)->newQueryWithoutScopes();

        return match ($class) {
            InventoryItem::class => $query->whereKey($scope['item_id']),
            InventoryItemWarehouse::class => $query->whereKey($scope['warehouse_id']),
            Inventory::class => $query->where('item_id', $scope['item_id'])->where('warehouse_id', $scope['warehouse_id']),
            InventoryBatch::class, InventoryTransaction::class => $query->whereIn('inventory_id', $scope['inventory_ids']),
            ReagentConsumption::class => $query->where('reagent_id', $scope['item_id'])->where('warehouse_id', $scope['warehouse_id']),
            ReagentConsumptionReversal::class => $query->whereIn('consumption_id', ReagentConsumption::query()
                ->where('reagent_id', $scope['item_id'])->where('warehouse_id', $scope['warehouse_id'])->select('id')),
            InventoryItemTransfer::class => $query->where('item_id', $scope['item_id'])->where(fn (Builder $query): Builder => $query
                ->where('source_id', $scope['warehouse_id'])->orWhere('destination_id', $scope['warehouse_id'])),
            InventoryTransactionType::class => $query->where('code', $scope['type_code']),
        };
    }

    /** @param class-string<Model> $class */
    private function fromRow(string $class, object $row): Model
    {
        $model = new $class;
        $model->setRawAttributes((array) $row, true);
        $model->exists = true;

        return $model;
    }

    /** @param array<class-string<Model>,Collection<int,Model>> $evidence */
    private function saveEvidence(Model $record, array &$evidence): void
    {
        $timestamp = $record->freshTimestamp();
        if ($record->getUpdatedAtColumn() !== null) {
            $record->setUpdatedAt($timestamp);
        }
        if (! $record->exists && $record->getCreatedAtColumn() !== null) {
            $record->setCreatedAt($timestamp);
        }
        $intended = clone $record;
        if (! $record::withoutTimestamps(fn (): bool => $record->save()) || ! $record->exists || ! $record->getKey()) {
            throw new LogicException('Stock adjustment evidence was not persisted.');
        }
        if (! $intended->exists && $evidence[$record::class]->has($record->getKey())) {
            throw new LogicException('Stock adjustment evidence identity was reused.');
        }
        $intended->setAttribute($record->getKeyName(), $record->getKey());
        $row = DB::table($record->getTable())->where($record->getKeyName(), $record->getKey())->first();
        $this->assertRecord($intended, $row === null ? null : $this->fromRow($record::class, $row));
        $evidence[$record::class][$record->getKey()] = $intended;
    }

    /** @param array{item_id:int,warehouse_id:int,inventory_ids:list<int>,type_code:string} $scope
     * @param  array<class-string<Model>,Collection<int,Model>>  $evidence
     */
    private function assertEvidence(array $scope, array $evidence): void
    {
        foreach ($evidence as $class => $expected) {
            $stored = (new $class)->newQueryWithoutScopes()->where(fn (Builder $query): Builder => $query
                ->whereIn('id', $this->evidenceQuery($class, $scope)->select('id'))->orWhereIn('id', $expected->keys()->all()))
                ->orderBy('id')->toBase()->get()->map(fn (object $row): Model => $this->fromRow($class, $row))->keyBy('id');
            if ($stored->keys()->sort()->values()->all() !== $expected->keys()->sort()->values()->all()) {
                throw new LogicException('Stock adjustment evidence set changed.');
            }
            foreach ($expected as $id => $intended) {
                $this->assertRecord($intended, $stored->get($id));
            }
        }
    }

    private function assertRecord(Model $intended, ?Model $stored): void
    {
        if ($stored === null) {
            throw new LogicException('Stock adjustment evidence disappeared.');
        }
        foreach (array_keys($intended->getAttributes()) as $key) {
            $expected = $intended->getAttribute($key);
            $actual = $stored->getAttribute($key);
            if ($expected instanceof DateTimeInterface && $actual instanceof DateTimeInterface) {
                $expected = $expected->format('Y-m-d H:i:s.uP');
                $actual = $actual->format('Y-m-d H:i:s.uP');
            }
            if ($expected !== $actual) {
                throw new LogicException('Stock adjustment evidence changed: '.$intended->getTable().'.'.$key.'.');
            }
        }
    }
}
