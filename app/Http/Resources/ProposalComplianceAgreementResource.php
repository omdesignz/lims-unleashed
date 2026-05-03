<?php

namespace App\Http\Resources;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalComplianceAgreementResource extends JsonResource
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
            'proposal_id' => $this->proposal_id,
            'proposal' => ProposalResource::make($this->whenLoaded('proposal')),
            'confidentiality' => $this->confidentiality,
            'impartiality' => $this->impartiality,
            'nondisclosure' => $this->nondisclosure,
            'acknowledged_at' => $this->acknowledged_at,
            'client_ip' => $this->client_ip,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
