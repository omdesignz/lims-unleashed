<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractGuideRequest extends FormRequest
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
                'guide_month' => 'required',
                'collection_id' => 'nullable|exists:lab_codes,id',
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'guide_no' => 'nullable',
                'ref_no' => 'nullable',
                'entry_point' => 'nullable',
                'collection_point' => 'nullable',
                'du_no' => 'nullable',
                'nif' => 'nullable',
                'contact' => 'nullable',
                'email' => 'nullable',
                'bl' => 'nullable',
                'date' => 'nullable|date_format:Y-m-d',
                'extra_data' => 'nullable',
                'items' => 'required|array|min:1',
                'items.*.guide_id' => 'nullable|exists:contract_guides,id',
                'items.*.product_id' => 'nullable|exists:products,id',
                'items.*.country_id' => 'nullable|exists:countries,id',
                'items.*.collection_id' => 'nullable|exists:lab_codes,id',
                'items.*.bl' => 'nullable',    
                'items.*.lot' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.manufacturer' => 'required',                                                                                                                                                                                                                                                   
                'items.*.origin' => 'required',                                                                                                                                                                                                                                                   
                'items.*.brand' => 'required',                                                                                                                                                                                                                                                
                'items.*.du_no' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.obs' => 'nullable',                                                                                                                                                                                                                                                   
                'items.*.date' => 'nullable',
            ];
        } else {
            $rules = [
                'id' => 'required|exists:contract_guides,id',
                'collection_id' => 'nullable|exists:lab_codes,id',
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'guide_no' => 'nullable',
                'ref_no' => 'nullable',
                'entry_point' => 'nullable',
                'collection_point' => 'nullable',
                'du_no' => 'nullable',
                'nif' => 'nullable',
                'contact' => 'nullable',
                'email' => 'nullable',
                'bl' => 'nullable',
                'obs' => 'nullable',
                'date' => 'nullable|date_format:Y-m-d',
                'extra_data' => 'nullable',

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
            'user_id' => trans('gestlab.general.labels.contract_guides.user_id'),
            'customer_id' => trans('gestlab.general.labels.contract_guides.customer_id'),
            'warehouse_id' => trans('gestlab.general.labels.contract_guides.warehouse_id'),
            'guide_no' => trans('gestlab.general.labels.contract_guides.guide_no'),
            'bl' => trans('gestlab.general.labels.contract_guides.bl'),
            'lot' => trans('gestlab.general.labels.contract_guides.lot'),
            'ref_no' => trans('gestlab.general.labels.contract_guides.ref_no'),
            'entry_point' => trans('gestlab.general.labels.contract_guides.entry_point'),
            'collection_point' => trans('gestlab.general.labels.contract_guides.collection_point'),
            'du_no' => trans('gestlab.general.labels.contract_guides.du_no'),
            'nif' => trans('gestlab.general.labels.contract_guides.nif'),
            'contact' => trans('gestlab.general.labels.contract_guides.contact'),
            'email' => trans('gestlab.general.labels.contract_guides.email'),
            'date' => trans('gestlab.general.labels.contract_guides.date'),
            'collection_id' => trans('gestlab.general.labels.contract_guides.collection_id'),
            'obs' => trans('gestlab.general.labels.contract_guides.obs'),
            'items' => trans('gestlab.general.labels.contract_guides.products'),
            'items.*.guide_id' => trans('gestlab.general.labels.contract_guides.guide_id'),
            'items.*.product_id' => trans('gestlab.general.labels.contract_guides.product_id'),
            'items.*.country_id' => trans('gestlab.general.labels.contract_guides.country_id'),
            'items.*.collection_id' => trans('gestlab.general.labels.contract_guides.collection_id'),
            'items.*.bl' => trans('gestlab.general.labels.contract_guides.bl'),
            'items.*.lot' => trans('gestlab.general.labels.contract_guides.lot'),
            'items.*.manufacturer' => trans('gestlab.general.labels.contract_guides.manufacturer'),
            'items.*.origin' => trans('gestlab.general.labels.contract_guides.country_id'),
            'items.*.brand' => trans('gestlab.general.labels.contract_guides.brand'),
            'items.*.du_no' => trans('gestlab.general.labels.contract_guides.du_no'),
            'items.*.obs' => trans('gestlab.general.labels.contract_guides.obs'),
            'items.*.date' => trans('gestlab.general.labels.contract_guides.date'),
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
        'items.*.product_id.required' => 'É obrigatória a indicação de um valor para o campo produto',
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
                'ref_no' => request()->ref_no,
                'entry_point' => request()->entry_point,
                'collection_point' => request()->collection_point,
                'du_no' => request()->du_no,
                'nif' => request()->nif,
                'contact' => request()->contact,
                'obs' => request()->obs,
                'email' => request()->email,
                'bl' => request()->bl,
                'guide_month' => now()->format('Y'),
                'collection_id' => !is_null(request()->collection_id) ? request()->collection_id['value'] : null,
                'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
                'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
                'date' => now()->format('Y-m-d'),
                'items' => is_null(request()->items) ? [] : collect(request()->items)->map(function($item) {
                    return [
                        'guide_id' => $item['guide_id'] ?? null,
                        'product_id' => $item['product_id']['value'] ?? null,
                        'country_id' => $item['country_id']['value'] ?? null,
                        'bl' => $item['bl'] ?? null,
                        'lot' => $item['lot'],
                        'manufacturer' => $item['manufacturer'],
                        'origin' => $item['country_id']['label'] ?? null,
                        'brand' => $item['brand'],
                        'obs' => $item['obs'],
                        'du_no' => $item['du_no'],
                        'date' => $item['date'],
                        'collection_id' => $item['collection_id'],
                    ];
                })->toArray()
            ]);
        } else {

            $this->merge([
                'obs' => request()->obs,
                'ref_no' => request()->ref_no,
                'entry_point' => request()->entry_point,
                'collection_point' => request()->collection_point,
                'du_no' => request()->du_no,
                'nif' => request()->nif,
                'contact' => request()->contact,
                'email' => request()->email,
                'bl' => request()->bl,
                'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
                'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
                'collection_id' => !is_null(request()->collection_id) ? request()->collection_id['value'] : null,
            ]);

        }

        
    }
}