<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VAPProposalTemplateResource extends JsonResource
{
    private bool $includeLayout = true;

    public function forAuthoring(): static
    {
        $this->includeLayout = false;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only(['id', 'name', 'category', 'content', 'description', 'is_active', 'created_at', 'updated_at']),
            'theme_preset' => $this->when($this->includeLayout, fn () => $this->theme_preset),
            'layout_schema' => $this->when($this->includeLayout, fn () => $this->layout_schema),
            'export_settings' => $this->when($this->includeLayout, fn () => $this->export_settings),
            'user' => $this->whenLoaded('user', fn (): array => UserIdentityResource::make($this->user)->resolve($request)),
            'proposals_count' => $this->whenCounted('proposals'),
            'accepted_proposals_count' => $this->whenHas('accepted_proposals_count'),
            'pending_proposals_count' => $this->whenHas('pending_proposals_count'),
            'rejected_proposals_count' => $this->whenHas('rejected_proposals_count'),
        ];
    }
}
