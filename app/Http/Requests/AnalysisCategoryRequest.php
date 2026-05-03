<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name' => 'required|min:1|unique:analysis_categories,name',
                'description' => 'nullable',
                'code' => 'required|min:1',
                'department_id' => 'required|exists:departments,id',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:analysis_categories,name,' . request()->category,
                'description' => 'nullable',
                'code' => 'required|min:1',
                'department_id' => 'required|exists:departments,id',

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
            'name' => trans('gestlab.general.labels.analysis_categories.name'),
            'code' => trans('gestlab.general.labels.analysis_categories.code'),
            'description' => trans('gestlab.general.labels.analysis_categories.description'),
            'department_id' => trans('gestlab.general.labels.analysis_categories.department_id'),
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
        ]);
    }
}
