<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaxTypeRequest extends FormRequest
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
                'name' => 'required|min:3|unique:tax_types,name',
                'percent' => 'required',
                'compound_tax' => 'required|boolean',
                'collective_tax' => 'required|boolean',
                'description' => 'nullable',
            ];
        } else {
            $rules = [
                'name' => 'required|min:3|unique:tax_types,name,' . request()->taxtype,
                'percent' => 'required',
                'compound_tax' => 'required|boolean',
                'collective_tax' => 'required|boolean',
                'description' => 'nullable',

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
            'name' => trans('gestlab.general.labels.tax_types.name'),
            'percent' => trans('gestlab.general.labels.tax_types.percent'),
            'compound_tax' => trans('gestlab.general.labels.tax_types.compound_tax'),
            'collective_tax' => trans('gestlab.general.labels.tax_types.collective_tax'),
            'description' => trans('gestlab.general.labels.tax_types.description'),
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
        // if ($this->isMethod('post')) {
        //     $this->merge([
        //         'password' => bcrypt('password'),
        //     ]);
        // }
    }
}
