<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariableResource extends JsonResource
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
            'value' => $this->value,
            'formula_id' => [
                'value' => $this->formula_id,
                'label' => $this->formula?->name,
            ],
            'formula' => $this->formula?->name,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('variables.edit', $this->id),
                'delete_path' => route('variables.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('variables.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
