<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VAPProposalItemResource extends JsonResource
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
                'id', 'item_id', 'itemable_type', 'itemable_id', 'item_description', 'standard_id', 'unit_id',
                'qty', 'unit_price', 'total', 'discount_id', 'discount_percentage', 'discount_amount',
                'tax_id', 'tax_percentage', 'tax_amount', 'charge_tax', 'withhold_tax',
                'exemption_id', 'exemption_code', 'obs',
            ]),
            'standard' => $this->whenLoaded('standard', fn (): array => $this->standard->only(['id', 'code', 'description'])),
            'unit' => $this->whenLoaded('unit', fn (): array => $this->unit->only(['id', 'code', 'description'])),
        ];
    }
}
