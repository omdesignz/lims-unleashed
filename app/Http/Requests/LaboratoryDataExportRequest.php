<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LaboratoryDataExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->input('view') === 'audit') {
            return $this->user()?->can('view_results') ?? false;
        }

        return $this->user()?->can('view_analysis') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'view' => ['required', Rule::in(['pending', 'audit'])],
            'stage' => ['required', Rule::in(['all', 'inserted', 'verified', 'approved'])],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaultView = $this->user()?->can('view_analysis') ? 'pending' : 'audit';

        $this->merge([
            'view' => $this->input('view', $defaultView),
            'stage' => $this->input('stage', 'all'),
            'department_id' => $this->filled('department_id') ? $this->input('department_id') : null,
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'per_page' => $this->integer('per_page', 25),
        ]);
    }
}
