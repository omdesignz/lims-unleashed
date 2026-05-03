<?php

namespace App\Http\Controllers;

use App\Exports\InventoryItemsExport;
use Illuminate\Http\Request;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\MaintenanceTaskResource;
use App\Models\InventoryItem;
use App\Models\MaintenanceTask;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\Support\MediaStream;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class InventoryItemController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_iitems'), 403, '');

        return Inertia::render('InventoryItems/Index', [
            'record' => InventoryItemResource::collection(
                InventoryItem::query()
                            ->with('category', 'department', 'unit', 'type', 'status', 'packagingType', 'supplier')
                            ->where('category_id', '!=', 1)
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('code', 'like', "%{$search}%")
                                        ->orWhere('internal_code', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter = 'trashed'){
                                    $query->withTrashed();
                                }
                            })
                            ->orderBy('internal_code', 'desc')
                            ->paginate(10)
                            ->withQueryString()
                        ),
            'slideOverEdit' => true,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.iitems.category_id'),
                    'value' => 'category'
                ],
                [
                    'name' => trans('gestlab.general.labels.iitems.internal_code'),
                    'value' => 'internal_code'
                ],
                [
                    'name' => trans('gestlab.general.labels.iitems.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.iitems.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.iitems.description'),
                    'value' => 'description'
                ],
            ],
            'model' => InventoryItem::MENU_NAME,
            'abilities' => method_exists(InventoryItem::class, 'getAbilities') ? collect(InventoryItem::ABILITIES)->map(function($item){
                return $item . '_' . InventoryItem::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . InventoryItem::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_iitems'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('InventoryItems/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryItemRequest $request)
    {
        abort_if( !auth()->user()->can('add_iitems'), 403, '');

        // Persiste data to DB
        // InventoryItem::create($request->validated());

        DB::transaction(function () use ($request) {
            $item = InventoryItem::create($request->safe()->except(['documents']));

            // Add Possible Documents
            if(request()->hasFile('documents')) {

                $fileAdders = $item
                    ->addMultipleMediaFromRequest(['documents'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('documents');
                    });
            }
        });

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
        return Inertia::render('InventoryItems/Show', [
            'record' => InventoryItemResource::make(
                InventoryItem::query()
                                 ->with('type', 'packagingType', 'department', 'user', 'supplier', 'status', 'unit', 'eq_cat', 'category', 'maintenance_tasks')
                                 ->find($id)
            ),
            'maintenanceTasks' => MaintenanceTaskResource::collection(
                MaintenanceTask::query()
                    ->with('equipment', 'category', 'supplier')
                    ->where('equipment_id', $id)
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            )
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_iitems'), 403, '');

        // Find the record
        $record = InventoryItem::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryItems/Edit', [
            'record' => InventoryItemResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryItemRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_iitems'), 403, '');

        // Find the record
        // $record = InventoryItem::findOrFail($id);

        // $record->update($request->validated());

        // dd($request->all());

        DB::transaction(function () use ($request, $id) {
            $item = InventoryItem::findOrFail($id);

            $item->update($request->safe()->except(['documents']));

            // Add Possible Documents
            if($request->documents){

                $fileAdders = $item
                    ->addMultipleMediaFromRequest(['documents'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('documents');
                    });
            }
        });

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
        abort_if( !auth()->user()->can('delete_iitems'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryItem::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_iitems'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryItem::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInventoryItem() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("i_items")
                ->select('i_items.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getReagentInventoryItem() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("i_items")
                ->select('i_items.*')
                ->where('category_id', 2)
                ->where('name','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function downloadallattachments()
    {
        // Get all Docs
        $documents = InventoryItem::findOrFail(request()->model_id)->getMedia('documents');

        return MediaStream::create('documents.zip')->addMedia($documents);
    }

    public function downloadsingleattachment()
    {
        return Media::findOrFail(request()->model_id);
    }

    public function deleteattachment()
    {
        $item = InventoryItem::findOrFail(request()->model_id);

        $media = Media::where('id', request()->id)->where('model_id', request()->model_id)->first()->delete();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    public function getMaintenanceTasks($id)
    {
        MaintenanceTaskResource::collection(
            MaintenanceTask::query()
                ->with('equipment')
                ->where('equipment_id', $id)
                ->latest()
                ->paginate(10)
                ->withQueryString()
        );
    }

    /**
     * Export all inventory items to an Excel file.
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function exportInventory(Request $request)
    {
        // dd($request->all());

        // Validate the date parameters
        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
        ]);
        
        $startDate = $request->input('start');
        $endDate = $request->input('end');
        $categories = [2, 3, 4];
        
        // Pass the date range to the export class
        return Excel::download(new InventoryItemsExport($startDate, $endDate, $categories), 'inventory_items.xlsx');
    }

}
