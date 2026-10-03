<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\CounterAnalysis;
use App\Models\Formula;
use App\Models\Parameter;
use App\Models\ParameterProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IssuedAnalyticalScope
{
    /** @return Collection<int, Parameter> */
    public function parametersFor(Analysis|CounterAnalysis $root, CollectionProduct $product): Collection
    {
        $snapshot = data_get($product->sampleEntry?->client_submitted_info, 'required_parameters');
        $validation = Validator::make(['parameters' => $snapshot], [
            'parameters' => ['required', 'array', 'list', 'min:1', 'max:1000'],
            'parameters.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'parameters.*.name' => ['required', 'string', 'max:255'],
            'parameters.*.code' => ['nullable', 'string', 'max:255'],
            'parameters.*.profile_ids' => ['required', 'array', 'list', 'min:1'],
            'parameters.*.profile_ids.*' => ['required', 'integer', 'min:1'],
            'parameters.*.requires_calculation' => ['sometimes', 'boolean'],
            'parameters.*.result_is_qualitative' => ['sometimes', 'boolean'],
            'parameters.*.result_type' => ['nullable', 'string', 'max:255'],
            'parameters.*.active' => ['sometimes', 'boolean'],
            'parameters.*.decimal_places' => ['nullable', 'integer', 'min:0'],
            'parameters.*.formula_expression' => ['nullable', 'string'],
            'parameters.*.formula_id' => ['nullable', 'integer', 'min:1'],
            'parameters.*.formula' => ['nullable', 'array'],
            'parameters.*.calculation_parameters' => ['nullable', 'array'],
            'parameters.*.optimal_analysis_time' => ['nullable', 'string'],
            'parameters.*.profile_definitions' => ['required', 'array', 'list', 'min:1'],
            'parameters.*.profile_definitions.*.profile_id' => ['required', 'integer', 'min:1'],
            'parameters.*.profile_definitions.*.unit_code' => ['nullable', 'string'],
            'parameters.*.profile_definitions.*.unit_id' => ['nullable', 'integer', 'min:1'],
            'parameters.*.profile_definitions.*.unit_label' => ['nullable', 'string', 'max:255'],
            'parameters.*.profile_definitions.*.protocol_id' => ['nullable', 'integer', 'min:1'],
            'parameters.*.profile_definitions.*.protocol_label' => ['nullable', 'string', 'max:255'],
            'parameters.*.profile_definitions.*.standard_id' => ['nullable', 'integer', 'min:1'],
            'parameters.*.profile_definitions.*.standard_label' => ['nullable', 'string', 'max:255'],
            'parameters.*.profile_definitions.*.nwp_id' => ['nullable', 'integer', 'min:1'],
            'parameters.*.profile_definitions.*.nwp_label' => ['nullable', 'string', 'max:255'],
            'parameters.*.profile_definitions.*.category_id' => ['nullable', 'integer', 'min:1'],
            'parameters.*.profile_definitions.*.category_label' => ['nullable', 'string', 'max:255'],
            'parameters.*.profile_definitions.*.min_ref_value' => ['nullable', 'string'],
            'parameters.*.profile_definitions.*.max_ref_value' => ['nullable', 'string'],
            'parameters.*.profile_definitions.*.ref_val_origin' => ['nullable', 'string', 'max:255'],
            'parameters.*.profile_definitions.*.dilutions' => ['nullable'],
        ]);

        if ($validation->fails()) {
            throw ValidationException::withMessages(['results' => 'O âmbito analítico emitido está ausente ou inválido.']);
        }

        $issued = collect($snapshot)->filter(fn (array $item): bool => in_array((int) $root->profile_id,
            array_map('intval', $item['profile_ids']), true))->values();

        if ($issued->isEmpty()) {
            throw ValidationException::withMessages(['results' => 'O perfil não pertence ao âmbito analítico emitido.']);
        }

        if ($issued->contains(fn (array $item): bool => collect($item['profile_definitions'] ?? [])
            ->filter(fn (array $definition): bool => (int) $definition['profile_id'] === (int) $root->profile_id)->count() !== 1)) {
            throw ValidationException::withMessages(['results' => 'A definição do perfil emitido está ausente ou repetida.']);
        }

        $catalogue = Parameter::withTrashed()->whereIn('id', $issued->pluck('id'))->get()->keyBy('id');
        if ($catalogue->count() !== $issued->count()) {
            throw ValidationException::withMessages(['results' => 'Um parâmetro emitido deixou de ter uma identidade verificável.']);
        }

        return $issued->map(function (array $item) use ($catalogue, $root): Parameter {
            $parameter = $catalogue[(int) $item['id']];
            $parameter->forceFill([
                'name' => $item['name'],
                'code' => $item['code'] ?? null,
                'requires_calculation' => $item['requires_calculation'] ?? null,
                'result_is_qualitative' => $item['result_is_qualitative'] ?? null,
                'result_type' => $item['result_type'] ?? null,
                'active' => $item['active'] ?? null,
                'decimal_places' => $item['decimal_places'] ?? null,
                'formula_expression' => $item['formula_expression'] ?? null,
                'formula_id' => $item['formula_id'] ?? null,
                'calculation_parameters' => $item['calculation_parameters'] ?? null,
                'optimal_analysis_time' => $item['optimal_analysis_time'] ?? null,
            ]);
            $formula = is_array($item['formula'] ?? null)
                ? (new Formula)->forceFill($item['formula'])
                : null;
            $parameter->setRelation('formula', $formula);
            $definition = collect($item['profile_definitions'] ?? [])
                ->first(fn (array $candidate): bool => (int) $candidate['profile_id'] === (int) $root->profile_id);
            $parameter->setRelation('pivot', new ParameterProfile([
                'profile_id' => $root->profile_id,
                'parameter_id' => $parameter->id,
                'unit_id' => $definition['unit_id'] ?? null,
                'unit_label' => $definition['unit_label'] ?? $definition['unit_code'] ?? null,
                'protocol_id' => $definition['protocol_id'] ?? null,
                'protocol_label' => $definition['protocol_label'] ?? null,
                'standard_id' => $definition['standard_id'] ?? null,
                'standard_label' => $definition['standard_label'] ?? null,
                'nwp_id' => $definition['nwp_id'] ?? null,
                'nwp_label' => $definition['nwp_label'] ?? null,
                'category_id' => $definition['category_id'] ?? null,
                'category_label' => $definition['category_label'] ?? null,
                'min_ref_value' => $definition['min_ref_value'] ?? null,
                'max_ref_value' => $definition['max_ref_value'] ?? null,
                'ref_val_origin' => $definition['ref_val_origin'] ?? null,
                'dilutions' => $definition['dilutions'] ?? null,
            ]));

            return $parameter;
        });
    }
}
