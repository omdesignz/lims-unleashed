<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceCategoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view_maintenance_categories');
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:200'], 'archived' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
