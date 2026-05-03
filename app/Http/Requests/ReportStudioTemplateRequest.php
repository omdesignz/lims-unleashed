<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportStudioTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'studio_type' => ['required', Rule::in(['analysis', 'executive', 'proposal'])],
            'renderer' => ['required', Rule::in(['internal', 'canva'])],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'is_default' => ['sometimes', 'boolean'],
            'theme_preset' => ['nullable', 'string', 'max:100'],
            'canva_design_url' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'layout_schema' => ['nullable', 'array'],
            'layout_schema.header' => ['nullable', 'array'],
            'layout_schema.sections' => ['nullable', 'array'],
            'layout_schema.footer' => ['nullable', 'array'],
            'export_settings' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'layout_schema' => $this->input('layout_schema', []),
            'export_settings' => $this->input('export_settings', []),
        ]);
    }
}
