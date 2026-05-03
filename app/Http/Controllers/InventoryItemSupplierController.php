<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InventoryItemSupplierRequest;
use App\Http\Resources\InventoryItemSupplierResource;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryItemSupplier;
use Inertia\Inertia;

class InventoryItemSupplierController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_isuppliers'), 403, '');

        return Inertia::render('InventoryItemSuppliers/Index', [
            'record' => InventoryItemSupplierResource::collection(
                InventoryItemSupplier::query()
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
                    'name' => trans('gestlab.general.labels.isuppliers.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.isuppliers.address'),
                    'value' => 'address'
                ],
            ],
            'model' => InventoryItemSupplier::MENU_NAME,
            'abilities' => method_exists(InventoryItemSupplier::class, 'getAbilities') ? collect(InventoryItemSupplier::ABILITIES)->map(function($item){
                return $item . '_' . InventoryItemSupplier::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . InventoryItemSupplier::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_isuppliers'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('InventoryItemSuppliers/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryItemSupplierRequest $request)
    {
        abort_if( !auth()->user()->can('add_isuppliers'), 403, '');

        // Persiste data to DB
        InventoryItemSupplier::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_isuppliers'), 403, '');

        // Find the record
        $record = InventoryItemSupplier::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryItemSuppliers/Edit', [
            'record' => InventoryItemSupplierResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryItemSupplierRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_isuppliers'), 403, '');

        // Find the record
        $record = InventoryItemSupplier::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_isuppliers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryItemSupplier::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_isuppliers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryItemSupplier::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInventoryItemSupplier() {
        if (! request()->filled('q')) {
            return response()->json([]);
        }

        $search = request()->string('q')->toString();

        $data = DB::table('i_suppliers')
            ->select('i_suppliers.*')
            ->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(25)
            ->get();

        return response()->json($data);
    }

}
