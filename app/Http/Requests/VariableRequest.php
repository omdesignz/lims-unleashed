<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VariableRequest extends FormRequest
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
                'name' => 'required',
                'value' => 'required',
                'formula_id' => 'required|exists:formulas,id',
            ];
        } else {
            $rules = [
                'name' => 'required',
                'value' => 'required',
                'formula_id' => 'required|exists:formulas,id',

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
            'name' => trans('gestlab.general.labels.variables.name'),
            'value' => trans('gestlab.general.labels.variables.value'),
            'formula_id' => trans('gestlab.general.labels.variables.formula_id'),
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
    public function prepareForValidation()
    {
        $formulaId = request()->input('formula_id');

        $this->merge([
            'formula_id' => is_array($formulaId) ? ($formulaId['value'] ?? null) : $formulaId,
        ]);
    }
}
