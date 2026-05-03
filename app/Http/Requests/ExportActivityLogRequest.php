<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportActivityLogRequest extends FormRequest
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
    public function rules()
    {
        return [
            'log_name' => 'nullable|string|max:255',
            'causer_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
            'subject_type' => 'nullable|string|max:255',
            'event' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:xlsx,csv,pdf',
        ];
    }
}
