<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'file_path' => $this->file_path,
            'version' => $this->version,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('documents.edit', $this->id),
                'delete_path' => route('documents.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('documents.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
