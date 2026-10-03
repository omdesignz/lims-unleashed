<?php

namespace App\Http\Controllers;

use App\Models\VAPSampleEntry;
use App\Services\InventoryCatalogueRead;
use App\Services\LabNetworkAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaboratoryWorkbenchController extends Controller
{
    public function __invoke(Request $request, LabNetworkAccess $access, InventoryCatalogueRead $catalogueRead): Response
    {
        $context = $access->context($request->user(), $request->session()->get('active_lab_id'));
        $lab = $context['active_lab'];
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:POR_INICIAR,EN_PROGRESO,EN_PAUSA,COMPLETADO,CANCELADO']]);
        $samples = VAPSampleEntry::query()->where('lab_id', $lab['id'] ?? -1);
        $counts = (clone $samples)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('LaboratoryWorkbench', [
            'lab' => $lab,
            'filters' => $filters,
            'metrics' => [
                'waiting' => (int) ($counts['POR_INICIAR'] ?? 0),
                'in_progress' => (int) ($counts['EN_PROGRESO'] ?? 0),
                'on_hold' => (int) ($counts['EN_PAUSA'] ?? 0),
                'completed' => (int) ($counts['COMPLETADO'] ?? 0),
            ],
            'samples' => $samples->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $pattern = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $query) => $query->whereLike('name', $pattern)->orWhereLike('code', $pattern));
            })->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
                ->orderByRaw("CASE status WHEN 'EN_PAUSA' THEN 0 WHEN 'POR_INICIAR' THEN 1 WHEN 'EN_PROGRESO' THEN 2 ELSE 3 END")
                ->orderBy('received_at')->orderBy('id')->paginate(20, ['id', 'code', 'name', 'status', 'sample_type', 'received_at', 'retention_due_at'])->withQueryString(),
            'stockAlerts' => $request->user()->can('view_inventory') ? $catalogueRead->stock($lab['id'] ?? -1, $request->user())
                ->whereHas('warehouse', fn (Builder $warehouses): Builder => $warehouses->where('lab_id', $lab['id'] ?? -1))
                ->lowStock()->count() : 0,
        ]);
    }
}
