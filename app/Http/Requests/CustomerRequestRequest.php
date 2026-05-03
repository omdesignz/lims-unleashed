<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequestRequest extends FormRequest
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
                'description' => 'required',
                'email' => 'required|email',
                'contact' => 'required',
                'answered' => 'nullable|boolean',
                'category_id' => 'required|exists:customer_request_categories,id',
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
            ];
        } else {
            $rules = [
                'description' => 'required',
                'email' => 'required|email',
                'contact' => 'required',
                'answered' => 'nullable|boolean',
                'category_id' => 'required|exists:customer_request_categories,id',
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',

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
            'description' => trans('gestlab.general.labels.customer_requests.description'),
            'category_id' => trans('gestlab.general.labels.customer_requests.category_id'),
            'customer_id' => trans('gestlab.general.labels.customer_requests.customer_id'),
            'warehouse_id' => trans('gestlab.general.labels.customer_requests.warehouse_id'),
            'email' => trans('gestlab.general.labels.customer_requests.email'),
            'contact' => trans('gestlab.general.labels.customer_requests.contact'),
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
        // dd(request()->all());
        $this->merge([
            'category_id' => !is_null(request()->category_id) ? request()->category_id['value'] : null,
            'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
            'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
        ]);
    }
}
