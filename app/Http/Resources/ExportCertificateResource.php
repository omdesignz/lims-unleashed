<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExportCertificateResource extends JsonResource
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
            'exporter_id' => $this->exporter_id,
            'exporter' => CustomerResource::make($this->whenLoaded('exporter'))?->name ?? null,
            'trans_type_id' => $this->trans_type_id,
            'trans_type' => TransportCategoryResource::make($this->whenLoaded('trans_type'))?->name ?? null,
            'exporter_warehouse_id' => $this->exporter_warehouse_id,
            'exporter_warehouse' => WarehouseResource::make($this->whenLoaded('exporter_warehouse'))?->address ?? null,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'authorized_personnel' => $this->authorized_personnel,
            'cert_no' => $this->cert_no,
            'country_origin_id' => $this->country_origin_id,
            'country_origin' => CountryResource::make($this->whenLoaded('country_origin'))?->name ?? null,
            'country_destination_id' => $this->country_destination_id,
            'country_destination' => CountryResource::make($this->whenLoaded('country_destination'))?->name ?? null,
            'origin_city' => $this->origin_city,
            'destination_city' => $this->destination_city,
            'expedition_date' => $this->expedition_date,
            'expedition_location' => $this->expedition_location,
            'obs' => $this->obs,
            'file' => $this->file,
            'date' => $this->date,
            'invoiced' => $this->invoiced,
            'invoice_id' => $this->invoice_id,
            'items' => ExportCertificateItemResource::collection($this->whenLoaded('items')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('exportcertificates.edit', $this->id),
                'delete_path' => route('exportcertificates.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('exportcertificates.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
