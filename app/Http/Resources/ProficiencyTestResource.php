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
            'date' => $this->date,
            'scheme_type' => $this->scheme_type,
            'provider_name' => $this->provider_name,
            'round_reference' => $this->round_reference,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'closed_at' => $this->closed_at,
            'scope' => $this->scope,
            'outcome' => $this->outcome,
            'z_score' => $this->z_score,
            'corrective_actions' => $this->corrective_actions,
            'notes' => $this->notes,
            'results' => $this->results,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('proficiency_tests.edit', $this->id),
                'delete_path' => route('proficiency_tests.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('proficiency_tests.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
