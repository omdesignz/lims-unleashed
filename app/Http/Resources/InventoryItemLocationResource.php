<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemLocationResource extends JsonResource
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
            'address' => $this->address,
            'department_id' => $this->department_id,
            'department' => DepartmentResource::make($this->whenLoaded('department'))?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('ilocations.edit', $this->id),
                'delete_path' => route('ilocations.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('ilocations.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
