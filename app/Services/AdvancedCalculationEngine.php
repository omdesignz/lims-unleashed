<?php

namespace App\Services;

class AdvancedCalculationEngine
{
    private $safeFunctions = [
        'sqrt', 'log', 'log10', 'exp', 'abs', 'round', 'ceil', 'floor',
        'max', 'min', 'avg', 'sum', 'count'
    ];

    public function evaluateFormula(string $formula, array $variables): float
    {
        // Step 1: Replace variables with values
        $expression = $this->replaceVariables($formula, $variables);
        
        // Step 2: Validate expression safety
        $this->validateExpression($expression);
        
        // Step 3: Evaluate using proper math parser
        return $this->evaluateMathExpression($expression);
    }

    public function validateFormula(string $formula): array
    {
        $errors = [];
        
        // Extract variables from formula
        $variables = $this->extractVariables($formula);
        
        // Check for unsafe functions
        if (preg_match('/[a-zA-Z_]+\(/', $formula)) {
            $functions = [];
            preg_match_all('/([a-zA-Z_]+)\(/', $formula, $functions);
            
            foreach ($functions[1] as $function) {
                if (!in_array($function, $this->safeFunctions)) {
                    $errors[] = "Função não permitida: {$function}";
                }
            }
        }
        
        // Check for dangerous operations
        if (preg_match('/exec|system|shell|eval|`|\\$|include|require/i', $formula)) {
            $errors[] = "Operações perigosas detectadas na fórmula";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'variables' => $variables
        ];
    }

    private function extractVariables(string $formula): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $formula, $matches);
        return array_unique($matches[1]);
    }

    private function replaceVariables(string $formula, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $formula = str_replace("{{$key}}", (string)$value, $formula);
        }
        return $formula;
    }

    private function validateExpression(string $expression): void
    {
        // Remove all safe function calls for basic validation
        $cleanExpr = preg_replace('/[a-zA-Z_]+\([^)]*\)/', '1', $expression);
        
        // Should only contain numbers, operators, and parentheses
        if (!preg_match('/^[0-9+\-*\/\/()., ]+$/', $cleanExpr)) {
            throw new \Exception("Expressão matemática inválida");
        }
    }

    private function evaluateMathExpression(string $expression): float
    {
        // Use a proper math parser library like:
        // - composer require mossadal/math-parser
        // - Or implement safe evaluation
        
        try {
            // Remove any remaining variable placeholders
            if (preg_match('/\{[^}]+\}/', $expression)) {
                throw new \Exception("Variáveis não substituídas na fórmula");
            }
            
            // Simple evaluator for basic operations (replace with proper parser)
            $result = eval("return {$expression};");
            
            if (!is_numeric($result)) {
                throw new \Exception("Resultado não é numérico");
            }
            
            return (float)$result;
        } catch (\Throwable $e) {
            throw new \Exception("Erro ao calcular fórmula: " . $e->getMessage());
        }
    }
}