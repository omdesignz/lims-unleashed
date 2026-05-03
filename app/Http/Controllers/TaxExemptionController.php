<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TaxExemptionRequest;
use App\Http\Resources\TaxExemptionResource;
use Illuminate\Support\Facades\DB;
use App\Models\TaxExemption;
use Inertia\Inertia;

class TaxExemptionController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_tax_exemptions'), 403, '');

        return Inertia::render('TaxExemptions/Index', [
            'record' => TaxExemptionResource::collection(
                TaxExemption::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('code', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.tax_exemptions.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.tax_exemptions.law'),
                    'value' => 'law'
                ],
                [
                    'name' => trans('gestlab.general.labels.tax_exemptions.reason'),
                    'value' => 'reason'
                ],
            ],
            'model' => TaxExemption::MENU_NAME,
            'abilities' => method_exists(TaxExemption::class, 'getAbilities') ? collect(TaxExemption::ABILITIES)->map(function($item){
                return $item . '_' . TaxExemption::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . TaxExemption::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_tax_exemptions'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('TaxExemptions/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(TaxExemptionRequest $request)
    {
        abort_if( !auth()->user()->can('add_tax_exemptions'), 403, '');

        // Persiste data to DB
        TaxExemption::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_tax_exemptions'), 403, '');

        // Find the record
        $record = TaxExemption::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('TaxExemptions/Edit', [
            'record' => TaxExemptionResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(TaxExemptionRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_tax_exemptions'), 403, '');

        // Find the record
        $record = TaxExemption::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_tax_exemptions'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (TaxExemption::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_tax_exemptions'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (TaxExemption::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getExemption() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("tax_exemptions")
                ->select('tax_exemptions.*')
                ->where('law','LIKE',"%$search%")
                ->orWhere('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
