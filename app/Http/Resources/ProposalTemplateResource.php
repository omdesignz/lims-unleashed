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
            'user_id' => UserIdentityResource::make($this->whenLoaded('user')),
            'user' => $this->whenLoaded('user', fn (): string => $this->user->name),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'deleted' => $this->resource->trashed(),
            'can_archive' => ! $this->resource->trashed() && ! $this->proposals_exists,
            'links' => [
                'edit_path' => route('proposaltemplates.edit', $this->id),
                'show_path' => route('vap-proposals.templates.show', $this->id),
                'delete_path' => route('proposaltemplates.destroy', [
                    'recordIds' => [$this->id],
                ]),
                'restore_path' => route('proposaltemplates.restore', [
                    'recordIds' => [$this->id],
                ]),
            ],
        ];
    }
}
