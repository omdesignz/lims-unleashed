<?php

namespace App\Exports;

use App\Models\ProficiencyTest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProficiencyTestResultsTemplateExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function __construct(private readonly ProficiencyTest $test) {}

    public function collection(): Collection
    {
        $participants = collect($this->test->participants ?: [[
            'code' => 'LAB',
            'name' => 'Laboratório interno',
            'contact' => null,
        ]]);

        $parameters = collect($this->test->parameters ?: [[
            'code' => 'PARAM',
            'name' => 'Parâmetro',
            'unit' => null,
            'assigned_value' => null,
            'standard_deviation' => null,
        ]]);

        $existingResults = collect($this->test->participant_results ?? []);

        return $participants
            ->flatMap(function (array $participant) use ($parameters, $existingResults) {
                $participantKey = $participant['code'] ?? $participant['name'] ?? null;
                $existingParticipant = $existingResults->first(fn (array $row) => ($row['code'] ?? $row['name'] ?? null) === $participantKey) ?? [];
                $participantResults = collect($existingParticipant['results'] ?? []);

                return $parameters->map(function (array $parameter) use ($participant, $participantResults) {
                    $parameterKey = $parameter['code'] ?? $parameter['name'] ?? null;
                    $existingResult = $participantResults->first(fn (array $row) => ($row['parameter_code'] ?? $row['parameter'] ?? null) === $parameterKey) ?? [];

                    return [
                        'participant_code' => $participant['code'] ?? '',
                        'participant_name' => $participant['name'] ?? '',
                        'participant_contact' => $participant['contact'] ?? '',
                        'participant_status' => $participant['status'] ?? 'pending',
                        'parameter_code' => $parameter['code'] ?? '',
                        'parameter_name' => $parameter['name'] ?? '',
                        'unit' => $existingResult['unit'] ?? $parameter['unit'] ?? '',
                        'assigned_value' => $existingResult['assigned_value'] ?? $parameter['assigned_value'] ?? '',
                        'standard_deviation' => $parameter['standard_deviation'] ?? '',
                        'value' => $existingResult['value'] ?? '',
                        'z_score' => $existingResult['z_score'] ?? '',
                        'outcome' => $existingResult['outcome'] ?? 'pending',
                        'notes' => $existingResult['notes'] ?? '',
                    ];
                });
            })
            ->values();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'participant_code',
            'participant_name',
            'participant_contact',
            'participant_status',
            'parameter_code',
            'parameter_name',
            'unit',
            'assigned_value',
            'standard_deviation',
            'value',
            'z_score',
            'outcome',
            'notes',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF0369A1'],
                ],
            ],
        ];
    }
}
