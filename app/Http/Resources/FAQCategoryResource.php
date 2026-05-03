<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FAQCategoryResource extends JsonResource
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
                'edit_path' => route('faqcategories.edit', $this->id),
                'delete_path' => route('faqcategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('faqcategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
