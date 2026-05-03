<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryTransactionRequest extends FormRequest
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
                'inventory_id' => 'required|exists:inventory,id',
                'qty' => 'required|integer',
                'user_id' => 'required|exists:users,id',
                'warehouse_id' => 'required|exists:i_warehouses,id',
                'item_id' => 'required|exists:i_items,id',
                'type_id' => 'required|exists:itransaction_types,id',
            ];
        } else {
            $rules = [
                'inventory_id' => 'required|exists:inventory,id',
                'qty' => 'required|integer',
                'user_id' => 'required|exists:users,id',
                'warehouse_id' => 'required|exists:i_warehouses,id',
                'item_id' => 'required|exists:i_items,id',
                'type_id' => 'required|exists:itransaction_types,id',
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
            'inventory_id' => trans('gestlab.general.labels.itransactions.inventory_id'),
            'qty' => trans('gestlab.general.labels.itransactions.qty'),
            'user_id' => trans('gestlab.general.labels.itransactions.user_id'),
            'warehouse_id' => trans('gestlab.general.labels.itransactions.warehouse_id'),
            'item_id' => trans('gestlab.general.labels.itransactions.item_id'),
            'type_id' => trans('gestlab.general.labels.itransactions.type_id'),
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
            'inventory_id' => !is_null(request()->inventory_id) ? request()->inventory_id['value'] : null,
            'user_id' => auth()->user()->id,
            'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
            'item_id' => !is_null(request()->item_id) ? request()->item_id['value'] : null,
            'type_id' => !is_null(request()->type_id) ? request()->type_id['value'] : null,
        ]);
            
    }
}
