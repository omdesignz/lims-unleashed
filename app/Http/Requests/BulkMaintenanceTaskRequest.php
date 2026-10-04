<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkMaintenanceTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        abort_if($this->session()->has('impersonate'), 403);

        $permission = match ($this->input('action')) {
            'delete' => 'delete_maintenance_tasks',
            'restore' => 'restore_maintenance_tasks',
            default => 'edit_maintenance_tasks',
        };

        return $this->user()?->can($permission) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'min:1', 'max:100'],
            'task_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'action' => ['required', Rule::in(['mark_executed', 'reschedule', 'delete', 'restore'])],
            'new_date' => ['nullable', 'required_if:action,reschedule', 'date_format:Y-m-d'],
            'send_notification' => ['prohibited'],
        ];
    }
}
