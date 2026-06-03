<?php

namespace App\Http\Requests\VAP;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInternalQualityControlDecisionRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                Rule::in([
                    'released',
                    'rejected',
                    'quarantined',
                    'investigation_required',
                    'trend_recorded',
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
