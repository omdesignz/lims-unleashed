<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VAPFileVersionResource extends JsonResource
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
            'file_id' => $this->file_id,
            'revision_code' => $this->revision_code,
            'content' => $this->content,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'created_by' => $this->created_by,
            'comment' => $this->comment,
            'change_reason' => $this->change_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
