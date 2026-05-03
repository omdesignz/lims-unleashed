<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CountryRequest;
use App\Http\Resources\CountryResource;
use Illuminate\Support\Facades\DB;
use App\Models\Country;
use Inertia\Inertia;

class CountryController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_countries'), 403, '');

        return Inertia::render('Countries/Index', [
            'record' => CountryResource::collection(
                Country::query()
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
                    'name' => trans('gestlab.general.labels.countries.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.countries.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.countries.phone_code'),
                    'value' => 'phone_code'
                ],
            ],
            'model' => Country::MENU_NAME,
            'abilities' => method_exists(Country::class, 'getAbilities') ? collect(Country::ABILITIES)->map(function($item){
                return $item . '_' . Country::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Country::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_countries'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Countries/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(CountryRequest $request)
    {
        abort_if( !auth()->user()->can('add_countries'), 403, '');

        // Persiste data to DB
        Country::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_countries'), 403, '');

        // Find the record
        $record = Country::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Countries/Edit', [
            'record' => CountryResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(CountryRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_countries'), 403, '');

        // Find the record
        $record = Country::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_countries'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Country::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_countries'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Country::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getCountry() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("countries")
                ->select('countries.*')
                ->where('name','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
