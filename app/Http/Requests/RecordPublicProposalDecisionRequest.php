<?php

namespace App\Http\Requests;

use App\Actions\RecordPublicProposalDecision;
use App\Models\VAPProposal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecordPublicProposalDecisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('proposal') instanceof VAPProposal;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return RecordPublicProposalDecision::rules($this->routeIs('proposals.api.accept'));
    }
}
