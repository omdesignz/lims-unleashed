<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Models\User;
use App\Models\VAPLab;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class InventoryConsumptionEvidence
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @return array<string,mixed> */
    public function lock(int $labId, int $userId, int $itemId, int $warehouseId, string $permission): array
    {
        abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
        $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
        abort_unless($lab, 403);
        $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
        $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
        $context = compact('labId', 'userId', 'permission', 'lab', 'membership', 'actor');
        $context['scope'] = ['item_id' => $itemId, 'warehouse_id' => $warehouseId, 'inventory_ids' => [], 'category_id' => null, 'unit_id' => null];
        $context['rows'] = [];
        foreach ([InventoryItem::class, InventoryItemWarehouse::class, Inventory::class, InventoryBatch::class,
            InventoryItemTransfer::class, InventoryTransaction::class, ReagentConsumption::class, ReagentConsumptionReversal::class,
            ItemCategory::class, InventoryUnit::class, InventoryTransactionType::class] as $class) {
            $context['rows'][$class] = $this->query($class, $context['scope'])->orderBy('id')->lockForUpdate()->toBase()->get()
                ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
            if ($class === InventoryItem::class) {
                $context['scope']['category_id'] = $context['rows'][$class][$itemId]['category_id'] ?? null;
                $context['scope']['unit_id'] = $context['rows'][$class][$itemId]['unit_id'] ?? null;
            }
            if ($class === Inventory::class) {
                $context['scope']['inventory_ids'] = array_keys($context['rows'][$class]);
            }
        }
        $this->assertAuthority($context);

        return $context;
    }

    /** @param array<string,mixed> $context @param class-string<Model> $class */
    public function record(array $context, string $class, int $id): ?Model
    {
        $row = $context['rows'][$class][$id] ?? null;

        return $row === null ? null : $this->hydrate($class, $row);
    }

    /** @param array<string,mixed> $context */
    public function position(array $context): Inventory
    {
        foreach ($context['rows'][Inventory::class] as $id => $row) {
            if ($row['deleted_at'] === null && (int) $row['lab_id'] === $context['labId']) {
                return $this->record($context, Inventory::class, $id);
            }
        }
        abort(404);
    }

    /** @param array<string,mixed> $context */
    public function save(array &$context, Model $record): void
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
            throw new LogicException('Consumption evidence was not persisted.');
        }
        if (! $intended->exists && isset($context['rows'][$record::class][$record->getKey()])) {
            throw new LogicException('Consumption evidence identity was reused.');
        }
        if (! $intended->exists) {
            $intended->setAttribute($record->getKeyName(), $record->getKey());
        }
        $row = (array) DB::table($record->getTable())->where($record->getKeyName(), $record->getKey())->first();
        $expectedColumns = array_keys($intended->getAttributes());
        $storedColumns = array_keys($row);
        sort($expectedColumns);
        sort($storedColumns);
        if ($expectedColumns !== $storedColumns) {
            throw new LogicException('Consumption evidence column set changed.');
        }
        $stored = $this->hydrate($record::class, $row);
        foreach (array_keys($intended->getAttributes()) as $key) {
            $expected = $intended->getAttribute($key);
            $actual = $stored->getAttribute($key);
            if ($expected instanceof DateTimeInterface && $actual instanceof DateTimeInterface) {
                $expected = $expected->format('Y-m-d H:i:s.uP');
                $actual = $actual->format('Y-m-d H:i:s.uP');
            }
            if ($expected !== $actual) {
                throw new LogicException('Consumption evidence changed: '.$record->getTable().'.'.$key.'.');
            }
        }
        $context['rows'][$record::class][$record->getKey()] = $row;
    }

    /** @param array<string,mixed> $context */
    public function type(array &$context, string $code): InventoryTransactionType
    {
        if (! in_array($code, ['consumption', 'consumption_reversal'], true)) {
            throw new LogicException('Unsupported consumption movement type.');
        }
        $row = collect($context['rows'][InventoryTransactionType::class])->firstWhere('code', $code);
        if ($row === null) {
            $type = new InventoryTransactionType(['code' => $code,
                'name' => $code === 'consumption' ? 'Consumo de reagente' : 'Reposição de consumo de reagente',
                'description' => 'Movimento de consumo de reagente registado automaticamente.']);
            $type->deleted_at = null;
            try {
                DB::transaction(function () use (&$context, $type): void {
                    $this->save($context, $type);
                });

                return $type;
            } catch (UniqueConstraintViolationException $exception) {
                $row = InventoryTransactionType::withTrashed()->where('code', $code)->lockForUpdate()->toBase()->first();
                if ($row === null) {
                    throw $exception;
                }
                $row = (array) $row;
                $context['rows'][InventoryTransactionType::class][(int) $row['id']] = $row;
            }
        }
        if ($row['deleted_at'] !== null) {
            throw ValidationException::withMessages(['consumption' => 'O tipo de movimento necessário está arquivado.']);
        }

        return $this->hydrate(InventoryTransactionType::class, $row);
    }

    /** @param array<string,mixed> $context */
    public function assert(array $context): void
    {
        $this->assertAuthority($context);
        foreach ($context['rows'] as $class => $expected) {
            $stored = (new $class)->newQueryWithoutScopes()->where(fn (Builder $query): Builder => $query
                ->whereIn('id', $this->query($class, $context['scope'])->select('id'))->orWhereIn('id', array_keys($expected)))
                ->orderBy('id')->toBase()->get()->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
            ksort($expected);
            if ($stored !== $expected) {
                throw new LogicException('Consumption retained evidence changed: '.(new $class)->getTable().'.');
            }
        }
    }

    /** @param array<string,mixed> $context */
    private function assertAuthority(array $context): void
    {
        $this->access->operator($context['userId'], $context['labId'], $context['permission']);
        abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
        if ((array) VAPLab::withTrashed()->whereKey($context['labId'])->toBase()->first() !== (array) $context['lab']
            || (array) User::withTrashed()->whereKey($context['userId'])->toBase()->first() !== (array) $context['actor']
            || (array) DB::table('lab_user')->where('lab_id', $context['labId'])->where('user_id', $context['userId'])->first() !== (array) $context['membership']) {
            throw new LogicException('Consumption authority evidence changed.');
        }
    }

    /** @param class-string<Model> $class @param array<string,mixed> $scope */
    private function query(string $class, array $scope): Builder
    {
        $query = (new $class)->newQueryWithoutScopes();

        return match ($class) {
            InventoryItem::class => $query->whereKey($scope['item_id']),
            InventoryItemWarehouse::class => $query->whereKey($scope['warehouse_id']),
            Inventory::class => $query->where('item_id', $scope['item_id'])->where('warehouse_id', $scope['warehouse_id']),
            InventoryBatch::class, InventoryTransaction::class => $query->whereIn('inventory_id', $scope['inventory_ids']),
            ReagentConsumption::class => $query->where(fn (Builder $query): Builder => $query
                ->where(fn (Builder $query): Builder => $query->where('reagent_id', $scope['item_id'])->where('warehouse_id', $scope['warehouse_id']))
                ->orWhereIn('inventory_transaction_id', InventoryTransaction::withTrashed()->whereIn('inventory_id', $scope['inventory_ids'])->select('id'))),
            ReagentConsumptionReversal::class => $query->whereIn('consumption_id', ReagentConsumption::query()
                ->where('reagent_id', $scope['item_id'])->where('warehouse_id', $scope['warehouse_id'])->select('id')),
            InventoryItemTransfer::class => $query->where('item_id', $scope['item_id'])->where(fn (Builder $query): Builder => $query
                ->where('source_id', $scope['warehouse_id'])->orWhere('destination_id', $scope['warehouse_id'])),
            ItemCategory::class => $query->whereKey($scope['category_id']),
            InventoryUnit::class => $query->whereKey($scope['unit_id']),
            InventoryTransactionType::class => $query->whereIn('code', ['consumption', 'consumption_reversal']),
        };
    }

    /** @param class-string<Model> $class @param array<string,mixed> $row */
    private function hydrate(string $class, array $row): Model
    {
        $record = new $class;
        $record->setRawAttributes($row, true);
        $record->exists = true;

        return $record;
    }
}
