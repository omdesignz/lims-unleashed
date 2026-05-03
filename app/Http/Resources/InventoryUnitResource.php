<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryUnitResource extends JsonResource
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
            'code' => $this->code,
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('iunits.edit', $this->id),
                'delete_path' => route('iunits.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('iunits.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
