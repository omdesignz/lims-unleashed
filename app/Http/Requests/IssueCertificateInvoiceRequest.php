<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class IssueCertificateInvoiceRequest extends InvoiceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $permission = $this->routeIs('importcertificates.*') ? 'view_import_certificates' : 'view_export_certificates';

        return $this->user()?->can('add_invoices') && $this->user()?->can($permission);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'certificate_id' => ['required', 'integer', 'min:1']];
    }
}
