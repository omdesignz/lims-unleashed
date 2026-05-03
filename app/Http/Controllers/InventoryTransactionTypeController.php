<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InventoryTransactionTypeRequest;
use App\Http\Resources\InventoryTransactionTypeResource;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryTransactionType;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryTransactionTypeController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_itransaction_types'), 403, '');

        $records = QueryBuilder::for(InventoryTransactionType::class)
                                ->allowedFilters(InventoryTransactionType::getAllowedFilters())
                                ->allowedSorts(InventoryTransactionType::getAllowedSorts())
                                ->paginate(request()->query('per_page', 10)); 


        return Inertia::render('InventoryTransactionTypes/Index', [
            'record' => InventoryTransactionTypeResource::collection($records),
            'initialFilters' => request()->query('filter', ['name' => '', 'description' => '', 'code' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => true,  
            'trashedFilter' => true,
            'trashedOptions' => InventoryTransactionType::getTrashedOptions(),
            'fields' => InventoryTransactionType::getColumns(),
            'model' => InventoryTransactionType::MENU_NAME,
            'abilities' => method_exists(InventoryTransactionType::class, 'getAbilities') ? collect(InventoryTransactionType::ABILITIES)->map(function($item){
                return $item . '_' . InventoryTransactionType::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . InventoryTransactionType::MENU_NAME;
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
        abort_if(!auth()->user()->can('add_itransaction_types'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('InventoryTransactionTypes/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryTransactionTypeRequest $request)
    {
        abort_if(!auth()->user()->can('add_itransaction_types'), 403, '');

        // Persiste data to DB
        InventoryTransactionType::create($request->validated());

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
        abort_if(!auth()->user()->can('edit_itransaction_types'), 403, '');

        // Find the record
        $record = InventoryTransactionType::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryTransactionTypes/Edit', [
            'record' => InventoryTransactionTypeResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryTransactionTypeRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_itransaction_types'), 403, '');

        // Find the record
        $record = InventoryTransactionType::findOrFail($id);

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
        abort_if(!auth()->user()->can('delete_itransaction_types'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryTransactionType::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(!auth()->user()->can('restore_itransaction_types'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryTransactionType::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
        ]);
    }


    public function getInventoryTransactionType()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table("itransaction_types")
                ->select('itransaction_types").*')
                ->where('name', 'LIKE', "%$search%")
                ->orWhere('code', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
