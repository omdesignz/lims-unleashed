<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LaboratoryMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! $this->session()->has('impersonate') && (bool) $this->user()?->can($this->isMethod('delete') ? 'delete_users' : 'add_users');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->isMethod('delete') ? [] : ['email' => ['required', 'string', 'email', 'max:255']];
    }

    /** @return array<callable(Validator):void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowed = $this->isMethod('delete') ? ['_token', '_method'] : ['email', '_token', '_method'];
            foreach (array_diff(array_keys($this->all()), $allowed) as $field) {
                $validator->errors()->add($field, 'Este campo não pode ser alterado neste formulário.');
            }
        }];
    }
}
