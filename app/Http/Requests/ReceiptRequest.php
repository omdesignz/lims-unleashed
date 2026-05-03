<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'obs' => 'nullable|string',
                'description' => 'nullable|string',
                'user_id' => 'nullable|exists:users,id',
                'customer_id' => 'nullable|exists:customers,id',
                'date' => 'nullable|date_format:Y-m-d',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'rec_month' => 'required',
                'items' => 'required|array|min:1',
                'items.*.invoice_id' => 'required|exists:invoices,id',
                'items.*.payment_id' => 'required|exists:payment_categories,id',
                'items.*.description' => 'nullable|string',
                'items.*.paid_amount' => 'required',
                'items.*.invoice_pending_amount' => 'required',
                'items.*.pending_amount' => 'required',
            ];
        } else {
            $rules = [
                
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
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        // dd(request()->all());

        $this->merge([
            'user_id' => auth()->user()->id ?? null,
            'obs' => request()->obs,
            'description' => '',
            'is_original' => true,
            'exported_saft' => false,
            'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
            'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
            'rec_month' => now()->format('Y'),
            'date' => now()->format('Y-m-d'),
            'items' => is_null(request()->formatted_items) ? [] : collect(request()->formatted_items)->map(function($item) {
                return [
                    'invoice_id' => $item['invoice_id'] ?? null,
                    'payment_id' => $item['payment_id'] ?? null,
                    'user_id' => auth()->user()->id ?? null,
                    'paid_amount' => $item['paid_amount'],
                    'pending_amount' => $item['pending_amount'],
                    'invoice_pending_amount' => $item['invoice_pending_amount'],
                    'obs' => $item['obs'],
                ];
            })->toArray()
        ]);
    }
}
