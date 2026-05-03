<?php

namespace App\Exports;

use App\Models\VAPNonConformity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class NonConformityDetailsExport implements WithMultipleSheets
{
    protected $nonConformity;

    public function __construct(VAPNonConformity $nonConformity)
    {
        $this->nonConformity = $nonConformity;
    }

    public function sheets(): array
    {
        $sheets = [
            new NonConformityDetailsSheet($this->nonConformity),
            new NonConformityActionsSheet($this->nonConformity)
        ];

        return $sheets;
    }
}

class NonConformityDetailsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $nonConformity;

    public function __construct(VAPNonConformity $nonConformity)
    {
        $this->nonConformity = $nonConformity;
    }

    public function collection()
    {
        return collect([$this->nonConformity]);
    }

    public function headings(): array
    {
        return [
            'DETALHES DA NÃO CONFORMIDADE',
            ''
        ];
    }

    public function map($nonConformity): array
    {
        return [
            ['Campo', 'Valor'],
            ['Número NC', $nonConformity->nc_number],
            ['Título', $nonConformity->title],
            ['Descrição', $nonConformity->description],
            ['Status', $this->getStatusText($nonConformity->status)],
            ['Severidade', $this->getSeverityText($nonConformity->severity)],
            ['Categoria', $this->getCategoryText($nonConformity->category)],
            ['Laboratório', $nonConformity->lab?->name],
            ['Departamento', $nonConformity->department?->name],
            ['Reportado por', $nonConformity->reported_by],
            ['Data do Relato', $nonConformity->reported_at?->format('d/m/Y H:i')],
            ['Data de Vencimento', $nonConformity->due_date?->format('d/m/Y')],
            ['Atribuído para', $nonConformity->assigned_to],
            ['Amostra ID', $nonConformity->sample_id],
            ['Método de Teste', $nonConformity->test_method],
            ['Equipamento ID', $nonConformity->equipment_id],
            ['Número do Lote', $nonConformity->batch_number],
            ['Área de Ocorrência', $nonConformity->occurrence_area],
            ['Causa Raiz', $nonConformity->root_cause],
            ['Ações Corretivas', $nonConformity->corrective_actions],
            ['Ações Preventivas', $nonConformity->preventive_actions],
            ['Comentários', $nonConformity->comments],
            ['Dias Abertos', $nonConformity->daysOpen()],
            ['Criado em', $nonConformity->created_at?->format('d/m/Y H:i')],
            ['Atualizado em', $nonConformity->updated_at?->format('d/m/Y H:i')]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Title style
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1E3A8A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Header style for field names
        $sheet->getStyle('A2:B2')->applyFromArray([
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

        // Data rows style
        $lastRow = count($this->map($this->nonConformity)) + 1;
        
        for ($i = 3; $i <= $lastRow; $i++) {
            // Field name column
            $sheet->getStyle("A{$i}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F4FF']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);

            // Field value column
            $sheet->getStyle("B{$i}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);
        }

        // Wrap text for long fields
        $sheet->getStyle('B3:B' . $lastRow)->getAlignment()->setWrapText(true);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(50);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT
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

class NonConformityActionsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $nonConformity;

    public function __construct(VAPNonConformity $nonConformity)
    {
        $this->nonConformity = $nonConformity;
    }

    public function collection()
    {
        return $this->nonConformity->actions;
    }

    public function headings(): array
    {
        return [
            'AÇÕES CORRETIVAS DA NC: ' . $this->nonConformity->nc_number
        ];
    }

    public function map($action): array
    {
        return [
            ['Campo', 'Valor'],
            ['Correção', $action->correction],
            ['Ação Corretiva', $action->corrective_action],
            ['Data de Vencimento', $action->due_at?->format('d/m/Y')],
            ['Data de Aprovação', $action->approved_at?->format('d/m/Y H:i')],
            ['Efetiva?', $action->was_effective ? 'Sim' : 'Não'],
            ['Evidências', $action->evidence],
            ['Criado em', $action->created_at?->format('d/m/Y H:i')],
            ['Atualizado em', $action->updated_at?->format('d/m/Y H:i')],
            ['', ''] // Empty row separator
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Title style
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1E3A8A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        $row = 2;
        foreach ($this->nonConformity->actions as $index => $action) {
            $startRow = $row;
            
            // Action header
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4B5563']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
            $sheet->setCellValue("A{$row}", "AÇÃO #" . ($index + 1));
            
            $row++;
            
            // Field names header
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
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
            
            $row++;
            
            // Data rows
            $dataRows = $this->map($action);
            foreach ($dataRows as $dataRow) {
                if (!empty($dataRow[0]) && !empty($dataRow[1])) {
                    $sheet->setCellValue("A{$row}", $dataRow[0]);
                    $sheet->setCellValue("B{$row}", $dataRow[1]);
                    
                    // Style field name column
                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F0F4FF']
                        ]
                    ]);
                    
                    $row++;
                }
            }
            
            // Add borders to action block
            $endRow = $row - 1;
            $sheet->getStyle("A{$startRow}:B{$endRow}")->applyFromArray([
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '1E3A8A']
                    ],
                    'inside' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);
            
            // Add empty row between actions
            $row++;
        }

        // Wrap text for long fields
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('B3:B' . $lastRow)->getAlignment()->setWrapText(true);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(50);

        return [];
    }
}