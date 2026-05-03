<?php

namespace App\Http\Requests;

use App\Enums\Proposals\ProposalTrackingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProposalRequest extends FormRequest
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
                'proposal_year' => 'required',
                'customer_id' => 'required|exists:customers,id',
                'template_id' => 'required|exists:proposal_templates,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'department_id' => 'required|exists:departments,id',
                'file_path' => 'nullable',
                'service_location' => 'nullable',
                'obs' => 'nullable',
                'sub_total' => 'required',
                'total' => 'required',
                'unique_hash' => 'nullable',
                'status' => ['required', new Enum(ProposalTrackingStatus::class)],
                'is_original' => 'required|boolean',
                'discount_type' => 'nullable|exists:discount_categories,id',
                'converted_to_invoice' => 'required|boolean',
                'use_matrix_price' => 'required|boolean',
                'details' => 'nullable',
                'withhold_tax' => 'nullable|boolean',
                'withholding_tax_amount' => 'required',
                'withholding_tax_percentage' => 'required',
                'global_discount_amount' => 'required',
                'global_discount_percentage' => 'required',
                'tolerance_days' => 'required',

                'items' => 'required|array|min:1',
                'items.*.proposal_id' => 'nullable|exists:proposals,id',
                'items.*.tax_id' => 'nullable|exists:tax_types,id',
                'items.*.itemable_id' => 'nullable',
                'items.*.itemable_type' => 'nullable',
                'items.*.unit_id' => 'nullable|exists:units,id',                                                                                                                                                                                                                                                   
                'items.*.standard_id' => 'nullable|exists:standards,id',                                                                                                                                                                                                                                                   
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
                'items.*.withhold_tax' => 'required',
                'items.*.global_discount_portion_percentage' => 'required',
                'items.*.global_discount_amount' => 'required',                                                                                                                                                                                                                                                   
                'items.*.extra_data' => 'nullable',
            ];
        } else {
            $rules = [
                'id' => 'required|exists:proposals,id',
                'customer_id' => 'required|exists:customers,id',
                'template_id' => 'required|exists:proposal_templates,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'department_id' => 'required|exists:departments,id',
                'file_path' => 'nullable',
                'service_location' => 'nullable',
                'obs' => 'nullable',
                'sub_total' => 'required',
                'total' => 'required',
                'unique_hash' => 'nullable',
                'status' => ['required', new Enum(ProposalTrackingStatus::class)],
                'is_original' => 'required|boolean',
                'discount_type' => 'nullable|exists:discount_categories,id',
                'converted_to_invoice' => 'required|boolean',
                'use_matrix_price' => 'required|boolean',
                'details' => 'required',
                'withhold_tax' => 'required|boolean',
                'withholding_tax_amount' => 'required',
                'withholding_tax_percentage' => 'required',
                'global_discount_amount' => 'required',
                'global_discount_percentage' => 'required',
                'tolerance_days' => 'required',

                'items' => 'required|array|min:1',
                'items.*.proposal_id' => 'nullable|exists:proposals,id',
                'items.*.tax_id' => 'nullable|exists:tax_types,id',
                'items.*.itemable_id' => 'nullable',
                'items.*.itemable_type' => 'nullable',
                'items.*.unit_id' => 'nullable|exists:units,id',                                                                                                                                                                                                                                                   
                'items.*.standard_id' => 'nullable|exists:standards,id',                                                                                                                                                                                                                                                   
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
                'items.*.withhold_tax' => 'required',
                'items.*.global_discount_portion_percentage' => 'required',
                'items.*.global_discount_amount' => 'required',                                                                                                                                                                                                                                                   
                'items.*.extra_data' => 'nullable',

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
            'customer_id' => trans('gestlab.general.labels.proposals.customer_id'),
            'warehouse_id' => trans('gestlab.general.labels.proposals.warehouse_id'),
            'template_id' => trans('gestlab.general.labels.proposals.template_id'),
            'details' => trans('gestlab.general.labels.proposals.details'),
            'discount' => trans('gestlab.general.labels.proposals.discount'),
            'service_location' => trans('gestlab.general.labels.proposals.service_location'),
            'items' => trans('gestlab.general.labels.proposals.items'),
            'items.*.proposal_id' => trans('gestlab.general.labels.proposals.proposal_id'),
            'items.*.unit_id' => trans('gestlab.general.labels.proposals.unit_id'),
            'items.*.standard_id' => trans('gestlab.general.labels.proposals.standard_id'),
            'items.*.exemption_id' => trans('gestlab.general.labels.proposals.exemption_id'),
            'items.*.discount_id' => trans('gestlab.general.labels.proposals.discount_id'),
            'items.*.item_id' => trans('gestlab.general.labels.proposals.item_id'),
            'items.*.qty' => trans('gestlab.general.labels.proposals.qty'),
            'items.*.unit_price' => trans('gestlab.general.labels.proposals.unit_price'),
            'items.*.total' => trans('gestlab.general.labels.proposals.total'),
            'items.*.discount_percentage' => trans('gestlab.general.labels.proposals.discount_percentage'),
            'items.*.tax_percentage' => trans('gestlab.general.labels.proposals.tax_percentage'),
            'items.*.charge_tax' => trans('gestlab.general.labels.proposals.charge_tax'),
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
                'status' => ProposalTrackingStatus::PENDING->value,
                'use_matrix_price' => request()->use_matrix_price,
                'converted_to_invoice' => false,
                'is_original' => true,
                'proposal_year' => now()->format('Y'),
                'tolerance_days' => request()->tolerance_days,
                'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
                'template_id' => !is_null(request()->template_id) ? request()->template_id['value'] : null,
                'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
                'department_id' => !is_null(request()->department_id) ? request()->department_id['value'] : null,
                'withhold_tax' => false,
                'discount_type' => null,
                'withholding_tax_amount' => 0,
                'withholding_tax_percentage' => 0,
                'global_discount_amount' => 0,
                'global_discount_percentage' => 0,
                'items' => is_null(request()->formatted_items) ? [] : collect(request()->formatted_items)->map(function($item) {
                    return [
                        'proposal_id' => $item['proposal_id'] ?? null,
                        'unit_id' => $item['unit_id']['value'] ?? null,
                        'exemption_id' => $item['exemption_id'] ?? null,
                        'exemption_code' => $item['exemption_code'] ?? null,
                        'discount_id' => $item['discount_id'],
                        'item_id' => $item['item_id']['value'],
                        'item_description' => $item['item_id']['label'],
                        'standard_id' => $item['standard_id']['value'] ?? null,
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
                        'withhold_tax' => $item['withhold_tax'], 
                        'global_discount_portion_percentage' => $item['global_discount_portion_percentage'],
                        'global_discount_amount' => $item['global_discount_amount'],
                    ];
                })->toArray()
            ]);
        } else {

            $this->merge([
                'id' => request()->id,
                'user_id' => auth()->user()->id ?? null,
                'obs' => request()->obs,
                'status' => request()->status,
                'discount_type' => request()->discount_type ?? null,
                'use_matrix_price' => request()->use_matrix_price,
                'is_original' => true,
                // 'proposal_year' => now()->format('Y'),
                'tolerance_days' => request()->tolerance_days,
                'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
                'template_id' => !is_null(request()->template_id) ? request()->template_id['value'] : null,
                'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
                'department_id' => !is_null(request()->department_id) ? request()->department_id['value'] : null,
                'items' => is_null(request()->formatted_items) ? [] : collect(request()->formatted_items)->map(function($item) {
                    return [
                        'proposal_id' => $item['proposal_id'] ?? null,
                        'unit_id' => $item['unit_id']['value'] ?? null,
                        'exemption_id' => $item['exemption_id'] ?? null,
                        'exemption_code' => $item['exemption_code'] ?? null,
                        'itemable_id' => $item['itemable_id'] ?? null,
                        'itemable_type' => $item['itemable_type'] ?? null,
                        'discount_id' => $item['discount_id'],
                        'item_id' => $item['item_id']['value'],
                        'item_description' => $item['item_id']['label'],
                        'standard_id' => $item['standard_id']['value'] ?? null,
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
                        'withhold_tax' => $item['withhold_tax'],
                        'global_discount_portion_percentage' => $item['global_discount_portion_percentage'],
                        'global_discount_amount' => $item['global_discount_amount'],
                    ];
                })->toArray()
            ]);

        }

        
    }
}