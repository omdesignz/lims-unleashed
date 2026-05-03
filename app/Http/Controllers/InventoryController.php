<?php

namespace App\Http\Controllers;

use App\Enums\Orders\InventoryOrderTrackingStatus;
use Illuminate\Http\Request;
use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Models\ReagentConsumption;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(!auth()->user()->can('view_inventory'), 403, '');

        return Inertia::render('Inventory/Index', [
            'record' => InventoryResource::collection(
                Inventory::query()
                    ->with('item.category', 'warehouse')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where(function ($searchQuery) use ($search) {
                            $searchQuery->where('qty_available', 'like', "%{$search}%")
                                ->orWhereRelation('item.category', 'name', 'like', "%{$search}%")
                                ->orWhereRelation('warehouse', 'name', 'like', "%{$search}%");
                        });
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
                    'name' => trans('gestlab.general.labels.inventory.item_id'),
                    'value' => 'item'
                ],
                [
                    'name' => trans('gestlab.general.labels.inventory.category_id'),
                    'value' => 'category'
                ],
                [
                    'name' => trans('gestlab.general.labels.inventory.qty_available'),
                    'value' => 'qty_available'
                ],
                // [
                //     'name' => trans('gestlab.general.labels.inventory.min_stock_level'),
                //     'value' => 'min_stock_level'
                // ],
                [
                    'name' => trans('gestlab.general.labels.inventory.warehouse_id'),
                    'value' => 'warehouse'
                ],
                // [
                //     'name' => trans('gestlab.general.labels.inventory.reorder_point'),
                //     'value' => 'reorder_point'
                // ],
            ],
            'model' => Inventory::MENU_NAME,
            'abilities' => method_exists(Inventory::class, 'getAbilities') ? collect(Inventory::ABILITIES)->map(function ($item) {
                return $item . '_' . Inventory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . Inventory::MENU_NAME;
            }),
            'query' => request()->only(['search', 'filter'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add_inventory'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Inventory/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryRequest $request)
    {
        abort_if(!auth()->user()->can('add_inventory'), 403, '');

        // Persiste data to DB
        Inventory::create($request->validated());

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
        return Inertia::render('Inventory/Show', [
            'record' => InventoryResource::make(
                Inventory::query()
                                 ->with('item.category', 'warehouse')
                                 ->find($id)
            )
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if(!auth()->user()->can('edit_inventory'), 403, '');

        // Find the record
        $record = Inventory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Inventory/Edit', [
            'record' => InventoryResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_inventory'), 403, '');

        // Find the record
        $record = Inventory::findOrFail($id);

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
        abort_if(!auth()->user()->can('delete_inventory'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Inventory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(!auth()->user()->can('restore_inventory'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Inventory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
        ]);
    }


    public function getInventory()
    {
        $data = [];

        if (request()->filled('q')) {
            $search = request('q');

            $data = Inventory::query()
                ->with(['item:id,name', 'warehouse:id,name'])
                ->select(['id', 'item_id', 'warehouse_id', 'qty_available', 'name', 'status'])
                ->where(function ($query) use ($search) {
                    $query->where('qty_available', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereRelation('item', 'name', 'like', "%{$search}%")
                        ->orWhereRelation('warehouse', 'name', 'like', "%{$search}%");
                })
                ->limit(25)
                ->get();
        }

        return response()->json($data);
    }

    public function getInventoryReagentItem() {
        $data = [];

        if (request()->filled('q')) {
            $search = request('q');

            $data = Inventory::query()
                ->select(['id', 'item_id', 'warehouse_id', 'qty_available', 'name', 'status', 'category_id'])
                ->where('category_id', 2)
                ->where('name', 'like', "%{$search}%")
                ->limit(25)
                ->get();
        }

        return response()->json($data);
    }

    // Increment Inventory Quantity

    public function increment(Request $request, $id)
    {
        $data = Inventory::findOrFail($id);

        $data->increment('qty_available', $request->qty);

        $data->save();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated') . '. Quantidade Disponível: ' . $data->qty_available . '.',
            ]
        ]);
    }

    // Decrement Inventory Quantity 

    public function decrement(Request $request, $id)
    {
        // dd($request->all());

        $data = Inventory::with('item')->findOrFail($id);

        $data->decrement('qty_available', $request->qty);

        $data->save();

        // Notify when stock is low
        if($data->qty_available < $data->min_stock_level) {
            auth()->user()->notify(new LowStockAlert($data, auth()->user()));
        }

        // If Inventory Item Is Reagent, then also update the reagent consumption
        if($data->category_id == 2) {
            ReagentConsumption::create([
                'date' => now()->format('Y-m-d'),
                'reagent_id' => $data->id,
                'reagent_name' => $data?->item?->name,
                'quantity_used' => $request->qty,
                'used_by' => auth()->user()->name,
                'used_at' => now()->format('Y-m-d'),
                'user_id' => auth()->user()->id,
                'remarks' => null,
            ]);
        }
        
        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated') . '. Quantidade Disponível: ' . $data->qty_available . '.',
            ]
        ]);

        // return response()->json($data);
    }
}
