<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VAPFileResource extends JsonResource
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
            'document_number' => $this->document_number,
            'type' => $this->type,
            'document_type' => $this->document_type,
            'category' => $this->category,
            'revision_code' => $this->revision_code,
            'size' => $this->size,
            'modified_at' => $this->modified_at,
            'parent_id' => $this->parent_id,
            'mime_type' => $this->mime_type,
            'content' => $this->content,
            'status' => $this->status,
            'confidentiality_level' => $this->confidentiality_level,
            'is_controlled' => $this->is_controlled,
            'requires_periodic_review' => $this->requires_periodic_review,
            'retention_period_days' => $this->retention_period_days,
            'effective_at' => $this->effective_at,
            'review_due_at' => $this->review_due_at,
            'approved_at' => $this->approved_at,
            'obsolete_at' => $this->obsolete_at,
            'change_reason' => $this->change_reason,
            'meta' => $this->meta,
            'created_by' => $this->created_by,
            'owner_id' => $this->owner_id,
            'approved_by' => $this->approved_by,
            'superseded_by' => $this->superseded_by,
            'archived' => $this->archived,
            'archived_at' => $this->archived_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'current_access_level' => $this->when(
                $request->user(),
                fn () => $this->accessLevelFor($request->user())
            ),
            
            // Relationships
            'parent' => new VAPFileResource($this->whenLoaded('parent')),
            'children' => VAPFileResource::collection($this->whenLoaded('children')),
            'versions' => VAPFileVersionResource::collection($this->whenLoaded('versions')),
            'permissions' => VAPFilePermissionResource::collection($this->whenLoaded('permissions')),
            'shares' => VAPFileShareResource::collection($this->whenLoaded('shares')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'superseded_by_file' => new VAPFileResource($this->whenLoaded('supersededBy')),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->pluck('name');
            }),
        ];
    }
}
