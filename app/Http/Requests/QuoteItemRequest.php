<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_quotes') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'obs' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'lab_code_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'itemable_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'itemable_type' => ['sometimes', 'nullable', Rule::in(['collectionproduct'])],
            'unit_id' => ['sometimes', 'nullable', 'integer', Rule::exists('units', 'id')->whereNull('deleted_at')],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['itemable_id', 'unit_id'] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $this->merge([$field => is_array($value) ? data_get($value, 'value') : $value]);
            }
        }
    }
}
