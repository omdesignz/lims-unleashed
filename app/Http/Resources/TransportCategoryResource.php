<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportCategoryResource extends JsonResource
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
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('transportcategories.edit', $this->id),
                'delete_path' => route('transportcategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('transportcategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
