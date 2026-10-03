<?php

namespace App\Services;

use App\Models\InventoryItemLocation;
use App\Models\InventoryItemWarehouse;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class InventoryWarehouseValidation
{
    /** @return array<string,array<mixed>> */
    public function rules(int $labId, ?InventoryItemWarehouse $warehouse = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique((new InventoryItemWarehouse)->getTable(), 'name')
                ->where('lab_id', $labId)->whereNull('deleted_at')->ignore($warehouse?->id)],
            'is_refrigerated' => ['required', 'boolean'],
            'is_ventilated' => ['required', 'boolean'],
            'has_air_exhaustion' => ['required', 'boolean'],
            'location_id' => ['required', 'integer', Rule::exists((new InventoryItemLocation)->getTable(), 'id')
                ->where(fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                    ->whereNull('deleted_at')->orWhere('id', $warehouse?->location_id)))],
        ];
    }
}
