<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'description' => $this->description,
            'code' => $this->code,
            'category_id' => CustomerCategoryResource::make($this->category),
            'category' => CustomerCategoryResource::make($this->category)?->name ?? null,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => WarehouseResource::make($this->whenLoaded('main_warehouse')),
            'warehouses' => WarehouseResource::collection($this->whenLoaded('warehouses')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('customers.edit', $this->id),
                'delete_path' => route('customers.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('customers.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
