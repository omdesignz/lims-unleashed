<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemLocationRequest extends FormRequest
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
                'name' => 'required|min:1|unique:i_locations,name',
                'description' => 'nullable',
                'address' => 'required|min:1',
                'department_id' => 'required|exists:departments,id',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:i_locations,name,' . request()->ilocation,
                'description' => 'nullable',
                'address' => 'required|min:1',
                'department_id' => 'required|exists:departments,id',
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
            'name' => trans('gestlab.general.labels.ilocations.name'),
            'description' => trans('gestlab.general.labels.ilocations.description'),
            'address' => trans('gestlab.general.labels.ilocations.address'),
            'department_id' => trans('gestlab.general.labels.ilocations.department_id'),
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
            'department_id' => !is_null(request()->department_id) ? request()->department_id['value'] : null,
        ]);
    }
}
