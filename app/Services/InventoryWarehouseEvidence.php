<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryDeliveryDetail;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeedItem;
use App\Models\InventoryOrderDetail;
use App\Models\InventoryTransaction;
use App\Models\ISOActivityLog;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InventoryWarehouseEvidence
{
    /** @return array<class-string,list<string>> */
    private function referenceModels(): array
    {
        return [Inventory::class => ['warehouse_id'], InventoryItemTransfer::class => ['source_id', 'destination_id'],
            InventoryOrderDetail::class => ['warehouse_id'], InventoryDeliveryDetail::class => ['warehouse_id'],
            InventoryNeedItem::class => ['warehouse_id'], InventoryTransaction::class => ['warehouse_id'], ReagentConsumption::class => ['warehouse_id']];
    }

    /** @param list<int> $recordIds */
    public function assertUnused(array $recordIds): void
    {
        foreach ($this->referenceModels() as $class => $columns) {
            if ((new $class)->newQueryWithoutScopes()->where(function (Builder $query) use ($columns, $recordIds): void {
                foreach ($columns as $column) {
                    $query->orWhereIn($column, $recordIds);
                }
            })->toBase()->exists()) {
                throw ValidationException::withMessages(['recordIds' => 'O armazém tem movimentos ou referências e não pode ser arquivado.']);
            }
        }
    }

    /** @param list<int> $recordIds
     * @param  array<class-string,array<int,array<string,mixed>>>  $retained
     * @return array<class-string,array<int,array<string,mixed>>>
     */
    public function references(array $recordIds, array $retained = []): array
    {
        $references = [];
        foreach ($this->referenceModels() as $class => $columns) {
            $references[$class] = (new $class)->newQueryWithoutScopes()->where(function (Builder $query) use ($columns, $recordIds, $retained, $class): void {
                foreach ($columns as $column) {
                    $query->orWhereIn($column, $recordIds);
                }
                $query->orWhereIn('id', array_keys($retained[$class] ?? []));
            })->orderBy('id')->lockForUpdate()->toBase()->get()->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
        }
        foreach ([[InventoryBatch::class, 'inventory_id', Inventory::class],
            [ReagentConsumptionReversal::class, 'consumption_id', ReagentConsumption::class]] as [$class, $column, $parent]) {
            $references[$class] = (new $class)->newQueryWithoutScopes()->where(function (Builder $query) use ($class, $column, $parent, $references, $retained): void {
                $query->whereIn($column, array_keys($references[$parent]))->orWhereIn('id', array_keys($retained[$class] ?? []));
                if ($class === ReagentConsumptionReversal::class) {
                    $query->orWhereIn('inventory_transaction_id', array_keys($references[InventoryTransaction::class]));
                }
            })
                ->orderBy('id')->lockForUpdate()->toBase()->get()->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
        }

        return $references;
    }

    /** @param list<int> $recordIds @param list<int> $retainedIds
     * @return array<int,array<string,mixed>>
     */
    public function history(array $recordIds, array $retainedIds = []): array
    {
        return ISOActivityLog::withoutGlobalScopes()->where(function (Builder $query) use ($recordIds, $retainedIds): void {
            $query->where(fn (Builder $query): Builder => $query->whereIn('subject_id', $recordIds)
                ->whereIn('subject_type', [(new InventoryItemWarehouse)->getMorphClass(), InventoryItemWarehouse::class]))
                ->orWhereIn('id', $retainedIds);
        })->orderBy('id')->lockForUpdate()->toBase()->get()->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
    }

    /** @param array<string,mixed> $attributes @return array<string,mixed> */
    public function canonicalAudit(array $attributes): array
    {
        $attributes['properties'] = is_string($attributes['properties']) ? json_decode($attributes['properties'], true, flags: JSON_THROW_ON_ERROR) : $attributes['properties'];
        if (is_array($attributes['properties'])) {
            ksort($attributes['properties']);
        }
        ksort($attributes);

        return $attributes;
    }
}
