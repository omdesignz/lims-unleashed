<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DirectCollectionRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'collaborations' => 'nullable|array',
                'collectionreasons' => 'nullable|array',
                'collection_date' => 'nullable|date_format:Y-m-d',
                'collaborations.*.collaboration_id' => 'required|exists:collection_collaborations,id',
                'products' => 'array|min:1|max:5',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.comercial_brand' => 'nullable',
                'products.*.qty' => 'nullable',
                'products.*.collected_qty' => 'nullable',
                'products.*.origin' => 'nullable',
                'products.*.location' => 'nullable',
                'products.*.owner_id' => 'nullable|exists:users,id',
                'products.*.du_no' => 'nullable',
                'products.*.container_no' => 'nullable',
                'products.*.term_no' => 'nullable',
                'products.*.lot' => 'nullable',
                'products.*.invoiced' => 'nullable|boolean',
                'products.*.status' => 'nullable|boolean',
                'products.*.recollection' => 'nullable|boolean',
                'products.*.processed' => 'nullable|boolean',
                'products.*.collected_by_lab' => 'nullable|boolean',
                'products.*.bl' => 'nullable',
                'products.*.obs' => 'nullable',
                'products.*.sample_status' => 'nullable',
                'products.*.sampling_plan_ref' => 'nullable',
                'products.*.customer_submitted_info' => 'nullable',
                'products.*.temperature_value' => 'nullable',
                'products.*.expiry_date' => 'nullable|date_format:Y-m-d',
                'products.*.production_date' => 'nullable|date_format:Y-m-d',
                'products.*.collection_date' => 'nullable|date_format:Y-m-d',
                'products.*.result_id' => 'required|exists:collection_end_results,id',
                'products.*.vehicle_id' => 'nullable|exists:vehicles,id',
                'products.*.invoice_id' => 'nullable|exists:invoices,id',
                'products.*.temperature_id' => 'nullable|exists:temperatures,id',
                'products.*.collection_id' => 'nullable|exists:collections,id',
                'products.*.pack_id' => 'nullable|exists:packaging_categories,id',
            ];
        } else {
            $rules = [
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'collaborations' => 'nullable|array',
                'collaborations.*.collaboration_id' => 'required|exists:collection_collaborations,id',
                'collectionreasons' => 'nullable|array',
                'collectionreasons.*.reason_id' => 'required|exists:collection_reasons,id',
                'product_id' => 'required|exists:products,id',
                'comercial_brand' => 'nullable',
                'qty' => 'required',
                'collected_qty' => 'required',
                'origin' => 'nullable',
                'location' => 'nullable',
                'owner_id' => 'nullable|exists:users,id',
                'du_no' => 'nullable',
                'container_no' => 'nullable',
                'term_no' => 'nullable',
                'lot' => 'nullable',
                'invoiced' => 'nullable|boolean',
                'status' => 'nullable|boolean',
                'recollection' => 'nullable|boolean',
                'processed' => 'nullable|boolean',
                'collected_by_lab' => 'nullable|boolean',
                'bl' => 'nullable',
                'temperature_value' => 'nullable',
                'obs' => 'nullable',
                'products.*.sample_status' => 'nullable',
                'products.*.sampling_plan_ref' => 'nullable',
                'products.*.customer_submitted_info' => 'nullable',
                'expiry_date' => 'nullable|date_format:Y-m-d',
                'production_date' => 'nullable|date_format:Y-m-d',
                'collection_date' => 'nullable|date_format:Y-m-d',
                'result_id' => 'required|exists:collection_end_results,id',
                'vehicle_id' => 'nullable|exists:vehicles,id',
                'invoice_id' => 'nullable|exists:invoices,id',
                'temperature_id' => 'nullable|exists:temperatures,id',
                'collection_id' => 'nullable|exists:collections,id',
                'pack_id' => 'required|exists:packaging_categories,id',

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
            'customer_id' => trans('gestlab.general.labels.direct_collections.customer_id'),
            'warehouse_id' => trans('gestlab.general.labels.direct_collections.warehouse_id'),
            'products' => trans('gestlab.general.labels.direct_collections.products'),
            'products.*.product_id' => trans('gestlab.general.labels.direct_collections.product_id'),
            'products.*.comercial_brand' => trans('gestlab.general.labels.direct_collections.comercial_brand'),
            'products.*.result_id' => trans('gestlab.general.labels.direct_collections.result_id'),
            'products.*.pack_id' => trans('gestlab.general.labels.direct_collections.pack_id'),
            'products.*.vehicle_id' => trans('gestlab.general.labels.direct_collections.vehicle_id'),
            'products.*.collection_date' => trans('gestlab.general.labels.direct_collections.collection_date'),
            'products.*.temperature_id' => trans('gestlab.general.labels.direct_collections.temperature_id'),
            'products.*.lot' => trans('gestlab.general.labels.direct_collections.lot'),
            'products.*.origin' => trans('gestlab.general.labels.direct_collections.origin'),
            'products.*.location' => trans('gestlab.general.labels.direct_collections.location'),
            'products.*.obs' => trans('gestlab.general.labels.direct_collections.obs'),
            'products.*.sample_status' => trans('gestlab.general.labels.direct_collections.sample_status'),
            'products.*.sampling_plan_ref' => trans('gestlab.general.labels.direct_collections.sampling_plan_ref'),
            'products.*.customer_submitted_info' => trans('gestlab.general.labels.direct_collections.customer_submitted_info'),
            'products.*.bl' => trans('gestlab.general.labels.direct_collections.bl'),
            'products.*.term_no' => trans('gestlab.general.labels.direct_collections.term_no'),
            'products.*.container_no' => trans('gestlab.general.labels.direct_collections.container_no'),
            'products.*.expiry_date' => trans('gestlab.general.labels.direct_collections.expiry_date'),
            'products.*.production_date' => trans('gestlab.general.labels.direct_collections.production_date'),
            'products.*.collected_qty' => trans('gestlab.general.labels.direct_collections.collected_qty'),
            'products.*.qty' => trans('gestlab.general.labels.direct_collections.qty'),
            'products.*.temperature_value' => trans('gestlab.general.labels.direct_collections.temperature_value'),
            'products.*.collected_by_lab' => trans('gestlab.general.labels.direct_collections.collected_by_lab'),
            'products.*.owner_id' => trans('gestlab.general.labels.direct_collections.owner_id'),
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
            'products.*.product_id.required' => 'É obrigatória a indicação de um valor para o campo produto',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        // dd(request()->all());

        if ($this->isMethod('post')) {

            $this->merge([
                'customer_id' => data_get(request()->customer_id, 'value'),
                'collection_date' => request()->collection_date,
                'warehouse_id' => data_get(request()->warehouse_id, 'value'),
                'collaborations' => collect(request()->collaborations)->map(function ($item) {
                    return ['collaboration_id' => data_get($item, 'value')];
                })->toArray(),
                'collectionreasons' => collect(request()->collectionreasons)->map(function ($item) {
                    return ['reason_id' => data_get($item, 'value')];
                })->toArray(),
                'products' => is_null(request()->products) ? [] : collect(request()->products)->map(function ($item) {
                    return [
                        'product_id' => data_get($item, 'product_id.value'),
                        'temperature_id' => data_get($item, 'temperature_id.value'),
                        'vehicle_id' => data_get($item, 'vehicle_id.value'),
                        'collection_id' => data_get($item, 'collection_id'),
                        'pack_id' => data_get($item, 'pack_id.value'),
                        'owner_id' => data_get($item, 'owner_id.value'),
                        'result_id' => data_get($item, 'result_id.value'),
                        'invoice_id' => data_get($item, 'invoice_id.value'),
                        'comercial_brand' => data_get($item, 'comercial_brand'),
                        'du_no' => data_get($item, 'du_no'),
                        'term_no' => data_get($item, 'term_no'),
                        'container_no' => data_get($item, 'container_no'),
                        'recollection' => data_get($item, 'recollection'),
                        'obs' => data_get($item, 'obs'),
                        'sample_status' => data_get($item, 'sample_status'),
                        'sampling_plan_ref' => data_get($item, 'sampling_plan_ref'),
                        'customer_submitted_info' => data_get($item, 'customer_submitted_info'),
                        'temperature_value' => data_get($item, 'temperature_value'),
                        'processed' => data_get($item, 'processed'),
                        'collected_by_lab' => data_get($item, 'collected_by_lab'),
                        'expiry_date' => data_get($item, 'expiry_date'),
                        'production_date' => data_get($item, 'production_date'),
                        'collection_date' => data_get($item, 'collection_date'),
                        'qty' => data_get($item, 'qty'),
                        'origin' => data_get($item, 'origin'),
                        'location' => data_get($item, 'location'),
                        'collected_qty' => data_get($item, 'collected_qty'),
                        'lot' => data_get($item, 'lot'),
                        'bl' => data_get($item, 'bl'),
                        'invoiced' => data_get($item, 'invoiced'),
                        'status' => data_get($item, 'status'),
                    ];
                })->toArray(),
            ]);

        } else {

            $this->merge([
                'customer_id' => data_get(request()->customer_id, 'value'),
                'owner_id' => data_get(request()->owner_id, 'value'),
                'collection_date' => request()->collection_date,
                'warehouse_id' => data_get(request()->warehouse_id, 'value'),
                'collaborations' => collect(request()->collaborations)->map(function ($item) {
                    return ['collaboration_id' => data_get($item, 'value')];
                })->toArray(),
                'collectionreasons' => collect(request()->collectionreasons)->map(function ($item) {
                    return ['reason_id' => data_get($item, 'value')];
                })->toArray(),
                'product_id' => data_get(request()->product_id, 'value'),
                'temperature_id' => data_get(request()->temperature_id, 'value'),
                'vehicle_id' => data_get(request()->vehicle_id, 'value'),
                'collection_id' => request()->collection_id,
                'pack_id' => data_get(request()->pack_id, 'value'),
                'result_id' => data_get(request()->result_id, 'value'),
                'invoice_id' => data_get(request()->invoice_id, 'value'),
                'comercial_brand' => request()->comercial_brand,
                'du_no' => request()->du_no,
                'origin' => request()->origin,
                'location' => request()->location,
                'term_no' => request()->term_no,
                'container_no' => request()->container_no,
                'recollection' => request()->recollection,
                'obs' => request()->obs,
                'sample_status' => request()->sample_status,
                'sampling_plan_ref' => request()->sampling_plan_ref,
                'customer_submitted_info' => request()->customer_submitted_info,
                'processed' => request()->processed,
                'collected_by_lab' => request()->collected_by_lab,
                'expiry_date' => request()->expiry_date,
                'production_date' => request()->production_date,
                'collection_date' => request()->collection_date,
                'qty' => request()->qty,
                'collected_qty' => request()->collected_qty,
                'lot' => request()->lot,
                'bl' => request()->bl,
                'temperature_value' => request()->temperature_value,
                'invoiced' => request()->invoiced,
                'status' => request()->status,
            ]);

        }
    }
}
