<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryOrderResource extends JsonResource
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
            'date' => $this->date,
            'supplier_id' => $this->supplier_id,
            'user_id' => $this->user_id,
            'reference' => $this->reference,
            'status' => $this->status,
            'supplier' => InventoryItemSupplierResource::make($this->supplier)?->name ?? null,
            'user' => UserResource::make($this->user)?->name ?? null,
            'items' => InventoryOrderDetailResource::collection($this->whenLoaded('items')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('iorders.edit', $this->id),
                'delete_path' => route('iorders.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('iorders.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
