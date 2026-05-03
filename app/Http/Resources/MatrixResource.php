<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatrixResource extends JsonResource
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
            'code' => $this->code,
            'description' => $this->description,
            'price' => $this->price_based_on_profiles,
            'fixed_price' => $this->fixed_price,
            'tax_percentage' => $this->tax_percentage,
            'charge_tax' => $this->charge_tax,
            'exemption_id' => TaxExemptionResource::make($this->exemption),
            'exemption' => TaxExemptionResource::make($this->exemption)?->code ?? null,
            'tax_id' => TaxTypeResource::make($this->tax_category),
            'tax' => TaxTypeResource::make($this->exemption)?->name ?? null,
            'profiles' => ProfileResource::collection($this->whenLoaded('profiles')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('matrixes.edit', $this->id),
                'show_path' => route('matrixes.show', $this->id),
                'delete_path' => route('matrixes.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('matrixes.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
