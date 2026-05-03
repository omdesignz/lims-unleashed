<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'sender_id' => $this->sender_id,
            'sender' => UserResource::make($this->whenLoaded('sender'))?->name ?? null,
            'receiver_id' => $this->receiver_id,
            'receiver' => UserResource::make($this->whenLoaded('receiver'))?->name ?? null,
            'message' => $this->message,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('messages.edit', $this->id),
                'delete_path' => route('messages.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('messages.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
