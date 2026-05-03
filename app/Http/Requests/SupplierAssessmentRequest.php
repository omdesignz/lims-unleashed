<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierAssessmentRequest extends FormRequest
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
        return [
            'inventory_item_supplier_id' => ['required', 'exists:i_suppliers,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'assessment_date' => ['required', 'date'],
            'next_review_at' => ['nullable', 'date', 'after_or_equal:assessment_date'],
            'status' => ['required', Rule::in(['approved', 'conditional', 'suspended', 'rejected'])],
            'risk_level' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'delivery_score' => ['nullable', 'integer', 'between:1,5'],
            'quality_score' => ['nullable', 'integer', 'between:1,5'],
            'compliance_score' => ['nullable', 'integer', 'between:1,5'],
            'responsiveness_score' => ['nullable', 'integer', 'between:1,5'],
            'evidence_reference' => ['nullable', 'string', 'max:255'],
            'approved_supplier' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'gaps' => ['nullable', 'string', 'max:5000'],
            'corrective_actions' => ['nullable', 'string', 'max:5000'],
            'follow_up_actions' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
