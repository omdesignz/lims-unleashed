<?php

namespace App\Actions;

use App\Exports\MaintenanceCalendarExport;
use App\Exports\MaintenanceTasksExport;
use App\Models\MaintenanceTask;
use App\Models\VAPLab;
use App\Settings\GeneralSettings;
use App\Support\MaintenanceTaskQuery;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Symfony\Component\HttpFoundation\Response;

class ExportMaintenanceTasks
{
    public function __construct(private readonly MaintenanceTaskQuery $query) {}

    /** @param array<string, mixed> $filters */
    public function execute(int $labId, string $type, string $format, array $filters): Response
    {
        Validator::make(compact('type', 'format'), ['type' => ['required', 'in:tasks,calendar'], 'format' => ['required', 'in:pdf,csv,excel']])->validate();
        if (isset($filters['task_id'])) {
            MaintenanceTask::forLaboratory($labId)->findOrFail($filters['task_id']);
        }
        $filename = 'maintenance_'.$type.'_'.now()->format('Y-m-d_H-i-s');
        if ($format === 'pdf') {
            $data = $type === 'tasks'
                ? ['tasks' => $this->query->exportRows($labId, $filters)]
                : ['calendar' => $this->query->calendar($labId, $filters)];
            $pdf = PDF::loadView('exports.maintenance.'.$type, [
                ...$data, 'filters' => $filters, 'settings' => app(GeneralSettings::class),
                'labName' => VAPLab::findOrFail($labId)->name, 'generated_at' => now(),
            ])->output();
            $response = response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            ]);
        } else {
            $export = $type === 'tasks' ? new MaintenanceTasksExport($labId, $filters) : new MaintenanceCalendarExport($labId, $filters);
            $response = Excel::download($export, $filename.($format === 'csv' ? '.csv' : '.xlsx'), $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX);
        }
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
