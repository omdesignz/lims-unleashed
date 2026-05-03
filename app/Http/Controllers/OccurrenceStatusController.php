<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OccurrenceStatusRequest;
use App\Http\Resources\OccurrenceStatusResource;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use App\Models\OccurrenceStatus;
use Carbon\Carbon;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class OccurrenceStatusController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_occurrence_statuses'), 403, '');

        $records = QueryBuilder::for(OccurrenceStatus::class)
                                ->allowedFilters(OccurrenceStatus::getAllowedFilters())
                                ->allowedSorts(OccurrenceStatus::getAllowedSorts())
                                ->paginate(request()->query('per_page', 10));


        return Inertia::render('OccurrenceStatuses/Index', [
            'record' => OccurrenceStatusResource::collection($records),
            'initialFilters' => request()->query('filter', ['name' => '', 'description' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => true,  
            'trashedFilter' => true,
            'trashedOptions' => OccurrenceStatus::getTrashedOptions(),
            'fields' => OccurrenceStatus::getColumns(),
            'model' => OccurrenceStatus::MENU_NAME,
            'abilities' => method_exists(OccurrenceStatus::class, 'getAbilities') ? collect(OccurrenceStatus::ABILITIES)->map(function($item){
                return $item . '_' . OccurrenceStatus::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . OccurrenceStatus::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_occurrence_statuses'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('OccurrenceStatuses/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(OccurrenceStatusRequest $request)
    {
        abort_if( !auth()->user()->can('add_occurrence_statuses'), 403, '');

        // Persiste data to DB
        OccurrenceStatus::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_occurrence_statuses'), 403, '');

        // Find the record
        $record = OccurrenceStatus::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('OccurrenceStatuses/Edit', [
            'record' => OccurrenceStatusResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(OccurrenceStatusRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_occurrence_statuses'), 403, '');

        // Find the record
        $record = OccurrenceStatus::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_occurrence_statuses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (OccurrenceStatus::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_occurrence_statuses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (OccurrenceStatus::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getOccurrenceStatus() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("occurrence_statuses")
                ->select('occurrence_statuses.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
