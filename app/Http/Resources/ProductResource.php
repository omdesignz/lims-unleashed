<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'matrix_id' => $this->matrix_id,
            'matrix' => MatrixResource::make($this->matrix)?->description ?? null,
            'exemption_code' => $this->exemption_code,
            'exemption_id' => TaxExemptionResource::make($this->exemption),
            'exemption' => TaxExemptionResource::make($this->exemption)?->reason ?? null,
            'price' => $this->price,
            // 'price' => MatrixResource::make($this->matrix)?->price || 0,
            'fixed_price' => $this->fixed_price,
            'tax_percentage' => $this->tax_percentage,
            'tax_id' => $this->tax_id,
            'tax_category' => TaxTypeResource::make($this->tax_category)?->name ?? null,
            'withhold_tax' => $this->withhold_tax,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('products.edit', $this->id),
                'delete_path' => route('products.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('products.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
