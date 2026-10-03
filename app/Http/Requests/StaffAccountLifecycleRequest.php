<?php

namespace App\Http\Requests;

use App\Services\StaffAccountAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StaffAccountLifecycleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->user()) {
            return false;
        }
        $permission = match ($this->route()->getName()) {
            'users.destroy' => 'delete_users',
            'users.restore' => 'restore_users',
            'users.setActiveStatus' => 'ban_users',
            default => abort(403),
        };
        app(StaffAccountAccess::class)->authorizeSystem($this->user(), $permission);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->routeIs('users.setActiveStatus') ? ['is_active' => ['required', 'boolean']] : [
            'recordIds' => ['required', 'array', 'list', 'min:1'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowed = $this->routeIs('users.setActiveStatus') ? ['is_active'] : ['recordIds'];
            foreach (array_diff(array_keys($this->all()), [...$allowed, '_token', '_method']) as $field) {
                $validator->errors()->add($field, 'Este campo não pode ser alterado nesta operação.');
            }
        }];
    }
}
