<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportCertificateRequest extends FormRequest
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
                'exporter_id' => 'required|exists:customers,id',
                'trans_type_id' => 'required|exists:trans_categories,id',
                'exporter_warehouse_id' => 'required|exists:warehouses,id',
                'user_id' => 'required|exists:users,id',
                'authorized_personnel' => 'required|min:1',
                'cert_no' => 'nullable|min:1',
                'country_origin_id' => 'required|exists:countries,id',
                'country_destination_id' => 'required|exists:countries,id',
                'origin_city' => 'required|min:1',
                'destination_city' => 'required|min:1',
                'expedition_date' => 'required|date',
                'expedition_location' => 'required|min:1',
                'obs' => 'nullable',
                'invoiced' => 'required|boolean',
                'invoice_id' => 'nullable|exists:invoices,id',
                'items' => 'required|array',
                'date' => 'nullable|date',
            ];
        } else {
            $rules = [
                'exporter_id' => 'required|exists:customers,id',
                'trans_type_id' => 'required|exists:trans_categories,id',
                'exporter_warehouse_id' => 'required|exists:warehouses,id',
                'user_id' => 'required|exists:users,id',
                'authorized_personnel' => 'required|min:1',
                'country_origin_id' => 'required|exists:countries,id',
                'country_destination_id' => 'required|exists:countries,id',
                'origin_city' => 'required|min:1',
                'destination_city' => 'required|min:1',
                'expedition_date' => 'required|date',
                'expedition_location' => 'required|min:1',
                'obs' => 'nullable',
                'invoiced' => 'required|boolean',
                'invoice_id' => 'nullable|exists:invoices,id',
                'items' => 'required|array',
                'date' => 'nullable|date',

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
            'customer_id' => trans('gestlab.general.labels.export_certificates.customer_id'),
            'trans_type_id' => trans('gestlab.general.labels.export_certificates.trans_type_id'),
            'warehouse_id' => trans('gestlab.general.labels.export_certificates.warehouse_id'),
            'user_id' => trans('gestlab.general.labels.export_certificates.user_id'),
            'authorized_personnel' => trans('gestlab.general.labels.export_certificates.authorized_personnel'),
            'cert_no' => trans('gestlab.general.labels.export_certificates.cert_no'),
            'country_origin_id' => trans('gestlab.general.labels.export_certificates.country_origin_id'),
            'country_destination_id' => trans('gestlab.general.labels.export_certificates.country_destination_id'),
            'city_origin' => trans('gestlab.general.labels.export_certificates.city_origin'),
            'city_destination' => trans('gestlab.general.labels.export_certificates.city_destination'),
            'expedition_date' => trans('gestlab.general.labels.export_certificates.expedition_date'),
            'expedition_location' => trans('gestlab.general.labels.export_certificates.expedition_location'),
            'obs' => trans('gestlab.general.labels.export_certificates.obs'),
            'invoiced' => trans('gestlab.general.labels.export_certificates.invoiced'),
            'invoice_id' => trans('gestlab.general.labels.export_certificates.invoice_id'),
            'items' => trans('gestlab.general.labels.export_certificates.items'),
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
            'exporter_id' => !is_null(request()->exporter_id) ? request()->exporter_id['value'] : null,
            'trans_type_id' => !is_null(request()->trans_type_id) ? request()->trans_type_id['value'] : null,
            'exporter_warehouse_id' => !is_null(request()->exporter_warehouse_id) ? request()->exporter_warehouse_id['value'] : null,
            'user_id' => !is_null(request()->user_id) ? request()->user_id['value'] : auth()->id(),
            'country_origin_id' => !is_null(request()->country_origin_id) ? request()->country_origin_id['value'] : null,
            'country_destination_id' => !is_null(request()->country_destination_id) ? request()->country_destination_id['value'] : null,
            'invoice_id' => !is_null(request()->invoice_id) ? request()->invoice_id['value'] : null,
            'items' => is_null(request()->items) ? [] : collect(request()->items)->map(function ($item) {
                return [
                    'product_id' => $item['product_id']['value'],
                    'qty' => $item['qty'],
                ];
            })->toArray(),
        ]);
    }
}
