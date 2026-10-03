<?php

namespace App\Services;

use App\Models\IntegrationConnector;
use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryDeliveryDetail;
use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryNeedItem;
use App\Models\InventoryOrderDetail;
use App\Models\InventoryTransaction;
use App\Models\ISOActivityLog;
use App\Models\MaintenanceTask;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Models\UncertaintySource;
use App\Models\VAPNonConformity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryItemEvidence
{
    /** @param array<class-string<Model>,array<int,array<string,mixed>>> $expected @param list<int> $newDocumentIds
     * @return array<class-string<Model>,array<int,array<string,mixed>>> */
    public function graph(int $itemId, array $expected = [], array $newDocumentIds = []): array
    {
        $graph = [];
        foreach ([Inventory::class => 'item_id', InventoryTransaction::class => 'item_id', InventoryItemTransfer::class => 'item_id',
            ReagentConsumption::class => 'reagent_id', ReagentConsumptionReversal::class => 'consumption_id',
            InventoryBatch::class => 'inventory_id', InventoryOrderDetail::class => 'item_id',
            InventoryDeliveryDetail::class => 'item_id', InventoryNeedItem::class => 'inventory_item_id', MaintenanceTask::class => 'equipment_id',
            UncertaintySource::class => 'inventory_item_id', IntegrationConnector::class => 'inventory_item_id',
            VAPNonConformity::class => 'equipment_id', InventoryItemDocumentMedia::class => 'model_id'] as $class => $field) {
            $query = (new $class)->newQueryWithoutScopes()->where(function (Builder $query) use ($class, $field, $itemId, $graph, $expected): void {
                if ($class === ReagentConsumptionReversal::class) {
                    $query->whereIn($field, array_keys($graph[ReagentConsumption::class]))
                        ->orWhereIn('inventory_transaction_id', array_keys($graph[InventoryTransaction::class]));
                } elseif ($class === InventoryBatch::class) {
                    $query->whereIn($field, array_keys($graph[Inventory::class]));
                } elseif ($class === InventoryItemDocumentMedia::class) {
                    $query->where('model_type', (new InventoryItem)->getMorphClass())->where($field, $itemId);
                } else {
                    $query->where($field, $itemId);
                }
                $query->orWhereIn('id', array_keys($expected[$class] ?? []));
            });
            if ($class === InventoryItemDocumentMedia::class) {
                $query->whereNotIn('id', $newDocumentIds);
            }
            $graph[$class] = $query->orderBy('id')->lockForUpdate()->toBase()->get()->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
        }

        return $graph;
    }

    /** @param list<int> $recordIds @param list<int> $retainedIds
     * @return array<int,array<string,mixed>> */
    public function history(array $recordIds, array $retainedIds = []): array
    {
        return ISOActivityLog::withoutGlobalScopes()->where(function (Builder $query) use ($recordIds, $retainedIds): void {
            $query->where(fn (Builder $query): Builder => $query->whereIn('subject_id', $recordIds)
                ->whereIn('subject_type', [(new InventoryItem)->getMorphClass(), InventoryItem::class]))
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
