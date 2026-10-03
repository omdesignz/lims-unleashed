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

    /**
     * Four-eyes review, approved on 2026-10-03: insertion, verification and approval
     * of a result are recorded by three different people. A stage recorded by an
     * unknown actor (older evidence without an actor id) does not block review.
     */
    public function ensureIndependentReviewer(Result $result, string $stage, int $operatorId): void
    {
        $earlier = match ($stage) {
            'verify' => ['inserted' => 'Quem inseriu o resultado não o pode verificar.'],
            'approve' => [
                'inserted' => 'Quem inseriu o resultado não o pode aprovar.',
                'verified' => 'Quem verificou o resultado não o pode aprovar.',
            ],
            default => [],
        };

        foreach ($earlier as $prefix => $message) {
            if ($result->{$prefix.'_by_id'} !== null && (int) $result->{$prefix.'_by_id'} === $operatorId) {
                throw ValidationException::withMessages(['results' => $message]);
            }
        }
    }

    private function meaningful(mixed $value): bool
    {
        return (is_string($value) && trim($value) !== '') || is_int($value) || (is_float($value) && is_finite($value));
    }
}
