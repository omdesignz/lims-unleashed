<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryEquipmentResource extends JsonResource
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
            'brand' => $this->brand,
            'location' => $this->location,
            'model' => $this->model,
            'software' => $this->software,
            'firmware' => $this->firmware,
            'internal_code' => $this->internal_code,
            'range' => $this->range,
            'precision' => $this->precision,
            'resolution' => $this->resolution,
            'description' => $this->description,
            'obs' => $this->obs,
            'acceptance_criteria' => $this->acceptance_criteria,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'serial_number' => $this->serial_number,
            'last_calibration_date' => $this->last_calibration_date,
            'next_calibration_date' => $this->next_calibration_date,
            'reagent_open_date' => $this->reagent_open_date,
            'reagent_expiry_date' => $this->reagent_expiry_date,
            'reorder_qty' => $this->reorder_qty,
            'packed_weight' => $this->packed_weight,
            'packed_height' => $this->packed_height,
            'packed_width' => $this->packed_width,
            'packed_depth' => $this->packed_depth,
            'refrigerated' => $this->refrigerated,
            'status' => $this->status,
            'has_safety_documentation' => $this->has_safety_documentation,
            'packaging_type_id' => $this->packaging_type_id,
            'packaging_type' => PackagingCategoryResource::make($this->packagingType)?->name ?? null,
            'category_id' => $this->category_id,
            'category' => ItemCategoryResource::make($this->category)?->name ?? null,
            'department_id' => $this->department_id,
            'department' => DepartmentResource::make($this->department)?->name ?? null,
            'unit_id' => $this->unit_id,
            'unit' => InventoryUnitResource::make($this->unit)?->code ?? null,
            'eq_cat_id' => $this->eq_cat_id,
            'eq_cat' => EquipmentCategoryResource::make($this->eq_cat)?->name ?? null,
            'type_id' => $this->type_id,
            'type' => InventoryItemTypeResource::make($this->type)?->name ?? null,
            // 'user_id' => $this->user_id,
            // 'user' => UserResource::make($this->user)?->name ?? null,
            'status_id' => $this->status_id,
            'status' => ItemStatusResource::make($this->status)?->name ?? null,
            'lot' => $this->lot,
            'supplier_id' => $this->supplier_id,
            'supplier' => InventoryItemSupplierResource::make($this->supplier)?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'documents' => $this->getInventoryItemDocuments()->map(function ($file) {
                return [
                    'id' => $file->id,
                    'uuid' => $file->uuid,
                    'name' => $file->name,
                    'file_name' => $file->file_name,
                    'original_url' => $file->getUrl(), // Optional: Add a thumbnail if applicable
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'extension' => $file->extension,
                    'order' => $file->order,
                ];
            }),
            'links' => [
                'edit_path' => route('iequipments.edit', $this->id),
                'delete_path' => route('iequipments.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('iequipments.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
