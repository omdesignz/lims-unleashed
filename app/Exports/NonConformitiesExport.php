<?php

namespace App\Exports;

use App\Support\NonConformityQuery;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NonConformitiesExport extends StringValueBinder implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly int $labId, private readonly array $filters = []) {}

    public function collection(): Collection
    {
        return app(NonConformityQuery::class)->exportRows($this->labId, $this->filters);
    }

    public function headings(): array
    {
        return [
            'Número NC',
            'Título',
            'Descrição',
            'Status',
            'Severidade',
            'Categoria',
            'Laboratório',
            'Departamento',
            'Reportado por',
            'Data do Relato',
            'Data de Vencimento',
            'Atribuído para',
            'Amostra ID',
            'Método de Teste',
            'Equipamento ID',
            'Número do Lote',
            'Causa Raiz',
            'Acções Corretivas',
            'Acções Preventivas',
            'Comentários',
            'Arquivada em',
        ];
    }

    public function map($nonConformity): array
    {
        return [
            $nonConformity->nc_number,
            $nonConformity->title,
            $nonConformity->description,
            $this->getStatusText($nonConformity->status),
            $this->getSeverityText($nonConformity->severity),
            $this->getCategoryText($nonConformity->category),
            $nonConformity->lab?->name,
            $nonConformity->department?->name,
            $nonConformity->reported_by,
            $nonConformity->reported_at?->format('d/m/Y H:i'),
            $nonConformity->due_date?->format('d/m/Y H:i'),
            $nonConformity->assigned_to,
            $nonConformity->sample_id,
            $nonConformity->test_method,
            $nonConformity->equipment_id,
            $nonConformity->batch_number,
            $nonConformity->root_cause,
            $nonConformity->corrective_actions,
            $nonConformity->preventive_actions,
            $nonConformity->comments,
            $nonConformity->deleted_at?->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Auto size columns
        foreach (range('A', 'U') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add border to all cells
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'inside' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
        ]);

        // Wrap text for description and other long fields
        $sheet->getStyle('C2:C'.$lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('Q2:S'.$lastRow)->getAlignment()->setWrapText(true);

        // Set column widths for better readability
        $sheet->getColumnDimension('A')->setWidth(15); // NC Number
        $sheet->getColumnDimension('B')->setWidth(30); // Title
        $sheet->getColumnDimension('C')->setWidth(50); // Description
        $sheet->getColumnDimension('D')->setWidth(12); // Status
        $sheet->getColumnDimension('E')->setWidth(12); // Severity
        $sheet->getColumnDimension('F')->setWidth(15); // Category
        $sheet->getColumnDimension('G')->setWidth(20); // Lab
        $sheet->getColumnDimension('H')->setWidth(20); // Department
        $sheet->getColumnDimension('Q')->setWidth(40); // Root Cause
        $sheet->getColumnDimension('R')->setWidth(40); // Corrective Actions
        $sheet->getColumnDimension('S')->setWidth(40); // Preventive Actions

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Reported At
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY, // Due Date
        ];
    }

    private function getStatusText($status): string
    {
        $statuses = [
            'opened' => 'Aberto',
            'in_progress' => 'Em Andamento',
            'resolved' => 'Resolvido',
            'closed' => 'Fechado',
        ];

        return $statuses[$status] ?? $status;
    }

    private function getSeverityText($severity): string
    {
        $severities = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'critical' => 'Crítica',
        ];

        return $severities[$severity] ?? $severity;
    }

    private function getCategoryText($category): string
    {
        $categories = [
            'quality' => 'Qualidade',
            'safety' => 'Segurança',
            'environmental' => 'Ambiental',
            'regulatory' => 'Regulatório',
            'other' => 'Outro',
        ];

        return $categories[$category] ?? $category;
    }
}
