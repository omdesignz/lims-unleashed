<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VAPFileShareResource extends JsonResource
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
            'shared_with' => $this->shared_with,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'shared_with_user' => new UserResource($this->whenLoaded('sharedWithUser')),
        ];
    }
}
