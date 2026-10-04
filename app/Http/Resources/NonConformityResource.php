<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class NonConformityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $nonConformity = $this->resource;
        $payload = Arr::except($nonConformity->toArray(), ['media']);
        $payload['workflow_history'] = collect($nonConformity->workflow_history ?? [])
            ->map(fn (array $entry) => Arr::only($entry, ['revision', 'action', 'from', 'to', 'actor_id', 'actor_name', 'at', 'evidence']))->all();
        $payload['media_attachments'] = $nonConformity
            ->getMedia('attachments')
            ->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'human_readable_size' => $media->human_readable_size,
                'url' => route('vap_non_conformities.attachments.show', [$nonConformity, $media]),
            ])
            ->values();

        foreach (['reported_at', 'due_date'] as $field) {
            $payload[$field] = $nonConformity->{$field}?->format('Y-m-d\\TH:i:s');
        }
        if ($nonConformity->relationLoaded('actions')) {
            $payload['actions'] = $nonConformity->actions->map(fn ($action) => [
                ...$action->toArray(),
                'due_at' => $action->due_at?->format('Y-m-d\\TH:i:s'),
            ])->values()->all();
        }

        return $payload;
    }
}
