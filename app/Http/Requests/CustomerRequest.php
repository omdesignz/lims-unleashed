<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'name' => ['required', 'string', 'min:1', Rule::unique('customers', 'name')->ignore($customerId)],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'min:1', Rule::unique('customers', 'code')->ignore($customerId)],
            'category_id' => ['required', 'integer', Rule::exists('customer_categories', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
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
     * @param  Validator  $validator
     */
    protected function prepareForValidation(): void
    {
        $category = $this->input('category_id');

        $this->merge([
            'category_id' => is_array($category) ? ($category['value'] ?? null) : $category,
        ]);
    }
}
