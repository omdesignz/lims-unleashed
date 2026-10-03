<?php

namespace App\Services;

use App\Models\Department;
use App\Models\EquipmentCategory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemType;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\ItemStatus;
use App\Models\PackagingCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class InventoryItemCreationValidation
{
    /** @return array<string,array<mixed>> */
    public function rules(int $labId): array
    {
        $rules = ['name' => ['required', 'string', 'max:255']];
        foreach (['code', 'barcode', 'internal_code'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255', Rule::unique((new InventoryItem)->getTable(), $field)->where('lab_id', $labId)];
        }
        foreach (self::references() as $field => $model) {
            $rules[$field] = [in_array($field, ['category_id', 'unit_id'], true) ? 'required' : 'nullable', 'integer',
                Rule::exists((new $model)->getTable(), 'id')->whereNull('deleted_at')];
        }
        foreach (['serial_number', 'model', 'brand', 'lot', 'resolution', 'precision', 'range', 'firmware', 'software', 'location',
            'metrological_traceability_reference'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }
        foreach (['standard_cost', 'last_purchase_price'] as $field) {
            $rules[$field] = ['nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:99999999999999.9999'];
        }
        foreach (['reorder_qty', 'packed_depth', 'packed_width', 'packed_height', 'packed_weight'] as $field) {
            $rules[$field] = ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'];
        }
        foreach (['packed_depth_unit', 'packed_width_unit', 'packed_height_unit', 'packed_weight_unit'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:20'];
        }
        foreach (['reagent_expiry_date', 'reagent_open_date', 'next_calibration_date', 'last_calibration_date', 'metrology_review_due_at'] as $field) {
            $rules[$field] = ['nullable', 'date'];
        }
        foreach (['description', 'acceptance_criteria', 'obs', 'metrology_notes'] as $field) {
            $rules[$field] = ['nullable', 'string'];
        }
        foreach (['has_safety_documentation', 'refrigerated', 'is_reagent'] as $field) {
            $rules[$field] = ['boolean'];
        }

        return $rules + [
            'metrological_uncertainty_value' => ['nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:99999999.9999'],
            'metrological_uncertainty_unit' => ['nullable', 'string', 'max:50'],
            'documents' => ['nullable', 'array', 'list', 'max:20'],
            'documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf,docx', 'max:2048'],
            'warehouses' => ['nullable', 'array', 'list', 'max:100'],
            'warehouses.*' => ['array:id,qty_available,min_stock_level,reorder_point'],
            'warehouses.*.id' => ['required', 'integer', 'distinct', Rule::exists((new InventoryItemWarehouse)->getTable(), 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'warehouses.*.qty_available' => ['required', 'numeric', 'decimal:0,4', 'min:0', 'max:99999999999999.9999'],
            'warehouses.*.min_stock_level' => ['nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:99999999999999.9999'],
            'warehouses.*.reorder_point' => ['nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:99999999999999.9999'],
        ];
    }

    /** @return array<string,class-string<Model>> */
    public static function references(): array
    {
        return ['category_id' => ItemCategory::class, 'unit_id' => InventoryUnit::class,
            'type_id' => InventoryItemType::class, 'supplier_id' => InventoryItemSupplier::class, 'status_id' => ItemStatus::class,
            'department_id' => Department::class, 'eq_cat_id' => EquipmentCategory::class, 'packaging_type_id' => PackagingCategory::class];
    }
}
