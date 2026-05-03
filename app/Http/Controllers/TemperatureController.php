<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TemperatureRequest;
use App\Http\Resources\TemperatureResource;
use Illuminate\Support\Facades\DB;
use App\Models\Temperature;
use Inertia\Inertia;

class TemperatureController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_temperatures'), 403, '');

        return Inertia::render('Temperatures/Index', [
            'record' => TemperatureResource::collection(
                Temperature::query()
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
                    'name' => trans('gestlab.general.labels.temperatures.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.temperatures.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.temperatures.description'),
                    'value' => 'description'
                ],
            ],
            'model' => Temperature::MENU_NAME,
            'abilities' => method_exists(Temperature::class, 'getAbilities') ? collect(Temperature::ABILITIES)->map(function($item){
                return $item . '_' . Temperature::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Temperature::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_temperatures'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Temperatures/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(TemperatureRequest $request)
    {
        abort_if( !auth()->user()->can('add_temperatures'), 403, '');

        // Persiste data to DB
        Temperature::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_temperatures'), 403, '');

        // Find the record
        $record = Temperature::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Temperatures/Edit', [
            'record' => TemperatureResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(TemperatureRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_temperatures'), 403, '');

        // Find the record
        $record = Temperature::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_temperatures'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Temperature::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_temperatures'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Temperature::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }

    public function getTemperature() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("temperatures")
                ->select('temperatures.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
