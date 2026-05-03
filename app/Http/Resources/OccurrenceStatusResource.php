<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OccurrenceStatusResource extends JsonResource
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
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('occurrencestatuses.edit', $this->id),
                'delete_path' => route('occurrencestatuses.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('occurrencestatuses.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
