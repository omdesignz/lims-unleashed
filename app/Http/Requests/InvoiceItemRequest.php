<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceItemRequest extends FormRequest
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
                'id' => 'required|exists:invoice_items,id',
                'obs' => 'nullable',
                'itemable_id' => 'nullable',
                'itemable_type' => 'nullable',
                'unit_id' => 'nullable|exists:units,id',

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
                // 'itemable_id' => request()->itemable_id,
                'itemable_id' => !is_null(request()->itemable_id) ? request()->itemable_id['value'] : null,
                'itemable_type' => request()->itemable_type,
                'unit_id' => !is_null(request()->unit_id) ? request()->unit_id['value'] : null,
            ]);

        }
   
    }
}