<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemCategoryResource extends JsonResource
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
            'parent' => ItemCategoryResource::make($this->whenLoaded('parent'))?->name ?? null,
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('itemcategories.edit', $this->id),
                'delete_path' => route('itemcategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('itemcategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
