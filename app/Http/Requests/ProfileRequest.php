<?php

namespace App\Http\Requests;

use App\Models\AnalysisCategory;
use App\Models\Parameter;
use Illuminate\Foundation\Http\FormRequest;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name' => 'required',
                'code' => 'nullable|min:1|unique:profiles,code',
                'description' => 'nullable',
                'price' => 'nullable',
                'category_id' => 'required|exists:analysis_categories,id',
                'parameters' => 'required|array|min:1',
                'parameters.*.parameter_id' => 'required|exists:parameters,id',
                'parameters.*.unit_id' => 'required',
                'parameters.*.unit_label' => 'nullable',
                'parameters.*.protocol_id' => 'nullable',
                'parameters.*.protocol_label' => 'nullable',
                'parameters.*.nwp_id' => 'nullable',
                'parameters.*.nwp_label' => 'nullable',
                'parameters.*.standard_id' => 'nullable',
                'parameters.*.standard_label' => 'nullable',
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
        } else {
            $rules = [
                'name' => 'required',
                'code' => 'nullable|min:1|unique:profiles,code,' . request()->profile,
                'description' => 'nullable',
                'price' => 'nullable',
                'category_id' => 'nullable|exists:analysis_categories,id',
                'parameters' => 'required|array|min:1',
                'parameters.*.parameter_id' => 'required|exists:parameters,id',
                'parameters.*.unit_id' => 'required',
                'parameters.*.unit_label' => 'nullable',
                'parameters.*.protocol_id' => 'nullable',
                'parameters.*.protocol_label' => 'nullable',
                'parameters.*.nwp_id' => 'nullable',
                'parameters.*.nwp_label' => 'nullable',
                'parameters.*.standard_id' => 'nullable',
                'parameters.*.standard_label' => 'nullable',
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

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
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

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        // dd(request()->all());
        $this->merge([
            'category_id' => !is_null(request()->category_id) ? request()->category_id['value'] : null,
            'parameters' => is_null(request()->parameters) ? [] : collect(request()->parameters)->map(function ($item) {
                return [
                    'parameter_id' => $item['parameter_id']['value'],
                    'unit_id' => $item['unit_id']['value'],
                    'unit_label' => $item['unit_id']['label'],
                    'protocol_id' => $item['protocol_id']['value'],
                    'protocol_label' => $item['protocol_id']['label'],
                    'nwp_id' => $item['nwp_id']['value'],
                    'nwp_label' => $item['nwp_id']['label'],
                    'standard_id' => $item['standard_id']['value'],
                    'standard_label' => $item['standard_id']['label'],
                    'count' => $item['count'],
                    'formula_id' => $item['formula_id']['value'] ?? null,
                    'formula_label' => $item['formula_id']['label'] ?? null,
                    'category_id' => $item['category_id']['value'],
                    'category_label' => $item['category_id']['label'],
                    'min_ref_value' => $item['min_ref_value'],
                    'max_ref_value' => $item['max_ref_value'],
                    'dilutions' => json_encode($item['dilutions']) ?? json_encode([]),
                    'extra_data' => json_encode($item['extra_data']) ?? json_encode([]),
                    'optimal_analysis_time' => $item['optimal_analysis_time'],
                    'ref_val_origin' => $item['ref_val_origin'],
                ];
            })->toArray()
        ]);
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
                        'Todos os parâmetros do perfil devem estar ativos. Inativos: ' . $inactiveParameters->implode(', ')
                    );
                }

                collect($this->input('parameters', []))
                    ->each(function (array $parameter, int $index) use ($validator): void {
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
