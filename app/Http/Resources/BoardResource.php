<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
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
            'description' => $this->description,
            'bgcolor' => $this->bgcolor,
            'iconcolor' => $this->iconcolor,
            'icon' => $this->icon,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'lists_count'=> $this?->lists?->count() ?? 0,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('analysis.edit', $this->id),
                'delete_path' => route('boards.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('analysis.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
