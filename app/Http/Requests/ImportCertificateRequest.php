<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCertificateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'importer_id' => 'required|exists:customers,id',
                'currency_id' => 'nullable|exists:currencies,id',
                'vat' => 'required|min:1',
                'vat_cost' => 'required|min:1',
                'importer_warehouse_id' => 'required|exists:warehouses,id',
                'user_id' => 'required|exists:users,id',
                'exporter_id' => 'required|exists:customers,id',
                'exporter_warehouse_id' => 'required|exists:warehouses,id',
                'trans_type_id' => 'required|exists:trans_categories,id',
                'port_exit' => 'required|min:1',
                'port_entry' => 'required|min:1',
                'destination_country_id' => 'required|exists:countries,id',
                'cost_freight' => 'required|min:1',
                'cost_insurance' => 'required|min:1',
                'cost_final' => 'required|min:1',
                'authorized_personnel' => 'required|min:1',
                'date' => 'required|date',
                'invoiced' => 'required|boolean',
                'invoice_id' => 'nullable|exists:invoices,id',
                'items' => 'required|array',
                'obs' => 'nullable'
            ];
        } else {
            $rules = [
                'importer_id' => 'required|exists:customers,id',
                'currency_id' => 'required|exists:currencies,id',
                'vat' => 'required|min:1',
                'vat_cost' => 'required|min:1',
                'importer_warehouse_id' => 'required|exists:warehouses,id',
                'user_id' => 'required|exists:users,id',
                'exporter_id' => 'required|exists:customers,id',
                'exporter_warehouse_id' => 'required|exists:warehouses,id',
                'trans_type_id' => 'required|exists:trans_categories,id',
                'port_exit' => 'required|min:1',
                'port_entry' => 'required|min:1',
                'destination_country_id' => 'required|exists:countries,id',
                'cost_freight' => 'required|min:1',
                'cost_insurance' => 'required|min:1',
                'cost_final' => 'required|min:1',
                'authorized_personnel' => 'required|min:1',
                'date' => 'required|date',
                'invoiced' => 'required|boolean',
                'invoice_id' => 'nullable|exists:invoices,id',
                'items' => 'required|array',
                'obs' => 'nullable'

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
            'importer_id' => trans('gestlab.general.labels.import_certificates.importer_id'),
            'currency_id' => trans('gestlab.general.labels.import_certificates.currency_id'),
            'vat' => trans('gestlab.general.labels.import_certificates.vat'),
            'vat_cost' => trans('gestlab.general.labels.import_certificates.vat_cost'),
            'importer_warehouse_id' => trans('gestlab.general.labels.import_certificates.importer_warehouse_id'),
            'user_id' => trans('gestlab.general.labels.import_certificates.user_id'),
            'exporter_id' => trans('gestlab.general.labels.import_certificates.exporter_id'),
            'exporter_warehouse_id' => trans('gestlab.general.labels.import_certificates.exporter_warehouse_id'),
            'cert_no' => trans('gestlab.general.labels.import_certificates.cert_no'),
            'trans_type_id' => trans('gestlab.general.labels.import_certificates.trans_type_id'),
            'port_exit' => trans('gestlab.general.labels.import_certificates.port_exit'),
            'port_entry' => trans('gestlab.general.labels.import_certificates.port_entry'),
            'destination_country_id' => trans('gestlab.general.labels.import_certificates.destination_country_id'),
            'cost_freight' => trans('gestlab.general.labels.import_certificates.cost_freight'),
            'cost_insurance' => trans('gestlab.general.labels.import_certificates.cost_insurance'),
            'cost_final' => trans('gestlab.general.labels.import_certificates.cost_final'),
            'authorized_personnel' => trans('gestlab.general.labels.import_certificates.authorized_personnel'),
            'date' => trans('gestlab.general.labels.import_certificates.date'),
            'obs' => trans('gestlab.general.labels.import_certificates.obs'),
            'invoiced' => trans('gestlab.general.labels.import_certificates.invoiced'),
            'invoice_id' => trans('gestlab.general.labels.import_certificates.invoice_id'),
            'items' => trans('gestlab.general.labels.import_certificates.items'),
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
            'exporter_id' => !is_null(request()->exporter_id) ? request()->exporter_id['value'] : null,
            'exporter_warehouse_id' => !is_null(request()->exporter_warehouse_id) ? request()->exporter_warehouse_id['value'] : null,
            'importer_id' => !is_null(request()->importer_id) ? request()->importer_id['value'] : null,
            'importer_warehouse_id' => !is_null(request()->importer_warehouse_id) ? request()->importer_warehouse_id['value'] : null,
            'destination_country_id' => !is_null(request()->destination_country_id) ? request()->destination_country_id['value'] : null,
            'currency_id' => !is_null(request()->currency_id) ? request()->currency_id['value'] : null,
            'trans_type_id' => !is_null(request()->trans_type_id) ? request()->trans_type_id['value'] : null,
            'user_id' => !is_null(request()->user_id) ? request()->user_id['value'] : auth()->id(),
            'invoiced' => request()->boolean('invoiced') ? 1 : 0,
            'items' => is_null(request()->items) ? [] : collect(request()->items)->map(function ($item) {
                return [
                    'product_id' => $item['product_id']['value'] ?? null,
                    'qty' => $item['qty'],
                    'origin' => $item['origin'],
                    'validity' => $item['validity'],
                    'lot' => $item['lot'],
                    'bl_no' => $item['bl_no'],
                ];
            })->toArray()
        ]);
    }
}
