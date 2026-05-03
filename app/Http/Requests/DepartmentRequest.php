<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
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
                'name' => 'required|min:5|unique:departments,name',
                'email' => 'required|email|min:3|unique:departments,email',
                'code' => 'nullable|min:2|unique:departments,code',
                'description' => 'nullable|min:5',
                'contact' => 'nullable|min:5',
                'extension' => 'nullable|min:1',
                'supervisor_id' => 'nullable|exists:users,id',
            ];
        } else {
            $rules = [
                'name' => 'required|min:5|unique:departments,name,' . request()->department,
                'email' => 'required|email|min:3|unique:departments,email,' . request()->department,
                'code' => 'nullable|min:2|unique:departments,code,' . request()->department,
                'description' => 'nullable|min:5',
                'contact' => 'nullable|min:5',
                'extension' => 'nullable|min:1',
                'supervisor_id' => 'nullable|exists:users,id',

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
            'name' => trans('gestlab.general.labels.departments.name'),
            'email' => trans('gestlab.general.labels.departments.email'),
            'code' => trans('gestlab.general.labels.departments.code'),
            'description' => trans('gestlab.general.labels.departments.description'),
            'extension' => trans('gestlab.general.labels.departments.extension'),
            'contact' => trans('gestlab.general.labels.departments.contact'),
            'supervisor_id' => trans('gestlab.general.labels.departments.supervisor_id'),
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
        $this->merge([
            'supervisor_id' => !is_null(request()->supervisor_id) ? request()->supervisor_id['value'] : null,
        ]);
    }
}
