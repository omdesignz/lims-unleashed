<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProficiencyTestRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $testId = $this->route('proficiency_test') ?? $this->route('test');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('proficiency_tests', 'name')->ignore($testId)],
            'scheme_type' => ['required', Rule::in(['proficiency', 'interlaboratory'])],
            'provider_name' => ['required', 'string', 'max:255'],
            'round_reference' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['planned', 'in_progress', 'completed', 'reviewed', 'closed'])],
            'date' => ['required', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'after_or_equal:date'],
            'scope' => ['nullable', 'string', 'max:500'],
            'outcome' => ['nullable', Rule::in(['satisfactory', 'questionable', 'unsatisfactory', 'pending'])],
            'z_score' => ['nullable', 'numeric', 'between:-10,10'],
            'corrective_actions' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'results' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => trans('gestlab.general.labels.proficiency_tests.name'),
            'date' => trans('gestlab.general.labels.proficiency_tests.date'),
            'results' => trans('gestlab.general.labels.proficiency_tests.results'),
            'scheme_type' => 'tipo de ensaio',
            'provider_name' => 'provedor',
            'round_reference' => 'referência da ronda',
            'status' => 'estado',
            'outcome' => 'resultado',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation(): void
    {
        $results = $this->input('results');

        if (is_string($results) && trim($results) !== '') {
            $decoded = json_decode($results, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $results = $decoded;
            }
        }

        $this->merge([
            'results' => is_array($results) ? $results : [],
            'outcome' => $this->input('outcome') ?: 'pending',
        ]);
    }
}
