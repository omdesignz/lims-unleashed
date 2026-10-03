<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VAPProposalSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only(['id', 'proposal_number', 'status', 'total']),
            'customer' => $this->whenLoaded('customer', fn (): array => $this->customer->only(['name'])),
            'can_view' => (bool) $request->user()?->can('view_proposals'),
        ];
    }
}
