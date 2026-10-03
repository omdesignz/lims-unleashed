<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLowStockResource extends JsonResource
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
            'item_id' => $this->item_id,
            'warehouse_id' => $this->warehouse_id,
            'qty_available' => $this->qty_available,
            'min_stock_level' => $this->min_stock_level,
            'reorder_point' => $this->reorder_point,
            'item' => [
                'id' => $this->item->id,
                'name' => $this->item->name,
                'code' => $this->item->code,
                'internal_code' => $this->item->internal_code,
                'needs_calibration' => $this->item->needs_calibration,
                'category' => $this->item->category ? [
                    'id' => $this->item->category->id,
                    'name' => $this->item->category->name,
                ] : null,
            ],
            'warehouse' => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'location' => $this->warehouse->location ? [
                    'id' => $this->warehouse->location->id,
                    'name' => $this->warehouse->location->name,
                ] : null,
            ],
        ];
    }
}
