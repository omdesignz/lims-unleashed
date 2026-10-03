<?php

namespace App\Services;

use App\Models\Result;
use Illuminate\Validation\ValidationException;

class LaboratoryResultStageIntegrity
{
    /** @param array<string, mixed> $row */
    public function ensure(Result $result, string $stage, array $row, int $index): void
    {
        $prefix = match ($stage) {
            'analyze' => 'inserted', 'verify' => 'verified', 'approve' => 'approved',
            default => throw ValidationException::withMessages(['action' => 'Etapa inválida.']),
        };
        if (! $this->meaningful($row[$prefix.'_value'] ?? null)) {
            throw ValidationException::withMessages(["results.$index.{$prefix}_value" => 'É obrigatório indicar um resultado não vazio.']);
        }
        $required = match ($stage) {
            'analyze' => [], 'verify' => ['inserted'], 'approve' => ['inserted', 'verified'],
        };
        foreach ($required as $previous) {
            if (! $result->exists || blank($result->{$previous.'_date'}) || ! $this->meaningful($result->{$previous.'_value'})) {
                throw ValidationException::withMessages(["results.$index.result_id" => 'O resultado deve ser inserido, verificado e aprovado por esta ordem.']);
            }
        }
    }

    private function meaningful(mixed $value): bool
    {
        return (is_string($value) && trim($value) !== '') || is_int($value) || (is_float($value) && is_finite($value));
    }
}
