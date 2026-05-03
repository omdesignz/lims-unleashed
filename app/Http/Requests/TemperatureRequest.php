<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TemperatureRequest extends FormRequest
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
                'name' => 'required|min:1|unique:temperatures,name',
                'code' => 'nullable|min:1|unique:temperatures,code',
                'description' => 'nullable',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:temperatures,name,' . request()->temperature,
                'code' => 'nullable|min:1|unique:temperatures,code,' . request()->temperature,
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
            'name' => trans('gestlab.general.labels.temperatures.name'),
            'code' => trans('gestlab.general.labels.temperatures.code'),
            'description' => trans('gestlab.general.labels.temperatures.description'),
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
