<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VAPProposalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'proposal_no', 'proposal_year', 'proposal_number', 'status', 'status_badge',
                'customer_id', 'warehouse_id', 'department_id', 'user_id', 'template_id', 'unique_hash',
                'service_location', 'tolerance_days', 'obs', 'use_matrix_price', 'withhold_tax', 'is_original',
                'converted_to_invoice', 'sub_total', 'total', 'tax', 'discount',
                'global_discount_amount', 'global_discount_percentage', 'withholding_tax_amount', 'withholding_tax_percentage',
                'created_at', 'updated_at', 'expiry_date', 'days_until_expiry',
            ]),
            'has_document' => filled($this->file_path),
            'can_revise' => (bool) $request->user()?->can('edit_proposals')
                && in_array($this->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'], true),
            'can_archive' => ! $this->resource->trashed() && (bool) $request->user()?->can('delete_proposals')
                && in_array($this->status, ['PENDING', 'REJECTED'], true),
            'items_count' => $this->whenCounted('items'),
            'customer' => $this->whenLoaded('customer', fn (): array => $this->customer->only(['id', 'name', 'code'])),
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => $this->warehouse->only(['id', 'name', 'address'])),
            'department' => $this->whenLoaded('department', fn (): array => $this->department->only(['id', 'name'])),
            'user' => $this->whenLoaded('user', fn (): array => UserIdentityResource::make($this->user)->resolve($request)),
            'template' => $this->whenLoaded('template', fn (): array => VAPProposalTemplateResource::make($this->template)->forAuthoring()->resolve($request)),
            'items' => $this->whenLoaded('items', fn (): array => VAPProposalItemResource::collection($this->items)->resolve($request)),
            'compliance_agreement' => $this->whenLoaded('complianceAgreement', fn (): array => $this->complianceAgreement->only([
                'id', 'proposal_id', 'confidentiality', 'impartiality', 'nondisclosure', 'acknowledged_at',
                'rejected_at', 'rejection_reason', 'client_ip',
            ])),
        ];
    }
}
