<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'inv_no' => $this->inv_no,
            'user_id' => UserResource::make($this->whenLoaded('user')),
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'customer_id' => CustomerResource::make($this->whenLoaded('customer')),
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'warehouse_id' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'discount_type' => DiscountCategoryResource::make($this->whenLoaded('discount_category')),
            'discount_category' => DiscountCategoryResource::make($this->whenLoaded('discount_category'))?->name ?? null,
            'type_id' => InvoiceCategoryResource::make($this->whenLoaded('invoice_category')),
            'invoice_category' => InvoiceCategoryResource::make($this->whenLoaded('invoice_category'))?->code ?? null,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')) ?? [], 
            'description' => $this->description,
            'internal_ref' => $this->internal_ref,
            'file_path' => $this->file_path,
            'date' => $this->date,
            'paid_date' => $this->paid_date,
            'discount' => $this->discount,
            'total' => $this->total,
            'sub_total' => $this->sub_total,
            'amount_due' => $this->amount_due,
            'obs' => $this->obs,
            'tax' => $this->tax,
            'unique_hash' => $this->unique_hash,
            'status_code' => $this->status_code,
            'status' => $this->status,
            'is_original' => $this->is_original,
            'use_matrix_price' => $this->use_matrix_price,
            'is_service' => $this->is_service,
            'exported_saft' => $this->exported_saft,
            'invoiceable_id' => $this->invoiceable_id,
            'invoiceable_type' => $this->invoiceable_type,
            'extra_data' => $this->extra_data,
            'revision_count' => $this->revision_count,
            'last_revision_at' => $this->last_revision_at,
            'deleted' => $this->deleted_at ? true : false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'links' => [
                'edit_path' => route('invoices.edit', $this->id),
                'pdf_path' => route('invoices.getPDF', ['id' => $this->id]),
                'delete_path' => route('invoices.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('invoices.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
