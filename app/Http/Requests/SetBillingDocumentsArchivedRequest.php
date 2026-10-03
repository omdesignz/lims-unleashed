<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetBillingDocumentsArchivedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $module = match (true) {
            $this->routeIs('invoices.*') => 'invoices',
            $this->routeIs('creditnotes.*') => 'credit_notes',
            $this->routeIs('receipts.*') => 'receipts',
            $this->routeIs('quotes.*') => 'quotes',
            $this->routeIs('importcertificates.*') => 'import_certificates',
            $this->routeIs('exportcertificates.*') => 'export_certificates',
            default => null,
        };

        return $module !== null && (int) $this->attributes->get('proposal_laboratory_id') > 0
            && ($this->user()?->can(($this->routeIs('*.restore') ? 'restore_' : 'delete_').$module) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
