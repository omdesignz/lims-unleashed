<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QualityCertificateResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'collection_id' => $this->collection_id,
            'code' => $this->code,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'invoice_id' => $this->invoice_id,
            'customer_id' => $this->customer_id,
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'product_id' => $this->product_id,
            'product' => ProductResource::make($this->whenLoaded('product'))?->name ?? null,
            'file_path' => $this->file_path,
            'cl_id' => $this->cl_id,
            'lab_code' => LabCodeResource::make($this->whenLoaded('lab_code'))?->code ?? null,
            'obs' => $this->obs,
            'status' => $this->status,
            'deleted' => $this->deleted_at ? true : false,
            'validated_by_user' => UserResource::make($this->whenLoaded('validated_by_user'))?->name ?? null,
            'validated_by_id' => $this->validated_by_id,
            'validated_on_behalf_of_user' => UserResource::make($this->whenLoaded('validated_on_behalf_of_user'))?->name ?? null,
            'validated_at' => $this->validated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'links' => [
                'edit_path' => route('qualitycertificates.edit', $this->id),
                'show_path' => route('qualitycertificates.show', $this->id),
                'pdf_path' => route('qualitycertificates.getPDF', ['id' => $this->id]),
                'delete_path' => route('qualitycertificates.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('qualitycertificates.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
