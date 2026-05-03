<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgrammedCollectionResource extends JsonResource
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
            'user_id' => UserResource::make($this->whenLoaded('user')),
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'vehicle_id' => VehicleResource::make($this->whenLoaded('vehicle')),
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle'))?->name ?? null,
            'quote_id' => QuoteResource::make($this->whenLoaded('quote')),
            'quote' => QuoteResource::make($this->whenLoaded('quote'))?->quote_no ?? null,
            'vehicle_reference' => $this->vehicle_reference,
            'col_date' => $this->col_date,
            'status' => $this->status,
            'placed_analysis' => $this->placed_analysis,
            'quoted' => $this->quoted,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('programmedcollections.edit', $this->id),
                'delete_path' => route('programmedcollections.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('programmedcollections.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
