<?php

namespace App\Http\Requests;

class ExportCertificateRequest extends TradeCertificateRequest
{
    protected function certificateKind(): string
    {
        return 'export';
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
}
