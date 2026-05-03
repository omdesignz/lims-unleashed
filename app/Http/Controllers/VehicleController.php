<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\VehicleRequest;
use App\Http\Resources\VehicleResource;
use Illuminate\Support\Facades\DB;
use App\Models\Vehicle;
use Inertia\Inertia;

class VehicleController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_vehicles'), 403, '');

        return Inertia::render('Vehicles/Index', [
            'record' => VehicleResource::collection(
                Vehicle::query()
                            ->with('category', 'department')
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
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
            'slideOverEdit' => true,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.vehicles.number_plate'),
                    'value' => 'number_plate'
                ],
                [
                    'name' => trans('gestlab.general.labels.vehicles.department_id'),
                    'value' => 'department'
                ],
                [
                    'name' => trans('gestlab.general.labels.vehicles.category_id'),
                    'value' => 'category'
                ],
            ],
            'model' => Vehicle::MENU_NAME,
            'abilities' => method_exists(Vehicle::class, 'getAbilities') ? collect(Vehicle::ABILITIES)->map(function($item){
                return $item . '_' . Vehicle::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Vehicle::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_vehicles'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Vehicles/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(VehicleRequest $request)
    {
        abort_if( !auth()->user()->can('add_vehicles'), 403, '');

        // Persiste data to DB
        Vehicle::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_vehicles'), 403, '');

        // Find the record
        $record = Vehicle::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Vehicles/Edit', [
            'record' => VehicleResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(VehicleRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_vehicles'), 403, '');

        // Find the record
        $record = Vehicle::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_vehicles'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Vehicle::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_vehicles'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Vehicle::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getVehicle() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("vehicles")
                ->select('vehicles.*')
                ->where('number_plate','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
