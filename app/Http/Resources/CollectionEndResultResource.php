<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionEndResultResource extends JsonResource
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
                'edit_path' => route('collectionendresults.edit', $this->id),
                'delete_path' => route('collectionendresults.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('collectionendresults.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
