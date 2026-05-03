<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchivedDocumentResource extends JsonResource
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
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('archived_documents.edit', $this->id),
                'delete_path' => route('archived_documents.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('archived_documents.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
