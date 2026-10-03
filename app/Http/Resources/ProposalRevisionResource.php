<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalRevisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = $this->properties;

        return [
            ...$this->resource->only(['id', 'description', 'created_at']),
            'event' => $this->event ?? ($this->description === 'revised' ? 'revised' : null),
            'causer' => $this->whenLoaded('causer', fn (): array => $this->causer->only(['id', 'name'])),
            'properties' => [
                'reason' => $this->when(is_string($properties['reason'] ?? null), fn () => $properties['reason']),
                'old_values' => $this->when(is_array($properties['old_values'] ?? null),
                    fn (): array => collect($properties['old_values'])->only(['total', 'items_count'])->all()),
                'new_values' => $this->when(is_array($properties['new_values'] ?? null),
                    fn (): array => collect($properties['new_values'])->only(['total', 'items_count'])->all()),
            ],
        ];
    }
}
