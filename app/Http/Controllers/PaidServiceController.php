<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PaidServiceRequest;
use App\Http\Resources\PaidServiceResource;
use Illuminate\Support\Facades\DB;
use App\Models\PaidService;
use Inertia\Inertia;

class PaidServiceController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_paid_services'), 403, '');

        return Inertia::render('PaidServices/Index', [
            'record' => PaidServiceResource::collection(
                PaidService::query()
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
            'slideOverEdit' => false,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.products.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.products.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.products.charge_tax'),
                    'value' => 'charge_tax'
                ],
                [
                    'name' => trans('gestlab.general.labels.products.exemption_id'),
                    'value' => 'exemption'
                ],
            ],
            'model' => PaidService::MENU_NAME,
            'abilities' => method_exists(PaidService::class, 'getAbilities') ? collect(PaidService::ABILITIES)->map(function($item){
                return $item . '_' . PaidService::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . PaidService::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_paid_services'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('PaidServices/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(PaidServiceRequest $request)
    {
        abort_if( !auth()->user()->can('add_paid_services'), 403, '');

        // Persiste data to DB
        PaidService::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_paid_services'), 403, '');

        // Find the record
        $record = PaidService::with('exemption', 'tax_category')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('PaidServices/Edit', [
            'record' => PaidServiceResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(PaidServiceRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_paid_services'), 403, '');

        // Find the record
        $record = PaidService::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_paid_services'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (PaidService::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_paid_services'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (PaidService::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getPaidService() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("paid_services")
                ->select('paid_services.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
