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
            'user' => $this->whenLoaded('user')->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse')->address,
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item')->name,
            'type_id' => $this->type_id,
            'type' => $this->whenLoaded('type')->name,
            'qty' => $this->qty,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'links' => [
                'edit_path' => route('itransactions.edit', $this->id),
                'delete_path' => route('itransactions.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('itransactions.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
