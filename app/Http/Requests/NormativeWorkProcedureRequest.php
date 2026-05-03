<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NormativeWorkProcedureRequest extends FormRequest
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
                'code' => 'required|min:1|unique:nwps,code',
                'description' => 'nullable',
            ];
        } else {
            $rules = [
                'code' => 'required|min:1|unique:nwps,code,' . request()->nwp,
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
            'code' => trans('gestlab.general.labels.nwps.code'),
            'description' => trans('gestlab.general.labels.nwps.description'),
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
