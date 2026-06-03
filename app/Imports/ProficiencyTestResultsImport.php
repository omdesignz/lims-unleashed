<?php

namespace App\Imports;

use App\Models\ProficiencyTest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProficiencyTestResultsImport implements ToCollection, WithHeadingRow
{
    /**
     * @param  Collection<int, mixed>  $collection
     */
    public function __construct(private readonly ProficiencyTest $test) {}

    public function collection(Collection $collection): void
    {
        $rows = $collection
            ->map(fn ($row) => $row instanceof Collection ? $row->toArray() : (array) $row)
            ->filter(fn (array $row) => filled($row['participant_code'] ?? null) || filled($row['participant_name'] ?? null))
            ->filter(fn (array $row) => filled($row['parameter_code'] ?? null) || filled($row['parameter_name'] ?? null))
            ->values();

        if ($rows->isEmpty()) {
            return;
        }

        $participants = $this->mergeParticipants($rows);
        $parameters = $this->mergeParameters($rows);

        $participantResults = $participants->map(function (array $participant) use ($parameters, $rows) {
            $participantKey = $participant['code'] ?: $participant['name'];
            $participantRows = $rows->filter(fn (array $row) => $this->participantKey($row) === $participantKey);

            return [
                'code' => $participant['code'],
                'name' => $participant['name'],
                'results' => $parameters->map(function (array $parameter) use ($participantRows) {
                    $parameterKey = $parameter['code'] ?: $parameter['name'];
                    $row = $participantRows->first(fn (array $item) => $this->parameterKey($item) === $parameterKey) ?? [];

                    return [
                        'parameter_code' => $parameter['code'],
                        'parameter' => $parameter['name'],
                        'unit' => $this->stringValue($row['unit'] ?? $parameter['unit'] ?? null),
                        'assigned_value' => $this->nullableNumeric($row['assigned_value'] ?? $parameter['assigned_value'] ?? null),
                        'value' => $this->stringValue($row['value'] ?? null),
                        'z_score' => $this->nullableNumeric($row['z_score'] ?? null),
                        'outcome' => $this->outcomeValue($row['outcome'] ?? null, $row['z_score'] ?? null),
                        'notes' => $this->stringValue($row['notes'] ?? null),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $this->test->fill([
            'participants' => $participants->values()->all(),
            'parameters' => $parameters->values()->all(),
            'participant_results' => $participantResults,
        ]);

        $this->test->performance_summary = $this->test->calculatePerformanceSummary();
        $this->test->save();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function mergeParticipants(Collection $rows): Collection
    {
        $existing = collect($this->test->participants ?? []);

        return $rows
            ->map(function (array $row) use ($existing) {
                $code = $this->stringValue($row['participant_code'] ?? null);
                $name = $this->stringValue($row['participant_name'] ?? null);
                $key = $code ?: $name;
                $existingRow = $existing->first(fn (array $participant) => ($participant['code'] ?? $participant['name'] ?? null) === $key) ?? [];

                return [
                    'code' => $code ?: $this->stringValue($existingRow['code'] ?? null),
                    'name' => $name ?: $this->stringValue($existingRow['name'] ?? null),
                    'contact' => $this->stringValue($row['participant_contact'] ?? $existingRow['contact'] ?? null),
                    'status' => $this->participantStatusValue($row['participant_status'] ?? $existingRow['status'] ?? null),
                ];
            })
            ->unique(fn (array $participant) => $participant['code'] ?: $participant['name'])
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function mergeParameters(Collection $rows): Collection
    {
        $existing = collect($this->test->parameters ?? []);

        return $rows
            ->map(function (array $row) use ($existing) {
                $code = $this->stringValue($row['parameter_code'] ?? null);
                $name = $this->stringValue($row['parameter_name'] ?? null);
                $key = $code ?: $name;
                $existingRow = $existing->first(fn (array $parameter) => ($parameter['code'] ?? $parameter['name'] ?? null) === $key) ?? [];

                return [
                    'code' => $code ?: $this->stringValue($existingRow['code'] ?? null),
                    'name' => $name ?: $this->stringValue($existingRow['name'] ?? null),
                    'unit' => $this->stringValue($row['unit'] ?? $existingRow['unit'] ?? null),
                    'assigned_value' => $this->nullableNumeric($row['assigned_value'] ?? $existingRow['assigned_value'] ?? null),
                    'standard_deviation' => $this->nullableNumeric($row['standard_deviation'] ?? $existingRow['standard_deviation'] ?? null),
                ];
            })
            ->unique(fn (array $parameter) => $parameter['code'] ?: $parameter['name'])
            ->values();
    }

    private function stringValue(mixed $value): string
    {
        return filled($value) ? trim((string) $value) : '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function participantKey(array $row): string
    {
        return $this->stringValue($row['participant_code'] ?? null) ?: $this->stringValue($row['participant_name'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function parameterKey(array $row): string
    {
        return $this->stringValue($row['parameter_code'] ?? null) ?: $this->stringValue($row['parameter_name'] ?? null);
    }

    private function nullableNumeric(mixed $value): float|string|null
    {
        if (! filled($value)) {
            return null;
        }

        return is_numeric($value) ? (float) $value : (string) $value;
    }

    private function outcomeValue(mixed $value, mixed $zScore): string
    {
        $outcome = strtolower($this->stringValue($value));

        if (in_array($outcome, ['pending', 'satisfactory', 'questionable', 'unsatisfactory'], true)) {
            return $outcome;
        }

        if (! is_numeric($zScore)) {
            return 'pending';
        }

        $absoluteScore = abs((float) $zScore);

        if ($absoluteScore >= 3) {
            return 'unsatisfactory';
        }

        if ($absoluteScore >= 2) {
            return 'questionable';
        }

        return 'satisfactory';
    }

    private function participantStatusValue(mixed $value): string
    {
        $status = strtolower($this->stringValue($value));

        return in_array($status, ['pending', 'enrolled', 'submitted', 'reviewed', 'requires_action'], true)
            ? $status
            : 'pending';
    }
}
