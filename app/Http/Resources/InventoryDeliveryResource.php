<?php

namespace App\Http\Resources;

use App\Models\InventoryDeliveryDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryDeliveryResource extends JsonResource
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
            'sales_date' => $this->sales_date,
            'customer_id' => $this->customer_id,
            'customer' => CustomerResource::make($this->customer)?->name ?? null,
            'items' => InventoryDeliveryDetailResource::collection($this->whenLoaded('items')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('ideliveries.edit', $this->id),
                'delete_path' => route('ideliveries.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('ideliveries.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
