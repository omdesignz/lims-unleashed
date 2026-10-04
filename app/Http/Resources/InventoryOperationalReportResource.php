<?php

namespace App\Http\Resources;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryOperationalReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->item;
        $itemData = $item ? [
            'id' => $item->id, 'name' => $item->name, 'code' => $item->code,
            'category' => $item->category ? ['id' => $item->category->id, 'name' => $item->category->name] : null,
        ] : null;
        $warehouse = $this->warehouse ? ['id' => $this->warehouse->id, 'name' => $this->warehouse->name] : null;
        if ($warehouse !== null && $this->warehouse->relationLoaded('location')) {
            $warehouse['location'] = $this->warehouse->location ? ['id' => $this->warehouse->location->id, 'name' => $this->warehouse->location->name] : null;
        }
        $data = [
            'id' => $this->id,
            'item' => $itemData,
            'warehouse' => $warehouse,
        ];
        if ($this->resource instanceof Inventory) {
            $data['item']['unit'] = $item->unit ? ['id' => $item->unit->id, 'code' => $item->unit->code] : null;
            $data['item']['standard_cost'] = $item->standard_cost;
            $data['item']['last_purchase_price'] = $item->last_purchase_price;

            return $data + $this->resource->only(['item_id', 'warehouse_id', 'qty_available']);
        }

        $data['user'] = $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null;
        if ($this->resource instanceof InventoryTransaction) {
            return $data + $this->resource->only(['item_id', 'warehouse_id', 'created_at', 'qty', 'notes', 'reason']) + [
                'is_addition' => $this->is_addition,
                'is_deduction' => $this->is_deduction,
                'type' => $this->type ? ['id' => $this->type->id, 'name' => $this->type->name, 'code' => $this->type->code] : null,
            ];
        }

        return $data + $this->resource->only(['reagent_id', 'reagent_name', 'warehouse_id', 'date', 'quantity_used', 'used_by', 'remarks']);
    }
}
