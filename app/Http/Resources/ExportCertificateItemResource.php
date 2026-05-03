<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExportCertificateItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product' => PhytosanitaryProductResource::make($this->whenLoaded('product'))?->name ?? null,
            'qty' => $this->qty,
            'obs' => $this->obs,
            'deleted' => $this->deleted_at ? true : false,
            // 'links' => [
            //     'edit_path' => route('export_certificate_items.edit', $this->id),
            //     'delete_path' => route('export_certificate_items.destroy', [
            //         'recordIds' => [$this->id]
            //     ]),
            //     'restore_path' => route('export_certificate_items.restore', [
            //         'recordIds' => [$this->id]
            //     ]),
            // ]
        ];
    }
}
