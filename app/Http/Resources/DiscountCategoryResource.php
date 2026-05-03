<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountCategoryResource extends JsonResource
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
            'symbol' => $this->symbol,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('discountcategories.edit', $this->id),
                'delete_path' => route('discountcategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('discountcategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
