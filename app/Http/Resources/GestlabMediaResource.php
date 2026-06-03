<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GestlabMediaResource extends JsonResource
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
            'name' => $this->name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ],
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
            'preview_url' => $this->preview_url,
            'url' => $this->url,
            'links' => [
                'edit_path' => route('media.edit', $this->id),
                'delete_path' => route('media.destroy', [
                    'recordIds' => [$this->id],
                ]),
                'restore_path' => route('media.restore', [
                    'recordIds' => [$this->id],
                ]),
            ],
        ];
    }
}
