<?php

namespace App\Exports;

use Generator;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PendingAnalysisWorksheetExport implements FromGenerator, ShouldAutoSize, WithHeadings, WithStrictNullComparison, WithStyles
{
    public function __construct(
        private readonly Builder $query
    ) {}

    public function generator(): Generator
    {
        foreach ((clone $this->query)->cursor() as $row) {
            yield [
                $this->safeText($row->laboratory_code),
                $this->safeText($row->sample_code),
                $this->safeText($row->sample_entry_code),
                $this->safeText($row->department),
                $this->safeText($row->customer),
                $this->safeText($row->product),
                $this->safeText($row->profile),
                $this->safeText($row->parameter_code),
                $this->safeText($row->parameter),
                $this->safeText($row->unit),
                $this->safeText($row->protocol),
                $this->safeText($row->nwp),
                $this->safeText($row->standard),
                $this->safeText($row->dilutions),
                $this->safeText($row->optimal_analysis_time),
                $row->entry_date ?? $row->col_date ?? $row->queued_at,
                'Pendente de análise',
            ];
        }
    }

    public function headings(): array
    {
        return [
            'Código laboratorial',
            'Amostra',
            'Entrada de amostra',
            'Departamento',
            'Cliente',
            'Produto',
            'Perfil',
            'Código do parâmetro',
            'Análise / parâmetro',
            'Unidade',
            'Protocolo',
            'PNT',
            'Norma',
            'Diluições',
            'Tempo óptimo',
            'Data de entrada',
            'Estado',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A:Q')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [];
    }

    private function safeText(mixed $value): mixed
    {
        if (! is_string($value) || ! preg_match('/^[=+\-@]/', $value)) {
            return $value;
        }

        return "'{$value}";
    }
}
