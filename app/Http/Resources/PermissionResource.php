<?php

namespace App\Http\Resources;

use App\Services\StaffAccountAccess;
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
            'action_capabilities' => array_fill_keys(['edit', 'delete', 'restore'], app(StaffAccountAccess::class)->isSystemAdministrator($request->user())),
            'links' => [
                'edit_path' => route('permissions.edit', $this->id),
                'delete_path' => route('permissions.destroy', [
                    'recordIds' => [$this->id],
                ]),
                'restore_path' => route('permissions.restore', [
                    'recordIds' => [$this->id],
                ]),
            ],
        ];
    }
}
