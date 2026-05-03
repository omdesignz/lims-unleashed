<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactCategoryRequest extends FormRequest
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
                'name' => 'required|min:1|unique:contact_categories,name',
                'description' => 'nullable',
                'code' => 'nullable|min:1|unique:contact_categories,code',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:contact_categories,name,' . request()->category,
                'description' => 'nullable',
                'code' => 'nullable|min:1|unique:contact_categories,code,' . request()->category,

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
            'name' => trans('gestlab.general.labels.contact_categories.name'),
            'code' => trans('gestlab.general.labels.contact_categories.code'),
            'description' => trans('gestlab.general.labels.contact_categories.description'),
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
