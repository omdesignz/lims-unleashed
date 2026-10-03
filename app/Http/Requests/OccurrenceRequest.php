<?php

namespace App\Http\Requests;

use App\Models\Occurrence;
use App\Services\SampleLaboratoryAccess;
use App\Support\OccurrenceValidation;
use Illuminate\Foundation\Http\FormRequest;

class OccurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $occurrence = $this->route('occurrence');

        if ($occurrence instanceof Occurrence && (int) $occurrence->lab_id !== $labId) {
            abort(404);
        }

        return $this->user()->can($this->isMethod('post') ? 'add_occurrences' : 'edit_occurrences');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return OccurrenceValidation::rules(app(SampleLaboratoryAccess::class)->activeLabId());
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return collect(array_keys($this->rules()))
            ->mapWithKeys(fn (string $field): array => [$field => trans('gestlab.general.labels.occurrences.'.$field)])
            ->all();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(OccurrenceValidation::normalize($this->all()));
    }
}
