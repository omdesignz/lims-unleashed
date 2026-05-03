<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryOrderDetailResource extends JsonResource
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
            'qty' => $this->qty,
            'expected_date' => $this->expected_date,
            'actual_date' => $this->actual_date,
            'item_id' => $this->item_id,
            'item' => InventoryItemResource::make($this->item)?->name ?? null,
            'order_id' => $this->order_id,
            'order' => InventoryOrderResource::make($this->order)?->date ?? null,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => InventoryItemWarehouseResource::make($this->warehouse)?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                // 'edit_path' => route('iorderdetails.edit', $this->id),
                // 'delete_path' => route('iorderdetails.destroy', [
                //     'recordIds' => [$this->id]
                // ]),
                // 'restore_path' => route('iorderdetails.restore', [
                //     'recordIds' => [$this->id]
                // ]),
            ]
        ];
    }
}
