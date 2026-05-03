<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemWarehouseRequest extends FormRequest
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
                'name' => 'required|min:1|unique:i_warehouses,name',
                'is_refrigerated' => 'required|boolean',
                'is_ventilated' => 'required|boolean',
                'has_air_exhaustion' => 'required|boolean',
                'location_id' => 'required|exists:i_locations,id',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:i_warehouses,name,' . request()->iwarehouse,
                'is_refrigerated' => 'required|boolean',
                'is_ventilated' => 'required|boolean',
                'has_air_exhaustion' => 'required|boolean',
                'location_id' => 'required|exists:i_locations,id',
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
            'name' => trans('gestlab.general.labels.iwarehouses.name'),
            'is_refrigerated' => trans('gestlab.general.labels.iwarehouses.is_refrigerated'),
            'is_ventilated' => trans('gestlab.general.labels.iwarehouses.is_ventilated'),
            'has_air_exhaustion' => trans('gestlab.general.labels.iwarehouses.has_air_exhaustion'),
            'location_id' => trans('gestlab.general.labels.iwarehouses.location_id'),
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
        $this->merge([
            'is_refrigerated' => request()->boolean('is_refrigerated') ? 1 : 0,
            'is_ventilated' => request()->boolean('is_ventilated') ? 1 : 0,
            'has_air_exhaustion' => request()->boolean('has_air_exhaustion') ? 1 : 0,
            'location_id' => !is_null(request()->location_id) ? request()->location_id['value'] : null,
        ]);
            
    }
}
