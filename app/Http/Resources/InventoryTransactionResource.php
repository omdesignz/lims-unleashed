<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
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
            'inventory_id' => $this->inventory_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user')?->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse')?->name,
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item')?->name,
            'type_id' => $this->type_id,
            'type' => $this->whenLoaded('type')?->name,
            'type_code' => $this->whenLoaded('type')?->code,
            'is_addition' => $this->is_addition,
            'is_deduction' => $this->is_deduction,
            'qty' => $this->qty,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'deleted' => (bool) $this->deleted_at,
        ];
    }
}
