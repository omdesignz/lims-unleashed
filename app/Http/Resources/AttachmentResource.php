<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
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
            'message_id' => $this->message_id,
            'message' => MessageResource::make($this->whenLoaded('message'))?->description ?? null,
            'file_type' => $this->file_type,
            'file_path' => $this->file_path,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('attachments.edit', $this->id),
                'delete_path' => route('attachments.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('attachments.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
