<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryDeliveryRequest extends FormRequest
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
                'sales_date' => 'required|date',
                'customer_id' => 'required|exists:customers,id',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|exists:i_items,id',
                'items.*.qty' => 'required',                                                                                                                                                                                                                                                   
                'items.*.expected_date' => 'required|date',                                                                                                                                                                                                                                                   
                'items.*.actual_date' => 'required|date',
                'items.*.warehouse_id' => 'required|exists:i_warehouses,id',
            ];
        } else {
            $rules = [
                'sales_date' => 'required|date',
                'customer_id' => 'required|exists:customers,id',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|exists:i_items,id',
                'items.*.qty' => 'required',                                                                                                                                                                                                                                                   
                'items.*.expected_date' => 'required|date',                                                                                                                                                                                                                                                   
                'items.*.actual_date' => 'required|date',
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
            'sales_date' => trans('gestlab.general.labels.ideliveries.date'),
            'customer_id' => trans('gestlab.general.labels.ideliveries.customer_id'),
            'items' => trans('gestlab.general.labels.ideliverydetails.items'),
            'items.*.item_id' => trans('gestlab.general.labels.ideliverydetails.item_id'),
            'items.*.expected_date' => trans('gestlab.general.labels.ideliverydetails.expected_date'),
            'items.*.actual_date' => trans('gestlab.general.labels.ideliverydetails.actual_date'),
            'items.*.qty' => trans('gestlab.general.labels.ideliverydetails.qty'),
            'items.*.warehouse_id' => trans('gestlab.general.labels.ideliverydetails.warehouse_id'),
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
            'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
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
