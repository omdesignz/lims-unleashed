<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemSupplierResource extends JsonResource
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
            'address' => $this->address,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('isuppliers.edit', $this->id),
                'delete_path' => route('isuppliers.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('isuppliers.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
