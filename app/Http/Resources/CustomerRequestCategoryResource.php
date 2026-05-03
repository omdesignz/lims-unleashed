<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerRequestCategoryResource extends JsonResource
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
                'edit_path' => route('customerrequestcategories.edit', $this->id),
                'delete_path' => route('customerrequestcategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('customerrequestcategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
