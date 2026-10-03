<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryReagentChoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id, 'name' => $this->name, 'code' => $this->code,
            'category' => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name] : null,
            'unit' => $this->unit ? ['id' => $this->unit->id, 'code' => $this->unit->code] : null,
            'reagent_expiry_date' => $this->reagent_expiry_date,
            'is_archived' => $this->resource->trashed(),
        ];
        if ($this->resource->relationLoaded('inventory')) {
            $data['inventory'] = $this->inventory->map(fn ($stock): array => $stock->only(['warehouse_id', 'qty_available']))->all();
            $data['total_stock'] = $this->inventory->sum('qty_available');
        }

        return $data;
    }
}
