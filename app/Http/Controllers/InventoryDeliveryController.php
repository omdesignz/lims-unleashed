<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InventoryDeliveryRequest;
use App\Http\Resources\InventoryDeliveryResource;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryDelivery;
use App\Models\InventoryDeliveryDetail;
use Inertia\Inertia;

class InventoryDeliveryController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_ideliveries'), 403, '');

        return Inertia::render('InventoryDeliveries/Index', [
            'record' => InventoryDeliveryResource::collection(
                InventoryDelivery::query()
                            ->with('customer')
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
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
            'slideOverEdit' => false,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.ideliveries.sales_date'),
                    'value' => 'sales_date'
                ],
                [
                    'name' => trans('gestlab.general.labels.ideliveries.customer_id'),
                    'value' => 'customer'
                ],
            ],
            'model' => InventoryDelivery::MENU_NAME,
            'abilities' => method_exists(InventoryDelivery::class, 'getAbilities') ? collect(InventoryDelivery::ABILITIES)->map(function($item){
                return $item . '_' . InventoryDelivery::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . InventoryDelivery::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_ideliveries'), 403, '');

        return Inertia::render('InventoryDeliveries/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryDeliveryRequest $request)
    {
        abort_if( !auth()->user()->can('add_ideliveries'), 403, '');


        DB::transaction(function () use ($request): void {
            DB::transaction(function () use ($request): void {
                $delivery = InventoryDelivery::create($request->safe()->except(['items']));
    
                foreach(collect($request->safe()->only(['items']))->first() as $item) {
    
                    $obj = new InventoryDeliveryDetail;
    
                    $obj->delivery_id = $delivery->id;
                    $obj->item_id = $item['item_id'];
                    $obj->qty = $item['qty'];
                    $obj->expected_date = $item['expected_date'];
                    $obj->actual_date = $item['actual_date'];
                    $obj->warehouse_id = $item['warehouse_id'];
    
                    $obj->save();
    
                }
            });
        });

        

        return to_route('ideliveries.index')->with([
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
        abort_if( !auth()->user()->can('edit_ideliveries'), 403, '');

        // Find the record
        $record = InventoryDelivery::with('customer', 'items')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryDeliveries/Edit', [
            // 'record' => InventoryDeliveryResource::make($record)
            'record' => [
                'id' => $record->id,
                'sales_date' => $record->sales_date,
                'customer_id' => [
                    'value' => $record->customer_id,
                    'label' => $record->customer->name ?? '',
                ],
                'items' => collect($record->items)->map(function($item) {
                    return [
                        'item_id' => [
                            'value' => $item->item_id,
                            'label' => $item->item?->name ?? '',
                        ],
                        'qty' => $item->qty,
                        'expected_date' => $item->expected_date,
                        'actual_date' => $item->actual_date,
                        'warehouse_id' => [
                            'value' => $item->warehouse_id,
                            'label' => $item->warehouse?->name ?? '',
                        ],
                    ];
                })
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryDeliveryRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_ideliveries'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            $record = tap(InventoryDelivery::findOrFail($id), function($record) use($request) {

                $record->update($request->safe()->except(['items']));

                InventoryDeliveryDetail::where('delivery_id', $record->id)->forcedelete();


                foreach(collect($request->safe()->only(['items']))->first() as $item) {

                    $obj = new InventoryDeliveryDetail;
    
                    $obj->delivery_id = $record->id;
                    $obj->item_id = $item['item_id'];
                    $obj->warehouse_id = $item['warehouse_id'];
                    $obj->qty = $item['qty'];
                    $obj->actual_date = $item['actual_date'];
                    $obj->expected_date = $item['expected_date'];
    
                    $obj->save();
    
                }
    
            });

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
        abort_if( !auth()->user()->can('delete_ideliveries'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryDelivery::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_ideliveries'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryDelivery::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInventoryDelivery() {
        $data = [];

        if (request()->filled('q')) {
            $search = request('q');

            $data = InventoryDelivery::query()
                ->with('customer:id,name')
                ->select(['id', 'sales_date', 'customer_id'])
                ->where(function ($query) use ($search) {
                    $query->where('sales_date', 'like', "%{$search}%")
                        ->orWhereRelation('customer', 'name', 'like', "%{$search}%");
                })
                ->limit(25)
                ->get();
        }

        return response()->json($data);
    }
}
