<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OccurrenceResource extends JsonResource
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
            'occurrence_no' => $this->occurrence_no,
            'date_reported' => $this->date_reported ? $this->date_reported?->format('Y-m-d') : null,
            'issue_description' => $this->issue_description,
            'corrective_action' => $this->corrective_action,
            'date_resolved' => $this->date_resolved ? $this->date_resolved?->format('Y-m-d') : null,
            'notification_date' => $this->notification_date ? $this->notification_date?->format('Y-m-d') : null,
            'client_process_open_notification_date' => $this->client_process_open_notification_date ? $this->client_process_open_notification_date?->format('Y-m-d') : null,
            'analysis' => $this->analysis,
            'has_risk_correction_budget' => $this->has_risk_correction_budget,
            'reason_for_no_risk_correction_budget' => $this->reason_for_no_risk_correction_budget,
            'has_non_conformity_terms' => $this->has_non_conformity_terms,
            'effect_corrective_actions' => $this->effect_corrective_actions,
            'cause_corrective_actions' => $this->cause_corrective_actions,
            'implementation_date' => $this->implementation_date ? $this->implementation_date?->format('Y-m-d') : null,
            'implementation_date_overdue' => $this->implementation_date ? $this->implementation_date < now()->format('Y-m-d') : false,
            'update_risk_matrix' => $this->update_risk_matrix,
            'client_process_close_notification_date' => $this->client_process_close_notification_date,
            'client_acceptance' => $this->client_acceptance,
            'client_acceptance_comments' => $this->client_acceptance_comments,
            'date_closed' => $this->date_closed ? $this->date_closed?->format('Y-m-d') : null, 
            'obs' => $this->obs,
            'was_effective' => $this->was_effective,
            'status_id' => $this->status_id,
            'status' => OccurrenceStatusResource::make($this->status)?->name ?? null,
            'responsible_name' => $this->responsible_name,
            'department_id' => $this->department_id,
            'department' => DepartmentResource::make($this->department)?->name ?? null,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->user)?->name ?? null,
            'origin_id' => $this->origin_id,
            'origin' => OccurrenceOriginResource::make($this->origin)?->name ?? null,
            'category_id' => $this->category_id,
            'category' => OccurrenceCategoryResource::make($this->whenLoaded('category'))?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('occurrences.edit', $this->id),
                'delete_path' => route('occurrences.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('occurrences.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
