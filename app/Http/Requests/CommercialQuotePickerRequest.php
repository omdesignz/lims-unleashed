<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CommercialQuotePickerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();

        return app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find($this->user()?->id)?->canAny([
            'view_quotes', 'add_quotes', 'edit_quotes', 'view_invoices', 'add_invoices', 'edit_invoices',
            'view_credit_notes', 'add_credit_notes', 'edit_credit_notes', 'view_receipts', 'add_receipts', 'edit_receipts',
            'view_quality_certificates', 'add_quality_certificates', 'edit_quality_certificates',
        ]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [$this->routeIs('labcodes.getWarehouseUninvoicedProducts') ? 'warehouse_id' : 'code_id' => ['required', 'integer', 'min:1'],
            'use_matrix_price' => ['sometimes', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('use_matrix_price'), ['true', 'false'], true)) {
            $this->merge(['use_matrix_price' => $this->input('use_matrix_price') === 'true']);
        }
    }
}
