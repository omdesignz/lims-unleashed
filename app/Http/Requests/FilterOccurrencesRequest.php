<?php

namespace App\Http\Requests;

use App\Services\SampleLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;

class FilterOccurrencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(SampleLaboratoryAccess::class)->activeLabId();

        return $this->user()->can('view_occurrences');
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'in:trashed'],
            'date' => ['nullable', 'array:start,end'],
            'date.start' => ['required_with:date.end', 'nullable', 'date'],
            'date.end' => ['required_with:date.start', 'nullable', 'date', 'after_or_equal:date.start'],
        ];
    }
}
