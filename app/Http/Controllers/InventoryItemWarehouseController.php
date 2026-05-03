<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InventoryItemWarehouseRequest;
use App\Http\Resources\InventoryItemWarehouseResource;
use App\Models\InventoryItemWarehouse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryItemWarehouseController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_iwarehouses'), 403, '');

        return Inertia::render('InventoryItemWarehouses/Index', [
            'record' => InventoryItemWarehouseResource::collection(
                InventoryItemWarehouse::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter = 'trashed'){
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
                    'name' => trans('gestlab.general.labels.iwarehouses.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.iwarehouses.is_ventilated'),
                    'value' => 'is_ventilated'
                ],
                [
                    'name' => trans('gestlab.general.labels.iwarehouses.is_refrigerated'),
                    'value' => 'is_refrigerated'
                ],
                [
                    'name' => trans('gestlab.general.labels.iwarehouses.has_air_exhaustion'),
                    'value' => 'has_air_exhaustion'
                ],
            ],
            'model' => InventoryItemWarehouse::MENU_NAME,
            'abilities' => method_exists(InventoryItemWarehouse::class, 'getAbilities') ? collect(InventoryItemWarehouse::ABILITIES)->map(function($item){
                return $item . '_' . InventoryItemWarehouse::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . InventoryItemWarehouse::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_iwarehouses'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('InventoryItemWarehouses/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryItemWarehouseRequest $request)
    {
        abort_if( !auth()->user()->can('add_iwarehouses'), 403, '');

        // Persiste data to DB
        InventoryItemWarehouse::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_iwarehouses'), 403, '');

        // Find the record
        $record = InventoryItemWarehouse::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryItemWarehouses/Edit', [
            'record' => InventoryItemWarehouseResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryItemWarehouseRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_iwarehouses'), 403, '');

        // Find the record
        $record = InventoryItemWarehouse::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_iwarehouses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryItemWarehouse::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_iwarehouses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryItemWarehouse::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInventoryItemWarehouse() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("i_warehouses")
                ->select('i_warehouses.*')
                ->where('name','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
