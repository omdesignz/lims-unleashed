<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $testId = $this->route('proficiency_test') ?? $this->route('test');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('proficiency_tests', 'name')->ignore($testId)],
            'scheme_type' => ['required', Rule::in(['proficiency', 'interlaboratory'])],
            'role' => ['required', Rule::in(['participant', 'organizer'])],
            'provider_name' => ['required', 'string', 'max:255'],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'participants' => ['nullable', 'array'],
            'participants.*.code' => ['nullable', 'string', 'max:100'],
            'participants.*.name' => ['nullable', 'string', 'max:255'],
            'participants.*.contact' => ['nullable', 'string', 'max:255'],
            'participants.*.status' => ['nullable', Rule::in(['pending', 'enrolled', 'submitted', 'reviewed', 'requires_action'])],
            'parameters' => ['nullable', 'array'],
            'parameters.*.code' => ['nullable', 'string', 'max:100'],
            'parameters.*.name' => ['nullable', 'string', 'max:255'],
            'parameters.*.unit' => ['nullable', 'string', 'max:100'],
            'parameters.*.assigned_value' => ['nullable', 'numeric'],
            'parameters.*.standard_deviation' => ['nullable', 'numeric'],
            'round_reference' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['planned', 'in_progress', 'completed', 'reviewed', 'closed'])],
            'date' => ['required', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'enrollment_deadline_at' => ['nullable', 'date'],
            'submission_deadline_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'after_or_equal:date'],
            'scope' => ['nullable', 'string', 'max:500'],
            'outcome' => ['nullable', Rule::in(['satisfactory', 'questionable', 'unsatisfactory', 'pending'])],
            'z_score' => ['nullable', 'numeric', 'between:-10,10'],
            'corrective_actions' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'results' => ['nullable', 'array'],
            'participant_results' => ['nullable', 'array'],
            'assigned_values' => ['nullable', 'array'],
            'performance_summary' => ['nullable', 'array'],
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
            'role' => 'papel do laboratório',
            'provider_name' => 'provedor',
            'organizer_name' => 'organizador',
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
     * @param  Validator  $validator
     */
    public function prepareForValidation(): void
    {
        $results = $this->input('results');
        $participants = $this->input('participants');
        $parameters = $this->input('parameters');
        $participantResults = $this->input('participant_results');

        if (is_string($results) && trim($results) !== '') {
            $decoded = json_decode($results, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $results = $decoded;
            }
        }

        $this->merge([
            'results' => is_array($results) ? $results : [],
            'participants' => is_array($participants) ? array_values($participants) : [],
            'parameters' => is_array($parameters) ? array_values($parameters) : [],
            'participant_results' => is_array($participantResults) ? array_values($participantResults) : [],
            'outcome' => $this->input('outcome') ?: 'pending',
            'role' => $this->input('role') ?: 'participant',
        ]);
    }
}
