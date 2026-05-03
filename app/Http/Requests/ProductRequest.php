<?php

namespace App\Http\Requests;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name' => 'required|min:1|unique:products,name',
                'charge_tax' => 'boolean',
                'price' => 'nullable',
                'fixed_price' => 'required',
                'tax_percentage' => 'required',
                'withhold_tax' => 'boolean',
                'description' => 'nullable',
                'matrix_id' => 'required|exists:matrixes,id',
                'exemption_id' => [
                    Rule::requiredIf(function () { 
                        return $this->input('charge_tax') == false; 
                    })
                ], 'exists:tax_exemptions,id',
                'exemption_code' => 'nullable', 
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:products,name,' . request()->product,
                'charge_tax' => 'boolean',
                'price' => 'nullable',
                'fixed_price' => 'required',
                'tax_percentage' => 'required',
                'withhold_tax' => 'boolean',
                'description' => 'nullable',
                'matrix_id' => 'required|exists:matrixes,id',
                'exemption_id' => [
                    Rule::requiredIf(function () { 
                        return $this->input('charge_tax') == false; 
                    })
                ], 'exists:tax_exemptions,id',
                'exemption_code' => 'nullable',

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

        if(request()->boolean('charge_tax')) {

            $this->merge([
                'charge_tax' => request()->boolean('charge_tax') ? 1 : 0,
                'withhold_tax' => request()->boolean('withhold_tax') ? 1 : 0,
                'exemption_id' => null,
                'exemption_code' => null,
                'tax_id' => !is_null(request()->tax_id) ? request()->tax_id['value'] : null,
                // 'tax_percentage' => !is_null(request()->tax_id) ? request()->tax_id['percent'] : 0,
                'tax_percentage' => request()->tax_percentage ?? 0,
                'matrix_id' => !is_null(request()->matrix_id) ? request()->matrix_id['value'] : null, 
            ]);

        } else {

            $this->merge([
                'charge_tax' => request()->boolean('charge_tax') ? 1 : 0,
                'withhold_tax' => request()->boolean('withhold_tax') ? 1 : 0,
                'exemption_id' => !is_null(request()->exemption_id) ? request()->exemption_id['value'] : null,
                'exemption_code' => !is_null(request()->exemption_id) ? request()->exemption_id['label'] : null,
                'tax_id' => null,
                'matrix_id' => !is_null(request()->matrix_id) ? request()->matrix_id['value'] : null,
                'tax_percentage' => 0
            ]);

        }
    }
}
