<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
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
            'label' => $this->label,
            'guard_name' => $this->guard_name,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('permissions.edit', $this->id),
                'delete_path' => route('permissions.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('permissions.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
