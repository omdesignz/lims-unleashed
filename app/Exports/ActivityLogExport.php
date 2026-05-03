<?php

namespace App\Exports;

use App\Models\SystemActivity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ActivityLogExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $activities;

    public function __construct($activities)
    {
        $this->activities = $activities;
    }

    public function collection()
    {
        return $this->activities;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Log Name',
            'Description',
            'Event',
            'Causer',
            'Causer Email',
            'Subject Type',
            'Subject ID',
            'Properties',
            'Batch UUID',
            'Created At',
            'Updated At',
        ];
    }

    public function map($activity): array
    {
        return [
            $activity->id,
            $activity->log_name ?? 'System',
            $activity->description,
            $activity->event ?? 'N/A',
            $activity->causer->name ?? 'System',
            $activity->causer->email ?? 'N/A',
            class_basename($activity->subject_type) ?? 'N/A',
            $activity->subject_id ?? 'N/A',
            json_encode($activity->properties, JSON_PRETTY_PRINT),
            $activity->batch_uuid ?? 'N/A',
            Carbon::parse($activity->created_at)->format('Y-m-d H:i:s'),
            Carbon::parse($activity->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Auto-size columns
            'A' => ['width' => 10],
            'B' => ['width' => 15],
            'C' => ['width' => 50],
            'D' => ['width' => 15],
            'E' => ['width' => 20],
            'F' => ['width' => 25],
            'G' => ['width' => 20],
            'H' => ['width' => 15],
            'I' => ['width' => 40],
            'J' => ['width' => 36],
            'K' => ['width' => 20],
            'L' => ['width' => 20],
        ];
    }

    public function title(): string
    {
        return 'Activity Logs';
    }
}