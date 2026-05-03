<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
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
            'quote_no' => $this->quote_no,
            'user_id' => UserResource::make($this->whenLoaded('user')),
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'customer_id' => CustomerResource::make($this->whenLoaded('customer')),
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'warehouse_id' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'discount_type' => DiscountCategoryResource::make($this->whenLoaded('discount_category')),
            'discount_category' => DiscountCategoryResource::make($this->whenLoaded('discount_category'))?->name ?? null,
            'description' => $this->description,
            'internal_ref' => $this->internal_ref,
            'items' => QuoteItemResource::collection($this->whenLoaded('items')) ?? [],
            'file_path' => $this->file_path,
            'invoice_id' => $this->invoice_id,
            'date' => $this->date,
            'due_date' => $this->due_date,
            'discount' => $this->discount,
            'total' => $this->total,
            'sub_total' => $this->sub_total,
            'obs' => $this->obs,
            'unique_hash' => $this->unique_hash,
            'status_code' => $this->status_code,
            'status' => $this->status,
            'is_original' => $this->is_original,
            'converted_to_invoice' => $this->converted_to_invoice,
            'use_matrix_price' => $this->use_matrix_price,
            'is_service' => $this->is_service,
            'exported_saft' => $this->exported_saft,
            'extra_data' => $this->extra_data,
            'revision_count' => $this->revision_count,
            'last_revision_at' => $this->last_revision_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('quotes.edit', $this->id),
                'pdf_path' => route('quotes.getPDF', ['id' => $this->id]),
                'delete_path' => route('quotes.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('quotes.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
