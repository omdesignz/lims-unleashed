<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportCertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'importer_id' => $this->importer_id,
            'importer' => CustomerResource::make($this->whenLoaded('importer'))?->name ?? null,
            'currency_id' => $this->currency_id,
            'currency' => CurrencyResource::make($this->whenLoaded('currency'))?->name ?? null,
            'vat' => $this->vat,
            'vat_cost' => $this->vat_cost,
            'importer_warehouse_id' => $this->importer_warehouse_id,
            'importer_warehouse' => WarehouseResource::make($this->whenLoaded('importer_warehouse'))?->address ?? null,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'exporter_id' => $this->exporter_id,
            'exporter' => CustomerResource::make($this->whenLoaded('exporter'))?->name ?? null,
            'exporter_warehouse_id' => $this->exporter_warehouse_id,
            'exporter_warehouse' => WarehouseResource::make($this->whenLoaded('exporter_warehouse'))?->address ?? null,
            'cert_no' => $this->cert_no,
            'trans_type_id' => $this->trans_type_id,
            'trans_type' => TransportCategoryResource::make($this->whenLoaded('trans'))?->name ?? null,
            'port_exit' => $this->port_exit,
            'port_entry' => $this->port_entry,
            'destination_country_id' => $this->destination_country_id,
            'destination_country' => CountryResource::make($this->whenLoaded('destination_country'))?->name ?? null,
            'cost_freight' => $this->cost_freight,
            'cost_insurance' => $this->cost_insurance,
            'cost_final' => $this->cost_final,
            'authorized_personnel' => $this->authorized_personnel,
            'date' => $this->date,
            'obs' => $this->obs,
            'file' => $this->file,
            'invoiced' => $this->invoiced,
            'invoice_id' => $this->invoice_id,
            'items' => ImportCertificateItemResource::collection($this->whenLoaded('items')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('importcertificates.edit', $this->id),
                'delete_path' => route('importcertificates.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('importcertificates.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
