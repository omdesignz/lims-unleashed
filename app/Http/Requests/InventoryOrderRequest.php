<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryOrderRequest extends FormRequest
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
                'date' => 'required|date',
                'user_id' => 'required|exists:users,id',
                'order_year' => 'required',
                'supplier_id' => 'required|exists:i_suppliers,id',
                'obs' => 'nullable',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|exists:i_items,id',
                'items.*.qty' => 'required',                                                                                                                                                                                                                                                   
                'items.*.expected_date' => 'nullable|date',                                                                                                                                                                                                                                                   
                'items.*.actual_date' => 'nullable|date',
                'items.*.warehouse_id' => 'required|exists:i_warehouses,id',
            ];
        } else {
            $rules = [
                'date' => 'required|date',
                'supplier_id' => 'required|exists:i_suppliers,id',
                'obs' => 'nullable',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|exists:i_items,id',
                'items.*.qty' => 'required',                                                                                                                                                                                                                                                   
                'items.*.expected_date' => 'nullable|date',                                                                                                                                                                                                                                                   
                'items.*.actual_date' => 'nullable|date',
                'items.*.warehouse_id' => 'required|exists:i_warehouses,id',

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
            'date' => trans('gestlab.general.labels.iorders.date'),
            'supplier_id' => trans('gestlab.general.labels.iorders.supplier_id'),
            'obs' => trans('gestlab.general.labels.iorders.obs'),
            'items' => trans('gestlab.general.labels.iorderdetails.items'),
            'items.*.item_id' => trans('gestlab.general.labels.iorderdetails.item_id'),
            'items.*.expected_date' => trans('gestlab.general.labels.iorderdetails.expected_date'),
            'items.*.actual_date' => trans('gestlab.general.labels.iorderdetails.actual_date'),
            'items.*.qty' => trans('gestlab.general.labels.iorderdetails.qty'),
            'items.*.warehouse_id' => trans('gestlab.general.labels.iorderdetails.warehouse_id'),
        ];
    }

    /**
 * Get the error messages for the defined validation rules.
 *
 * @return array<string, string>
 */
public function messages(): array
{
    return [
        'items.*.item_id.required' => 'É obrigatória a indicação de um valor para o campo item',
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
        $this->merge([
            'supplier_id' => !is_null(request()->supplier_id) ? request()->supplier_id['value'] : null,
            'user_id' => auth()->user()->id,
            'order_year' => now()->year,
            'items' => is_null(request()->items) ? [] : collect(request()->items)->map(function($item) {
                return [
                    'item_id' => $item['item_id']['value'],
                    'warehouse_id' => $item['warehouse_id']['value'],
                    'qty' => $item['qty'],
                    'expected_date' => $item['expected_date'],
                    'actual_date' => $item['actual_date'],
                ];
            })->toArray()
        ]);
    }
}
