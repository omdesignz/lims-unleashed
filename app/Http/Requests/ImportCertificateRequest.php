<?php

namespace App\Http\Requests;

class ImportCertificateRequest extends TradeCertificateRequest
{
    protected function certificateKind(): string
    {
        return 'import';
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
}
