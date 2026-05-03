<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisCategoryResource extends JsonResource
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
            'department_id' => $this->department_id,
            'department' => $this->department?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('analysiscategories.edit', $this->id),
                'delete_path' => route('analysiscategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('analysiscategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
