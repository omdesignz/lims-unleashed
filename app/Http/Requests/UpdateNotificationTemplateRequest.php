<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_settings') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title_template' => ['required', 'string', 'max:255'],
            'in_app_template' => ['required', 'string', 'max:2000'],
            'email_subject_template' => ['nullable', 'string', 'max:255'],
            'email_template' => ['nullable', 'string', 'max:5000'],
            'action_label_template' => ['nullable', 'string', 'max:100'],
            'action_url_template' => ['nullable', 'string', 'max:1000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', Rule::in(['database', 'broadcast', 'mail'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
