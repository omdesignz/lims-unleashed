<?php

namespace App\Http\Requests;

use App\Services\StaffAccountAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }
        app(StaffAccountAccess::class)->authorizeSystem($this->user(), $this->isMethod('post') ? 'add_roles' : 'edit_roles');

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name' => 'required|min:1|unique:roles,name',
                'label' => 'nullable',
                'guard_name' => 'nullable',
            ];
        } else {
            $rules = [
                'name' => 'required|min:1|unique:roles,name,'.request()->role,
                'label' => 'nullable',
                'guard_name' => 'nullable',
                'permissions' => 'nullable|array|min:1',

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
            'name' => trans('gestlab.general.labels.roles.name'),
            'label' => trans('gestlab.general.labels.roles.label'),
            'permissions' => trans('gestlab.general.labels.roles.permissions'),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        $this->merge([
            // 'permissions' => collect(request()->permissions)->pluck('value')->toArray(),
        ]);
    }
}
