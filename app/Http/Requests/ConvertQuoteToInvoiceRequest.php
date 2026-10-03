<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConvertQuoteToInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view_quotes') && $this->user()?->can('add_invoices');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_id' => ['required', 'integer', 'min:1'],
            'type_id' => ['required', 'integer', 'exists:invoice_categories,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type_id');
        $this->merge(['type_id' => is_array($type) ? data_get($type, 'value') : ($type ?? 1)]);
    }
}
