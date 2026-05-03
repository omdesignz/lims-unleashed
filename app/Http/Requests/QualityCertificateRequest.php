<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QualityCertificateRequest extends FormRequest
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
                'customer_id' => 'required|exists:customers,id',
                'cl_id' => 'required|exists:lab_codes,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'invoice_id' => 'nullable|exists:invoices,id',
                'obs' => 'nullable',
                'status' => 'boolean',
            ];
        } else {
            $rules = [
                'customer_id' => 'required|exists:customers,id',
                'cl_id' => 'required|exists:lab_codes,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'invoice_id' => 'nullable|exists:invoices,id',
                'obs' => 'nullable',
                'status' => 'boolean',
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
            'customer_id' => trans('gestlab.general.labels.quality_certificates.customer_id'),
            'cl_id' => trans('gestlab.general.labels.quality_certificates.cl_id'),
            'product_id' => trans('gestlab.general.labels.quality_certificates.product_id'),
            'warehouse_id' => trans('gestlab.general.labels.quality_certificates.warehouse_id'),
            'invoice_id' => trans('gestlab.general.labels.quality_certificates.invoice_id'),
            'obs' => trans('gestlab.general.labels.quality_certificates.obs'),
            'status' => trans('gestlab.general.labels.quality_certificates.status'),
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
        $this->merge([
            'customer_id' => !is_null(request()->customer_id) ? request()->customer_id['value'] : null,
            'warehouse_id' => !is_null(request()->warehouse_id) ? request()->warehouse_id['value'] : null,
            'cl_id' => !is_null(request()->cl_id) ? request()->cl_id['value'] : null,
            'product_id' => !is_null(request()->product_id) ? request()->product_id['value'] : null,
            'invoice_id' => !is_null(request()->invoice_id) ? request()->invoice_id['value'] : null,
            'status' => request()->boolean('status'),
        ]);
    }
}
