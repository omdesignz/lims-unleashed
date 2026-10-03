<?php

namespace App\Http\Requests;

use App\Support\ProposalAuthoringPayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVAPProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('edit_proposals');
    }

    protected function prepareForValidation(): void
    {
        $this->replace(ProposalAuthoringPayload::prepare($this->all()));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ProposalAuthoringPayload::rules(false, $this->all());
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [ProposalAuthoringPayload::checkSources(...)];
    }
}
