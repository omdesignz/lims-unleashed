<?php

namespace App\Http\Controllers;

use App\Exports\NonConformitiesExport;
use App\Exports\NonConformityDetailsExport;
use App\Models\VAPNonConformity;
use App\Models\VAPNonConformityAction;
use App\Models\VAPLab;
use App\Models\Department;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('lab_id')) {
            $query->where('lab_id', $request->lab_id);
        }

        if ($request->has('search')) {
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
            'labs' => VAPLab::all(['id', 'name']),
            'departments' => Department::all(['id', 'name'])
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
            'defaultNcNumber' => (new VAPNonConformity())->generateNcNumber()
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
            'actions' => 'nullable|array',
            'actions.*.correction' => 'nullable|string',
            'actions.*.corrective_action' => 'nullable|string',
            'actions.*.due_at' => 'nullable|date'
        ]);

        DB::transaction(function () use ($validated) {
            $nonConformity = VAPNonConformity::create($validated);

            // Save actions if provided
            if (!empty($validated['actions'])) {
                foreach ($validated['actions'] as $actionData) {
                    $actionData['nc_id'] = $nonConformity->id;
                    VAPNonConformityAction::create($actionData);
                }
            }
        });

        return redirect()->route('vap_non_conformities.index')
            ->with('success', 'Non-conformity created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(VAPNonConformity $nonConformity)
    {
        $nonConformity->load(['lab', 'department', 'actions', 'reportedByUser', 'assignedToUser']);

        return Inertia::render('VAPNonConformities/Show', [
            'nonConformity' => $nonConformity,
            'labs' => VAPLab::all(['id', 'name']),
            'departments' => Department::all(['id', 'name'])
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VAPNonConformity $nonConformity)
    {
        $nonConformity->load(['lab', 'department', 'actions']);

        return Inertia::render('VAPNonConformities/Edit', [
            'nonConformity' => $nonConformity,
            'labs' => VAPLab::all(['id', 'name']),
            'departments' => Department::all(['id', 'name'])
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
            'nc_number' => 'required|string|max:255|unique:v_non_conformities,nc_number,' . $nonConformity->id,
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
            'actions' => 'nullable|array',
            'actions.*.id' => 'nullable|exists:v_non_conformity_actions,id',
            'actions.*.correction' => 'nullable|string',
            'actions.*.corrective_action' => 'nullable|string',
            'actions.*.due_at' => 'nullable|date'
        ]);

        DB::transaction(function () use ($nonConformity, $validated) {
            $nonConformity->update($validated);

            // Update or create actions
            if (!empty($validated['actions'])) {
                $existingActionIds = [];
                
                foreach ($validated['actions'] as $actionData) {
                    if (isset($actionData['id'])) {
                        // Update existing action
                        $action = VAPNonConformityAction::find($actionData['id']);
                        if ($action) {
                            $action->update($actionData);
                            $existingActionIds[] = $action->id;
                        }
                    } else {
                        // Create new action
                        $actionData['nc_id'] = $nonConformity->id;
                        $action = VAPNonConformityAction::create($actionData);
                        $existingActionIds[] = $action->id;
                    }
                }

                // Delete actions not in the request
                VAPNonConformityAction::where('nc_id', $nonConformity->id)
                    ->whereNotIn('id', $existingActionIds)
                    ->delete();
            }
        });

        return redirect()->route('vap_non_conformities.show', $nonConformity)
            ->with('success', 'Non-conformity updated successfully.');
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
                ->pluck('count', 'category')
        ];
    }

    /**
     * Export non-conformities to Excel.
     */
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['status', 'severity', 'category', 'lab_id', 'start_date', 'end_date']);
        
        $fileName = 'non_conformities_' . now()->format('Y_m_d_His') . '.xlsx';
        
        return Excel::download(new NonConformitiesExport($filters), $fileName);
    }

    /**
     * Export non-conformity details to Excel.
     */
    public function exportDetailsExcel(VAPNonConformity $nonConformity)
    {
        $fileName = 'nc_details_' . $nonConformity->nc_number . '_' . now()->format('Y_m_d_His') . '.xlsx';
        
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
            'exportDate' => now()->format('d/m/Y H:i:s')
        ]);
        
        $fileName = 'non_conformities_' . now()->format('Y_m_d_His') . '.pdf';
        
        return $pdf->download($fileName);
    }

    /**
     * Export non-conformity details to PDF.
     */
    public function exportDetailsPdf(VAPNonConformity $nonConformity)
    {
        $nonConformity->load(['lab', 'department', 'actions', 'reportedByUser', 'assignedToUser']);
        
        $pdf = PDF::loadView('exports.non-conformities.details-pdf', [
            'nonConformity' => $nonConformity,
            'title' => 'Detalhes da Não Conformidade ' . $nonConformity->nc_number,
            'exportDate' => now()->format('d/m/Y H:i:s')
        ]);
        
        $fileName = 'nc_details_' . $nonConformity->nc_number . '_' . now()->format('Y_m_d_His') . '.pdf';
        
        return $pdf->download($fileName);
    }

    /**
     * Get filtered non-conformities for export.
     */
    private function getFilteredNonConformities(array $filters)
    {
        $query = VAPNonConformity::with(['lab', 'department'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('reported_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('reported_at', '<=', $filters['end_date']);
        }

        return $query->get();
    }
}