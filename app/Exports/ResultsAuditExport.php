<?php

namespace App\Exports;

use App\Support\LaboratoryDataExportQuery;
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

class ResultsAuditExport implements FromGenerator, ShouldAutoSize, WithHeadings, WithStrictNullComparison, WithStyles
{
    public function __construct(
        private readonly Builder $query,
        private readonly LaboratoryDataExportQuery $dataExportQuery
    ) {}

    public function generator(): Generator
    {
        foreach ((clone $this->query)->cursor() as $row) {
            $stage = $this->dataExportQuery->auditStage($row);

            yield [
                $row->result_id,
                $this->safeText($row->laboratory_code),
                $this->safeText($row->sample_code),
                $this->safeText($row->sample_entry_code),
                $this->safeText($row->department),
                $this->safeText($row->customer),
                $this->safeText($row->product),
                $this->safeText($row->parameter),
                $this->safeText($row->unit),
                $this->safeText($row->protocol),
                $this->safeText($row->nwp),
                $this->safeText($row->standard),
                $this->safeText($row->inserted_value),
                $this->safeText($row->uncertainty_value),
                $this->safeText($row->inserted_by),
                $row->inserted_date,
                $this->safeText($row->verified_value),
                $this->safeText($row->verified_by),
                $row->verified_date,
                $this->safeText($row->approved_value),
                $this->safeText($row->approved_by),
                $row->approved_date,
                $this->dataExportQuery->auditStageLabel($stage),
                $row->updated_at,
            ];
        }
    }

    public function headings(): array
    {
        return [
            'ID do resultado',
            'Código laboratorial',
            'Amostra',
            'Entrada de amostra',
            'Departamento',
            'Cliente',
            'Produto',
            'Análise / parâmetro',
            'Unidade',
            'Protocolo',
            'PNT',
            'Norma',
            'Valor inserido',
            'Incerteza',
            'Inserido por',
            'Data de inserção',
            'Valor verificado',
            'Verificado por',
            'Data de verificação',
            'Valor aprovado',
            'Aprovado por',
            'Data de aprovação',
            'Estado actual',
            'Última alteração',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getStyle('A1:X1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4B68']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A:X')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

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
