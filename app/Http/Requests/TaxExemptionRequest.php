<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaxExemptionRequest extends FormRequest
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
                'code' => 'required|min:2|unique:tax_exemptions,code',
                'reason' => 'required',
                'law' => 'required',
                'description' => 'required|min:3',
            ];
        } else {
            $rules = [
                'code' => 'required|min:2|unique:tax_exemptions,code,' . request()->taxexemption,
                'reason' => 'required',
                'law' => 'required',
                'description' => 'required|min:3',

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
            'code' => trans('gestlab.general.labels.tax_exemptions.code'),
            'reason' => trans('gestlab.general.labels.tax_exemptions.reason'),
            'law' => trans('gestlab.general.labels.tax_exemptions.law'),
            'description' => trans('gestlab.general.labels.tax_exemptions.description'),
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
