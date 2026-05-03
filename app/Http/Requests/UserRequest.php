<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
                'departments' => 'nullable|array',
                'name' => 'required|min:5',
                'email' => 'required|email|min:5|unique:users,email',
                'username' => 'nullable|min:5|unique:users,username',
                'gender' => 'required',
                'password' => 'required'
            ];
        } else {
            $rules = [
                'departments' => 'nullable|array',
                'name' => 'required|min:5',
                'email' => 'required|min:5|unique:users,email,' . request()->user,
                'username' => 'nullable|min:5|unique:users,username,' . request()->user,
                'primary_phone' => 'nullable|min:5',
                'secondary_phone' => 'nullable|min:5',
                'dob' => 'nullable|date',
                'id_number' => 'nullable|min:5',
                'gender' => 'required',
                'roles' => 'nullable|array',
                'permissions' => 'nullable|array',
                'personnel_qualifications' => 'nullable|array',
                'personnel_qualifications.*.capability' => 'required_with:personnel_qualifications|string|max:255',
                'personnel_qualifications.*.department_id' => 'nullable|exists:departments,id',
                'personnel_qualifications.*.authorized_from' => 'nullable|date',
                'personnel_qualifications.*.authorized_until' => 'nullable|date|after_or_equal:personnel_qualifications.*.authorized_from',
                'personnel_qualifications.*.training_completed_at' => 'nullable|date',
                'personnel_qualifications.*.training_reference' => 'nullable|string|max:255',
                'personnel_qualifications.*.notes' => 'nullable|string',
                'personnel_qualifications.*.is_active' => 'nullable|boolean',
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
            'name' => trans('gestlab.general.labels.users.name'),
            'email' => trans('gestlab.general.labels.users.email'),
            'username' => trans('gestlab.general.labels.users.username'),
            'gender' => trans('gestlab.general.labels.users.gender'),
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
        if ($this->isMethod('post')) {
            $this->merge([
                'password' => bcrypt('password'),
                'departments' => collect(request()->departments)->map(function($item) { return [ 'department_id' => $item['value']]; })->toArray(),
            ]);
        } else {
            
            $this->merge([
                'departments' => collect(request()->departments)->map(function($item) { return [ 'department_id' => $item['value']]; })->toArray(),
                'roles' => collect(request()->roles)->pluck('value')->toArray(),
                'permissions' => collect(request()->permissions)->pluck('value')->toArray(),
                'personnel_qualifications' => collect(request()->personnel_qualifications)->map(function ($item) {
                    return [
                        'capability' => $item['capability'] ?? null,
                        'department_id' => data_get($item, 'department_id.value', $item['department_id'] ?? null),
                        'authorized_from' => $item['authorized_from'] ?? null,
                        'authorized_until' => $item['authorized_until'] ?? null,
                        'training_completed_at' => $item['training_completed_at'] ?? null,
                        'training_reference' => $item['training_reference'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                    ];
                })->filter(fn ($item) => ! blank($item['capability']))->values()->toArray(),
            ]);

        }
    }
}
