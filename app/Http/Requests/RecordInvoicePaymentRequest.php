<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordInvoicePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit_invoices') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'array:value,label'],
            'payment_method.value' => ['required', 'integer', 'exists:payment_categories,id'],
            'payment_method.label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
