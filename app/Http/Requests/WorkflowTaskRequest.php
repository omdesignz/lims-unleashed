<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        if ($this->isMethod('post')) {
            $rules = [
                'file_id' => 'required|exists:v_files,id',
                'type' => 'required|in:review,approve,publish',
                'assigned_to' => 'required|exists:users,id',
                'due_date' => 'nullable|date',
            ];
        } else {
            $rules = [
                'file_id' => 'required|exists:v_files,id',
                'type' => 'required|in:review,approve,publish',
                'assigned_to' => 'required|exists:users,id',
                'due_date' => 'nullable|date',
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
        $assignedTo = request()->assigned_to;

        if (is_array($assignedTo)) {
            $assignedTo = $assignedTo['value'] ?? null;
        }

        $this->merge([
            'assigned_to' => $assignedTo,
        ]);
    }
}
