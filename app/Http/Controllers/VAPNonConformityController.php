<?php

namespace App\Http\Controllers;

use App\Exports\NonConformitiesExport;
use App\Exports\NonConformityDetailsExport;
use App\Models\Department;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Models\VAPNonConformityAction;
use App\Support\PdfResponse;
use App\Support\QualityModuleNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class VAPNonConformityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = VAPNonConformity::with(['lab', 'department'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('lab_id')) {
            $query->where('lab_id', $request->lab_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nc_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $nonConformities = $query->paginate(20);

        return Inertia::render('VAPNonConformities/Index', [
            'nonConformities' => $nonConformities,
            'filters' => $request->only(['search', 'status', 'severity', 'category', 'lab_id']),
            'stats' => $this->getStats(),
            'charts' => $this->getCharts(),
            'labs' => VAPLab::all(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('VAPNonConformities/Create', [
            'labs' => VAPLab::all(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
            'defaultNcNumber' => (new VAPNonConformity)->generateNcNumber(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lab_id' => 'nullable|exists:labs,id',
            'department_id' => 'nullable|exists:departments,id',
            'nc_number' => 'required|string|max:255|unique:v_non_conformities,nc_number',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:opened,in_progress,resolved,closed',
            'severity' => 'required|in:low,medium,high,critical',
            'category' => 'required|in:quality,safety,environmental,regulatory,other',
            'sample_id' => 'nullable|string|max:255',
            'test_method' => 'nullable|string|max:255',
            'equipment_id' => 'nullable|string|max:255',
            'batch_number' => 'nullable|string|max:255',
            'reported_by' => 'required|string|max:255',
            'reported_by_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|string|max:255',
            'assigned_to_id' => 'nullable|exists:users,id',
            'reported_at' => 'required|date',
            'due_date' => 'nullable|date',
            'occurrence_area' => 'nullable|string|max:255',
            'root_cause' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'preventive_actions' => 'nullable|string',
            'comments' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachment_files' => 'nullable|array',
            'attachment_files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt|max:10240',
            'actions' => 'nullable|array',
            'actions.*.correction' => 'nullable|string',
            'actions.*.corrective_action' => 'nullable|string',
            'actions.*.due_at' => 'nullable|date',
        ]);

        $nonConformity = DB::transaction(function () use ($validated) {
            $nonConformity = VAPNonConformity::create(Arr::except($validated, ['actions', 'attachment_files']));

            // Save actions if provided
            if (! empty($validated['actions'])) {
                foreach ($validated['actions'] as $actionData) {
                    $actionData['nc_id'] = $nonConformity->id;
                    VAPNonConformityAction::create($actionData);
                }
            }

            return $nonConformity->load(['assignedToUser', 'reportedByUser']);
        });

        $this->storeAttachments($request, $nonConformity);

        app(QualityModuleNotifier::class)->notifyNonConformityCreated($nonConformity);

        return redirect()->route('vap_non_conformities.index')
            ->with('success', 'Non-conformity created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(VAPNonConformity $nonConformity)
    {
        $nonConformity->load(['lab', 'department', 'actions', 'reportedByUser', 'assignedToUser', 'media']);

        return Inertia::render('VAPNonConformities/Show', [
            'nonConformity' => $this->serializeNonConformity($nonConformity),
            'labs' => VAPLab::all(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VAPNonConformity $nonConformity)
    {
        $nonConformity->load(['lab', 'department', 'actions', 'media']);

        return Inertia::render('VAPNonConformities/Edit', [
            'nonConformity' => $this->serializeNonConformity($nonConformity),
            'labs' => VAPLab::all(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VAPNonConformity $nonConformity)
    {
        $validated = $request->validate([
            'lab_id' => 'nullable|exists:labs,id',
            'department_id' => 'nullable|exists:departments,id',
            'nc_number' => 'required|string|max:255|unique:v_non_conformities,nc_number,'.$nonConformity->id,
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:opened,in_progress,resolved,closed',
            'severity' => 'required|in:low,medium,high,critical',
            'category' => 'required|in:quality,safety,environmental,regulatory,other',
            'sample_id' => 'nullable|string|max:255',
            'test_method' => 'nullable|string|max:255',
            'equipment_id' => 'nullable|string|max:255',
            'batch_number' => 'nullable|string|max:255',
            'reported_by' => 'required|string|max:255',
            'reported_by_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|string|max:255',
            'assigned_to_id' => 'nullable|exists:users,id',
            'reported_at' => 'required|date',
            'due_date' => 'nullable|date',
            'occurrence_area' => 'nullable|string|max:255',
            'root_cause' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'preventive_actions' => 'nullable|string',
            'comments' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachment_files' => 'nullable|array',
            'attachment_files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt|max:10240',
            'actions' => 'nullable|array',
            'actions.*.id' => 'nullable|exists:v_non_conformity_actions,id',
            'actions.*.correction' => 'nullable|string',
            'actions.*.corrective_action' => 'nullable|string',
            'actions.*.due_at' => 'nullable|date',
        ]);

        $before = $nonConformity->only(['status', 'severity']);

        DB::transaction(function () use ($nonConformity, $validated) {
            $nonConformity->update(Arr::except($validated, ['actions', 'attachment_files']));

            $existingActionIds = [];

            foreach ($validated['actions'] ?? [] as $actionData) {
                if (isset($actionData['id'])) {
                    $action = $nonConformity->actions()
                        ->whereKey($actionData['id'])
                        ->firstOrFail();
                    $action->update($actionData);
                    $existingActionIds[] = $action->id;

                    continue;
                }

                $actionData['nc_id'] = $nonConformity->id;
                $action = VAPNonConformityAction::create($actionData);
                $existingActionIds[] = $action->id;
            }

            VAPNonConformityAction::query()
                ->where('nc_id', $nonConformity->id)
                ->when($existingActionIds !== [], fn ($query) => $query->whereNotIn('id', $existingActionIds))
                ->delete();
        });

        $this->storeAttachments($request, $nonConformity);

        $nonConformity->refresh()->load(['assignedToUser', 'reportedByUser']);
        app(QualityModuleNotifier::class)->notifyNonConformityUpdated($nonConformity, $before);

        return redirect()->route('vap_non_conformities.show', $nonConformity)
            ->with('success', 'Non-conformity updated successfully.');
    }

    private function storeAttachments(Request $request, VAPNonConformity $nonConformity): void
    {
        if (! $request->hasFile('attachment_files')) {
            return;
        }

        foreach ($request->file('attachment_files', []) as $file) {
            $nonConformity
                ->addMedia($file)
                ->toMediaCollection('attachments');
        }
    }

    private function serializeNonConformity(VAPNonConformity $nonConformity): array
    {
        $payload = $nonConformity->toArray();
        $payload['media_attachments'] = $nonConformity
            ->getMedia('attachments')
            ->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'human_readable_size' => $media->human_readable_size,
                'url' => $media->getUrl(),
            ])
            ->values();

        return $payload;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VAPNonConformity $nonConformity)
    {
        $nonConformity->delete();

        return redirect()->route('vap_non_conformities.index')
            ->with('success', 'Non-conformity deleted successfully.');
    }

    /**
     * Get statistics for vap_non_conformities.
     */
    private function getStats(): array
    {
        return [
            'total' => VAPNonConformity::count(),
            'open' => VAPNonConformity::whereIn('status', ['opened', 'in_progress'])->count(),
            'critical' => VAPNonConformity::where('severity', 'critical')->count(),
            'overdue' => VAPNonConformity::open()
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
            'by_status' => VAPNonConformity::groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status'),
            'by_severity' => VAPNonConformity::groupBy('severity')
                ->selectRaw('severity, count(*) as count')
                ->pluck('count', 'severity'),
            'by_category' => VAPNonConformity::groupBy('category')
                ->selectRaw('category, count(*) as count')
                ->pluck('count', 'category'),
        ];
    }

    private function getCharts(): array
    {
        return [
            'status' => $this->distributionChart('status', [
                'opened' => 'Aberta',
                'in_progress' => 'Em progresso',
                'resolved' => 'Resolvida',
                'closed' => 'Fechada',
            ]),
            'severity' => $this->distributionChart('severity', [
                'low' => 'Baixa',
                'medium' => 'Média',
                'high' => 'Alta',
                'critical' => 'Crítica',
            ]),
            'category' => $this->distributionChart('category', [
                'quality' => 'Qualidade',
                'safety' => 'Segurança',
                'environmental' => 'Ambiental',
                'regulatory' => 'Regulatório',
                'other' => 'Outro',
            ]),
            'trend' => $this->monthlyTrendChart(),
        ];
    }

    private function distributionChart(string $column, array $labels): array
    {
        $distribution = VAPNonConformity::query()
            ->selectRaw("{$column}, count(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', $column);

        $items = collect($labels)->map(fn (string $label, string $key) => [
            'label' => $label,
            'value' => (int) ($distribution[$key] ?? 0),
        ]);

        return [
            'labels' => $items->pluck('label')->values()->all(),
            'series' => $items->pluck('value')->values()->all(),
        ];
    }

    private function monthlyTrendChart(): array
    {
        $months = collect(range(5, 0))->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));
        $firstMonth = $months->first()->copy();
        $lastMonth = $months->last()->copy()->endOfMonth();

        $created = VAPNonConformity::query()
            ->whereBetween('created_at', [$firstMonth, $lastMonth])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, count(*) as aggregate")
            ->groupBy('month_key')
            ->pluck('aggregate', 'month_key');

        $closed = VAPNonConformity::query()
            ->whereBetween('resolved_at', [$firstMonth, $lastMonth])
            ->selectRaw("DATE_FORMAT(resolved_at, '%Y-%m') as month_key, count(*) as aggregate")
            ->groupBy('month_key')
            ->pluck('aggregate', 'month_key');

        return [
            'categories' => $months->map(fn ($month) => $month->translatedFormat('M Y'))->values()->all(),
            'series' => [
                [
                    'name' => 'Registadas',
                    'data' => $months->map(fn ($month) => (int) ($created[$month->format('Y-m')] ?? 0))->values()->all(),
                ],
                [
                    'name' => 'Resolvidas',
                    'data' => $months->map(fn ($month) => (int) ($closed[$month->format('Y-m')] ?? 0))->values()->all(),
                ],
            ],
        ];
    }

    /**
     * Export non-conformities to Excel.
     */
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['status', 'severity', 'category', 'lab_id', 'start_date', 'end_date']);

        $fileName = 'non_conformities_'.now()->format('Y_m_d_His').'.xlsx';

        return Excel::download(new NonConformitiesExport($filters), $fileName);
    }

    /**
     * Export non-conformity details to Excel.
     */
    public function exportDetailsExcel(VAPNonConformity $nonConformity)
    {
        $fileName = 'nc_details_'.$nonConformity->nc_number.'_'.now()->format('Y_m_d_His').'.xlsx';

        return Excel::download(new NonConformityDetailsExport($nonConformity), $fileName);
    }

    /**
     * Export non-conformities to PDF.
     */
    public function exportPdf(Request $request)
    {
        $filters = $request->only(['status', 'severity', 'category', 'lab_id', 'start_date', 'end_date']);

        $nonConformities = $this->getFilteredNonConformities($filters);

        $pdf = PDF::loadView('exports.non-conformities.pdf', [
            'nonConformities' => $nonConformities,
            'filters' => $filters,
            'title' => 'Relatório de Não Conformidades',
            'exportDate' => now()->format('d/m/Y H:i:s'),
        ]);

        $fileName = 'non_conformities_'.now()->format('Y_m_d_His').'.pdf';

        return PdfResponse::download($pdf, $fileName);
    }

    /**
     * Export non-conformity details to PDF.
     */
    public function exportDetailsPdf(VAPNonConformity $nonConformity)
    {
        $nonConformity->load(['lab', 'department', 'actions', 'reportedByUser', 'assignedToUser']);

        $pdf = PDF::loadView('exports.non-conformities.details-pdf', [
            'nonConformity' => $nonConformity,
            'title' => 'Detalhes da Não Conformidade '.$nonConformity->nc_number,
            'exportDate' => now()->format('d/m/Y H:i:s'),
        ]);

        $fileName = 'nc_details_'.$nonConformity->nc_number.'_'.now()->format('Y_m_d_His').'.pdf';

        return PdfResponse::download($pdf, $fileName);
    }

    /**
     * Get filtered non-conformities for export.
     */
    private function getFilteredNonConformities(array $filters)
    {
        $query = VAPNonConformity::with(['lab', 'department'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('reported_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('reported_at', '<=', $filters['end_date']);
        }

        return $query->get();
    }
}
