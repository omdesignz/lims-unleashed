<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
                Rule::unique('products', 'name')->ignore($this->route('product')),
            ],
            'description' => ['nullable', 'string'],
            'matrix_id' => ['required', 'exists:matrixes,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'fixed_price' => ['required', 'numeric', 'min:0'],
            'charge_tax' => ['required', 'boolean'],
            'withhold_tax' => ['required', 'boolean'],
            'tax_id' => [
                Rule::requiredIf($this->boolean('charge_tax')),
                'nullable',
                'exists:tax_types,id',
            ],
            'tax_percentage' => ['required', 'numeric', 'min:0'],
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
            'name' => trans('gestlab.general.labels.products.name'),
            'description' => trans('gestlab.general.labels.products.description'),
            'charge_tax' => trans('gestlab.general.labels.products.charge_tax'),
            'withhold_tax' => trans('gestlab.general.labels.products.withhold_tax'),
            'matrix_id' => trans('gestlab.general.labels.products.matrix_id'),
            'exemption_id' => trans('gestlab.general.labels.products.exemption_id'),
            'exemption_code' => trans('gestlab.general.labels.products.exemption_code'),
            'tax_id' => trans('gestlab.general.labels.products.tax_id'),
            'price' => trans('gestlab.general.labels.products.price'),
            'fixed_price' => trans('gestlab.general.labels.products.fixed_price'),
            'tax_percentage' => trans('gestlab.general.labels.products.tax_percentage'),
        ];
    }

    /**
     * Normalize combobox values before validation.
     */
    protected function prepareForValidation(): void
    {
        $chargesTax = $this->boolean('charge_tax');

        $this->merge([
            'matrix_id' => $this->comboboxValue('matrix_id'),
            'charge_tax' => $chargesTax,
            'withhold_tax' => $this->boolean('withhold_tax'),
            'tax_id' => $chargesTax ? $this->comboboxValue('tax_id') : null,
            'tax_percentage' => $chargesTax ? $this->input('tax_percentage', 0) : 0,
            'exemption_id' => $chargesTax ? null : $this->comboboxValue('exemption_id'),
            'exemption_code' => $chargesTax ? null : $this->comboboxLabel('exemption_id'),
        ]);
    }

    private function comboboxValue(string $field): mixed
    {
        $value = $this->input($field);

        return is_array($value) ? ($value['value'] ?? null) : $value;
    }

    private function comboboxLabel(string $field): ?string
    {
        $value = $this->input($field);

        return is_array($value) ? ($value['label'] ?? null) : null;
    }
}
