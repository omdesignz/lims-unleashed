<?php

namespace App\Http\Requests;

use App\Models\CustomerRequest;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->isMethod('post') ? 'add_customer_requests' : 'edit_customer_requests')
            && ! $this->session()->has('impersonate');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $record = $this->isMethod('post') ? null : CustomerRequest::query()
            ->forLaboratory(app(SampleLaboratoryAccess::class)->activeLabId())->findOrFail($this->route('request'));

        return [
            'lab_id' => ['prohibited'],
            'description' => ['required', 'string', 'max:5000'],
            'email' => ['required', 'email', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'answered' => ['sometimes', 'boolean'],
            'category_id' => ['required', 'integer', Rule::exists('customer_request_categories', 'id')->whereNull('deleted_at')],
            'customer_id' => ['required', 'integer', $record ? Rule::in([$record->customer_id]) : Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'warehouse_id' => ['required', 'integer', $record ? Rule::in([$record->warehouse_id]) : Rule::exists('warehouses', 'id')->where('customer_id', $this->input('customer_id'))->whereNull('deleted_at')],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['category_id', 'customer_id', 'warehouse_id'] as $field) {
            if (is_array($this->input($field))) {
                $this->merge([$field => $this->input($field.'.value')]);
            }
        }
    }
}
