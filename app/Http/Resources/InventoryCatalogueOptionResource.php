<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCatalogueOptionResource extends JsonResource
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
            'code' => $this->code,
            'internal_code' => $this->internal_code,
            'category_id' => $this->category_id,
            'inventory_type' => $this->category->inventory_type?->value,
            'unit_id' => $this->unit_id,
            'is_reagent' => $this->is_reagent,
        ];
    }
}
