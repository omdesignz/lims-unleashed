<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractGuideItemResource extends JsonResource
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
            'guide_id' => ContractGuideResource::make($this->whenLoaded('guide')),
            'guide' => ContractGuideResource::make($this->whenLoaded('guide'))?->guide_no ?? null,
            'product_id' => ProductResource::make($this->whenLoaded('product')),
            'product' => ProductResource::make($this->whenLoaded('product'))?->name ?? null,
            'country_id' => CountryResource::make($this->whenLoaded('country')),
            'country' => CountryResource::make($this->whenLoaded('country'))?->address ?? null,
            'bl' => $this->bl,
            'lot' => $this->lot,
            'manufacturer' => $this->manufacturer,
            'origin' => $this->origin,
            'brand' => $this->brand,
            'obs' => $this->obs,
            'du_no' => $this->du_no,
            'collection_id' => $this->collection_id,
            'date' => $this->date,
            'extra_data' => $this->extra_data,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('contractguideitems.edit', $this->id),
                'delete_path' => route('contractguideitems.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('contractguideitems.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
