<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParameterProfileResource extends JsonResource
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
            'name' => $this->parameter?->name,
            'price' => $this->parameter?->price,
            'unit_label' => $this->unit_label,
            'protocol_label' => $this->protocol_label,
            'category_label' => $this->category_label,
            'nwp_label' => $this->nwp_label,
            'min_ref_value' => $this->min_ref_value,
            'max_ref_value' => $this->max_ref_value,
            'optimal_analysis_time' => $this->optimal_analysis_time,
        ];
    }
}
