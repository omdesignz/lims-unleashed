<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can($this->isMethod('post') ? 'add_receipts' : 'edit_receipts') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'obs' => 'nullable|string',
                'description' => 'nullable|string',
                'user_id' => 'nullable|exists:users,id',
                'customer_id' => 'required|integer|exists:customers,id',
                'date' => 'nullable|date_format:Y-m-d',
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'rec_month' => 'required',
                'items' => 'required|array|list|min:1|max:100',
                'items.*.invoice_id' => ['required', 'integer', 'distinct:strict', Rule::exists('invoices', 'id')
                    ->where('lab_id', $this->attributes->get('proposal_laboratory_id'))->whereNull('deleted_at')],
                'items.*.payment_id' => 'required|exists:payment_categories,id',
                'items.*.description' => 'nullable|string',
                'items.*.paid_amount' => ['required', 'numeric', 'gt:0', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
                'items.*.obs' => 'nullable|string|max:5000',
            ];
        } else {
            $rules = [
                'obs' => 'nullable|string|max:5000',
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
            'obs' => trans('gestlab.general.labels.receipts.obs'),
            'description' => trans('gestlab.general.labels.receipts.description'),
            'items' => trans('gestlab.general.labels.receipts.items'),
            'items.*.invoice_id' => trans('gestlab.general.labels.receipts.invoice_id'),
            'items.*.payment_id' => trans('gestlab.general.labels.receipts.payment_id'),
            'items.*.description' => trans('gestlab.general.labels.receipts.obs'),
            'items.*.paid_amount' => trans('gestlab.general.labels.receipts.paid_amount'),
            'items.*.invoice_pending_amount' => trans('gestlab.general.labels.receipts.invoice_pending_amount'),
            'items.*.pending_amount' => trans('gestlab.general.labels.receipts.pending_amount'),
        ];
    }

    public function messages()
    {
        return [];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    public function prepareForValidation(): void
    {
        if (! $this->isMethod('post')) {
            return;
        }

        $this->merge([
            'user_id' => auth()->user()->id ?? null,
            'obs' => request()->obs,
            'description' => '',
            'is_original' => true,
            'exported_saft' => false,
            'customer_id' => data_get($this->input('customer_id'), 'value'),
            'warehouse_id' => data_get($this->input('warehouse_id'), 'value'),
            'rec_month' => now()->format('Y'),
            'date' => now()->format('Y-m-d'),
            'items' => ! is_array($this->input('formatted_items')) ? [] : collect($this->input('formatted_items'))->map(function ($item): array {
                $item = is_array($item) ? $item : [];

                return [
                    'invoice_id' => $item['invoice_id'] ?? null,
                    'payment_id' => $item['payment_id'] ?? null,
                    'user_id' => auth()->user()->id ?? null,
                    'paid_amount' => $item['paid_amount'] ?? null,
                    'obs' => $item['obs'] ?? null,
                ];
            })->toArray(),
        ]);
    }
}
