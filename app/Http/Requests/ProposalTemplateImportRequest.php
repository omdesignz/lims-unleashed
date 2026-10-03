<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProposalTemplateImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('import_proposal_templates');
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['template_file' => ['required', 'file', 'mimes:json,txt,xlsx,csv', 'extensions:json,txt,xlsx,csv', 'max:5120']];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->all()), ['template_file', '_token', '_method']) as $field) {
                $validator->errors()->add($field, 'Este campo não pode ser alterado neste formulário.');
            }
        }];
    }
}
