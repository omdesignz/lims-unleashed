<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
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
            'board_id' => $this->board_id,
            'board' => BoardResource::make($this->whenLoaded('board'))?->name ?? null,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'members' => CardMemberResource::collection($this->whenLoaded('members')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('analysis.edit', $this->id),
                'delete_path' => route('analysis.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('analysis.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
