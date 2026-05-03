<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ItemStatusRequest;
use App\Http\Resources\ItemStatusResource;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use App\Models\ItemStatus;
use Carbon\Carbon;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class ItemStatusController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_item_statuses'), 403, '');

        $records = QueryBuilder::for(ItemStatus::class)
                                ->with('category')
                                ->allowedFilters(ItemStatus::getAllowedFilters())
                                ->allowedSorts(ItemStatus::getAllowedSorts())
                                ->paginate(request()->query('per_page', 10));


        return Inertia::render('ItemStatuses/Index', [
            'record' => ItemStatusResource::collection($records),
            'initialFilters' => request()->query('filter', ['name' => '', 'description' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => true,  
            'trashedFilter' => true,
            'trashedOptions' => ItemStatus::getTrashedOptions(),
            'fields' => ItemStatus::getColumns(),
            'model' => ItemStatus::MENU_NAME,
            'abilities' => method_exists(ItemStatus::class, 'getAbilities') ? collect(ItemStatus::ABILITIES)->map(function($item){
                return $item . '_' . ItemStatus::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . ItemStatus::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_item_statuses'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('ItemStatuses/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ItemStatusRequest $request)
    {
        abort_if( !auth()->user()->can('add_item_statuses'), 403, '');

        // Persiste data to DB
        ItemStatus::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_item_statuses'), 403, '');

        // Find the record
        $record = ItemStatus::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ItemStatuses/Edit', [
            'record' => ItemStatusResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ItemStatusRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_item_statuses'), 403, '');

        // Find the record
        $record = ItemStatus::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_item_statuses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (ItemStatus::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_item_statuses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (ItemStatus::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getItemStatus() {
        $data = [];

        if(request()->has('q')) {
            $search = request()->q;
            
            $data = DB::table("item_statuses")
                ->select('item_statuses.*')
                ->where('category_id', '=', request()->category_id)
                // ->orWhere('category_id', '=', null)
                ->where('name','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
