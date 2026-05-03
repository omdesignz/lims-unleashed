<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionTypeResource extends JsonResource
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
            'created_at' => $this->created_at?->format('Y-m-d'),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('itransactiontypes.edit', $this->id),
                'delete_path' => route('itransactiontypes.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('itransactiontypes.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
