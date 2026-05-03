<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            'contact' => $this->contact,
            'extension' => $this->extension,
            'code' => $this->code,
            'email' => $this->email,
            'supervisor_id' => UserResource::make($this->supervisor),
            'supervisor' => UserResource::make($this->supervisor)?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('departments.edit', $this->id),
                'delete_path' => route('departments.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('departments.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
