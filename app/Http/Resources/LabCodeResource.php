<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabCodeResource extends JsonResource
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
            'collection_id' => $this->collection_id,
            'code' => $this->code,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                // 'edit_path' => route('units.edit', $this->id),
                // 'delete_path' => route('units.destroy', [
                //     'recordIds' => [$this->id]
                // ]),
                // 'restore_path' => route('units.restore', [
                //     'recordIds' => [$this->id]
                // ]),
            ]
        ];
    }
}
