<?php

namespace App\Http\Requests;

use App\Models\ReportStudioTemplate;
use App\Support\ReportStudioPdfBuilder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The values typed to issue a free-form document: its number, revision and
 * date, and one value for each field its template declares.
 */
class ReportStudioIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'document_code' => ['required', 'string', 'max:80'],
            'document_revision' => ['nullable', 'string', 'max:20'],
            'issue_date' => ['nullable', 'date'],
            'fields' => ['nullable', 'array'],
        ];

        foreach ($this->templateFields() as $field) {
            $rules['fields.'.$field['key']] = [
                $field['required'] ? 'required' : 'nullable',
                ...match ($field['type']) {
                    'date' => ['date'],
                    'number' => ['numeric'],
                    'long_text' => ['string', 'max:20000'],
                    default => ['string', 'max:500'],
                },
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_code' => 'número do documento',
            'document_revision' => 'revisão',
            'issue_date' => 'data de emissão',
            ...collect($this->templateFields())->mapWithKeys(fn (array $field): array => ['fields.'.$field['key'] => mb_strtolower($field['label'])])->all(),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, type: string, sample: string, required: bool}>
     */
    private function templateFields(): array
    {
        $template = $this->route('reportStudio');

        return $template instanceof ReportStudioTemplate
            ? app(ReportStudioPdfBuilder::class)->customFieldsOf($template->layout_schema ?? [])
            : [];
    }
}
