<?php

namespace App\Http\Resources;

use App\Support\MaintenanceTaskValidation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceTaskAuthoringResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_replace($this->resource->only(MaintenanceTaskValidation::EDITABLE_FIELDS), [
            'id' => $this->id,
            'maintenance_task_no' => $this->maintenance_task_no,
            'due_date' => $this->due_date?->toDateString(),
        ]);
    }
}
