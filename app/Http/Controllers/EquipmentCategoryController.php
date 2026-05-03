<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ItemCategoryRequest;
use App\Http\Resources\EquipmentCategoryResource;
use App\Models\EquipmentCategory;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EquipmentCategoryController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_equipment_categories'), 403, '');

        return Inertia::render('EquipmentCategories/Index', [
            'record' => EquipmentCategoryResource::collection(
                EquipmentCategory::query()
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
                    'name' => trans('gestlab.general.labels.equipment_categories.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.equipment_categories.description'),
                    'value' => 'description'
                ],
            ],
            'model' => EquipmentCategory::MENU_NAME,
            'abilities' => method_exists(EquipmentCategory::class, 'getAbilities') ? collect(EquipmentCategory::ABILITIES)->map(function($item){
                return $item . '_' . EquipmentCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . EquipmentCategory::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_equipment_categories'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('EquipmentCategories/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ItemCategoryRequest $request)
    {
        abort_if( !auth()->user()->can('add_equipment_categories'), 403, '');

        // Persiste data to DB
        EquipmentCategory::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_equipment_categories'), 403, '');

        // Find the record
        $record = EquipmentCategory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('EquipmentCategories/Edit', [
            'record' => EquipmentCategoryResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ItemCategoryRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_equipment_categories'), 403, '');

        // Find the record
        $record = EquipmentCategory::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_equipment_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (EquipmentCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_equipment_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (EquipmentCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getEquipmentCategory() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("equipment_categories")
                ->select('equipment_categories.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
