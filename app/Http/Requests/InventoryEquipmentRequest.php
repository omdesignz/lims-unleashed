<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name' => 'required|min:1|unique:i_items,name',
                'brand' => 'nullable|min:1',
                'location' => 'nullable|min:1',
                'model' => 'nullable|min:1',
                'software' => 'nullable|min:1',
                'firmware' => 'nullable|min:1',
                'internal_code' => 'nullable|min:1|unique:i_items,internal_code',
                'range' => 'nullable',
                'precision' => 'nullable',
                'resolution' => 'nullable',
                'description' => 'nullable',
                'obs' => 'nullable',
                'acceptance_criteria' => 'nullable',
                'code' => 'nullable|min:1|unique:i_items,code',
                'barcode' => 'nullable|min:1',
                'serial_number' => 'nullable|min:1',
                'last_calibration_date' => 'nullable|date',
                'next_calibration_date' => 'nullable|date',
                'reagent_open_date' => 'nullable|date',
                'reagent_expiry_date' => 'nullable|date',
                'refrigerated' => 'required|boolean',
                'status_id' => 'nullable|exists:item_statuses,id',
                'has_safety_documentation' => 'required|boolean',
                'documents' => 'nullable|array',
                'documents.*' => ['nullable', 'file', 'mimes:jpg,png,pdf,docx', 'max:2048'],
                'packaging_type_id' => 'nullable|numeric|exists:packaging_categories,id',
                'reorder_qty' => 'nullable|numeric',
                'packed_weight' => 'nullable|numeric',
                'packed_weight_unit' => 'nullable|string',
                'packed_height' => 'nullable|numeric',
                'packed_height_unit' => 'nullable|string',
                'packed_width' => 'nullable|numeric',
                'packed_width_unit' => 'nullable|string',
                'packed_depth' => 'nullable|numeric',
                'packed_depth_unit' => 'nullable|string',
                'category_id' => 'nullable|numeric',
                'unit_id' => 'nullable|numeric',
                'eq_cat_id' => 'nullable|numeric',
                'type_id' => 'nullable|numeric|exists:i_types,id',
                'lot' => 'nullable|min:1',
                'supplier_id' => 'nullable|numeric|exists:i_suppliers,id',
                'department_id' => 'nullable|numeric|exists:departments,id',
                // 'user_id' => 'nullable|numeric|exists:users,id',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:i_items,name,' . request()->iitem,
                'brand' => 'nullable|min:1',
                'location' => 'nullable|min:1',
                'model' => 'nullable|min:1',
                'software' => 'nullable|min:1',
                'firmware' => 'nullable|min:1',
                'internal_code' => 'nullable|min:1|unique:i_items,internal_code,' . request()->iitem,
                'range' => 'nullable',
                'precision' => 'nullable',
                'resolution' => 'nullable',
                'description' => 'nullable',
                'obs' => 'nullable',
                'acceptance_criteria' => 'nullable',
                'code' => 'nullable|min:1|unique:i_items,code,' . request()->iitem,
                'barcode' => 'nullable|min:1',
                'serial_number' => 'nullable|min:1',
                'last_calibration_date' => 'nullable|date',
                'next_calibration_date' => 'nullable|date',
                'reagent_open_date' => 'nullable|date',
                'reagent_expiry_date' => 'nullable|date',
                'refrigerated' => 'required|boolean',
                'status_id' => 'nullable|exists:item_statuses,id',
                'has_safety_documentation' => 'required|boolean',
                'documents' => 'nullable|array',
                'documents.*' => ['nullable', 'file', 'mimes:jpg,png,pdf,docx', 'max:2048'],
                'packaging_type_id' => 'nullable|numeric|exists:packaging_categories,id',
                'reorder_qty' => 'nullable|numeric',
                'packed_weight' => 'nullable|numeric',
                'packed_weight_unit' => 'nullable|string',
                'packed_height' => 'nullable|numeric',
                'packed_height_unit' => 'nullable|string',
                'packed_width' => 'nullable|numeric',
                'packed_width_unit' => 'nullable|string',
                'packed_depth' => 'nullable|numeric',
                'packed_depth_unit' => 'nullable|string',
                'category_id' => 'nullable|numeric',
                'unit_id' => 'nullable|numeric',
                'eq_cat_id' => 'nullable|numeric',
                'type_id' => 'nullable|numeric|exists:i_types,id',
                'lot' => 'nullable|min:1',
                'supplier_id' => 'nullable|numeric|exists:i_suppliers,id',
                'department_id' => 'nullable|numeric|exists:departments,id',
                // 'user_id' => 'nullable|numeric|exists:users,id',
            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => trans('gestlab.general.labels.iitems.name'),
            'brand' => trans('gestlab.general.labels.iitems.brand'),
            'location' => trans('gestlab.general.labels.iitems.location'),
            'model' => trans('gestlab.general.labels.iitems.model'),
            'software' => trans('gestlab.general.labels.iitems.software'),
            'firmware' => trans('gestlab.general.labels.iitems.firmware'),
            'internal_code' => trans('gestlab.general.labels.iitems.internal_code'),
            'range' => trans('gestlab.general.labels.iitems.range'),
            'precision' => trans('gestlab.general.labels.iitems.precision'),
            'resolution' => trans('gestlab.general.labels.iitems.resolution'),
            'code' => trans('gestlab.general.labels.iitems.code'),
            'unit_id' => trans('gestlab.general.labels.iitems.unit_id'),
            'eq_cat_id' => trans('gestlab.general.labels.iitems.eq_cat_id'),
            'type_id' => trans('gestlab.general.labels.iitems.type_id'),
            'lot' => trans('gestlab.general.labels.iitems.lot'),
            'supplier_id' => trans('gestlab.general.labels.iitems.supplier_id'),
            'user_id' => trans('gestlab.general.labels.iitems.user_id'),
            'category_id' => trans('gestlab.general.labels.iitems.category_id'),
            'department_id' => trans('gestlab.general.labels.iitems.department_id'),
            'description' => trans('gestlab.general.labels.iitems.description'),
            'obs' => trans('gestlab.general.labels.iitems.obs'),
            'acceptance_criteria' => trans('gestlab.general.labels.iitems.acceptance_criteria'),
            'barcode' => trans('gestlab.general.labels.iitems.barcode'),
            'serial_number' => trans('gestlab.general.labels.iitems.serial_number'),
            'last_calibration_date' => trans('gestlab.general.labels.iitems.last_calibration_date'),
            'next_calibration_date' => trans('gestlab.general.labels.iitems.next_calibration_date'),
            'reagent_open_date' => trans('gestlab.general.labels.iitems.reagent_open_date'),
            'reagent_expiry_date' => trans('gestlab.general.labels.iitems.reagent_expiry_date'),
            'refrigerated' => trans('gestlab.general.labels.iitems.refrigerated'),
            'status_id' => trans('gestlab.general.labels.iitems.status_id'),
            'has_safety_documentation' => trans('gestlab.general.labels.iitems.has_safety_documentation'),
            'packaging_type_id' => trans('gestlab.general.labels.iitems.packaging_type_id'),
            'reorder_qty' => trans('gestlab.general.labels.iitems.reorder_qty'),
            'packed_weight' => trans('gestlab.general.labels.iitems.packed_weight'),
            'packed_weight_unit' => trans('gestlab.general.labels.iitems.packed_weight_unit'),
            'packed_height' => trans('gestlab.general.labels.iitems.packed_height'),
            'packed_height_unit' => trans('gestlab.general.labels.iitems.packed_height_unit'),
            'packed_width' => trans('gestlab.general.labels.iitems.packed_width'),
            'packed_width_unit' => trans('gestlab.general.labels.iitems.packed_width_unit'),
            'packed_depth' => trans('gestlab.general.labels.iitems.packed_depth'),
            'packed_depth_unit' => trans('gestlab.general.labels.iitems.packed_depth_unit'),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        // dd(request()->all());

        if (request()->boolean('refrigerated')) {
            $this->merge([
                'refrigerated' => request()->boolean('refrigerated') ? 1 : 0,
                'status_id' => !is_null(request()->status_id) ? request()->status_id['value'] : null,
                'has_safety_documentation' => request()->boolean('has_safety_documentation') ? 1 : 0,
                'packaging_type_id' => !is_null(request()->packaging_type_id) ? request()->packaging_type_id['value'] : null,
                'unit_id' => !is_null(request()->unit_id) ? request()->unit_id['value'] : null,
                'eq_cat_id' => !is_null(request()->eq_cat_id) ? request()->eq_cat_id['value'] : null,
                'category_id' => 1,
                'department_id' => !is_null(request()->department_id) ? request()->department_id['value'] : null,
                'type_id' => !is_null(request()->type_id) ? request()->type_id['value'] : null,
                'supplier_id' => !is_null(request()->supplier_id) ? request()->supplier_id['value'] : null,
            ]);
        } else {
            $this->merge([
                'refrigerated' => request()->boolean('refrigerated') ? 1 : 0,
                'status_id' => !is_null(request()->status_id) ? request()->status_id['value'] : null,
                'has_safety_documentation' => request()->boolean('has_safety_documentation') ? 1 : 0,
                'packaging_type_id' => !is_null(request()->packaging_type_id) ? request()->packaging_type_id['value'] : null,
                'unit_id' => !is_null(request()->unit_id) ? request()->unit_id['value'] : null,
                'eq_cat_id' => !is_null(request()->eq_cat_id) ? request()->eq_cat_id['value'] : null,
                'category_id' => 1,
                'department_id' => !is_null(request()->department_id) ? request()->department_id['value'] : null,
                'type_id' => !is_null(request()->type_id) ? request()->type_id['value'] : null,
                'supplier_id' => !is_null(request()->supplier_id) ? request()->supplier_id['value'] : null,
            ]);
        }
    }
}
