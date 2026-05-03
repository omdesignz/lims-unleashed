<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentCategoryResource extends JsonResource
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
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'parent' => EquipmentCategoryResource::make($this->whenLoaded('parent'))?->name ?? null,
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('equipmentcategories.edit', $this->id),
                'delete_path' => route('equipmentcategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('equipmentcategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
