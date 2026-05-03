<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
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
                'category_id' => 'required|exists:trans_categories,id',
                'department_id' => 'required|exists:departments,id',
                'description' => 'nullable',
                'number_plate' => 'required|min:1|unique:vehicles,number_plate',
            ];
        } else {
            $rules = [
                'category_id' => 'required|exists:trans_categories,id',
                'department_id' => 'required|exists:departments,id',
                'description' => 'nullable',
                'number_plate' => 'required|min:1|unique:vehicles,number_plate,' . request()->vehicle,
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
            'category_id' => trans('gestlab.general.labels.vehicles.category_id'),
            'department_id' => trans('gestlab.general.labels.vehicles.department_id'),
            'number_plate' => trans('gestlab.general.labels.vehicles.number_plate'),
            'description' => trans('gestlab.general.labels.vehicles.description'),
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
        $this->merge([
            'category_id' => !is_null(request()->category_id) ? request()->category_id['value'] : null,
            'department_id' => !is_null(request()->department_id) ? request()->department_id['value'] : null,
        ]);
    }
}
