<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ParameterRequest;
use App\Http\Resources\ParameterResource;
use App\Models\Formula;
use Illuminate\Support\Facades\DB;
use App\Models\Parameter;
use Inertia\Inertia;

class ParameterController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_parameters'), 403, '');

        return Inertia::render('Parameters/Index', [
            'record' => ParameterResource::collection(
                Parameter::query()
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
                    'name' => trans('gestlab.general.labels.parameters.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.parameters.optimal_analysis_time'),
                    'value' => 'optimal_analysis_time'
                ],
                [
                    'name' => trans('gestlab.general.labels.parameters.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.parameters.price'),
                    'value' => 'price'
                ],
            ],
            'model' => Parameter::MENU_NAME,
            'abilities' => method_exists(Parameter::class, 'getAbilities') ? collect(Parameter::ABILITIES)->map(function($item){
                return $item . '_' . Parameter::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Parameter::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed']),
            'formulas' => Formula::active()->get() // Add formulas to props
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_parameters'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Parameters/Create', [
            'formulas' => Formula::active()->get() // Add formulas to props
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ParameterRequest $request)
    {
        abort_if( !auth()->user()->can('add_parameters'), 403, '');

        // Persiste data to DB
        Parameter::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_parameters'), 403, '');

        // Find the record
        $record = Parameter::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Parameters/Edit', [
            'record' => ParameterResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ParameterRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_parameters'), 403, '');

        // Find the record
        $record = Parameter::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_parameters'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Parameter::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_parameters'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Parameter::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    // public function getParameter() {
    //     $data = [];

    //     if(request()->has('q')){
    //         $search = request()->q;
            
    //         $data = DB::table("parameters")
    //             ->select('parameters.*')
    //             ->where('active', 1)
    //             ->where('name','LIKE',"%$search%")
    //             ->orWhere('code','LIKE',"%$search%")
    //             ->get();
    //     }

    //     return response()->json($data);
    // }

    public function getParameter() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("parameters")
                ->select([
                    'parameters.id',
                    'parameters.name',
                    'parameters.code',
                    'parameters.active',
                    'parameters.price',
                    'parameters.optimal_analysis_time',
                ])
                ->where('active', 1)
                ->where(function($query) use ($search) {
                    $query->where('name', 'LIKE', "%$search%")
                        ->orWhere('code', 'LIKE', "%$search%");
                })
                ->get();
        }

        return response()->json($data);
    }

}
