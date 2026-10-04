<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceTaskResource extends JsonResource
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
            'maintenance_task_no' => $this->maintenance_task_no,
            'name' => $this->name,
            'description' => $this->description,
            'equipment_id' => $this->equipment_id,
            'equipment' => $this->equipment?->name,
            'category_id' => $this->category_id,
            'category' => $this->category?->name,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->supplier?->name,
            'due_date' => $this->due_date,
            'previous_date' => $this->previous_date,
            'next_date' => $this->next_date,
            'acceptance_criteria' => $this->acceptance_criteria,
            'executed_by_supplier' => $this->executed_by_supplier,
            'obs' => $this->obs,
            'cost' => $this->cost,
            'is_planned' => $this->is_planned,
            'periodicity' => $this->periodicity,
            'periodicity_unit' => $this->periodicity_unit,
            'range' => $this->range,
            'calibration_points' => $this->calibration_points,
            'calibration_status' => $this->calibration_status,
            'calibration_certificate_no' => $this->calibration_certificate_no,
            'result' => $this->result,
            'is_executed' => $this->is_executed,
            'deleted' => $this->deleted_at ? true : false,
            'action_capabilities' => ['delete' => false, 'restore' => false],
            'links' => [
                'edit_path' => route('vap-maintenance.tasks.edit', $this->id),
                'show_path' => route('vap-maintenance.tasks.show', $this->id),
            ],
        ];
    }
}
