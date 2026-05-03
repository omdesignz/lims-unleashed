<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
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
            'proposal_no' => $this->proposal_no,
            'user_id' => UserResource::make($this->whenLoaded('user')),
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'customer_id' => CustomerResource::make($this->whenLoaded('customer')),
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'warehouse_id' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'discount_type' => DiscountCategoryResource::make($this->whenLoaded('discount_category')),
            'discount_category' => DiscountCategoryResource::make($this->whenLoaded('discount_category'))?->name ?? null,
            'template_id' => ProposalTemplateResource::make($this->whenLoaded('template')),
            'template' => ProposalTemplateResource::make($this->whenLoaded('template'))?->name ?? null,
            'template_id' => $this->template_id,
            'service_location' => $this->service_location,
            'details' => json_decode($this->details),
            'expiry_date' => $this->expiry_date,
            'is_original' => $this->is_original,
            'qr' => $this->Qr,
            'discount_type' => $this->discount_type,
            'file_path' => $this->file_path,
            'sub_total' => $this->sub_total,
            'total' => $this->total,
            'unique_hash' => $this->unique_hash,
            'use_matrix_price' => $this->use_matrix_price,
            'withholding_tax_amount' => $this->withholding_tax_amount,
            'withholding_tax_percentage' => $this->withholding_tax_percentage,
            'global_discount_amount' => $this->global_discount_amount,
            'global_discount_percentage' => $this->global_discount_percentage,
            'withhold_tax' => $this->withhold_tax,
            'converted_to_invoice' => $this->converted_to_invoice,
            'items' => $this->items,
            'user_id' => $this->user_id,
            'tolerance_days' => $this->tolerance_days,
            'obs' => $this->obs,
            'revision_count' => $this->revision_count,
            'last_revision_at' => $this->last_revision_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'links' => [
                'edit_path' => route('proposals.edit', $this->id),
                'show_path' => route('proposals.show', $this->id),
                'delete_path' => route('proposals.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('proposals.restore', [
                    'recordIds' => [$this->id]
                ]),
                'pdf_path' => route('proposals.getPDF', ['id' => $this->id])
            ]
        ];
    }
}
