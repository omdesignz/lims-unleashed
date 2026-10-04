<?php

namespace App\Exports;

use App\Support\MaintenanceTaskQuery;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenanceCalendarExport extends StringValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    protected $calendar;

    public function __construct(private readonly int $labId, $filters = [])
    {
        $this->filters = $filters;
        $this->calendar = $this->buildCalendar();
    }

    public function collection()
    {
        return collect($this->calendar);
    }

    private function buildCalendar(): array
    {
        $rows = [];
        foreach (app(MaintenanceTaskQuery::class)->calendar($this->labId, $this->filters) as $day) {
            if ($day['tasks']->isEmpty()) {
                $rows[] = ['date' => $day['date'], 'day' => $day['day'], 'task_number' => '', 'task_name' => 'Sem tarefas agendadas', 'category' => '', 'equipment' => '', 'status' => ''];

                continue;
            }
            foreach ($day['tasks'] as $task) {
                $rows[] = [
                    'date' => $day['date'], 'day' => $day['day'],
                    'task_number' => $task->maintenance_task_no, 'task_name' => $task->name,
                    'category' => $task->category?->name ?? 'Categoria arquivada',
                    'equipment' => $task->equipment?->name ?? 'Equipamento indisponível',
                    'status' => $task->is_executed ? 'Executada' : ($task->due_date->lt(today()) ? 'Em atraso' : 'Agendada'),
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Data',
            'Dia',
            'Número da tarefa',
            'Nome da tarefa',
            'Categoria',
            'Equipamento',
            'Estado',
        ];
    }

    public function map($row): array
    {
        $values = [
            $row['date'],
            $row['day'],
            $row['task_number'],
            $row['task_name'],
            $row['category'],
            $row['equipment'],
            $row['status'],
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
                    'color' => ['argb' => 'FFF0F7FF'],
                ],
            ],
        ];
    }
}
