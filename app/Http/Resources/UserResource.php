<?php

namespace App\Http\Resources;

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
            'competence_summary' => $this->competenceSummary(),
            'personnel_qualifications' => $this->whenLoaded('personnelQualifications', function () {
                return $this->personnelQualifications->map(fn ($qualification) => [
                    'id' => $qualification->id,
                    'capability' => $qualification->capability,
                    'department_id' => $qualification->department_id,
                    'authorized_from' => $qualification->authorized_from?->format('Y-m-d'),
                    'authorized_until' => $qualification->authorized_until?->format('Y-m-d'),
                    'training_completed_at' => $qualification->training_completed_at?->format('Y-m-d'),
                    'training_reference' => $qualification->training_reference,
                    'notes' => $qualification->notes,
                    'is_active' => $qualification->is_active,
                    'monitoring_status' => $qualification->monitoringStatus(),
                    'renewal_readiness' => $qualification->renewalReadiness(),
                    'follow_up_due_at' => $qualification->followUpDueAt()?->toDateString(),
                    'days_until_expiry' => $qualification->daysUntilExpiry(),
                ])->values();
            }),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('users.edit', $this->id),
                'delete_path' => route('users.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('users.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
