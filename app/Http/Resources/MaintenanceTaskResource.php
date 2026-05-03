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
            'equipment' => InventoryItemResource::make($this->equipment)?->name ?? null,
            'category_id' => $this->category_id,
            'category' => MaintenanceCategoryResource::make($this->category)?->name ?? null,
            'supplier_id' => $this->supplier_id,
            'supplier' => InventoryItemSupplierResource::make($this->supplier)?->name ?? null,
            'due_date' => $this->due_date,
            'previous_date' => $this->previous_date,
            'next_date' => $this->next_date,
            'acceptance_criteria' => $this->acceptance_criteria,
            'executed_by_supplier' => $this->executed_by_supplier,
            'supplier_id' => $this->supplier_id,
            'supplier' => InventoryItemSupplierResource::make($this->supplier)?->name ?? null,
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
            'due_date' => $this->due_date,
            'is_executed' => $this->is_executed,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('maintenancetasks.edit', $this->id),
                'delete_path' => route('maintenancetasks.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('maintenancetasks.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
