<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratorySampleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'sample_type' => $this->sample_type,
            'status' => $this->status,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer?->only(['id', 'name'])),
            'received_at' => $this->received_at?->toIso8601String(),
            'retention_due_at' => $this->retention_due_at?->toDateString(),
            'details_url' => route('vap_samples.queue.show', $this->id),
            'details' => $this->when($request->routeIs('vap_samples.queue.show'), fn () => [
                'requested_services' => $this->requested_services,
                'observations' => $this->obs,
                'analysis_started_at' => $this->analysis_start_date?->toIso8601String(),
                'analysis_completed_at' => $this->analysis_end_date?->toIso8601String(),
            ]),
        ];
    }
}
