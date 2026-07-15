<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array'],
            'preferences.*.category' => ['required', Rule::in(['laboratory', 'inventory', 'quality', 'maintenance', 'commercial', 'trade', 'documents', 'system'])],
            'preferences.*.database_enabled' => ['required', 'boolean'],
            'preferences.*.broadcast_enabled' => ['required', 'boolean'],
            'preferences.*.mail_enabled' => ['required', 'boolean'],
            'preferences.*.quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'preferences.*.quiet_hours_end' => ['nullable', 'date_format:H:i'],
            'preferences.*.timezone' => ['required', 'timezone:all'],
        ];
    }
}
