<?php

namespace App\Exports;

use App\Models\MaintenanceTask;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenanceTasksExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = MaintenanceTask::with(['category', 'equipment', 'supplier']);

        if (isset($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (isset($this->filters['status'])) {
            if ($this->filters['status'] === 'overdue') {
                $query->where('due_date', '<', now())
                    ->where('is_executed', false);
            } elseif ($this->filters['status'] === 'executed') {
                $query->where('is_executed', true);
            }
        }

        if (isset($this->filters['date_from'])) {
            $query->where('due_date', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->where('due_date', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('due_date')->get();
    }

    public function headings(): array
    {
        return [
            'Task Number',
            'Task Name',
            'Category',
            'Equipment',
            'Serial Number',
            'Due Date',
            'Status',
            'Cost (AOA)',
            'Supplier',
            'Certificate Number',
            'Created At',
            'Description',
        ];
    }

    public function map($task): array
    {
        return [
            $task->maintenance_task_no,
            $task->name,
            $task->category->name,
            $task->equipment->name,
            $task->equipment->serial_number,
            $task?->due_date?->format('d/m/Y') ?? 'N/A',
            $task->is_executed ? 'Executed' : ($task->due_date < now() ? 'Overdue' : 'Pending'),
            number_format($task->cost, 2, ',', '.'),
            $task->supplier ? $task->supplier->name : 'Internal',
            $task->calibration_certificate_no,
            $task->created_at->format('d/m/Y H:i'),
            strip_tags($task->description),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE8F4FF']
                ]
            ],
        ];
    }
}