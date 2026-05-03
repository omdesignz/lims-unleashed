<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardListResource extends JsonResource
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
            'board_id' => $this->board_id,
            'board' => BoardResource::make($this->whenLoaded('board'))?->name ?? null,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
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
