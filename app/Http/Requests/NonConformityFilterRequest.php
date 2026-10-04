<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NonConformityFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view_occurrences') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'archived' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['opened', 'in_progress', 'resolved', 'closed'])],
            'severity' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'category' => ['nullable', Rule::in(['quality', 'safety', 'environmental', 'regulatory', 'other'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('start_date'), ['after_or_equal:start_date'])],
        ];
    }
}
