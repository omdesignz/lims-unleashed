<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CountryRequest extends FormRequest
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
    public function rules()
    {
        if ($this->isMethod('post')) {
            $rules = [
                'code' => 'required|min:2',
                'name' => 'required|min:3',
                'phone_code' => 'required|min:2',
            ];
        } else {
            $rules = [
                'code' => 'required|min:2|unique:countries,name,' . request()->country,
                'name' => 'required|min:3',
                'phone_code' => 'required|min:2',
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
            'code' => trans('gestlab.general.labels.countries.code'),
            'name' => trans('gestlab.general.labels.countries.name'),
            'phone_code' => trans('gestlab.general.labels.countries.phone_code'),
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
