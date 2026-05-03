<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportCertificateItemResource extends JsonResource
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
            'certificate_id' => $this->certificate_id,
            'certificate' => ImportCertificateResource::make($this->whenLoaded('certificate'))?->cert_no ?? null,
            'product_id' => $this->product_id,
            'product' => PhytosanitaryProductResource::make($this->whenLoaded('product'))?->name ?? null,
            'formatted_product_id' => [
                'value' => $this->product_id,
                'label' => PhytosanitaryProductResource::make($this->whenLoaded('product'))?->name ?? null
            ],
            'qty' => $this->qty,
            'origin' => $this->origin,
            'validity' => $this->validity,
            'lot' => $this->lot,
            'bl_no' => $this->bl_no,
            'obs' => $this->obs,
            'deleted' => $this->deleted_at ? true : false,
            // 'links' => [
            //     'edit_path' => route('importcertificate_items.edit', $this->id),
            //     'delete_path' => route('importcertificate_items.destroy', [
            //         'recordIds' => [$this->id]
            //     ]),
            //     'restore_path' => route('importcertificate_items.restore', [
            //         'recordIds' => [$this->id]
            //     ]),
            // ]
        ];
    }
}
