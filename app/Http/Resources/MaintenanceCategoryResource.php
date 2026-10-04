<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'code' => $this->code,
            'description' => $this->description,
            'created_at' => $this->created_at?->toDateString(),
            'deleted' => $this->trashed(),
            'is_preset' => $this->lab_id === null,
            'code_locked' => (bool) $this->code_locked,
        ];
    }
}
