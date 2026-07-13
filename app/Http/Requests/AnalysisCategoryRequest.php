<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnalysisCategoryRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                Rule::unique('analysis_categories', 'name')->ignore($this->route('category')),
            ],
            'description' => ['nullable', 'string'],
            'code' => ['required', 'string', 'min:1'],
            'department_id' => ['required', Rule::exists('departments', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('gestlab.general.labels.analysis_categories.name'),
            'code' => trans('gestlab.general.labels.analysis_categories.code'),
            'description' => trans('gestlab.general.labels.analysis_categories.description'),
            'department_id' => trans('gestlab.general.labels.analysis_categories.department_id'),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    public function prepareForValidation(): void
    {
        $department = $this->input('department_id');

        $this->merge([
            'department_id' => is_array($department) || is_object($department)
                ? data_get($department, 'value')
                : ($department === '' ? null : $department),
        ]);
    }
}
