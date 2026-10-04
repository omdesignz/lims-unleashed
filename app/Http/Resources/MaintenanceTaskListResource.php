<?php

namespace App\Http\Resources;

use App\Support\MaintenanceTaskValidation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceTaskListResource extends JsonResource
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
            'previous_date' => $this->previous_date?->toDateString(),
            'next_date' => $this->next_date?->toDateString(),
            'days_until_due' => $this->due_date ? (int) today()->diffInDays($this->due_date, false) : null,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'category' => $this->category?->only(['id', 'name', 'code']),
            'equipment' => $this->equipment?->only(['id', 'name', 'internal_code', 'serial_number']),
            'supplier' => $this->supplier?->only(['id', 'name']),
        ]);
    }
}
