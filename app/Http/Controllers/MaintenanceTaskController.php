<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceTaskPeriodicityUnit;
use Illuminate\Http\Request;
use App\Http\Requests\MaintenanceTaskRequest;
use App\Http\Resources\MaintenanceTaskPeriodicityUnitResource;
use App\Http\Resources\MaintenanceTaskResource;
use App\Models\MaintenanceTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MaintenanceTaskController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(!auth()->user()->can('view_maintenance_tasks'), 403, '');

        return Inertia::render('MaintenanceTasks/Index', [
            'record' => MaintenanceTaskResource::collection(
                MaintenanceTask::query()
                    ->with('equipment', 'category', 'supplier')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%")
                        ->orWhere('maintenance_task_no', 'like', "%{$search}%");
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'periodicityUnits' => MaintenanceTaskPeriodicityUnitResource::collection(MaintenanceTaskPeriodicityUnit::cases()),
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.maintenance_tasks.maintenance_task_no'),
                    'value' => 'maintenance_task_no'
                ],
                [
                    'name' => trans('gestlab.general.labels.maintenance_tasks.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.maintenance_tasks.description'),
                    'value' => 'description'
                ],
            ],
            'model' => MaintenanceTask::MENU_NAME,
            'abilities' => method_exists(MaintenanceTask::class, 'getAbilities') ? collect(MaintenanceTask::ABILITIES)->map(function ($item) {
                return $item . '_' . MaintenanceTask::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . MaintenanceTask::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add_maintenance_tasks'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('MaintenanceTasks/Create', [
            'periodicityUnits' => MaintenanceTaskPeriodicityUnitResource::collection(MaintenanceTaskPeriodicityUnit::cases()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(MaintenanceTaskRequest $request)
    {
        // dd($request->all());
        abort_if(!auth()->user()->can('add_maintenance_tasks'), 403, '');

        // Persiste data to DB
        MaintenanceTask::create($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show($id)
    {
        return Inertia::render('MaintenanceTasks/Show', [
            'record' => MaintenanceTaskResource::make(
                MaintenanceTask::query()
                                 ->with('equipment', 'category', 'supplier')
                                 ->find($id)
            )
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if(!auth()->user()->can('edit_maintenance_tasks'), 403, '');

        // Find the record
        $record = MaintenanceTask::with('supplier', 'category', 'equipment')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('MaintenanceTasks/Edit', [
            'record' => MaintenanceTaskResource::make($record),
            'periodicityUnits' => MaintenanceTaskPeriodicityUnitResource::collection(MaintenanceTaskPeriodicityUnit::cases()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(MaintenanceTaskRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_maintenance_tasks'), 403, '');

        // Find the record
        $record = MaintenanceTask::findOrFail($id);

        $record->update($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy()
    {
        abort_if(!auth()->user()->can('delete_maintenance_tasks'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (MaintenanceTask::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    /**
     * restore the specified resource from storage.
     *
     */
    public function restore()
    {
        abort_if(!auth()->user()->can('restore_maintenance_tasks'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (MaintenanceTask::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
        ]);
    }
}
