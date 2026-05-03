<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
                'name' => 'required|min:1|unique:customers,name',
                'description' => 'nullable',
                'code' => 'nullable|min:1|unique:customers,code',
                'category_id' => 'required|exists:customer_categories,id',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:customers,name,' . request()->customer,
                'description' => 'nullable',
                'code' => 'nullable|min:1|unique:customers,code,' . request()->customer,
                'category_id' => 'required|exists:customer_categories,id',

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
            'name' => trans('gestlab.general.labels.customers.name'),
            'code' => trans('gestlab.general.labels.customers.code'),
            'description' => trans('gestlab.general.labels.customers.description'),
            'category_id' => trans('gestlab.general.labels.customers.category_id'),
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
        $this->merge([
            'category_id' => !is_null(request()->category_id) ? request()->category_id['value'] : null,
        ]);
    }
}
