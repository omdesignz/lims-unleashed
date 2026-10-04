<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTask;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceTaskController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function index(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('view_maintenance_tasks'), 403);

        return redirect()->route('vap-maintenance.tasks', [
            'archived' => $request->input('filter') === 'trashed' ? 1 : null,
            'search' => $request->input('search'),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('add_maintenance_tasks'), 403);

        return redirect()->route('vap-maintenance.tasks.create');
    }

    public function show(Request $request, int $maintenancetask): RedirectResponse
    {
        abort_unless($request->user()->can('view_maintenance_tasks'), 403);
        $task = MaintenanceTask::forLaboratory($this->laboratoryAccess->activeLabId())->findOrFail($maintenancetask);

        return redirect()->route('vap-maintenance.tasks.show', $task);
    }

    public function edit(Request $request, int $maintenancetask): RedirectResponse
    {
        abort_unless($request->user()->can('edit_maintenance_tasks'), 403);
        $task = MaintenanceTask::forLaboratory($this->laboratoryAccess->activeLabId())->findOrFail($maintenancetask);

        return redirect()->route('vap-maintenance.tasks.edit', $task);
    }
}
