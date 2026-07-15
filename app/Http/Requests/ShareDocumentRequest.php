<?php

namespace App\Http\Requests;

use App\Support\ShareableDocumentRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ShareDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $definition = app(ShareableDocumentRegistry::class)->definition($this->string('document_type')->toString());

        return $definition && ($this->user()?->can($definition['permission']) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(app(ShareableDocumentRegistry::class)->keys())],
            'document_id' => ['required', 'integer', 'min:1'],
            'recipients' => ['required', 'array', 'min:1', 'max:10'],
            'recipients.*' => ['required', 'email:rfc', 'max:255'],
            'cc' => ['nullable', 'array', 'max:10'],
            'cc.*' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['document_type', 'document_id'])) {
                    return;
                }

                $definition = app(ShareableDocumentRegistry::class)->definition($this->string('document_type')->toString());
                $model = $definition['model'] ?? null;

                if (! $model || ! $model::query()->whereKey($this->integer('document_id'))->exists()) {
                    $validator->errors()->add('document_id', 'O documento seleccionado já não está disponível.');
                }
            },
        ];
    }
}
