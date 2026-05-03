<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InventoryItemLocationRequest;
use App\Http\Resources\InventoryItemLocationResource;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryItemLocation;
use Inertia\Inertia;

class InventoryItemLocationController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_ilocations'), 403, '');

        return Inertia::render('InventoryItemLocations/Index', [
            'record' => InventoryItemLocationResource::collection(
                InventoryItemLocation::query()
                            ->with('department')
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
                    'name' => trans('gestlab.general.labels.ilocations.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.ilocations.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.ilocations.address'),
                    'value' => 'address'
                ],
                [
                    'name' => trans('gestlab.general.labels.ilocations.department_id'),
                    'value' => 'department'
                ],
            ],
            'model' => InventoryItemLocation::MENU_NAME,
            'abilities' => method_exists(InventoryItemLocation::class, 'getAbilities') ? collect(InventoryItemLocation::ABILITIES)->map(function($item){
                return $item . '_' . InventoryItemLocation::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . InventoryItemLocation::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_ilocations'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('InventoryItemLocations/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryItemLocationRequest $request)
    {
        abort_if( !auth()->user()->can('add_ilocations'), 403, '');

        // Persiste data to DB
        InventoryItemLocation::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_ilocations'), 403, '');

        // Find the record
        $record = InventoryItemLocation::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryItemLocations/Edit', [
            'record' => InventoryItemLocationResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryItemLocationRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_ilocations'), 403, '');

        // Find the record
        $record = InventoryItemLocation::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_ilocations'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryItemLocation::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_ilocations'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryItemLocation::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInventoryItemLocation() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("i_locations")
                ->select('i_locations.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('address','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
