<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactCategoryResource extends JsonResource
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
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('contactcategories.edit', $this->id),
                'delete_path' => route('contactcategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('contactcategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
