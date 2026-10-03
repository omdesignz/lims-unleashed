<?php

namespace App\Http\Requests;

use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkflowTaskRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();

        return [
            'file_id' => ['required', Rule::exists('v_files', 'id')->where('lab_id', $labId)],
            'type' => ['required', Rule::in(['review', 'approve', 'publish'])],
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id'),
                Rule::exists('lab_user', 'user_id')->where('lab_id', $labId),
            ],
            'due_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $assignedTo = $this->input('assigned_to');

        if (is_array($assignedTo)) {
            $assignedTo = $assignedTo['value'] ?? null;
        }

        $this->merge([
            'assigned_to' => $assignedTo,
        ]);
    }
}
