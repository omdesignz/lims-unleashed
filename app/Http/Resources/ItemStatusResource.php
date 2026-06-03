<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemStatusResource extends JsonResource
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
            'category_id' => $this->category_id,
            // 'category' => ItemCategoryResource::make($this->whenLoaded('category'))->name ?? null,
            'category' => $this->category?->name ?? null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('itemstatuses.edit', $this->id),
                'delete_path' => route('itemstatuses.destroy', [
                    'recordIds' => [$this->id],
                ]),
                'restore_path' => route('itemstatuses.restore', [
                    'recordIds' => [$this->id],
                ]),
            ],
        ];
    }
}
