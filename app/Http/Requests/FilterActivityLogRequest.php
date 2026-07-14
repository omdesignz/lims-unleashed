<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
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
            'end_date.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'per_page.min' => 'O número de registos por página deve ser, no mínimo, 10.',
            'per_page.max' => 'O número de registos por página não pode ser superior a 100.',
        ];
    }
}
