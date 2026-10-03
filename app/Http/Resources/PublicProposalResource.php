<?php

namespace App\Http\Resources;

use App\Models\VAPProposalItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProposalResource extends JsonResource
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
                'proposal_number', 'status', 'status_badge', 'unique_hash', 'created_at', 'expiry_date', 'days_until_expiry',
                'service_location', 'tolerance_days', 'use_matrix_price', 'withhold_tax', 'obs', 'sub_total', 'total', 'tax', 'discount',
                'global_discount_amount', 'global_discount_percentage', 'withholding_tax_amount', 'withholding_tax_percentage',
            ]),
            'customer' => $this->whenLoaded('customer', fn (): array => $this->customer->only([
                'name', 'code', 'address', 'nif', 'primary_phone', 'contact', 'phone', 'email', 'invoicing_email',
            ])),
            'warehouse' => $this->whenLoaded('warehouse', fn (): array => $this->warehouse->only(['name', 'address'])),
            'department' => $this->whenLoaded('department', fn (): array => $this->department->only(['name'])),
            'user' => $this->whenLoaded('user', fn (): array => $this->user->only(['name'])),
            'template' => $this->whenLoaded('template', fn (): array => $this->template->only(['name', 'content'])),
            'compliance_agreement' => $this->whenLoaded('complianceAgreement', fn (): array => $this->complianceAgreement->only([
                'confidentiality', 'impartiality', 'nondisclosure', 'acknowledged_at', 'rejected_at', 'rejection_reason',
            ])),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (VAPProposalItem $item): array => [
                ...$item->only([
                    'id', 'item_description', 'obs', 'qty', 'unit_price', 'total', 'charge_tax', 'withhold_tax', 'exemption_code',
                    'discount_id', 'discount_percentage', 'discount_amount', 'tax_percentage', 'tax_amount',
                ]),
                'standard' => $item->standard?->only(['code']),
                'unit' => $item->unit?->only(['code']),
            ])),
        ];
    }
}
