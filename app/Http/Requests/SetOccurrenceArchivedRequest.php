<?php

namespace App\Http\Requests;

use App\Services\SampleLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;

class SetOccurrenceArchivedRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(SampleLaboratoryAccess::class)->activeLabId();

        return $this->user()->can($this->routeIs('occurrences.restore') ? 'restore_occurrences' : 'delete_occurrences');
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'recordIds' => ['required', 'array', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
