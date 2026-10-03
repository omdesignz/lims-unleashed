<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FinancialDocumentObservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $permission = match (true) {
            $this->routeIs('invoices.*', 'invoiceitems.*') => 'edit_invoices',
            $this->routeIs('creditnotes.*') => 'edit_credit_notes',
            $this->routeIs('receipts.*') => 'edit_receipts',
            default => null,
        };

        return $permission !== null && ($this->user()?->can($permission) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'obs' => ['present', 'nullable', 'string', 'max:5000'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->all()), ['obs', '_token', '_method']) as $field) {
                $validator->errors()->add($field, 'O conteúdo financeiro emitido está bloqueado. Apenas as observações podem ser corrigidas.');
            }
        }];
    }
}
