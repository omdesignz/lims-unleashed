<?php

namespace App\Http\Resources;

use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'qty_available' => $this->qty_available,
            'min_stock_level' => $this->min_stock_level,
            'reorder_point' => $this->reorder_point,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => InventoryItemWarehouseResource::make($this->warehouse)?->name ?? null,
            'item_id' => $this->item_id,
            'item' => InventoryItemResource::make($this->item)?->name ?? null,
            'category' => $this->item?->category?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('inventory.edit', $this->id),
                'delete_path' => route('inventory.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('inventory.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
