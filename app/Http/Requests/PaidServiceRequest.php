<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaidServiceRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                Rule::unique('paid_services', 'name')->ignore($this->route('service')),
            ],
            'charge_tax' => ['required', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'fixed_price' => ['required', 'numeric', 'min:0'],
            'tax_percentage' => ['required', 'numeric', 'min:0'],
            'tax_id' => [
                Rule::requiredIf($this->boolean('charge_tax')),
                'nullable',
                'exists:tax_types,id',
            ],
            'withhold_tax' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
            'exemption_id' => [
                Rule::requiredIf(! $this->boolean('charge_tax')),
                'nullable',
                'exists:tax_exemptions,id',
            ],
            'exemption_code' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('gestlab.general.labels.paid_services.name'),
            'description' => trans('gestlab.general.labels.paid_services.description'),
            'charge_tax' => trans('gestlab.general.labels.paid_services.charge_tax'),
            'withhold_tax' => trans('gestlab.general.labels.paid_services.withhold_tax'),
            'exemption_id' => trans('gestlab.general.labels.paid_services.exemption_id'),
            'exemption_code' => trans('gestlab.general.labels.paid_services.exemption_code'),
            'tax_id' => trans('gestlab.general.labels.paid_services.tax_id'),
            'price' => trans('gestlab.general.labels.paid_services.price'),
            'fixed_price' => trans('gestlab.general.labels.paid_services.fixed_price'),
            'tax_percentage' => trans('gestlab.general.labels.paid_services.tax_percentage'),
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
     * Normalize combobox values before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->boolean('charge_tax')) {
            $this->merge([
                'fixed_price' => $this->input('price', 0),
                'charge_tax' => $this->boolean('charge_tax'),
                'withhold_tax' => $this->boolean('withhold_tax'),
                'exemption_id' => null,
                'exemption_code' => null,
                'tax_id' => data_get($this->input('tax_id'), 'value'),
                'tax_percentage' => data_get($this->input('tax_id'), 'percent', 0),
            ]);
        } else {
            $this->merge([
                'fixed_price' => $this->input('price', 0),
                'charge_tax' => $this->boolean('charge_tax'),
                'withhold_tax' => $this->boolean('withhold_tax'),
                'exemption_id' => data_get($this->input('exemption_id'), 'value'),
                'exemption_code' => data_get($this->input('exemption_id'), 'label'),
                'tax_id' => null,
                'tax_percentage' => 0,
            ]);
        }
    }
}
