<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WarehouseRequest extends FormRequest
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
        if ($this->isMethod('post')) {
            $rules = [
                'email' => 'required|email|min:6|unique:warehouses,email',
                'invoicing_email' => 'nullable|email|min:6',
                'primary_phone' => 'nullable',
                'alternative_phone' => 'nullable',
                'nif' => 'nullable',
                'address' => 'required',
                'municipality' => 'nullable',
                'province' => 'nullable',
                'focal_point' => 'nullable',
                'focal_point_email' => 'nullable|email',
                'focal_point_contact' => 'nullable',
                'description' => 'nullable',
                'code' => 'nullable|min:6|unique:warehouses,code',
                'name' => 'nullable|min:6|unique:warehouses,name',
                'customer_id' => 'required|exists:customers,id',
            ];
        } else {
            $rules = [
                'email' => 'required|email|min:6|unique:warehouses,email,'.request()->warehouse,
                'invoicing_email' => 'nullable|email|min:6',
                'primary_phone' => 'nullable',
                'alternative_phone' => 'nullable',
                'nif' => 'nullable',
                'address' => 'required',
                'municipality' => 'nullable',
                'province' => 'nullable',
                'focal_point' => 'nullable',
                'focal_point_email' => 'nullable|email',
                'focal_point_contact' => 'nullable',
                'description' => 'nullable',
                'code' => 'nullable|min:6|unique:warehouses,code,'.request()->warehouse,
                'name' => 'nullable|min:6|unique:warehouses,name,'.request()->warehouse,
                'customer_id' => 'required|exists:customers,id',
            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => trans('gestlab.general.labels.warehouses.email'),
            'primary_phone' => trans('gestlab.general.labels.warehouses.primary_phone'),
            'alternative_phone' => trans('gestlab.general.labels.warehouses.alternative_phone'),
            'nif' => trans('gestlab.general.labels.warehouses.nif'),
            'address' => trans('gestlab.general.labels.warehouses.address'),
            'description' => trans('gestlab.general.labels.warehouses.description'),
            'customer_id' => trans('gestlab.general.labels.warehouses.customer_id'),
            'code' => trans('gestlab.general.labels.warehouses.code'),
            'name' => trans('gestlab.general.labels.warehouses.name'),
            'focal_point' => trans('gestlab.general.labels.warehouses.focal_point'),
            'focal_point_email' => trans('gestlab.general.labels.warehouses.focal_point_email'),
            'focal_point_contact' => trans('gestlab.general.labels.warehouses.focal_point_contact'),
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
        $customer = $this->input('customer_id');

        $this->merge([
            'customer_id' => is_array($customer) ? ($customer['value'] ?? null) : $customer,
        ]);
    }
}
