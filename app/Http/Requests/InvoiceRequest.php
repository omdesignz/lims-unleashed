<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
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
                'user_id' => 'required|exists:users,id',
                'labcode_id' => 'nullable|exists:lab_codes,id',
                'assign_lab_code' => 'required|boolean',
                'invoice_month' => 'required',
                'type_id' => 'required|exists:invoice_categories,id',
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'description' => 'nullable',
                'internal_ref' => 'nullable',
                'file_path' => 'nullable',
                'payment_method' => 'nullable',
                'date' => 'nullable|date_format:Y-m-d',
                'paid_date' => 'nullable|date_format:Y-m-d',
                'due_date' => 'nullable|date_format:Y-m-d',
                'discount' => 'required',
                'tax' => 'required',
                'sub_total' => 'required',
                'total' => 'required',
                'amount_due' => 'required',
                'obs' => 'nullable',
                'unique_hash' => 'nullable',
                'status_code' => 'nullable',
                'status' => 'required|boolean',
                'is_original' => 'required|boolean',
                'use_matrix_price' => 'required|boolean',
                'is_service' => 'required|boolean',
                'exported_saft' => 'required|boolean',
                'invoiceable_id' => 'nullable',
                'invoiceable_type' => 'nullable',
                'extra_data' => 'nullable',
                'items' => 'required|array|min:1',
                'items.*.invoice_id' => 'nullable|exists:invoices,id',
                'items.*.tax_id' => 'nullable|exists:tax_types,id',
                'items.*.itemable_id' => 'nullable',
                'items.*.itemable_type' => 'nullable',
                'items.*.unit_id' => 'nullable|exists:units,id',                                                                                                                                                                                                                                                   
                'items.*.exemption_id' => 'nullable|exists:tax_exemptions,id',   
                'items.*.item_id' => 'required|exists:parameters,id',
                'items.*.item_description' => 'nullable',    
                'items.*.exemption_code' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.qty' => 'required',                                                                                                                                                                                                                                                   
                'items.*.unit_price' => 'required',                                                                                                                                                                                                                                                   
                'items.*.total' => 'required',                                                                                                                                                                                                                                                   
                'items.*.discount_id' => 'required|exists:discount_categories,id',                                                                                                                                                                                                                                                   
                'items.*.discount_percentage' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.discount_amount' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.tax_percentage' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.tax_amount' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.obs' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.charge_tax' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.extra_data' => 'nullable',
            ];
        } else {
            $rules = [
                'id' => 'required|exists:invoices,id',
                'user_id' => 'nullable|exists:users,id',
                'labcode_id' => 'nullable|exists:lab_codes,id',
                'assign_lab_code' => 'required|boolean',
                'type_id' => 'required|exists:invoice_categories,id',
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'description' => 'nullable',
                'internal_ref' => 'nullable',
                'file_path' => 'nullable',
                'payment_method' => 'nullable',
                'date' => 'nullable|date_format:Y-m-d',
                'paid_date' => 'nullable|date_format:Y-m-d',
                'due_date' => 'nullable|date_format:Y-m-d',
                'obs' => 'nullable',

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
            'type_id' => trans('gestlab.general.labels.invoices.type_id'),
            'customer_id' => trans('gestlab.general.labels.invoices.customer_id'),
            'warehouse_id' => trans('gestlab.general.labels.invoices.warehouse_id'),
            'internal_ref' => trans('gestlab.general.labels.invoices.internal_ref'),
            'obs' => trans('gestlab.general.labels.invoices.obs'),
            'items' => trans('gestlab.general.labels.invoices.items'),
            'items.*.invoice_id' => trans('gestlab.general.labels.invoices.invoice_id'),
            'items.*.unit_id' => trans('gestlab.general.labels.invoices.unit_id'),
            'items.*.exemption_id' => trans('gestlab.general.labels.invoices.exemption_id'),
            'items.*.discount_id' => trans('gestlab.general.labels.invoices.discount_id'),
            'items.*.item_id' => trans('gestlab.general.labels.invoices.item_id'),
            'items.*.qty' => trans('gestlab.general.labels.invoices.qty'),
            'items.*.unit_price' => trans('gestlab.general.labels.invoices.unit_price'),
            'items.*.total' => trans('gestlab.general.labels.invoices.total'),
            'items.*.discount_percentage' => trans('gestlab.general.labels.invoices.discount_percentage'),
            'items.*.tax_percentage' => trans('gestlab.general.labels.invoices.tax_percentage'),
            'items.*.charge_tax' => trans('gestlab.general.labels.invoices.charge_tax'),
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

        if ($this->isMethod('post')) {

            $this->merge([
                'user_id' => auth()->user()->id ?? null,
                'obs' => request()->obs,
                'status' => false,
                'status_code' => Invoice::STATUS_CODE_NORMAL,
                'use_matrix_price' => request()->use_matrix_price,
                'is_original' => true,
                'exported_saft' => false,
                'invoice_month' => now()->format('Y'),
                'type_id' => !is_null(request()->type_id) ? request()->type_id['value'] : null,
                'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
                'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
                'date' => now()->format('Y-m-d'),
                'items' => is_null(request()->formatted_items) ? [] : collect(request()->formatted_items)->map(function($item) {
                    return [
                        'invoice_id' => $item['invoice_id'] ?? null,
                        'unit_id' => $item['unit_id']['value'] ?? null,
                        'exemption_id' => $item['exemption_id'] ?? null,
                        'exemption_code' => $item['exemption_code'] ?? null,
                        'discount_id' => $item['discount_id'],
                        'item_id' => $item['item_id']['value'],
                        'item_description' => $item['item_id']['label'],
                        'itemable_id' => !is_null($item['itemable_id']) ? $item['itemable_id'] : null,
                        'itemable_type' => !is_null($item['itemable_type']) ? $item['itemable_type'] : null,
                        'qty' => $item['qty'] ?? 1,
                        'unit_price' => $item['unit_price'],
                        'tax_id' => $item['tax_id'],
                        'total' => $item['total'],
                        'discount_percentage' => $item['discount_percentage'],
                        'discount_amount' => $item['discount_amount'],
                        'tax_percentage' => $item['tax_percentage'],
                        'tax_amount' => $item['tax_amount'],
                        'obs' => $item['obs'],
                        'charge_tax' => $item['charge_tax'],
                    ];
                })->toArray()
            ]);
        } else {

            $this->merge([
                'user_id' => auth()->user()->id ?? null,
                'obs' => request()->obs,
                'status' => request()->status,
                'use_matrix_price' => request()->use_matrix_price,
                'is_original' => request()->is_original,
                'exported_saft' => request()->exported_saft,
                'type_id' => !is_null(request()->type_id) ? request()->type_id['value'] : null,
                'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
                'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
            ]);

        }

        
    }
}