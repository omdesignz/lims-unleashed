<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use App\Support\SpecimenParameterSelection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSpecimensRequest extends FormRequest
{
    public function authorize(LaboratoryWorkflowOwnership $ownership, SampleLaboratoryAccess $laboratory): bool
    {
        $operator = $ownership->eligibleUsers($laboratory->activeLabId())->find($this->user()?->id);

        return $operator?->can('view_samples') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'parameters' => ['sometimes', 'array', 'list', 'max:100'],
            'parameters.*' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'filter' => ['sometimes', 'array:collection.code,code,created_at,globalFilter,parameters,trashed'],
            'filter.collection\.code' => ['nullable', 'string', 'max:255'],
            'filter.code' => ['nullable', 'string', 'max:255'],
            'filter.created_at' => ['nullable', 'date_format:Y-m-d'],
            'filter.globalFilter' => ['nullable', 'string', 'max:255'],
            'filter.parameters' => ['sometimes', 'array', 'list', 'max:100'],
            'filter.parameters.*' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX],
            'filter.trashed' => ['nullable', Rule::in(['only', 'with', 'without'])],
            'sort' => ['nullable', 'string', 'max:255'],
            'globalFilter' => ['nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:2147483647'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'includes' => ['sometimes', 'array', 'list', 'max:10'],
            'includes.*' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('parameters')) {
            $this->merge(['parameters' => SpecimenParameterSelection::normalize($this->input('parameters'))]);
        }

        $filter = $this->input('filter');
        if (is_array($filter) && array_key_exists('parameters', $filter)) {
            $filter['parameters'] = SpecimenParameterSelection::normalize($filter['parameters'], 'filter.parameters');
            $this->merge(['filter' => $filter]);
        }

        if (is_string($this->input('q'))) {
            $this->merge(['q' => trim($this->input('q'))]);
        }
    }
}
