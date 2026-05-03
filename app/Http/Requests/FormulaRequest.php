<?php

namespace App\Http\Requests;

use App\Services\AdvancedCalculationEngine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FormulaRequest extends FormRequest
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
        $formulaId = $this->route('formula');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('formulas', 'name')->ignore($formulaId),
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_\\-]+$/',
                Rule::unique('formulas', 'code')->ignore($formulaId),
            ],
            'category' => ['required', Rule::in(['general', 'microbiology', 'physicochemical', 'custom'])],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:8'],
            'is_active' => ['required', 'boolean'],
            'output_unit' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'formula_expression' => ['required', 'string', 'max:2000'],
            'expression' => ['required', 'string', 'max:2000'],
            'variables' => ['required', 'array', 'min:1'],
            'variables.*.name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'],
            'variables.*.label' => ['nullable', 'string', 'max:255'],
            'variables.*.value' => ['nullable', 'numeric'],
            'variables.*.unit' => ['nullable', 'string', 'max:50'],
            'variables.*.type' => ['nullable', Rule::in(['number', 'integer', 'decimal'])],
            'variables.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('gestlab.general.labels.formulas.name'),
            'code' => trans('gestlab.general.labels.formulas.code'),
            'category' => trans('gestlab.general.labels.formulas.category'),
            'decimal_places' => trans('gestlab.general.labels.formulas.decimal_places'),
            'is_active' => trans('gestlab.general.labels.formulas.is_active'),
            'output_unit' => trans('gestlab.general.labels.formulas.output_unit'),
            'description' => trans('gestlab.general.labels.formulas.description'),
            'formula_expression' => trans('gestlab.general.labels.formulas.formula_expression'),
            'expression' => trans('gestlab.general.labels.formulas.expression'),
            'variables' => trans('gestlab.general.labels.formulas.variables'),
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
            'variables.min' => 'Defina pelo menos uma variável para a fórmula.',
            'variables.*.name.regex' => 'Os nomes das variáveis devem começar por uma letra e conter apenas letras, números e underscore.',
            'code.regex' => 'O código da fórmula só pode conter letras, números, underscore e hífen.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation(): void
    {
        $variables = collect($this->input('variables', []))
            ->filter(fn ($variable) => is_array($variable))
            ->map(function (array $variable) {
                $name = trim((string) ($variable['name'] ?? ''));

                return [
                    'name' => $name,
                    'label' => trim((string) ($variable['label'] ?? $name)),
                    'value' => $variable['value'] ?? null,
                    'unit' => trim((string) ($variable['unit'] ?? '')),
                    'type' => $variable['type'] ?? 'number',
                    'description' => trim((string) ($variable['description'] ?? '')),
                ];
            })
            ->values()
            ->all();

        $expression = trim((string) $this->input('expression', ''));
        $formulaExpression = trim((string) $this->input('formula_expression', ''));

        if ($formulaExpression === '' && $expression !== '') {
            $formulaExpression = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)/', '{$1}', $expression) ?? $expression;
        }

        $this->merge([
            'code' => str($this->input('code', ''))
                ->trim()
                ->replaceMatches('/[^a-zA-Z0-9_\-]+/', '_')
                ->trim('_')
                ->value(),
            'expression' => $expression,
            'formula_expression' => $formulaExpression,
            'variables' => $variables,
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $formulaExpression = (string) $this->input('formula_expression', '');
            $definedVariables = collect($this->input('variables', []))
                ->pluck('name')
                ->filter()
                ->values()
                ->all();

            preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $formulaExpression, $matches);
            $referencedVariables = collect($matches[1] ?? [])->unique()->values()->all();

            $missingDefinitions = array_values(array_diff($referencedVariables, $definedVariables));

            if ($missingDefinitions !== []) {
                $validator->errors()->add(
                    'variables',
                    'A fórmula referencia variáveis não definidas: ' . implode(', ', $missingDefinitions) . '.'
                );
            }

            $engineValidation = app(AdvancedCalculationEngine::class)->validateFormula($formulaExpression);

            foreach ($engineValidation['errors'] as $error) {
                $validator->errors()->add('formula_expression', $error);
            }
        });
    }
}
