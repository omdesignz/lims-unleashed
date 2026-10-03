<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetProposalRecordsArchivedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (int) $this->attributes->get('proposal_laboratory_id') > 0
            && $this->user()?->can($this->routeIs('*.restore') ? 'restore_proposals' : 'delete_proposals');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
