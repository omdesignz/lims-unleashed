<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data;

        if (is_string($data)) {
            $data = json_decode($data, true);
        } else {
            $data = $data;
        }

        return [
            'id' => $this->id,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'sender' => [
                'id' => $data['sender_id'] ?? null,
                'name' => $data['sender_name'] ?? null,
            ],
            'recipient_id' => $this->notifiable_id,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'links' => [
                'edit_path' => route('notifications.edit', $this->id),
                'delete_path' => route('notifications.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'read_path' => route('notifications.read', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
