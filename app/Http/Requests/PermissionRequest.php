<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
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
                'name' => 'required|min:1|unique:permissions,name',
                'label' => 'nullable',
                'guard_name' => 'nullable',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:permissions,name,' . request()->permission,
                'label' => 'nullable',
                'guard_name' => 'nullable',

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
            'name' => trans('gestlab.general.labels.permissions.name'),
            'label' => trans('gestlab.general.labels.permissions.label'),
            'guard_name' => trans('gestlab.general.labels.permissions.guard_name'),
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
        // $this->merge([
        //     'permissions' => collect(request()->permissions)->map(function($item) { return [ 'permission_id' => $item['value']]; })->toArray(),
        // ]);
    }
}
