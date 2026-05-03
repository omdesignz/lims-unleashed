<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name,
            'warehouse_id' => WarehouseResource::make($this->whenLoaded('warehouse'))?->name ?? null,
            'processed' => $this->processed,
            'recollection' => $this->recollection,
            'collectionable_id' => $this->collectionable_id,
            'collectionable_type' => $this->collectionable_type,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('collections.edit', $this->id),
                'delete_path' => route('collections.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('collections.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
