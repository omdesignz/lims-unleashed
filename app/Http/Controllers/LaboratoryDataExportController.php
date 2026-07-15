<?php

namespace App\Http\Controllers;

use App\Exports\PendingAnalysisWorksheetExport;
use App\Exports\ResultsAuditExport;
use App\Http\Requests\LaboratoryDataExportRequest;
use App\Models\Department;
use App\Support\LaboratoryDataExportQuery;
use App\Support\SpreadsheetDownloadResponder;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaboratoryDataExportController extends Controller
{
    public function __construct(
        private readonly LaboratoryDataExportQuery $dataExportQuery
    ) {}

    public function index(LaboratoryDataExportRequest $request): Response
    {
        $filters = $request->validated();
        $isAudit = $filters['view'] === 'audit';
        $query = $isAudit
            ? $this->dataExportQuery->auditedResults($filters)
            : $this->dataExportQuery->pendingWorksheet($filters);

        $records = $query
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (object $row): array => $isAudit
                ? $this->dataExportQuery->presentAuditRow($row)
                : $this->dataExportQuery->presentPendingRow($row));

        return Inertia::render('Analysis/DataExports', [
            'records' => $records,
            'summary' => $isAudit
                ? $this->dataExportQuery->auditSummary($filters)
                : $this->dataExportQuery->pendingSummary($filters),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Department $department): array => [
                    'value' => $department->id,
                    'label' => $department->name,
                ]),
            'filters' => $filters,
            'canViewPending' => $request->user()->can('view_analysis'),
            'canViewAudit' => $request->user()->can('view_results'),
        ]);
    }

    public function download(LaboratoryDataExportRequest $request): BinaryFileResponse
    {
        $filters = $request->validated();
        $timestamp = now()->format('Ymd-His');

        if ($filters['view'] === 'audit') {
            return SpreadsheetDownloadResponder::download(
                new ResultsAuditExport($this->dataExportQuery->auditedResults($filters), $this->dataExportQuery),
                "auditoria-resultados-{$timestamp}.xlsx"
            );
        }

        return SpreadsheetDownloadResponder::download(
            new PendingAnalysisWorksheetExport($this->dataExportQuery->pendingWorksheet($filters)),
            "folha-analises-pendentes-{$timestamp}.xlsx"
        );
    }
}
