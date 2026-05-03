<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StandardRequest;
use App\Http\Resources\StandardResource;
use Illuminate\Support\Facades\DB;
use App\Models\Standard;
use Inertia\Inertia;

class StandardController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_standards'), 403, '');

        return Inertia::render('Standards/Index', [
            'record' => StandardResource::collection(
                Standard::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('code', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.standards.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.standards.description'),
                    'value' => 'description'
                ],
            ],
            'model' => Standard::MENU_NAME,
            'abilities' => method_exists(Standard::class, 'getAbilities') ? collect(Standard::ABILITIES)->map(function($item){
                return $item . '_' . Standard::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Standard::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_standards'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Standards/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(StandardRequest $request)
    {
        abort_if( !auth()->user()->can('add_standards'), 403, '');

        // Persiste data to DB
        Standard::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_standards'), 403, '');

        // Find the record
        $record = Standard::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Standards/Edit', [
            'record' => StandardResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(StandardRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_standards'), 403, '');

        // Find the record
        $record = Standard::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_standards'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Standard::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_standards'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Standard::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getStandard() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("standards")
                ->select('standards.*')
                ->where('description','LIKE',"%$search%")
                ->orWhere('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
