<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\MaintenanceCategoryRequest;
use App\Http\Resources\MaintenanceCategoryResource;
use Illuminate\Support\Facades\DB;
use App\Models\MaintenanceCategory;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class MaintenanceCategoryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_maintenance_categories'), 403, '');

        $records = QueryBuilder::for(MaintenanceCategory::class)
                                ->allowedFilters(MaintenanceCategory::getAllowedFilters())
                                ->allowedSorts(MaintenanceCategory::getAllowedSorts())
                                ->paginate(request()->query('per_page', 10)); 


        return Inertia::render('MaintenanceCategories/Index', [
            'record' => MaintenanceCategoryResource::collection($records),
            'initialFilters' => request()->query('filter', ['name' => '', 'description' => '', 'code' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => true,  
            'trashedFilter' => true,
            'trashedOptions' => MaintenanceCategory::getTrashedOptions(),
            'fields' => MaintenanceCategory::getColumns(),
            'model' => MaintenanceCategory::MENU_NAME,
            'abilities' => method_exists(MaintenanceCategory::class, 'getAbilities') ? collect(MaintenanceCategory::ABILITIES)->map(function($item){
                return $item . '_' . MaintenanceCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . MaintenanceCategory::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed', 'date', 'orderBy'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add_maintenance_categories'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('MaintenanceCategories/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(MaintenanceCategoryRequest $request)
    {
        abort_if(!auth()->user()->can('add_maintenance_categories'), 403, '');

        // Persiste data to DB
        MaintenanceCategory::create($request->validated());

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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if(!auth()->user()->can('edit_maintenance_categories'), 403, '');

        // Find the record
        $record = MaintenanceCategory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('MaintenanceCategories/Edit', [
            'record' => MaintenanceCategoryResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(MaintenanceCategoryRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_maintenance_categories'), 403, '');

        // Find the record
        $record = MaintenanceCategory::findOrFail($id);

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
        abort_if(!auth()->user()->can('delete_maintenance_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (MaintenanceCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(!auth()->user()->can('restore_maintenance_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (MaintenanceCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
        ]);
    }


    public function getMaintenanceCategory()
    {
        $data = [];

        if (request()->filled('q')) {
            $search = request('q');

            $data = MaintenanceCategory::query()
                ->select(['id', 'name', 'code', 'description'])
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })
                ->limit(25)
                ->get();
        }

        return response()->json($data);
    }
}
