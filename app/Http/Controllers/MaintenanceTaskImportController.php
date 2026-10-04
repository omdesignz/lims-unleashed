<?php

namespace App\Http\Controllers;

use App\Actions\ImportMaintenanceTasks;
use App\Http\Requests\ImportMaintenanceTasksRequest;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\MaintenanceCategory;
use App\Services\SampleLaboratoryAccess;
use App\Support\MaintenanceTaskCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceTaskImportController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function form(Request $request, MaintenanceTaskCsv $csv): Response
    {
        abort_if($request->session()->has('impersonate'), 403);
        $labId = $this->laboratoryAccess->activeLabId();

        return Inertia::render('VAPMaintenance/Tasks/Import', [
            'breadcrumbs' => [
                ['title' => 'Tarefas de manutenção', 'url' => route('vap-maintenance.tasks'), 'current' => false],
                ['title' => 'Importar tarefas', 'current' => true],
            ],
            'requestKey' => (string) Str::uuid(),
            'maxRows' => MaintenanceTaskCsv::MAX_ROWS,
            'columns' => $csv->columns(),
            'equipment' => InventoryItem::forLaboratory($labId)->equipment()->orderBy('name')->get(['id', 'name', 'internal_code']),
            'categories' => MaintenanceCategory::availableToLaboratory($labId)->orderBy('name')->get(['id', 'name']),
            'suppliers' => InventoryItemSupplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function upload(ImportMaintenanceTasksRequest $request, ImportMaintenanceTasks $import): RedirectResponse
    {
        $receipt = $import->execute(
            $this->laboratoryAccess->activeLabId(), $request->user()->id,
            $request->file('file')->getRealPath(), $request->validated('request_key'),
        );

        return redirect()->route('vap-maintenance.tasks')->with('toast', [
            'title' => 'Importação confirmada',
            'message' => $receipt->row_count.' tarefa(s) importada(s). Repetir a mesma operação não cria duplicados.',
        ]);
    }

    public function template(MaintenanceTaskCsv $csv): StreamedResponse
    {
        $this->laboratoryAccess->activeLabId();

        return response()->streamDownload(function () use ($csv): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $csv->columns(), ';', '"', '');
            fclose($stream);
        }, 'modelo-tarefas-manutencao.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
