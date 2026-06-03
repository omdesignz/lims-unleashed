<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProficiencyTestResource extends JsonResource
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
            'date' => $this->date?->toDateString(),
            'scheme_type' => $this->scheme_type,
            'role' => $this->role ?? 'participant',
            'provider_name' => $this->provider_name,
            'organizer_name' => $this->organizer_name,
            'participants' => $this->participants ?? [],
            'parameters' => $this->parameters ?? [],
            'round_reference' => $this->round_reference,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toDateString(),
            'enrollment_deadline_at' => $this->enrollment_deadline_at?->toDateString(),
            'submission_deadline_at' => $this->submission_deadline_at?->toDateString(),
            'closed_at' => $this->closed_at?->toDateString(),
            'deadline_date' => $this->deadlineDate()?->toDateString(),
            'deadline_state' => $this->deadlineState(),
            'days_until_deadline' => $this->deadlineDate() ? now()->startOfDay()->diffInDays($this->deadlineDate()->copy()->startOfDay(), false) : null,
            'scope' => $this->scope,
            'outcome' => $this->outcome,
            'z_score' => $this->z_score,
            'corrective_actions' => $this->corrective_actions,
            'notes' => $this->notes,
            'results' => $this->results,
            'participant_results' => $this->participant_results ?? [],
            'assigned_values' => $this->assigned_values ?? [],
            'performance_summary' => $this->performance_summary ?? $this->calculatePerformanceSummary(),
            'participant_count' => $this->participantCount(),
            'parameter_count' => $this->parameterCount(),
            'result_count' => $this->resultCount(),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'show_path' => route('proficiency_tests.show', $this->id),
                'edit_path' => route('proficiency_tests.edit', $this->id),
                'delete_path' => route('proficiency_tests.destroy', [
                    'recordIds' => [$this->id],
                ]),
                'restore_path' => route('proficiency_tests.restore', [
                    'recordIds' => [$this->id],
                ]),
            ],
        ];
    }
}
