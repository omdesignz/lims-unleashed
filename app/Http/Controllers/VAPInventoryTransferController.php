<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VAPInventoryTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItemTransfer::with([
            'item.category',
            'source',
            'destination',
            'item'
        ])
        ->when($request->search, function ($query, $search) {
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        })
        ->when($request->status, function ($query, $status) {
            if ($status === 'pending') {
                $query->whereNull('received_date');
            } elseif ($status === 'received') {
                $query->whereNotNull('received_date');
            } elseif ($status === 'sent') {
                $query->whereNotNull('sent_date')->whereNull('received_date');
            }
        })
        ->when($request->source_id, function ($query, $sourceId) {
            $query->where('source_id', $sourceId);
        })
        ->when($request->destination_id, function ($query, $destinationId) {
            $query->where('destination_id', $destinationId);
        })
        ->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc');

        return Inertia::render('VAPInventory/Transfers/Index', [
            'transfers' => $query->paginate($request->per_page ?? 20)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'source_id', 'destination_id', 'sort_by', 'sort_direction']),
            'warehouses' => InventoryItemWarehouse::with('location')->active()->get(),
            'stats' => [
                'pending_transfers' => InventoryItemTransfer::whereNull('received_date')->count(),
                'sent_today' => InventoryItemTransfer::whereDate('sent_date', today())->count(),
                'received_today' => InventoryItemTransfer::whereDate('received_date', today())->count(),
                'total_transfers' => InventoryItemTransfer::count(),
            ],
        ]);
    }

    // public function create()
    // {
    //     return Inertia::render('VAPInventory/Transfers/Create', [
    //         'items' => InventoryItem::with(['inventory.warehouse'])->active()->get(),
    //         'warehouses' => InventoryItemWarehouse::with('location')->active()->get(),
    //         'defaultSource' => request('source_id'),
    //         'defaultItem' => request('item_id'),
    //     ]);
    // }

    public function create()
    {
        $items = InventoryItem::with(['inventory.warehouse'])->active()->get();
        $warehouses = InventoryItemWarehouse::with('location')->active()->get();
        
        // Pre-calculate all stock information
        $stockInfo = [];
        
        // Get all inventory records for these items and warehouses
        $inventory = Inventory::whereIn('item_id', $items->pluck('id'))
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->get(['item_id', 'warehouse_id', 'qty_available']);
        
        // Create lookup array
        foreach ($inventory as $stock) {
            $key = "{$stock->item_id}_{$stock->warehouse_id}";
            $stockInfo[$key] = $stock->qty_available;
        }
        
        // Set defaults to 0 for missing combinations
        foreach ($items as $item) {
            foreach ($warehouses as $warehouse) {
                $key = "{$item->id}_{$warehouse->id}";
                if (!isset($stockInfo[$key])) {
                    $stockInfo[$key] = 0;
                }
            }
        }

        return Inertia::render('VAPInventory/Transfers/Create', [
            'items' => $items,
            'warehouses' => $warehouses,
            'defaultSource' => request('source_id'),
            'defaultItem' => request('item_id'),
            'initialStockInfo' => $stockInfo, // Pass pre-calculated stock
        ]);
    }

    public function store(Request $request)
    {
        // dd(request()->all());

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:i_items,id',
            'source_id' => 'required|exists:i_warehouses,id',
            'destination_id' => 'required|exists:i_warehouses,id|different:source_id',
            'qty' => 'required|integer|min:1',
            'sent_date' => 'nullable|date',
            'expected_date' => 'nullable|date|after_or_equal:today',
            'obs' => 'nullable|string|max:1000',
            'batch_id' => 'nullable|exists:i_inventory_batches,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if source has enough stock
        $sourceInventory = Inventory::where('item_id', $request->item_id)
            ->where('warehouse_id', $request->source_id)
            ->first();

        if (!$sourceInventory || $sourceInventory->qty_available < $request->qty) {
            return redirect()->back()
                ->with('error', 'Stock insuficiente no armazém de origem.')
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $transfer = InventoryItemTransfer::create([
                'item_id' => $request->item_id,
                'source_id' => $request->source_id,
                'destination_id' => $request->destination_id,
                'qty' => $request->qty,
                'sent_date' => $request->sent_date ?? now(),
                'expected_date' => $request->expected_date,
                'obs' => $request->obs,
                'batch_id' => $request->batch_id ?? null,
            ]);

            // Update source inventory (reserve stock)
            $sourceInventory->decrement('qty_available', $request->qty);

            // Create transaction for source (stock out)
            $outType = InventoryTransactionType::where('code', 'stock_out')->first();
            if ($outType) {
                InventoryTransaction::create([
                    'inventory_id' => $sourceInventory->id,
                    'user_id' => auth()->id(),
                    'warehouse_id' => $request->source_id,
                    'item_id' => $request->item_id,
                    'type_id' => $outType->id,
                    'qty' => $request->qty,
                    'reason' => 'Transfer to ' . InventoryItemWarehouse::find($request->destination_id)->name,
                    'notes' => $request->obs,
                    'batch_id' => $request->batch_id ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('vap-inventory.transfers.show', $transfer)
                ->with('success', 'Transferência criada com sucesso. O stock ficou reservado no armazém de origem.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Não foi possível criar a transferência.')
                ->withInput();
        }
    }

    public function show(InventoryItemTransfer $transfer)
    {
        $transfer->load([
            'item.category',
            'source.location',
            'destination.location',
            'item.unit',
        ]);

        // Get stock info for both warehouses
        $sourceStock = Inventory::where('item_id', $transfer->item_id)
            ->where('warehouse_id', $transfer->source_id)
            ->first();

        $destinationStock = Inventory::where('item_id', $transfer->item_id)
            ->where('warehouse_id', $transfer->destination_id)
            ->first();

        $daysInTransfer = max((int) $transfer->created_at?->startOfDay()->diffInDays(now()->startOfDay()), 0);
        $daysUntilExpected = $transfer->expected_date
            ? (int) now()->startOfDay()->diffInDays($transfer->expected_date->startOfDay(), false)
            : 0;
        $transferGap = max((int) $transfer->qty - (int) ($destinationStock?->qty_available ?? 0), 0);

        return Inertia::render('VAPInventory/Transfers/Show', [
            'transfer' => $transfer,
            'sourceStock' => $sourceStock,
            'destinationStock' => $destinationStock,
            'canReceive' => !$transfer->received_date && $transfer->sent_date,
            'canCancel' => !$transfer->received_date,
            'charts' => [
                'quantity_flow' => [
                    'labels' => ['Quantidade transferida', 'Stock origem', 'Stock destino'],
                    'series' => [
                        (int) $transfer->qty,
                        (int) ($sourceStock?->qty_available ?? 0),
                        (int) ($destinationStock?->qty_available ?? 0),
                    ],
                ],
                'timing_pressure' => [
                    'labels' => ['Dias em curso', 'Dias até expectativa', 'Dias em atraso'],
                    'series' => [
                        $daysInTransfer,
                        max($daysUntilExpected, 0),
                        $transfer->is_overdue ? $transfer->days_overdue : 0,
                    ],
                ],
                'execution_pulse' => [
                    'labels' => ['Gap destino', 'Pode receber', 'Pode cancelar'],
                    'series' => [
                        $transferGap,
                        !$transfer->received_date && $transfer->sent_date ? 1 : 0,
                        !$transfer->received_date ? 1 : 0,
                    ],
                ],
            ],
        ]);
    }

    public function receive(Request $request, InventoryItemTransfer $transfer)
    {
        if ($transfer->received_date) {
            return redirect()->back()
                ->with('error', 'A transferência já foi rececionada.');
        }

        $validator = Validator::make($request->all(), [
            'actual_qty' => 'required|integer|min:1|max:' . $transfer->qty,
            'received_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Update transfer
            $transfer->update([
                'received_date' => $request->received_date,
                'obs' => ($transfer->obs ? $transfer->obs . "\n" : '') . 
                         "Received: " . $request->notes . 
                         " (Qty: {$request->actual_qty})",
            ]);

            // Find or create destination inventory
            $destinationInventory = Inventory::firstOrCreate(
                [
                    'item_id' => $transfer->item_id,
                    'warehouse_id' => $transfer->destination_id,
                ],
                [
                    'qty_available' => 0,
                    'min_stock_level' => 0,
                    'reorder_point' => 0,
                    'category_id' => $transfer->item->category_id,
                    'status' => 'AVAILABLE',
                    'name' => 'AVAILABLE',
                ]
            );

            // Update destination inventory
            $destinationInventory->increment('qty_available', $request->actual_qty);

            // Create transaction for destination (stock in)
            $inType = InventoryTransactionType::where('code', 'stock_in')->first();
            if ($inType) {
                InventoryTransaction::create([
                    'inventory_id' => $destinationInventory->id,
                    'user_id' => auth()->id(),
                    'warehouse_id' => $transfer->destination_id,
                    'item_id' => $transfer->item_id,
                    'type_id' => $inType->id,
                    'qty' => $request->actual_qty,
                    'reason' => 'Transfer from ' . $transfer->source->name,
                    'notes' => $request->notes,
                    'batch_id' => $request->batch_id ?? null,
                ]);
            }

            // If actual quantity differs from expected, adjust source inventory
            if ($request->actual_qty != $transfer->qty) {
                $difference = $transfer->qty - $request->actual_qty;
                
                // Return difference to source
                $sourceInventory = Inventory::where('item_id', $transfer->item_id)
                    ->where('warehouse_id', $transfer->source_id)
                    ->first();

                if ($sourceInventory && $difference > 0) {
                    $sourceInventory->increment('qty_available', $difference);

                    // Create adjustment transaction
                    $adjType = InventoryTransactionType::where('code', 'stock_adjustment_add')->first();
                    if ($adjType) {
                        InventoryTransaction::create([
                            'inventory_id' => $sourceInventory->id,
                            'user_id' => auth()->id(),
                            'warehouse_id' => $transfer->source_id,
                            'item_id' => $transfer->item_id,
                            'type_id' => $adjType->id,
                            'qty' => $difference,
                            'reason' => 'Adjustment - Transfer quantity difference',
                            'notes' => 'Expected: ' . $transfer->qty . ', Received: ' . $request->actual_qty,
                            'batch_id' => $request->batch_id ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('vap-inventory.transfers.show', $transfer)
                ->with('success', 'Transferência rececionada com sucesso. O stock foi atualizado no armazém de destino.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Não foi possível registar a receção da transferência.');
        }
    }

    public function cancel(Request $request, InventoryItemTransfer $transfer)
    {
        if ($transfer->received_date) {
            return redirect()->back()
                ->with('error', 'Não é possível cancelar uma transferência já concluída.');
        }

        try {
            DB::beginTransaction();

            // Return stock to source
            $sourceInventory = Inventory::where('item_id', $transfer->item_id)
                ->where('warehouse_id', $transfer->source_id)
                ->first();

            if ($sourceInventory) {
                $sourceInventory->increment('qty_available', $transfer->qty);

                // Create adjustment transaction
                $adjType = InventoryTransactionType::where('code', 'stock_adjustment_add')->first();
                if ($adjType) {
                    InventoryTransaction::create([
                        'inventory_id' => $sourceInventory->id,
                        'user_id' => auth()->id(),
                        'warehouse_id' => $transfer->source_id,
                        'item_id' => $transfer->item_id,
                        'type_id' => $adjType->id,
                        'qty' => $transfer->qty,
                        'reason' => 'Transfer cancelled',
                        'notes' => $request->notes ?? 'Transfer #' . $transfer->id . ' cancelled',
                        'batch_id' => $request->batch_id ?? null,
                    ]);
                }
            }

            // Mark transfer as cancelled
            $transfer->update([
                'obs' => ($transfer->obs ? $transfer->obs . "\n" : '') . 
                         "CANCELLED: " . ($request->notes ?? 'No reason provided'),
                'deleted_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('vap-inventory.transfers.index')
                ->with('success', 'Transferência cancelada com sucesso. O stock foi devolvido ao armazém de origem.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Não foi possível cancelar a transferência.');
        }
    }

    public function getItemStock(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:i_items,id',
            'warehouse_id' => 'required|exists:i_warehouses,id',
        ]);

        $stock = Inventory::where('item_id', $request->item_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->first();

        return response()->json([
            'available' => $stock ? $stock->qty_available : 0,
            'item' => InventoryItem::find($request->item_id),
            'warehouse' => InventoryItemWarehouse::find($request->warehouse_id),
        ]);
    }

    public function bulkTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transfers' => 'required|array|min:1',
            'transfers.*.item_id' => 'required|exists:i_items,id',
            'transfers.*.source_id' => 'required|exists:i_warehouses,id',
            'transfers.*.destination_id' => 'required|exists:i_warehouses,id',
            'transfers.*.qty' => 'required|integer|min:1',
            'transfers.*.batch_id' => 'nullable|exists:i_inventory_batches,id',
            'sent_date' => 'nullable|date',
            'expected_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $createdTransfers = [];
            $errors = [];

            foreach ($request->transfers as $index => $transferData) {
                // Check stock availability
                $sourceInventory = Inventory::where('item_id', $transferData['item_id'])
                    ->where('warehouse_id', $transferData['source_id'])
                    ->first();

                if (!$sourceInventory || $sourceInventory->qty_available < $transferData['qty']) {
                    $errors[] = [
                        'index' => $index,
                        'item' => InventoryItem::find($transferData['item_id'])->name,
                        'error' => 'Insufficient stock',
                    ];
                    continue;
                }

                // Create transfer
                $transfer = InventoryItemTransfer::create([
                    'item_id' => $transferData['item_id'],
                    'source_id' => $transferData['source_id'],
                    'destination_id' => $transferData['destination_id'],
                    'qty' => $transferData['qty'],
                    'sent_date' => $request->sent_date ?? now(),
                    'expected_date' => $request->expected_date,
                    'obs' => 'Bulk transfer',
                ]);

                // Update source inventory
                $sourceInventory->decrement('qty_available', $transferData['qty']);

                // Create transaction
                $outType = InventoryTransactionType::where('code', 'stock_out')->first();
                if ($outType) {
                    InventoryTransaction::create([
                        'inventory_id' => $sourceInventory->id,
                        'user_id' => auth()->id(),
                        'warehouse_id' => $transferData['source_id'],
                        'item_id' => $transferData['item_id'],
                        'type_id' => $outType->id,
                        'qty' => $transferData['qty'],
                        'reason' => 'Bulk transfer',
                        'notes' => 'Transfer to ' . InventoryItemWarehouse::find($transferData['destination_id'])->name,
                        'batch_id' => $request->batch_id ?? null,
                    ]);
                }

                $createdTransfers[] = $transfer;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdTransfers) . ' transferências criadas com sucesso.',
                'transfers' => $createdTransfers,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível criar a transferência em massa.',
            ], 500);
        }
    }

    public function getAllStockInfo(Request $request)
    {
        // Get all active items
        $items = InventoryItem::active()->get(['id', 'name', 'code']);
        $warehouses = InventoryItemWarehouse::active()->get(['id', 'name']);
        
        $stockInfo = [];
        
        // Query all stock at once
        $inventory = Inventory::whereIn('item_id', $items->pluck('id'))
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->get(['item_id', 'warehouse_id', 'qty_available']);
        
        // Format as item_warehouse => stock
        foreach ($inventory as $stock) {
            $key = "{$stock->item_id}_{$stock->warehouse_id}";
            $stockInfo[$key] = $stock->qty_available;
        }
        
        return response()->json([
            'stock_info' => $stockInfo,
            'items' => $items,
            'warehouses' => $warehouses,
        ]);
    }

    public function getItemStockAllWarehouses(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:i_items,id',
        ]);

        $warehouses = InventoryItemWarehouse::active()->get();
        $stocks = [];
        
        foreach ($warehouses as $warehouse) {
            $stock = Inventory::where('item_id', $request->item_id)
                ->where('warehouse_id', $warehouse->id)
                ->first();
            
            $stocks[$warehouse->id] = $stock ? $stock->qty_available : 0;
        }

        return response()->json([
            'item_id' => $request->item_id,
            'stocks' => $stocks,
            'warehouses' => $warehouses,
        ]);
    }
}
