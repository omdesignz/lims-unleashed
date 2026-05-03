<?php

namespace App\Http\Requests;

use App\Models\Formula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ParameterRequest extends FormRequest
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
        $parameterId = $this->route('parameter');

        $rules = [
            'name' => ['required', 'min:1', Rule::unique('parameters', 'name')->ignore($parameterId)],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'min:1', Rule::unique('parameters', 'code')->ignore($parameterId)],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_id' => [
                Rule::requiredIf(fn () => $this->boolean('charge_tax')),
                'nullable',
                'exists:tax_types,id',
            ],
            'tax_percentage' => ['required', 'numeric', 'min:0'],
            'charge_tax' => ['required', 'boolean'],
            'withhold_tax' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
            'result_is_qualitative' => ['required', 'boolean'],
            'exemption_id' => [
                Rule::requiredIf(fn () => ! $this->boolean('charge_tax')),
                'nullable',
                'exists:tax_exemptions,id',
            ],
            'exemption_code' => ['nullable', 'string'],
            'optimal_analysis_time' => ['nullable', 'string', 'max:255'],
            'variables' => ['nullable', 'array'],
            'formula_id' => ['nullable', 'exists:formulas,id'],
            'formula_expression' => ['nullable', 'string', 'max:2000'],
            'calculation_parameters' => ['nullable', 'array'],
            'calculation_parameters.*' => ['string', 'min:1'],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:8'],
            'result_type' => ['nullable', Rule::in(['quantitative', 'qualitative'])],
            'requires_calculation' => ['required', 'boolean'],
        ];

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
            'name' => trans('gestlab.general.labels.parameters.name'),
            'code' => trans('gestlab.general.labels.parameters.code'),
            'tax_id' => trans('gestlab.general.labels.parameters.tax_id'),
            'charge_tax' => trans('gestlab.general.labels.parameters.charge_tax'),
            'withhold_tax' => trans('gestlab.general.labels.parameters.withhold_tax'),
            'description' => trans('gestlab.general.labels.parameters.description'),
            'price' => trans('gestlab.general.labels.parameters.price'),
            'optimal_analysis_time' => trans('gestlab.general.labels.parameters.optimal_analysis_time'),
            'result_is_qualitative' => trans('gestlab.general.labels.parameters.result_is_qualitative'),
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
        $resultIsQualitative = request()->boolean('result_is_qualitative');
        $requiresCalculation = request()->boolean('requires_calculation');
        $formulaId = request()->input('formula_id');

        if (is_array($formulaId)) {
            $formulaId = $formulaId['value'] ?? null;
        }

        $calculationParameters = request()->input('calculation_parameters', []);

        if (is_string($calculationParameters)) {
            $decoded = json_decode($calculationParameters, true);
            $calculationParameters = is_array($decoded) ? $decoded : [];
        }

        $normalizedCalculationParameters = collect($calculationParameters)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $baseData = [
            'withhold_tax' => request()->boolean('withhold_tax') ? 1 : 0,
            'active' => request()->boolean('active') ? 1 : 0,
            'charge_tax' => request()->boolean('charge_tax') ? 1 : 0,
            'result_is_qualitative' => $resultIsQualitative ? 1 : 0,
            'requires_calculation' => $requiresCalculation ? 1 : 0,
            'formula_id' => $formulaId,
            'formula_expression' => trim((string) request()->input('formula_expression', '')),
            'calculation_parameters' => $normalizedCalculationParameters,
            'decimal_places' => request()->input('decimal_places'),
            'result_type' => $resultIsQualitative ? 'qualitative' : 'quantitative',
        ];

        if(request()->boolean('charge_tax')) {
            $this->merge([
                'exemption_id' => null,
                'exemption_code' => null,
                'tax_id' => !is_null(request()->tax_id) ? request()->tax_id['value'] : null,
                'tax_percentage' => !is_null(request()->tax_id) ? request()->tax_id['percent'] : 0,
                ...$baseData,
            ]);
        } else {
            $this->merge([
                'exemption_id' => !is_null(request()->exemption_id) ? request()->exemption_id['value'] : null,
                'exemption_code' => !is_null(request()->exemption_id) ? request()->exemption_id['label'] : null,
                'tax_id' => !is_null(request()->tax_id) ? request()->tax_id['value'] ?? null : null,
                'tax_percentage' => !is_null(request()->tax_id) ? request()->tax_id['percent'] : 0,
                ...$baseData,
            ]);
        }

        if (! $requiresCalculation) {
            $this->merge([
                'formula_id' => null,
                'formula_expression' => '',
                'calculation_parameters' => [],
            ]);
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $requiresCalculation = $this->boolean('requires_calculation');
                $resultIsQualitative = $this->boolean('result_is_qualitative');

                if ($resultIsQualitative && $requiresCalculation) {
                    $validator->errors()->add(
                        'requires_calculation',
                        'Parâmetros qualitativos não devem depender de cálculo automático.'
                    );
                }

                if (! $requiresCalculation) {
                    return;
                }

                $formula = $this->filled('formula_id')
                    ? Formula::query()->find($this->input('formula_id'))
                    : null;

                if ($this->filled('formula_id') && (! $formula || ! $formula->is_active)) {
                    $validator->errors()->add('formula_id', 'A fórmula selecionada deve estar ativa.');
                }

                $formulaExpression = trim((string) $this->input('formula_expression', ''));
                $resolvedExpression = $formulaExpression !== ''
                    ? $formulaExpression
                    : (string) ($formula?->formula_expression ?? '');

                if ($resolvedExpression === '') {
                    $validator->errors()->add(
                        'formula_expression',
                        'Parâmetros calculados precisam de uma fórmula ativa ou de uma expressão personalizada.'
                    );
                }

                $declaredParameters = collect($this->input('calculation_parameters', []))
                    ->filter(fn ($value) => filled($value))
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->unique()
                    ->values();

                if ($declaredParameters->isEmpty()) {
                    $validator->errors()->add(
                        'calculation_parameters',
                        'Defina os parâmetros de entrada necessários para o cálculo.'
                    );
                }

                preg_match_all('/\{([^}]+)\}/', $resolvedExpression, $matches);
                $expressionVariables = collect($matches[1] ?? [])
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->unique()
                    ->values();

                if ($expressionVariables->isNotEmpty()) {
                    $missingVariables = $expressionVariables->diff($declaredParameters);
                    $extraVariables = $declaredParameters->diff($expressionVariables);

                    if ($missingVariables->isNotEmpty()) {
                        $validator->errors()->add(
                            'calculation_parameters',
                            'Faltam parâmetros declarados para a expressão: ' . $missingVariables->implode(', ')
                        );
                    }

                    if ($extraVariables->isNotEmpty()) {
                        $validator->errors()->add(
                            'calculation_parameters',
                            'Existem parâmetros declarados que não aparecem na expressão: ' . $extraVariables->implode(', ')
                        );
                    }
                }
            },
        ];
    }
}
