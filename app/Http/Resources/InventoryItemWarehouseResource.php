<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemWarehouseResource extends JsonResource
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
            'name' => $this->name,
            'is_refrigerated' => $this->is_refrigerated,
            'is_ventilated' => $this->is_ventilated,
            'has_air_exhaustion' => $this->has_air_exhaustion,
            'location_id' => $this->location_id,
            'location' => InventoryItemLocationResource::make($this->location)?->address ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('iwarehouses.edit', $this->id),
                'delete_path' => route('iwarehouses.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('iwarehouses.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
