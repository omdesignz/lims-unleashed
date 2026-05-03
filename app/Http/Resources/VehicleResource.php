<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => TransportCategoryResource::make($this->whenLoaded('category'))?->name ??null,
            'department_id' => $this->department_id,
            'department' => DepartmentResource::make($this->whenLoaded('department'))?->name ??null,
            'number_plate' => $this->number_plate,
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('vehicles.edit', $this->id),
                'delete_path' => route('vehicles.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('vehicles.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
