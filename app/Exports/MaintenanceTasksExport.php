<?php

namespace App\Exports;

use App\Support\MaintenanceTaskQuery;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenanceTasksExport extends StringValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(private readonly int $labId, $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        return app(MaintenanceTaskQuery::class)->exportRows($this->labId, $this->filters);
    }

    public function headings(): array
    {
        return [
            'Número da tarefa',
            'Nome da tarefa',
            'Categoria',
            'Equipamento',
            'Número de série',
            'Data de vencimento',
            'Estado',
            'Custo (AOA)',
            'Fornecedor',
            'Número do certificado',
            'Criado em',
            'Descrição',
        ];
    }

    public function map($task): array
    {
        $values = [
            $task->maintenance_task_no,
            $task->name,
            $task->category?->name ?? 'Categoria arquivada',
            $task->equipment?->name ?? 'Equipamento indisponível',
            $task->equipment?->serial_number,
            $task?->due_date?->format('d/m/Y') ?? 'N/D',
            $task->is_executed ? 'Executada' : ($task->due_date?->lt(today()) ? 'Em atraso' : 'Pendente'),
            number_format($task->cost, 2, ',', '.'),
            $task->supplier ? $task->supplier->name : 'Interno',
            $task->calibration_certificate_no,
            $task->created_at->format('d/m/Y H:i'),
            strip_tags($task->description ?? ''),
        ];

        return array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+\-@]/', $value) ? "'".$value : $value, $values);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE8F4FF'],
                ],
            ],
        ];
    }
}
