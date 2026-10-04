<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IssuePortalServiceInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('add_customer_requests') && ! $this->session()->has('impersonate');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_email' => ['required', 'email', 'max:255'],
            'lab_id' => ['prohibited'],
            'warehouse_id' => ['prohibited'],
            'customer_id' => ['prohibited'],
            'issued_by_id' => ['prohibited'],
            'token' => ['prohibited'],
            'expires_at' => ['prohibited'],
        ];
    }
}
