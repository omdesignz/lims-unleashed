<?php

namespace App\Http\Requests;

use App\Support\ProposalAuthoringPayload;
use Illuminate\Foundation\Http\FormRequest;

class StoreVAPProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('add_proposals');
    }

    protected function prepareForValidation(): void
    {
        $this->replace(ProposalAuthoringPayload::prepare($this->all()));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ProposalAuthoringPayload::rules(true, $this->all());
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [ProposalAuthoringPayload::checkSources(...)];
    }
}
