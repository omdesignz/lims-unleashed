<?php

namespace App\Http\Requests\VAP;

use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSampleDiscardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('delete_samples') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sample_id' => ['required', 'integer', Rule::exists('sample_entries', 'id')
                ->where('lab_id', app(SampleLaboratoryAccess::class)->activeLabId())->whereNull('deleted_at')],
            'discard_method' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'string', 'max:255'],
            'discarded_at' => ['nullable', 'date'],
            'lab_id' => ['nullable', 'integer', Rule::in([app(SampleLaboratoryAccess::class)->activeLabId()])],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
    }
}
