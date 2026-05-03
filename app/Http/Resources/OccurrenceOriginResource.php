<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OccurrenceOriginResource extends JsonResource
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
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('occurrenceorigins.edit', $this->id),
                'delete_path' => route('occurrenceorigins.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('occurrenceorigins.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
