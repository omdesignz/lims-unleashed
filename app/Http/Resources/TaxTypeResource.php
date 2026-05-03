<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxTypeResource extends JsonResource
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
            'percent' => $this->percent,
            'compound_tax' => $this->compound_tax,
            'collective_tax' => $this->collective_tax,
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('taxtypes.edit', $this->id),
                'delete_path' => route('taxtypes.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('taxtypes.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
