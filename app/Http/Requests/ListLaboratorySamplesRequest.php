<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLaboratorySamplesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view_samples') ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['POR_INICIAR', 'EN_PROGRESO', 'EN_PAUSA', 'COMPLETADO', 'CANCELADO'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', Rule::in([25, 50, 100])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('search'))) {
            $this->merge(['search' => trim($this->input('search'))]);
        }
    }
}
