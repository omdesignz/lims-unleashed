<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class ReportStudioDraftPreviewRequest extends ReportStudioTemplateRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'name' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
