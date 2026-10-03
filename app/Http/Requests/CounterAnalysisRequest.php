<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class CounterAnalysisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit_counter_analysis') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $record = app(LaboratoryWorkflowOwnership::class)
            ->counterAnalysesForLaboratory(app(SampleLaboratoryAccess::class)->activeLabId())
            ->findOrFail($this->route('analysis'));

        if ($record->end_date !== null || $record->status) {
            throw ValidationException::withMessages(['analysis' => 'Esta contra-análise está concluída e não pode ser modificada.']);
        }

        return [
            'col_date' => ['nullable', 'date_format:Y-m-d'],
            'init_date' => ['nullable', 'date_format:Y-m-d'],
            'entry_date' => ['nullable', 'date_format:Y-m-d'],
            'sample_id' => ['required', 'integer', Rule::in([$record->sample_id])],
            'result_id' => ['required', 'integer', Rule::in([$record->result_id])],
            'cl_id' => ['required', 'integer', Rule::in([$record->cl_id])],
            'profile_id' => ['required', 'integer', Rule::in([$record->profile_id])],
            'parameter_id' => ['required', 'integer', Rule::in([$record->parameter_id])],
            'department_id' => ['required', 'integer', Rule::in([$record->department_id])],
            'type_id' => ['required', 'integer', Rule::in([$record->type_id])],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'col_date' => 'data de colheita',
            'init_date' => 'data de início',
            'entry_date' => 'data de entrada',
            'department_id' => 'departamento',
            'sample_id' => 'amostra',
            'profile_id' => 'perfil',
            'parameter_id' => 'parâmetro',
            'type_id' => 'tipo de análise',
            'cl_id' => 'código',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    protected function prepareForValidation(): void
    {
        foreach (['department_id', 'sample_id', 'profile_id', 'parameter_id', 'result_id', 'type_id', 'cl_id'] as $field) {
            $value = $this->input($field);
            $this->merge([$field => is_array($value) ? data_get($value, 'value') : $value]);
        }
    }
}
