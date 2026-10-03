<?php

namespace App\Http\Resources;

use App\Services\StaffAccountAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'gender' => $this->gender,
            'email' => $this->email,
            'username' => $this->username,
            'department' => $this->departments,
            'primary_phone' => $this->primary_phone,
            'profile_photo_url' => $this->profile_photo_url,
            'secondary_phone' => $this->secondary_phone,
            'id_number' => $this->id_number,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'last_activity_at' => $this->last_activity_at,
            'dob' => $this->dob?->format('Y-m-d'),
            'deleted' => $this->deleted_at ? true : false,
            'action_capabilities' => app(StaffAccountAccess::class)->capabilities($request->user(), $this->resource),
            'links' => [
                'edit_path' => route('users.edit', $this->id),
                'delete_path' => route('users.destroy'),
                'restore_path' => route('users.restore'),
            ],
        ];
    }
}
