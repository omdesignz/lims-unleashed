<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalTemplateResource extends JsonResource
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
            'content' => $this->content,
            'user_id' => UserResource::make($this->whenLoaded('user')),
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'links' => [
                'edit_path' => route('proposaltemplates.edit', $this->id),
                'delete_path' => route('proposaltemplates.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('proposaltemplates.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
