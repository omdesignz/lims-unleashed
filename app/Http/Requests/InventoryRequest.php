<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRequest extends FormRequest
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
                'qty_available' => 'nullable',
                'min_stock_level' => 'required|integer',
                'reorder_point' => 'required|integer',
                'warehouse_id' => 'required|exists:i_warehouses,id',
                'item_id' => 'required|exists:i_items,id',
                'name' => 'nullable',
                'category_id' => 'required|exists:item_categories,id',
            ];
        } else {
            $rules = [
                'qty_available' => 'nullable',
                'min_stock_level' => 'required|integer',
                'reorder_point' => 'required|integer',
                'warehouse_id' => 'required|exists:i_warehouses,id',
                'item_id' => 'required|exists:i_items,id',
                'name' => 'nullable',
                'category_id' => 'required|exists:item_categories,id',
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
            'qty_available' => trans('gestlab.general.labels.inventory.qty_available'),
            'min_stock_level' => trans('gestlab.general.labels.inventory.min_stock_level'),
            'reorder_point' => trans('gestlab.general.labels.inventory.reorder_point'),
            'warehouse_id' => trans('gestlab.general.labels.inventory.warehouse_id'),
            'item_id' => trans('gestlab.general.labels.inventory.item_id'),
            'name' => trans('gestlab.general.labels.inventory.name'),
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
        $this->merge([
            'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
            'item_id' => !is_null(request()->item_id) ? request()->item_id['value'] : null,
        ]);
            
    }
}
