<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
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
            'note_no' => $this->note_no,
            'user_id' => UserResource::make($this->whenLoaded('user')),
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'customer_id' => CustomerResource::make($this->whenLoaded('customer')),
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'warehouse_id' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'discount_type' => DiscountCategoryResource::make($this->whenLoaded('discount_category')),
            'discount_category' => DiscountCategoryResource::make($this->whenLoaded('discount_category'))?->name ?? null,
            'invoice_id' => InvoiceResource::make($this->whenLoaded('invoice')),
            'description' => $this->description,
            'internal_ref' => $this->internal_ref,
            'reason' => $this->reason,
            'file_path' => $this->file_path,
            'date' => $this->date,
            'discount' => $this->discount,
            'total' => $this->total,
            'sub_total' => $this->sub_total,
            'amount' => $this->amount,
            'obs' => $this->obs,
            'unique_hash' => $this->unique_hash,
            'status' => $this->status,
            'is_original' => $this->is_original,
            'use_matrix_price' => $this->use_matrix_price,
            'is_service' => $this->is_service,
            'exported_saft' => $this->exported_saft,
            'extra_data' => $this->extra_data,
            'revision_count' => $this->revision_count,
            'last_revision_at' => $this->last_revision_at,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('creditnotes.edit', $this->id),
                'pdf_path' => route('creditnotes.getPDF', ['id' => $this->id]),
                'delete_path' => route('creditnotes.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('creditnotes.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
