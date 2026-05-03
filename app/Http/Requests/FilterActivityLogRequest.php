<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterActivityLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'log_name' => 'nullable|string|max:255',
            'causer_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
            'subject_type' => 'nullable|string|max:255',
            'event' => 'nullable|string|max:255',
            'property' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'batch_uuid' => 'nullable|string|max:36',
            'per_page' => 'nullable|integer|min:10|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'per_page.min' => 'The per page value must be at least 10.',
            'per_page.max' => 'The per page value may not be greater than 100.',
        ];
    }
}
