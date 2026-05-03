<?php

namespace App\Exports;

use App\Models\MaintenanceTask;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenanceCalendarExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;
    protected $calendar;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
        $this->calendar = $this->buildCalendar();
    }

    public function collection()
    {
        return collect($this->calendar);
    }

    private function buildCalendar()
    {
        $startDate = $this->filters['date_from'] ?? now()->startOfMonth();
        $endDate = $this->filters['date_to'] ?? now()->addMonths(3)->endOfMonth();

        $tasks = MaintenanceTask::with(['category', 'equipment'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->when(isset($this->filters['category_id']), function ($query) {
                $query->where('category_id', $this->filters['category_id']);
            })
            ->orderBy('due_date')
            ->get();

        $calendar = [];
        $currentDate = Carbon::parse($startDate);
        
        while ($currentDate <= Carbon::parse($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayTasks = $tasks->filter(function ($task) use ($currentDate) {
                return Carbon::parse($task->due_date)->format('Y-m-d') === $currentDate->format('Y-m-d');
            });

            if ($dayTasks->count() > 0) {
                foreach ($dayTasks as $task) {
                    $calendar[] = [
                        'date' => $currentDate->format('d/m/Y'),
                        'day' => $currentDate->format('l'),
                        'task_number' => $task->maintenance_task_no,
                        'task_name' => $task->name,
                        'category' => $task->category->name,
                        'equipment' => $task->equipment->name,
                        'status' => $task->is_executed ? 'Executed' : 
                                   ($task->due_date < now() ? 'Overdue' : 'Scheduled'),
                        'time' => $task->due_date->format('H:i'),
                    ];
                }
            } else {
                $calendar[] = [
                    'date' => $currentDate->format('d/m/Y'),
                    'day' => $currentDate->format('l'),
                    'task_number' => '',
                    'task_name' => 'No tasks scheduled',
                    'category' => '',
                    'equipment' => '',
                    'status' => '',
                    'time' => '',
                ];
            }

            $currentDate->addDay();
        }

        return $calendar;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Day',
            'Task Number',
            'Task Name',
            'Category',
            'Equipment',
            'Status',
            'Time',
        ];
    }

    public function map($row): array
    {
        return [
            $row['date'],
            $row['day'],
            $row['task_number'],
            $row['task_name'],
            $row['category'],
            $row['equipment'],
            $row['status'],
            $row['time'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFF0F7FF']
                ]
            ],
        ];
    }
}