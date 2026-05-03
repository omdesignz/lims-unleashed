<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemActivityResource extends JsonResource
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
            'log_name' => $this->log_name,
            'description' => $this->description,
            'subject_id' => $this->subject_id,
            'causer_type' => $this->causer_type,
            'causer' => $this->whenLoaded('causer') ?? null,
            'properties' => $this->properties,
            'changes' => collect($this->changes)->values(),
            'created_at' => $this->created_at->toDateTimeString(),
            'links' => [
                'delete_path' => route('standards.destroy', [
                    'recordIds' => [$this->id]
                ])
            ]
        ];
    }
}
