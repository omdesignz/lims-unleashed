<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DirectCollectionResource extends JsonResource
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
            'description' => $this->description,
            'col_date' => $this->col_date,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('directcollections.edit', $this->id),
                'delete_path' => route('directcollections.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('directcollections.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
