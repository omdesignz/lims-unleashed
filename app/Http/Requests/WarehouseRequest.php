<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
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
                'email' => 'required|email|min:6|unique:warehouses,email,' . request()->warehouse,
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
                'code' => 'nullable|min:6|unique:warehouses,code,' . request()->warehouse,
                'name' => 'nullable|min:6|unique:warehouses,name,' . request()->warehouse,
                'customer_id' => 'required|exists:customers,id',
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
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        // dd(collect(request()->warehouses)->map(function($item) {
        //     return [
        //         'id' => $item['id'] ?? null,
        //         'email' => $item['email'],
        //         'primary_phone' => $item['primary_phone'],
        //         'alternative_phone' => $item['alternative_phone'],
        //         'nif' => $item['nif'],
        //         'address' => $item['address'],
        //         'description' => $item['description'],
        //         'code' => $item['code']
        //     ];
        // })->toArray());
        // dd(request()->all());
        // dd(request()->all());
        
        $this->merge([
            'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
        ]);
    }
}
