<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InventoryItemTypeRequest;
use App\Http\Resources\InventoryItemTypeResource;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryItemType;
use Inertia\Inertia;

class InventoryItemTypeController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_itypes'), 403, '');

        return Inertia::render('InventoryItemTypes/Index', [
            'record' => InventoryItemTypeResource::collection(
                InventoryItemType::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter === 'trashed'){
                                    $query->withTrashed();
                                }
                            })
                            ->latest()
                            ->paginate(10)
                            ->withQueryString()
                        ),
            'slideOverEdit' => true,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.itypes.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.itypes.description'),
                    'value' => 'description'
                ],
            ],
            'model' => InventoryItemType::MENU_NAME,
            'abilities' => method_exists(InventoryItemType::class, 'getAbilities') ? collect(InventoryItemType::ABILITIES)->map(function($item){
                return $item . '_' . InventoryItemType::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . InventoryItemType::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_itypes'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('InventoryItemTypes/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryItemTypeRequest $request)
    {
        abort_if( !auth()->user()->can('add_itypes'), 403, '');

        // Persiste data to DB
        InventoryItemType::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_itypes'), 403, '');

        // Find the record
        $record = InventoryItemType::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryItemTypes/Edit', [
            'record' => InventoryItemTypeResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryItemTypeRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_itypes'), 403, '');

        // Find the record
        $record = InventoryItemType::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_itypes'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryItemType::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_itypes'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryItemType::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInventoryItemType() {
        $data = [];

        if (request()->filled('q')) {
            $search = request('q');

            $data = InventoryItemType::query()
                ->select(['id', 'name', 'description'])
                ->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })
                ->limit(25)
                ->get();
        }

        return response()->json($data);
    }

}
