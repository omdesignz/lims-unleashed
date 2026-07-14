<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationMappingRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'field_paths' => ['required', 'array'],
            'field_paths.sample_code' => ['required', 'string', 'max:255'],
            'field_paths.parameter_code' => ['required', 'string', 'max:255'],
            'field_paths.value' => ['required', 'string', 'max:255'],
            'field_paths.external_id' => ['nullable', 'string', 'max:255'],
            'field_paths.unit' => ['nullable', 'string', 'max:255'],
            'field_paths.measured_at' => ['nullable', 'string', 'max:255'],
            'field_paths.instrument_serial' => ['nullable', 'string', 'max:255'],
            'field_paths.operator' => ['nullable', 'string', 'max:255'],
            'transformations' => ['nullable', 'array'],
            'transformations.*' => ['nullable', 'array'],
            'transformations.*.*' => ['string', Rule::in(['trim', 'uppercase', 'lowercase', 'decimal_comma'])],
            'constants' => ['nullable', 'array'],
            'constants.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
