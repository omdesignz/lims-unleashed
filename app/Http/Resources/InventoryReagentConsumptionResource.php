<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryReagentConsumptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->item;
        $itemData = $item ? (new InventoryReagentChoiceResource($item))->resolve($request) : null;
        if ($itemData !== null && $request->routeIs('vap-inventory.reagents.consumption.show')) {
            $itemData += $item->only(['internal_code', 'brand', 'model', 'serial_number']);
            $itemData['supplier'] = $item->supplier ? ['id' => $item->supplier->id, 'name' => $item->supplier->name] : null;
        }
        $warehouse = $this->warehouse ? ['id' => $this->warehouse->id, 'name' => $this->warehouse->name] : null;
        if ($warehouse !== null && $this->warehouse->relationLoaded('location')) {
            $warehouse['location'] = $this->warehouse->location ? ['id' => $this->warehouse->location->id, 'name' => $this->warehouse->location->name] : null;
        }
        $reversal = $this->reversal;

        return $this->resource->only(['id', 'reagent_id', 'reagent_name', 'quantity_used', 'used_by', 'used_at', 'remarks', 'date', 'user_id', 'warehouse_id', 'created_at']) + [
            'item' => $itemData,
            'warehouse' => $warehouse,
            'user' => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null,
            'reversal' => $reversal ? $reversal->only(['id', 'consumption_id', 'inventory_transaction_id', 'user_id', 'reversed_at']) + [
                'user' => $reversal->user ? ['id' => $reversal->user->id, 'name' => $reversal->user->name] : null,
            ] : null,
        ];
    }
}
