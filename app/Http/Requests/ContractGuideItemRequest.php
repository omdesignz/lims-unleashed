<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractGuideItemRequest extends FormRequest
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
                
            ];
        } else {
            $rules = [
                'id' => 'required|exists:contract_guide_items,id',
                'obs' => 'nullable',
                'bl' => 'nullable',
                'lot' => 'nullable',
                'manufacturer' => 'nullable',
                'du_no' => 'nullable',
                'origin' => 'nullable',
                'brand' => 'nullable',
                'product_id' => 'nullable|exists:products,id',
                'country_id' => 'nullable|exists:countries,id',

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

        ];
    }

    /**
 * Get the error messages for the defined validation rules.
 *
 * @return array<string, string>
 */
public function messages(): array
{
    return [
        
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
        // dd(request()->all());

        if ($this->isMethod('post')) {

            $this->merge([
                
            ]);
        } else {

            $this->merge([
                'id' => request()->id ?? null,
                'obs' => request()->obs,
                'product_id' => !is_null(request()->product_id) ? request()->product_id['value'] : null,
                'country_id' => !is_null(request()->country_id) ? request()->country_id['value'] : null,
                'bl' => request()->bl,
                'lot' => request()->lot,
                'manufacturer' => request()->manufacturer,
                'origin' => request()->origin,
                'brand' => request()->brand,
                'du_no' => request()->du_no,
            ]);

        }
   
    }
}