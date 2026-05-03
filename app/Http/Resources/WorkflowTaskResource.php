<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowTaskResource extends JsonResource
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
            'file_id' => $this->file_id,
            'file' => new VAPFileResource($this->whenLoaded('file')),
            'type' => $this->type,
            'assigned_to' => $this->assigned_to,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'comments' => WorkflowTaskCommentResource::collection($this->whenLoaded('comments')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
        ];
    }
}
