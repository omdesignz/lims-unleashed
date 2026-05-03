<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaidServiceResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'charge_tax' => $this->charge_tax,
            'exemption_code' => $this->exemption_code,
            'exemption_id' => TaxExemptionResource::make($this->exemption),
            'exemption' => TaxExemptionResource::make($this->exemption)?->reason ?? null,
            'price' => $this->price,
            'fixed_price' => $this->fixed_price,
            'tax_percentage' => $this->tax_percentage,
            'tax_id' => $this->tax_id,
            'tax_category' => TaxTypeResource::make($this->tax_category)?->name ?? null,
            'withhold_tax' => $this->withhold_tax,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('paidservices.edit', $this->id),
                'delete_path' => route('paidservices.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('paidservices.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
