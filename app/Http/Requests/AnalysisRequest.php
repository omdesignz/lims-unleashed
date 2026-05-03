<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalysisRequest extends FormRequest
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
        if ($this->isMethod('post')) {
            $rules = [
                'col_date' => 'nullable|date_format:Y-m-d',
                'init_date' => 'nullable|date_format:Y-m-d',
                'entry_date' => 'nullable|date_format:Y-m-d',
                'sample_id' => 'required|exists:samples,id|unique:analysis,sample_id',
                'profile_id' => 'required|exists:profiles,id',
                'department_id' => 'required|exists:departments,id',
                'type_id' => 'required|exists:analysis_categories,id',
            ];
        } else {
            $rules = [
                'col_date' => 'nullable|date_format:Y-m-d',
                'init_date' => 'nullable|date_format:Y-m-d',
                'entry_date' => 'nullable|date_format:Y-m-d',
                'sample_id' => 'required|exists:samples,id|unique:analysis,sample_id,' . request()->analysis,
                'profile_id' => 'required|exists:profiles,id',
                'cl_id' => 'required|exists:lab_codes,id',
                'department_id' => 'required|exists:departments,id',
                'type_id' => 'required|exists:analysis_categories,id',

            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'col_date' => 'nome',
            'init_date' => 'código',
            'entry_date' => 'descrição',
            'department_id' => 'departamento',
            'sample_id' => 'amostra',
            'profile_id' => 'perfil',
            'type_id' => 'tipo de análise',
            'cl_id' => 'código',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        $this->merge([
            'department_id' => !is_null(request()->department_id) ? request()->department_id['value'] : null,
            'sample_id' => !is_null(request()->sample_id) ? request()->sample_id['value'] : null,
            'profile_id' => !is_null(request()->profile_id) ? request()->profile_id['value'] : null,
            'type_id' => !is_null(request()->type_id) ? request()->type_id['value'] : null,
            'cl_id' => !is_null(request()->cl_id) ? request()->cl_id['value'] : null,
        ]);
    }
}
