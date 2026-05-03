<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerCategoryRequest extends FormRequest
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
                'name' => 'required|min:1|unique:customer_categories,name',
                'description' => 'nullable',
                'code' => 'required|min:1',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:customer_categories,name,' . request()->category,
                'description' => 'nullable',
                'code' => 'required|min:1',

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
            'name' => trans('gestlab.general.labels.customer_categories.name'),
            'code' => trans('gestlab.general.labels.customer_categories.code'),
            'description' => trans('gestlab.general.labels.customer_categories.description'),
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
