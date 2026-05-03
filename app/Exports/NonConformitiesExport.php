<?php

namespace App\Exports;

use App\Models\VAPNonConformity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class NonConformitiesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = VAPNonConformity::with(['lab', 'department'])
            ->orderBy('created_at', 'desc');

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['severity'])) {
            $query->where('severity', $this->filters['severity']);
        }

        if (!empty($this->filters['category'])) {
            $query->where('category', $this->filters['category']);
        }

        if (!empty($this->filters['lab_id'])) {
            $query->where('lab_id', $this->filters['lab_id']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('reported_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('reported_at', '<=', $this->filters['end_date']);
        }

        return $query->get();
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
            'Ações Corretivas',
            'Ações Preventivas',
            'Comentários',
            'Dias Abertos'
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
            $nonConformity->due_date?->format('d/m/Y'),
            $nonConformity->assigned_to,
            $nonConformity->sample_id,
            $nonConformity->test_method,
            $nonConformity->equipment_id,
            $nonConformity->batch_number,
            $nonConformity->root_cause,
            $nonConformity->corrective_actions,
            $nonConformity->preventive_actions,
            $nonConformity->comments,
            $nonConformity->daysOpen()
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Auto size columns
        foreach(range('A', 'U') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add border to all cells
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();
        
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ],
                'inside' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD']
                ]
            ]
        ]);

        // Wrap text for description and other long fields
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('Q2:S' . $lastRow)->getAlignment()->setWrapText(true);

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
            'closed' => 'Fechado'
        ];
        
        return $statuses[$status] ?? $status;
    }

    private function getSeverityText($severity): string
    {
        $severities = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'critical' => 'Crítica'
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
            'other' => 'Outro'
        ];
        
        return $categories[$category] ?? $category;
    }
}