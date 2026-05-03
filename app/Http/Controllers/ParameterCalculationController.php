<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use App\Models\Sample;
use App\Services\AdvancedCalculationEngine;
use Illuminate\Http\Request;

class ParameterCalculationController extends Controller
{
    public function calculate(Request $request, AdvancedCalculationEngine $calculator)
    {
        $request->validate([
            'parameter_id' => 'required|exists:parameters,id',
            'inputs' => 'required|array'
        ]);

        $parameter = Parameter::query()->with('formula')->findOrFail($request->parameter_id);

        if (!$parameter->requires_calculation) {
            return response()->json([
                'error' => 'Parameter does not require calculation'
            ], 422);
        }

        try {
            $formulaExpression = $parameter->formula?->formula_expression ?? $parameter->formula_expression;

            if (! $formulaExpression) {
                throw new \Exception('No formula defined for this parameter');
            }

            $result = $calculator->evaluateFormula($formulaExpression, $request->inputs);

            // Apply parameter-specific formatting
            $result = round($result, $parameter->decimal_places);

            return response()->json([
                'success' => true,
                'result' => $result,
                'parameter' => $parameter->code,
                'formula_used' => $formulaExpression,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function storeResults(Request $request)
    {
        $request->validate([
            'sample_id' => 'required|exists:samples,id',
            'results' => 'required|array',
            'overrides' => 'sometimes|array'
        ]);

        $sample = Sample::findOrFail($request->sample_id);

        foreach ($request->results as $parameterCode => $value) {
            $parameter = Parameter::query()->with('formula')->where('code', $parameterCode)->first();
            
            if (!$parameter) {
                continue;
            }

            $isOverride = $request->overrides[$parameterCode] ?? false;
            $isCalculated = $parameter->requires_calculation && !$isOverride;

            $sample->results()->updateOrCreate(
                ['parameter_code' => $parameterCode],
                [
                    'value' => $value,
                    'is_calculated' => $isCalculated,
                    'is_override' => $isOverride,
                    'technician_id' => auth()->id(),
                    'calculation_metadata' => $isCalculated ? [
                        'formula_used' => $parameter->formula?->formula_expression ?? $parameter->formula_expression,
                        'calculated_at' => now(),
                        'inputs' => $this->getCalculationInputs($parameter, $request->results)
                    ] : null
                ]
            );
        }

        // Update sample status
        $sample->update([
            'results_status' => 'completed',
            'completed_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    private function getCalculationInputs(Parameter $parameter, array $results): array
    {
        $inputs = [];
        $required = $parameter->getCalculationRequirements();
        
        foreach ($required as $inputCode) {
            if (isset($results[$inputCode])) {
                $inputs[$inputCode] = $results[$inputCode];
            }
        }
        
        return $inputs;
    }
}
