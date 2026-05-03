<?php

namespace App\Http\Controllers;

use App\Enums\Orders\InventoryOrderTrackingStatus;
use App\Events\InventoryOrderUpdatedEvent;
use Illuminate\Http\Request;
use App\Http\Requests\InventoryOrderRequest;
use App\Http\Resources\InventoryOrderResource;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\RatingRequest;
use App\Notifications\RatingRequestNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Predis\Command\Redis\DEL;

class InventoryOrderController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_iorders'), 403, '');

        return Inertia::render('InventoryOrders/Index', [
            'record' => InventoryOrderResource::collection(
                InventoryOrder::query()
                            ->with('supplier')
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
                    'name' => trans('gestlab.general.labels.iorders.reference'),
                    'value' => 'reference'
                ],
                [
                    'name' => trans('gestlab.general.labels.iorders.date'),
                    'value' => 'date'
                ],
                [
                    'name' => trans('gestlab.general.labels.iorders.supplier_id'),
                    'value' => 'supplier'
                ],
            ],
            'model' => InventoryOrder::MENU_NAME,
            'abilities' => method_exists(InventoryOrder::class, 'getAbilities') ? collect(InventoryOrder::ABILITIES)->map(function($item){
                return $item . '_' . InventoryOrder::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . InventoryOrder::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_iorders'), 403, '');

        return Inertia::render('InventoryOrders/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryOrderRequest $request)
    {
        abort_if( !auth()->user()->can('add_iorders'), 403, '');


        // DB::transaction(function () use ($request): void {
            DB::transaction(function () use ($request): void {
                $order = InventoryOrder::create($request->safe()->except(['items']));
    
                foreach(collect($request->safe()->only(['items']))->first() as $item) {
    
                    $obj = new InventoryOrderDetail;
    
                    $obj->order_id = $order->id;
                    $obj->item_id = $item['item_id'];
                    $obj->qty = $item['qty'];
                    $obj->expected_date = $item['expected_date'];
                    $obj->actual_date = $item['actual_date'];
                    $obj->warehouse_id = $item['warehouse_id'];
    
                    $obj->save();
    
                }
            });
        // });

        

        return to_route('iorders.index')->with([
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
        return Inertia::render('InventoryOrders/Show', [
            'record' => InventoryOrderResource::make(
                InventoryOrder::query()
                                 ->with('user', 'items')
                                 ->find($id)
            ),
            'steps' => collect(InventoryOrderTrackingStatus::cases())->map(fn($item, $index) => [
                'id' => $index + 1,
                'statusKey' => $item->value,
                'name' => trans("gestlab.progress_tracker.iorders.steps.{$item->value}.name"),
                'href' => null,
                'description' => trans("gestlab.progress_tracker.iorders.steps.{$item->value}.description")
            ])->toArray(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_iorders'), 403, '');

        // Find the record
        $record = InventoryOrder::with('supplier', 'items')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryOrders/Edit', [
            // 'record' => InventoryOrderResource::make($record)
            'record' => [
                'id' => $record->id,
                'date' => $record->date,
                'obs' => $record->obs,
                'supplier_id' => [
                    'value' => $record->supplier_id,
                    'label' => $record->supplier->name ?? '',
                ],
                'items' => collect($record->items)->map(function($item) {
                    return [
                        'item_id' => [
                            'value' => $item->item_id,
                            'label' => $item->item?->name ?? '',
                        ],
                        'qty' => $item->qty,
                        'date' => $item->date,
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
    public function update(InventoryOrderRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_iorders'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            $record = tap(InventoryOrder::findOrFail($id), function($record) use($request) {

                $record->update($request->safe()->except(['items']));

                InventoryOrderDetail::where('order_id', $record->id)->forcedelete();


                foreach(collect($request->safe()->only(['items']))->first() as $item) {

                    $obj = new InventoryOrderDetail;
    
                    $obj->order_id = $record->id;
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
        abort_if( !auth()->user()->can('delete_iorders'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryOrder::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_iorders'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryOrder::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }

    public function completeOrder($orderId)
    {
        $order = InventoryOrder::find($orderId);

        // Complete the order (your business logic)
        $order->status = 'completed';
        $order->save();

        // Create a rating request for the user
        $ratingRequest = RatingRequest::create([
            'user_id' => $order->user_id,
            'rateable_type' => 'order',  // Specify type as 'order'
            'rateable_id' => $order->id,
            'status' => 'pending',
        ]);

        // Send notification to the user
        Notification::send($order->user, new RatingRequestNotification($ratingRequest));

        return response()->json(['message' => 'Order completed and rating request sent!']);
    }


    public function getInventoryOrder() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("i_orders")
                ->select('i_orders.*')
                ->where('date','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    // Change the status of the order

    public function changeOrderStatus(Request $request, $orderId)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                new Enum(InventoryOrderTrackingStatus::class),
            ],
        ]);

        $order = InventoryOrder::findOrFail($orderId);

        $order->status = $validated['status'];
        $order->save();

        // Update Inventory Quantity If Status === DELIVERED

        if($order->status === InventoryOrderTrackingStatus::DELIVERED) {
            $order->items->each(function($item) {
                
            });
        }

        broadcast(new InventoryOrderUpdatedEvent($order));

        // Create a rating request for the user
        // $ratingRequest = RatingRequest::create([
        //     'user_id' => $order->user_id,
        //     'rateable_type' => 'order',  // Specify type as 'order'
        //     'rateable_id' => $order->id,
        //     'status' => 'pending',
        // ]);

        // Send notification to the user
        // Notification::send($order->user, new RatingRequestNotification($ratingRequest));

        // return redirect()->route('iorders.show', $order->id);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    // public function placeOrder(Request $request)
    // {
    //     DB::transaction(function () use ($request) {
    //         $order = Order::create($request->all());
            
    //         foreach ($request->items as $item) {
    //             $product = Product::findOrFail($item['product_id']);
                
    //             if ($product->stock < $item['quantity']) {
    //                 throw new \Exception('Not enough stock for ' . $product->name);
    //             }
                
    //             $product->decrement('stock', $item['quantity']);
    //             $order->items()->create($item);
    //         }
            
    //         $user = Auth::user();
    //         $user->increment('total_orders');
    //         $user->increment('total_spent', $order->total_amount);
    //     });
        
    //     return response()->json(['message' => 'Order placed successfully']);
    // }
}
