<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryDeliveryDetailResource extends JsonResource
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
            'delivery_id' => $this->delivery_id,
            'delivery' => InventoryDeliveryResource::make($this->delivery)?->sales_date ?? null,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => InventoryItemWarehouseResource::make($this->warehouse)?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('ideliverydetails.edit', $this->id),
                'delete_path' => route('ideliverydetails.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('ideliverydetails.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
