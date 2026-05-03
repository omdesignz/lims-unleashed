<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReagentConsumptionResource extends JsonResource
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
            'reagent_id' => $this->reagent_id,
            'reagent' => InventoryItemResource::make($this->whenLoaded('reagent'))?->name ?? null,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'reagent_name' => $this->reagent_name,
            'quantity_used' => $this->quantity_used,
            'used_by' => $this->used_by,
            'used_at' => $this->used_at,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
        ];
    }
}
