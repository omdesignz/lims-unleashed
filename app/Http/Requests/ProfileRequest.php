<?php

namespace App\Http\Requests;

use App\Models\AnalysisCategory;
use App\Models\Parameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $codeRule = Rule::unique('profiles', 'code');

        if (! $this->isMethod('post')) {
            $codeRule->ignore((int) $this->route('profile'));
        }

        return [
            'name' => 'required',
            'code' => ['nullable', 'min:1', $codeRule],
            'description' => 'nullable',
            'price' => 'nullable',
            'category_id' => [$this->isMethod('post') ? 'required' : 'nullable', 'exists:analysis_categories,id'],
            'parameters' => 'required|array|min:1',
            'parameters.*.parameter_id' => 'required|exists:parameters,id',
            'parameters.*.unit_id' => 'required|exists:units,id',
            'parameters.*.unit_label' => 'nullable',
            'parameters.*.protocol_id' => 'nullable|exists:protocols,id',
            'parameters.*.protocol_label' => 'nullable',
            'parameters.*.nwp_id' => 'nullable|exists:nwps,id',
            'parameters.*.nwp_label' => 'nullable',
            'parameters.*.standard_id' => 'nullable|exists:standards,id',
            'parameters.*.standard_label' => 'nullable',
            'parameters.*.count' => 'boolean',
            'parameters.*.min_ref_value' => 'required',
            'parameters.*.max_ref_value' => 'nullable',
            'parameters.*.category_label' => 'nullable',
            'parameters.*.dilutions' => 'nullable',
            'parameters.*.extra_data' => 'nullable',
            'parameters.*.category_id' => 'required|exists:result_categories,id',
            'parameters.*.formula_label' => 'nullable',
            'parameters.*.formula_id' => 'nullable|exists:formulas,id',
            'parameters.*.optimal_analysis_time' => 'nullable',
            'parameters.*.ref_val_origin' => 'nullable',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('gestlab.general.labels.profiles.name'),
            'code' => trans('gestlab.general.labels.profiles.code'),
            'description' => trans('gestlab.general.labels.profiles.description'),
            'price' => trans('gestlab.general.labels.profiles.price'),
            'category_id' => trans('gestlab.general.labels.profiles.category_id_1'),
            'parameters' => trans('gestlab.general.labels.profiles.parameters'),
            'parameters.*.parameter_id' => trans('gestlab.general.labels.profiles.parameter_id'),
            'parameters.*.formula_id' => trans('gestlab.general.labels.profiles.formula_id'),
            'parameters.*.unit_id' => trans('gestlab.general.labels.profiles.unit_id'),
            'parameters.*.protocol_id' => trans('gestlab.general.labels.profiles.protocol_id'),
            'parameters.*.dilutions' => trans('gestlab.general.labels.profiles.dilutions'),
            'parameters.*.nwp_id' => trans('gestlab.general.labels.profiles.nwp_id'),
            'parameters.*.standard_id' => trans('gestlab.general.labels.profiles.standard_id'),
            'parameters.*.min_ref_value' => trans('gestlab.general.labels.profiles.min_ref_value'),
            'parameters.*.max_ref_value' => trans('gestlab.general.labels.profiles.max_ref_value'),
            'parameters.*.category_id' => trans('gestlab.general.labels.profiles.category_id'),
            'parameters.*.optimal_analysis_time' => trans('gestlab.general.labels.profiles.optimal_analysis_time'),
            'parameters.*.ref_val_origin' => trans('gestlab.general.labels.profiles.ref_val_origin'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parameters.*.parameter_id.required' => 'É obrigatória a indicação de um valor para o campo parâmetro',
        ];
    }

    public function prepareForValidation(): void
    {
        $parameters = $this->input('parameters');

        $this->merge([
            'category_id' => $this->optionValue($this->input('category_id')),
            'parameters' => is_array($parameters) ? collect($parameters)->map(function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                return [
                    'parameter_id' => $this->optionValue(data_get($item, 'parameter_id')),
                    'unit_id' => $this->optionValue(data_get($item, 'unit_id')),
                    'unit_label' => $this->optionLabel(data_get($item, 'unit_id')),
                    'protocol_id' => $this->optionValue(data_get($item, 'protocol_id')),
                    'protocol_label' => $this->optionLabel(data_get($item, 'protocol_id')),
                    'nwp_id' => $this->optionValue(data_get($item, 'nwp_id')),
                    'nwp_label' => $this->optionLabel(data_get($item, 'nwp_id')),
                    'standard_id' => $this->optionValue(data_get($item, 'standard_id')),
                    'standard_label' => $this->optionLabel(data_get($item, 'standard_id')),
                    'count' => data_get($item, 'count', true),
                    'formula_id' => $this->optionValue(data_get($item, 'formula_id')),
                    'formula_label' => $this->optionLabel(data_get($item, 'formula_id')),
                    'category_id' => $this->optionValue(data_get($item, 'category_id')),
                    'category_label' => $this->optionLabel(data_get($item, 'category_id')),
                    'min_ref_value' => data_get($item, 'min_ref_value'),
                    'max_ref_value' => data_get($item, 'max_ref_value'),
                    'dilutions' => $this->encodeJsonValue(data_get($item, 'dilutions')),
                    'extra_data' => $this->encodeJsonValue(data_get($item, 'extra_data')),
                    'optimal_analysis_time' => data_get($item, 'optimal_analysis_time'),
                    'ref_val_origin' => data_get($item, 'ref_val_origin'),
                ];
            })->all() : $parameters,
        ]);
    }

    private function optionValue(mixed $option): mixed
    {
        if (is_array($option) || is_object($option)) {
            return data_get($option, 'value');
        }

        return $option === '' ? null : $option;
    }

    private function optionLabel(mixed $option): ?string
    {
        if ($this->optionValue($option) === null) {
            return null;
        }

        $label = data_get($option, 'label');

        return is_scalar($label) ? (string) $label : null;
    }

    private function encodeJsonValue(mixed $value): string
    {
        if (is_string($value)) {
            json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        return json_encode($value ?? [], JSON_THROW_ON_ERROR);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $parameterIds = collect($this->input('parameters', []))
                    ->pluck('parameter_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($parameterIds->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('parameters', 'O perfil não pode repetir o mesmo parâmetro.');
                }

                if ($parameterIds->isEmpty()) {
                    return;
                }

                $inactiveParameters = Parameter::query()
                    ->whereIn('id', $parameterIds)
                    ->where('active', false)
                    ->pluck('name');

                if ($inactiveParameters->isNotEmpty()) {
                    $validator->errors()->add(
                        'parameters',
                        'Todos os parâmetros do perfil devem estar activos. Inactivos: '.$inactiveParameters->implode(', ')
                    );
                }

                collect($this->input('parameters', []))
                    ->each(function (mixed $parameter, int $index) use ($validator): void {
                        if (! is_array($parameter)) {
                            return;
                        }

                        $min = data_get($parameter, 'min_ref_value');
                        $max = data_get($parameter, 'max_ref_value');

                        if ($min !== null && $min !== '' && ! is_numeric($min)) {
                            $validator->errors()->add("parameters.$index.min_ref_value", 'O limite mínimo deve ser numérico.');
                        }

                        if ($max !== null && $max !== '' && ! is_numeric($max)) {
                            $validator->errors()->add("parameters.$index.max_ref_value", 'O limite máximo deve ser numérico.');
                        }

                        if (
                            $min !== null && $min !== ''
                            && $max !== null && $max !== ''
                            && is_numeric($min) && is_numeric($max)
                            && (float) $max < (float) $min
                        ) {
                            $validator->errors()->add("parameters.$index.max_ref_value", 'O limite máximo não pode ser inferior ao mínimo.');
                        }
                    });

                $categoryId = $this->input('category_id');

                if (! $categoryId) {
                    return;
                }

                $analysisCategory = AnalysisCategory::query()->find($categoryId);

                if (! $analysisCategory || ! $analysisCategory->department_id) {
                    $validator->errors()->add(
                        'category_id',
                        'A categoria analítica do perfil deve estar vinculada a um departamento.'
                    );
                }
            },
        ];
    }
}
