<?php

namespace App\Http\Requests;

use App\Services\StaffAccountAccess;
use App\Support\PersonnelQualificationValidation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $actor = $this->user();
        if (! $actor) {
            return false;
        }
        $accounts = app(StaffAccountAccess::class);
        if ($this->isMethod('post')) {
            $accounts->authorizeSystem($actor, 'add_users');

            return true;
        }
        $accounts->authorizeUpdate($actor, (int) $this->route('user'), $this->all());

        return $actor->can('edit_users');
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
                'departments' => ['sometimes', 'array', 'list'],
                'departments.*.department_id' => ['required', 'integer', 'min:1', Rule::exists('departments', 'id')],
                'name' => ['required', 'string', 'min:5', 'max:255'],
                'email' => ['required', 'string', 'email', 'min:5', 'max:255', Rule::unique('users', 'email')],
                'username' => ['nullable', 'string', 'min:5', 'max:255', Rule::unique('users', 'username')],
                'gender' => ['required', 'string', 'in:F,M,O'],
                'password' => ['required', 'string', Password::min(8)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
                'password_confirmation' => ['required', 'string'],
            ];
        } else {
            $rules = [
                'departments' => 'sometimes|array',
                'departments.*.department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
                'name' => 'sometimes|required|string|min:5|max:255',
                'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore((int) $this->route('user'))],
                'username' => ['nullable', 'string', 'min:5', 'max:255', Rule::unique('users', 'username')->ignore((int) $this->route('user'))],
                'primary_phone' => 'nullable|min:5',
                'secondary_phone' => 'nullable|min:5',
                'dob' => 'nullable|date',
                'id_number' => 'nullable|min:5',
                'gender' => 'sometimes|required|string|in:F,M,O',
                'roles' => 'sometimes|array',
                'roles.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
                'permissions' => 'sometimes|array',
                'permissions.*' => ['required', 'integer', 'distinct', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
                ...app(PersonnelQualificationValidation::class)->rules(),
            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('gestlab.general.labels.users.name'),
            'email' => trans('gestlab.general.labels.users.email'),
            'username' => trans('gestlab.general.labels.users.username'),
            'gender' => trans('gestlab.general.labels.users.gender'),
            ...app(PersonnelQualificationValidation::class)->attributes(),
        ];
    }

    /** @return array<int,callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowed = $this->isMethod('post')
                ? ['name', 'email', 'username', 'gender', 'departments', 'password', 'password_confirmation']
                : [...StaffAccountAccess::PROFILE_FIELDS, ...StaffAccountAccess::GLOBAL_ACCESS_FIELDS, 'personnel_qualifications'];
            foreach (array_diff(array_keys($this->all()), [...$allowed, '_token', '_method']) as $field) {
                $validator->errors()->add($field, 'Este campo não pode ser alterado neste formulário.');
            }
        }];
    }

    /**
     * Normalize form controls before validation.
     */
    public function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->has('departments') && is_array($this->input('departments'))) {
            $normalized['departments'] = collect($this->input('departments'))
                ->map(fn ($item): array => ['department_id' => data_get($item, 'value', data_get($item, 'department_id'))])
                ->all();
        }

        if ($this->isMethod('post')) {
            $this->merge($normalized);

            return;
        }

        foreach (['roles', 'permissions'] as $field) {
            if ($this->has($field) && is_array($this->input($field))) {
                $normalized[$field] = collect($this->input($field))
                    ->map(fn ($item) => data_get($item, 'value', $item))
                    ->all();
            }
        }

        $this->merge([...$normalized, ...app(PersonnelQualificationValidation::class)->normalize($this->all())]);
    }
}
