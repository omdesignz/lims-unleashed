<?php

namespace App\Http\Controllers;

use App\Enums\Orders\InventoryOrderItemStatus;
use App\Enums\Orders\InventoryOrderTrackingStatus;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\VAPNonConformity;
use App\Support\PdfResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PDF;

class VAPInventoryOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = InventoryOrder::with(['supplier'])
            ->withCount(['items as items_count'])
            ->withSum('items as total_quantity', 'qty')
            ->withSum('items as total_amount', 'total_price')
            ->select('i_orders.*')
            ->addSelect([
                'earliest_expected_date' => InventoryOrderDetail::select('expected_date')
                    ->whereColumn('order_id', 'i_orders.id')
                    ->orderBy('expected_date')
                    ->limit(1),
            ]);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('seq', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('obs', 'like', "%{$search}%")
                    ->orWhereHas('items.item', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Apply sorting
        $sortBy = $request->filled('sort_by') ? $request->sort_by : 'created_at';
        $sortDirection = $request->filled('sort_direction') ? $request->sort_direction : 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Get stats for the dashboard
        $stats = [
            'total_orders' => InventoryOrder::count(),
            'pending_orders' => InventoryOrder::where('status', 'pending')->count(),
            'orders_today' => InventoryOrder::whereDate('date', today())->count(),
            'total_value' => InventoryOrder::where('status', '!=', 'cancelled')
                ->sum('total_amount'),
            'open_items' => InventoryOrderDetail::whereIn('status', ['pending', 'ordered', 'partially_received'])
                ->count(),
        ];

        $orders = $query->paginate(15)->withQueryString();
        $receptionNonConformityLookup = $this->receptionNonConformityLookup($orders->getCollection()->pluck('id'));

        $orders->setCollection(
            $orders->getCollection()->map(function (InventoryOrder $order) use ($receptionNonConformityLookup) {
                $order->setRelation('supplier', $this->decorateSupplierWithAssessment($order->supplier));
                $order->setAttribute(
                    'reception_non_conformity_summary',
                    $receptionNonConformityLookup[$order->id] ?? [
                        'count' => 0,
                        'open_count' => 0,
                        'latest_severity' => null,
                        'latest_status' => null,
                    ]
                );

                return $order;
            })
        );

        $suppliers = InventoryItemSupplier::select('id', 'name', 'address')->get();

        return Inertia::render('VAPInventory/Orders/Index', [
            'orders' => $orders,
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'status', 'supplier_id', 'date_from', 'date_to', 'sort_by', 'sort_direction']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $items = InventoryItem::active()->with(['category', 'unit', 'inventory'])
            ->select('id', 'name', 'code', 'category_id', 'unit_id', 'last_purchase_price', 'standard_cost')
            // ->where('is_active', true)
            ->get();

        $suppliers = $this->supplierOptions();

        $warehouses = InventoryItemWarehouse::select('id', 'name')
            ->active()
            ->get();

        return Inertia::render('VAPInventory/Orders/Create', [
            'items' => $items,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd(request()->all());

        if ($request->has('status')) {
            $request->merge([
                'status' => strtoupper($request->status),
            ]);
        }

        if ($request->has('order_items')) {
            $request->merge([
                'order_items' => collect($request->order_items)->map(function ($item) {
                    return [
                        'item_id' => $item['item_id'],
                        'qty' => $item['qty'],
                        'expected_date' => $item['expected_date'],
                        'warehouse_id' => $item['warehouse_id'],
                        'status' => strtoupper($item['status']),
                        'unit_price' => $item['unit_price'],
                    ];
                })->toArray(),
            ]);
        }

        $request->validate([
            'supplier_id' => 'required|exists:i_suppliers,id',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'status' => ['required', Rule::enum(InventoryOrderTrackingStatus::class)],
            'obs' => 'nullable|string|max:500',
            'currency' => 'nullable|string|size:3',
            'order_items' => 'required|array|min:1',
            'order_items.*.item_id' => 'required|exists:i_items,id',
            'order_items.*.qty' => 'required|integer|min:1',
            'order_items.*.warehouse_id' => 'required|exists:i_warehouses,id',
            'order_items.*.expected_date' => 'nullable|date|after_or_equal:date',
            'order_items.*.unit_price' => 'required|numeric|min:0',
            'order_items.*.status' => ['nullable', Rule::enum(InventoryOrderItemStatus::class)],
        ]);

        DB::beginTransaction();

        try {

            // Get supplier currency
            $supplier = InventoryItemSupplier::find($request->supplier_id);
            $supplierAssessmentBlocker = $this->supplierAssessmentBlocker($supplier);

            if ($supplierAssessmentBlocker !== null) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $supplierAssessmentBlocker);
            }

            $currency = $request->currency ?? $supplier->currency ?? 'USD';

            // Create the order
            $order = InventoryOrder::create([
                'date' => $request->date,
                'user_id' => auth()->id(),
                'supplier_id' => $request->supplier_id,
                'order_year' => now()->format('Y'),
                'obs' => $request->obs,
                'status' => InventoryOrderTrackingStatus::from($request->status),
                'currency' => $currency,
            ]);

            $totalAmount = 0;

            // Create order items
            foreach ($request->order_items as $item) {
                $unitPrice = $item['unit_price'];
                $totalPrice = $unitPrice * $item['qty'];

                InventoryOrderDetail::create([
                    'order_id' => $order->id,
                    'item_id' => $item['item_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $unitPrice,
                    'warehouse_id' => $item['warehouse_id'],
                    'expected_date' => $item['expected_date'] ?? null,
                    'status' => InventoryOrderItemStatus::from($item['status']) ?? InventoryOrderItemStatus::PENDING,
                    'currency' => $currency,
                ]);

                $totalAmount += $totalPrice;

                // Update item's last purchase price
                $this->updateItemPurchasePrice($item['item_id'], $unitPrice);
            }

            // Update order total amount
            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            $response = redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('success', 'Pedido de compra criado com sucesso.');

            if ($warning = $this->supplierAssessmentWarning($supplier)) {
                $response->with('warning', $warning);
            }

            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Não foi possível criar o pedido de compra.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryOrder $order)
    {
        $order->load([
            'supplier',
            'user',
            'items' => function ($query) {
                $query->with(['item', 'warehouse']);
            },
        ]);

        // Calculate received quantity for each item from both field and transactions
        $order->items->each(function ($item) {
            // Get total received quantity from inventory transactions
            $receivedQtyFromTransactions = InventoryTransaction::where('item_id', $item->item_id)
                ->whereHas('type', function ($query) {
                    $query->where('code', 'RECEIPT');
                })
                ->whereHas('inventory', function ($query) use ($item) {
                    $query->where('warehouse_id', $item->warehouse_id);
                })
                ->where('notes', 'LIKE', '%Order #'.$item->order_id.'%')
                ->sum('qty');

            // Use the maximum between stored value and calculated value
            $item->received_qty = max((int) $item->received_qty, (int) $receivedQtyFromTransactions);

            // Update item status based on received quantity
            if ($item->received_qty >= $item->qty) {
                $item->status = InventoryOrderItemStatus::RECEIVED;
            } elseif ($item->received_qty > 0) {
                $item->status = InventoryOrderItemStatus::PARTIALLY_RECEIVED;
            }
        });

        // Calculate order summary
        $order->item_count = $order->items->count();
        $order->total_quantity = $order->items->sum('qty');
        $order->received_quantity = $order->items->sum('received_qty');
        $order->total_amount = $order->items->sum('total_price');
        $order->received_amount = $order->items->sum(function ($item) {
            if ($item->unit_price && $item->received_qty) {
                return $item->unit_price * $item->received_qty;
            }

            return 0;
        });

        $receptionNonConformitySummary = $this->receptionNonConformityLookup(collect([$order->id]))[$order->id] ?? [
            'count' => 0,
            'open_count' => 0,
            'latest_severity' => null,
            'latest_status' => null,
        ];

        $order->setRelation('supplier', $this->decorateSupplierWithAssessment($order->supplier));
        $order->setAttribute('reception_non_conformity_summary', $receptionNonConformitySummary);

        $pendingQuantity = max($order->total_quantity - $order->received_quantity, 0);
        $supplierScore = (int) data_get($order->supplier, 'latest_assessment.total_score', 0);
        $daysSinceCreation = max((int) $order->created_at?->startOfDay()->diffInDays(now()->startOfDay()), 0);
        $pendingItemCount = $order->items->filter(function ($item) {
            return (int) ($item->received_qty ?? 0) < (int) $item->qty;
        })->count();

        return Inertia::render('VAPInventory/Orders/Show', [
            'order' => $order,
            'charts' => [
                'reception_progress' => [
                    'labels' => ['Quantidade pedida', 'Quantidade recebida', 'Quantidade pendente'],
                    'series' => [$order->total_quantity, $order->received_quantity, $pendingQuantity],
                ],
                'item_status_mix' => [
                    'labels' => ['Itens pendentes', 'Itens parciais', 'Itens completos'],
                    'series' => [
                        $order->items->filter(fn ($item) => (int) ($item->received_qty ?? 0) === 0)->count(),
                        $order->items->filter(fn ($item) => (int) ($item->received_qty ?? 0) > 0 && (int) ($item->received_qty ?? 0) < (int) $item->qty)->count(),
                        $order->items->filter(fn ($item) => (int) ($item->received_qty ?? 0) >= (int) $item->qty)->count(),
                    ],
                ],
                'governance_summary' => [
                    'labels' => ['Score fornecedor', 'NC abertas', 'Dias em curso', 'Linhas pendentes'],
                    'series' => [
                        $supplierScore,
                        (int) data_get($receptionNonConformitySummary, 'open_count', 0),
                        $daysSinceCreation,
                        $pendingItemCount,
                    ],
                ],
            ],
        ]);
    }
    // public function show(InventoryOrder $order)
    // {
    //     $order->load([
    //         'supplier',
    //         'user',
    //         'items' => function ($query) {
    //             $query->with(['item', 'warehouse']);
    //         }
    //     ]);

    //     // Calculate received quantity for each item
    //     $order->items->each(function ($item) {
    //         // Get total received quantity from inventory transactions
    //         $receivedQty = InventoryTransaction::where('item_id', $item->item_id)
    //             ->whereHas('type', function ($query) {
    //                 $query->where('code', 'RECEIPT');
    //             })
    //             ->whereHas('inventory', function ($query) use ($item) {
    //                 $query->where('warehouse_id', $item->warehouse_id);
    //             })
    //             ->where('notes', 'LIKE', '%Order #' . $item->order_id . '%')
    //             ->sum('qty');

    //         $item->received_qty = (int) $receivedQty;

    //         // Update item status based on received quantity
    //         if ($item->received_qty >= $item->qty) {
    //             $item->status = InventoryOrderItemStatus::RECEIVED;
    //         } elseif ($item->received_qty > 0) {
    //             $item->status = InventoryOrderItemStatus::PARTIALLY_RECEIVED;
    //         }
    //     });

    //     // Calculate order summary
    //     $order->item_count = $order->items->count();
    //     $order->total_quantity = $order->items->sum('qty');
    //     $order->received_quantity = $order->items->sum('received_qty');
    //     $order->total_amount = $order->items->sum('total_price');
    //     $order->received_amount = $order->items->sum(function ($item) {
    //         if ($item->unit_price && $item->received_qty) {
    //             return $item->unit_price * $item->received_qty;
    //         }
    //         return 0;
    //     });

    //     return Inertia::render('VAPInventory/Orders/Show', [
    //         'order' => $order,
    //     ]);
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryOrder $order)
    {
        // Only allow editing of pending or approved orders
        if (! in_array($order->status, [InventoryOrderTrackingStatus::PENDING, InventoryOrderTrackingStatus::APPROVED])) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Only pending or approved orders can be edited.');
        }

        $order->load(['items' => function ($query) {
            $query->with('item');
        }]);

        // Get received quantity for each item
        $order->items->each(function ($item) {
            $receivedQty = InventoryTransaction::where('item_id', $item->item_id)
                ->whereHas('type', function ($query) {
                    $query->where('code', 'RECEIPT');
                })
                ->whereHas('inventory', function ($query) use ($item) {
                    $query->where('warehouse_id', $item->warehouse_id);
                })
                ->where('notes', 'LIKE', '%Order #'.$item->order_id.'%')
                ->sum('qty');

            $item->received_qty = (int) $receivedQty;
        });

        $items = InventoryItem::active()->with(['category', 'unit'])
            ->select('id', 'name', 'code', 'category_id', 'unit_id', 'last_purchase_price', 'standard_cost')
            ->get();

        $suppliers = $this->supplierOptions();

        $warehouses = InventoryItemWarehouse::active()->select('id', 'name')
            ->get();

        return Inertia::render('VAPInventory/Orders/Edit', [
            'order' => $order,
            'items' => $items,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InventoryOrder $order)
    {
        // Only allow updating of pending or approved orders
        if (! in_array($order->status, [InventoryOrderTrackingStatus::PENDING, InventoryOrderTrackingStatus::APPROVED])) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Only pending or approved orders can be updated.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:i_suppliers,id',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'status' => ['required', Rule::enum(InventoryOrderTrackingStatus::class)],
            'obs' => 'nullable|string|max:500',
            'currency' => 'nullable|string|size:3',
            'order_items' => 'required|array|min:1',
            'order_items.*.id' => 'nullable|exists:i_order_details,id',
            'order_items.*.item_id' => 'required|exists:i_items,id',
            'order_items.*.qty' => 'required|integer|min:1',
            'order_items.*.warehouse_id' => 'required|exists:i_warehouses,id',
            'order_items.*.expected_date' => 'nullable|date|after_or_equal:date',
            'order_items.*.unit_price' => 'required|numeric|min:0',
            'order_items.*.received_qty' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Get supplier currency if changed
            $currency = $request->currency ?? $order->currency;
            $supplier = InventoryItemSupplier::find($request->supplier_id);
            $supplierAssessmentBlocker = $this->supplierAssessmentBlocker($supplier);

            if ($supplierAssessmentBlocker !== null) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $supplierAssessmentBlocker);
            }

            // Update the order
            $order->update([
                'date' => $request->date,
                'supplier_id' => $request->supplier_id,
                'reference' => $request->reference,
                'status' => InventoryOrderTrackingStatus::from($request->status),
                'obs' => $request->obs,
                'currency' => $currency,
            ]);

            // Get existing item IDs
            $existingItemIds = $order->items->pluck('id')->toArray();
            $updatedItemIds = [];
            $totalAmount = 0;

            // Update or create order items
            foreach ($request->order_items as $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = InventoryOrderDetail::find($itemData['id']);

                    // Get received quantity from transactions
                    $receivedQty = InventoryTransaction::where('item_id', $item->item_id)
                        ->whereHas('type', function ($query) {
                            $query->where('code', 'RECEIPT');
                        })
                        ->whereHas('inventory', function ($query) use ($item) {
                            $query->where('warehouse_id', $item->warehouse_id);
                        })
                        ->where('notes', 'LIKE', '%Order #'.$item->order_id.'%')
                        ->sum('qty');

                    $receivedQty = (int) $receivedQty;

                    // Check if item has been received
                    if ($receivedQty > 0) {
                        // Can't change item if it has been received
                        if ($item->item_id != $itemData['item_id'] ||
                            $item->warehouse_id != $itemData['warehouse_id']) {
                            throw new \Exception('Cannot change item or warehouse for received items.');
                        }
                    }

                    // Check quantity is not less than received quantity
                    if ($itemData['qty'] < $receivedQty) {
                        throw new \Exception('Quantity cannot be less than received quantity.');
                    }

                    $unitPrice = $itemData['unit_price'];
                    $totalPrice = $unitPrice * $itemData['qty'];

                    $item->update([
                        'item_id' => $itemData['item_id'],
                        'qty' => $itemData['qty'],
                        'unit_price' => $unitPrice,
                        'warehouse_id' => $itemData['warehouse_id'],
                        'expected_date' => $itemData['expected_date'] ?? null,
                        'currency' => $currency,
                    ]);

                    $totalAmount += $totalPrice;
                    $updatedItemIds[] = $itemData['id'];

                    // Update item's last purchase price if changed
                    if ($item->wasChanged('unit_price')) {
                        $this->updateItemPurchasePrice($itemData['item_id'], $unitPrice);
                    }
                } else {
                    // Create new item
                    $unitPrice = $itemData['unit_price'];
                    $totalPrice = $unitPrice * $itemData['qty'];

                    InventoryOrderDetail::create([
                        'order_id' => $order->id,
                        'item_id' => $itemData['item_id'],
                        'qty' => $itemData['qty'],
                        'unit_price' => $unitPrice,
                        'warehouse_id' => $itemData['warehouse_id'],
                        'expected_date' => $itemData['expected_date'] ?? null,
                        'status' => 'pending',
                        'currency' => $currency,
                    ]);

                    $totalAmount += $totalPrice;

                    // Update item's last purchase price
                    $this->updateItemPurchasePrice($itemData['item_id'], $unitPrice);
                }
            }

            // Delete items that were removed
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (! empty($itemsToDelete)) {
                // Check if any of these items have been received
                foreach ($itemsToDelete as $itemId) {
                    $item = InventoryOrderDetail::find($itemId);
                    $receivedQty = InventoryTransaction::where('item_id', $item->item_id)
                        ->whereHas('type', function ($query) {
                            $query->where('code', 'RECEIPT');
                        })
                        ->whereHas('inventory', function ($query) use ($item) {
                            $query->where('warehouse_id', $item->warehouse_id);
                        })
                        ->where('notes', 'LIKE', '%Order #'.$item->order_id.'%')
                        ->sum('qty');

                    if ($receivedQty > 0) {
                        throw new \Exception('Cannot delete items that have been received.');
                    }
                }

                InventoryOrderDetail::whereIn('id', $itemsToDelete)->delete();
            }

            // Update order total amount
            $order->update(['total_amount' => $totalAmount]);

            // Update overall order status if items have been received
            $this->updateOrderStatus($order);

            DB::commit();

            $response = redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('success', 'Pedido de compra atualizado com sucesso.');

            if ($warning = $this->supplierAssessmentWarning($supplier)) {
                $response->with('warning', $warning);
            }

            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Não foi possível atualizar o pedido de compra.');
        }
    }

    private function supplierOptions()
    {
        return InventoryItemSupplier::query()
            ->active()
            ->with(['assessments' => function ($query) {
                $query->latest('assessment_date');
            }])
            ->get(['id', 'name', 'address', 'currency'])
            ->map(function (InventoryItemSupplier $supplier) {
                $latestAssessment = $supplier->assessments->first();

                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'address' => $supplier->address,
                    'currency' => $supplier->currency,
                    'latest_assessment' => $latestAssessment ? [
                        'id' => $latestAssessment->id,
                        'status' => $latestAssessment->status,
                        'risk_level' => $latestAssessment->risk_level,
                        'total_score' => $latestAssessment->total_score,
                        'approved_supplier' => $latestAssessment->approved_supplier,
                        'next_review_at' => $latestAssessment->next_review_at?->toDateString(),
                    ] : null,
                ];
            })
            ->values();
    }

    private function decorateSupplierWithAssessment(?InventoryItemSupplier $supplier): ?InventoryItemSupplier
    {
        if ($supplier === null) {
            return null;
        }

        $latestAssessment = $supplier->relationLoaded('assessments')
            ? $supplier->assessments->sortByDesc('assessment_date')->first()
            : $supplier->assessments()->latest('assessment_date')->first();

        $supplier->setAttribute('latest_assessment', $latestAssessment ? [
            'id' => $latestAssessment->id,
            'status' => $latestAssessment->status,
            'risk_level' => $latestAssessment->risk_level,
            'total_score' => $latestAssessment->total_score,
            'approved_supplier' => (bool) $latestAssessment->approved_supplier,
            'next_review_at' => $latestAssessment->next_review_at?->toDateString(),
        ] : null);

        return $supplier;
    }

    private function supplierAssessmentBlocker(?InventoryItemSupplier $supplier): ?string
    {
        $assessment = $supplier?->assessments()->latest('assessment_date')->first();

        if (! $assessment) {
            return null;
        }

        if (in_array($assessment->status, ['rejected', 'suspended'], true)) {
            return 'O fornecedor selecionado está bloqueado por avaliação de desempenho. Atualize a avaliação antes de emitir a encomenda.';
        }

        if ($assessment->risk_level === 'critical' && ! $assessment->approved_supplier) {
            return 'O fornecedor selecionado está com risco crítico e sem aprovação ativa. Reavalie o fornecedor antes de prosseguir.';
        }

        return null;
    }

    private function supplierAssessmentWarning(?InventoryItemSupplier $supplier): ?string
    {
        $assessment = $supplier?->assessments()->latest('assessment_date')->first();

        if (! $assessment) {
            return 'A encomenda foi registada sem avaliação formal do fornecedor. Recomenda-se abrir a avaliação de fornecedores.';
        }

        if ($assessment->next_review_at && $assessment->next_review_at->isPast()) {
            return 'A avaliação do fornecedor está vencida e deve ser revista.';
        }

        if ($assessment->status === 'conditional' || $assessment->risk_level === 'high') {
            return 'A encomenda foi registada com fornecedor sob monitorização reforçada. Acompanhe o plano de seguimento.';
        }

        return null;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryOrder $order)
    {
        // Only allow deletion of pending or cancelled orders
        if (! in_array($order->status, [InventoryOrderTrackingStatus::PENDING, InventoryOrderTrackingStatus::CANCELLED])) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Apenas pedidos pendentes ou cancelados podem ser eliminados.');
        }

        // Check if any items have been received
        $hasReceivedItems = false;
        foreach ($order->items as $item) {
            $receivedQty = InventoryTransaction::where('item_id', $item->item_id)
                ->whereHas('type', function ($query) {
                    $query->where('code', 'RECEIPT');
                })
                ->whereHas('inventory', function ($query) use ($item) {
                    $query->where('warehouse_id', $item->warehouse_id);
                })
                ->where('notes', 'LIKE', '%Order #'.$item->order_id.'%')
                ->sum('qty');

            if ($receivedQty > 0) {
                $hasReceivedItems = true;
                break;
            }
        }

        if ($hasReceivedItems) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Não é possível eliminar um pedido com itens já rececionados.');
        }

        DB::beginTransaction();

        try {
            // Delete order items first
            $order->items()->delete();

            // Delete the order
            $order->delete();

            DB::commit();

            return redirect()->route('vap-inventory.orders.index')
                ->with('success', 'Pedido de compra eliminado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Não foi possível eliminar o pedido de compra.');
        }
    }

    /**
     * Receive an order or order items.
     */
    /**
     * Receive an order or order items.
     */
    public function receive(Request $request, InventoryOrder $order)
    {
        // Only allow receiving of ordered or partially_received orders
        if (! in_array($order->status, [InventoryOrderTrackingStatus::ORDERED, InventoryOrderTrackingStatus::PARTIALLY_RECEIVED])) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Only ordered or partially received orders can be received.');
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:i_order_details,id',
            'items.*.received_qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'receive_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'register_non_conformity' => 'nullable|boolean',
            'non_conformity_title' => 'nullable|string|max:255|required_if:register_non_conformity,true',
            'non_conformity_description' => 'nullable|string|required_if:register_non_conformity,true',
            'non_conformity_severity' => 'nullable|in:low,medium,high,critical',
        ]);

        DB::beginTransaction();

        try {
            $receivedItems = [];

            foreach ($request->items as $itemData) {
                $orderItem = InventoryOrderDetail::find($itemData['id']);

                // Check if item belongs to this order
                if ($orderItem->order_id !== $order->id) {
                    throw new \Exception('Invalid order item.');
                }

                // Use provided unit price or fallback to order price
                $unitPrice = $itemData['unit_price'] ?? $orderItem->unit_price;

                // Check if quantity is valid
                $alreadyReceived = $this->getReceivedQuantity($orderItem);
                $newReceivedQty = $itemData['received_qty'];
                $totalReceived = $alreadyReceived + $newReceivedQty;

                if ($totalReceived > $orderItem->qty) {
                    throw new \Exception("Cannot receive more than ordered quantity for item: {$orderItem->item->name}");
                }

                // Update inventory stock with cost
                $this->updateInventoryStock($orderItem, $newReceivedQty, $unitPrice, $request->receive_date, $request->reason, $request->notes);

                // Calculate new total received quantity
                $newTotalReceived = $alreadyReceived + $newReceivedQty;

                // Update item's received_qty field
                $orderItem->received_qty = $newTotalReceived;

                // Update item status based on received quantity
                if ($newTotalReceived >= $orderItem->qty) {
                    $orderItem->status = InventoryOrderItemStatus::RECEIVED;
                    $orderItem->actual_date = $request->receive_date;
                } elseif ($newTotalReceived > 0) {
                    $orderItem->status = InventoryOrderItemStatus::PARTIALLY_RECEIVED;
                    $orderItem->actual_date = $request->receive_date;
                }

                $orderItem->save();

                // Update item's last purchase price if different
                if ($unitPrice != $orderItem->unit_price) {
                    $this->updateItemPurchasePrice($orderItem->item_id, $unitPrice);
                }

                $receivedItems[] = [
                    'order_item' => $orderItem->fresh(['item', 'warehouse']),
                    'received_qty' => $newReceivedQty,
                    'unit_price' => $unitPrice,
                    'already_received' => $alreadyReceived,
                ];
            }

            // Update overall order status
            $this->updateOrderStatus($order);

            $createdNonConformity = null;

            if ($request->boolean('register_non_conformity')) {
                $createdNonConformity = $this->createReceivingNonConformity(
                    $order->fresh(['supplier']),
                    $receivedItems,
                    $request
                );
            }

            DB::commit();

            $response = redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('success', 'Itens do pedido rececionados com sucesso.');

            if ($createdNonConformity !== null) {
                $response->with('warning', 'Foi registada uma não conformidade de recepção: '.$createdNonConformity->nc_number.'.');
            }

            return $response;
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Não foi possível registar a receção dos itens do pedido.');
        }
    }
    // public function receive(Request $request, InventoryOrder $order)
    // {
    //     // Only allow receiving of ordered or partially_received orders
    //     if (!in_array($order->status, [InventoryOrderTrackingStatus::ORDERED, InventoryOrderTrackingStatus::PARTIALLY_RECEIVED])) {
    //         return redirect()->route('vap-inventory.orders.show', $order->id)
    //             ->with('error', 'Only ordered or partially received orders can be received.');
    //     }

    //     $request->validate([
    //         'items' => 'required|array',
    //         'items.*.id' => 'required|exists:i_order_details,id',
    //         'items.*.received_qty' => 'required|integer|min:1',
    //         'items.*.unit_price' => 'nullable|numeric|min:0',
    //         'receive_date' => 'required|date',
    //         'reason' => 'nullable|string|max:255',
    //         'notes' => 'nullable|string|max:500',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         foreach ($request->items as $itemData) {
    //             $orderItem = InventoryOrderDetail::find($itemData['id']);

    //             // Check if item belongs to this order
    //             if ($orderItem->order_id !== $order->id) {
    //                 throw new \Exception('Invalid order item.');
    //             }

    //             // Use provided unit price or fallback to order price
    //             $unitPrice = $itemData['unit_price'] ?? $orderItem->unit_price;

    //             // Check if quantity is valid
    //             $alreadyReceived = $this->getReceivedQuantity($orderItem);
    //             $newReceivedQty = $itemData['received_qty'];
    //             $totalReceived = $alreadyReceived + $newReceivedQty;

    //             if ($totalReceived > $orderItem->qty) {
    //                 throw new \Exception("Cannot receive more than ordered quantity for item: {$orderItem->item->name}");
    //             }

    //             // Update inventory stock with cost
    //             $this->updateInventoryStock($orderItem, $newReceivedQty, $unitPrice, $request->receive_date, $request->reason, $request->notes);

    //             // Update item status based on received quantity
    //             if ($totalReceived >= $orderItem->qty) {
    //                 $orderItem->status = InventoryOrderItemStatus::RECEIVED;
    //                 $orderItem->actual_date = $request->receive_date;
    //             } elseif ($totalReceived > 0) {
    //                 $orderItem->status = InventoryOrderItemStatus::PARTIALLY_RECEIVED;
    //                 $orderItem->actual_date = $request->receive_date;
    //             }
    //             $orderItem->save();

    //             // Update item's last purchase price if different
    //             if ($unitPrice != $orderItem->unit_price) {
    //                 $this->updateItemPurchasePrice($orderItem->item_id, $unitPrice);
    //             }
    //         }

    //         // Update overall order status
    //         $this->updateOrderStatus($order);

    //         DB::commit();

    //         return redirect()->route('vap-inventory.orders.show', $order->id)
    //             ->with('success', 'Order items received successfully!');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Failed to receive order items: ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Failed to receive order items: ' . $e->getMessage());
    //     }
    // }

    /**
     * Cancel an order.
     */
    public function cancel(InventoryOrder $order)
    {
        // Only allow cancellation of pending, approved, or ordered orders
        if (! in_array($order->status, ['pending', 'approved', 'ordered'])) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Only pending, approved, or ordered orders can be cancelled.');
        }

        // Check if any items have been received
        $hasReceivedItems = false;
        foreach ($order->items as $item) {
            $receivedQty = $this->getReceivedQuantity($item);
            if ($receivedQty > 0) {
                $hasReceivedItems = true;
                break;
            }
        }

        if ($hasReceivedItems) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Cannot cancel order with received items.');
        }

        $order->update([
            'status' => InventoryOrderTrackingStatus::CANCELLED,
        ]);

        // Also cancel all order items
        $order->items()->update(['status' => InventoryOrderItemStatus::CANCELLED]);

        return redirect()->route('vap-inventory.orders.show', $order->id)
            ->with('success', 'Order cancelled successfully!');
    }

    /**
     * Get received quantity for an order item from transactions.
     */
    private function getReceivedQuantity(InventoryOrderDetail $orderItem): int
    {
        // First check the stored received_qty field
        $storedReceivedQty = (int) $orderItem->received_qty;

        // Also check from transactions for legacy data
        $transactionReceivedQty = InventoryTransaction::where('item_id', $orderItem->item_id)
            ->whereHas('type', function ($query) {
                $query->where('code', 'RECEIPT');
            })
            ->whereHas('inventory', function ($query) use ($orderItem) {
                $query->where('warehouse_id', $orderItem->warehouse_id);
            })
            ->where('notes', 'LIKE', '%Order #'.$orderItem->order_id.'%')
            ->sum('qty');

        // Return the maximum between stored value and transaction value
        return max($storedReceivedQty, (int) $transactionReceivedQty);
    }
    // private function getReceivedQuantity(InventoryOrderDetail $orderItem): int
    // {
    //     $receivedQty = InventoryTransaction::where('item_id', $orderItem->item_id)
    //         ->whereHas('type', function ($query) {
    //             $query->where('code', 'RECEIPT');
    //         })
    //         ->whereHas('inventory', function ($query) use ($orderItem) {
    //             $query->where('warehouse_id', $orderItem->warehouse_id);
    //         })
    //         ->where('notes', 'LIKE', '%Order #' . $orderItem->order_id . '%')
    //         ->sum('qty');

    //     return (int) $receivedQty;
    // }

    /**
     * Update inventory stock for received items.
     */
    private function updateInventoryStock(InventoryOrderDetail $orderItem, $receivedQty, $unitPrice, $receiveDate, $reason = null, $notes = null)
    {
        // Get or create inventory record
        $inventory = Inventory::where('item_id', $orderItem->item_id)
            ->where('warehouse_id', $orderItem->warehouse_id)
            ->first();

        if (! $inventory) {
            $inventory = Inventory::create([
                'item_id' => $orderItem->item_id,
                'warehouse_id' => $orderItem->warehouse_id,
                'qty_available' => 0,
                'min_stock_level' => 0,
                'reorder_point' => 0,
                'status' => 'AVAILABLE',
            ]);
        }

        // Get receipt transaction type
        $receiptType = InventoryTransactionType::where('code', 'RECEIPT')->first();

        if (! $receiptType) {
            $receiptType = InventoryTransactionType::create([
                'name' => 'Receipt',
                'code' => 'RECEIPT',
                'description' => 'Stock receipt from purchase orders',
            ]);
        }

        // Calculate total cost
        $totalCost = $unitPrice * $receivedQty;

        // Create transaction record with cost
        InventoryTransaction::create([
            'inventory_id' => $inventory->id,
            'user_id' => auth()->id(),
            'warehouse_id' => $orderItem->warehouse_id,
            'item_id' => $orderItem->item_id,
            'type_id' => $receiptType->id,
            'qty' => $receivedQty,
            'reason' => $reason ?: 'Order Receipt',
            'notes' => $notes ?: "Received {$receivedQty} units from Order #{$orderItem->order->reference} at {$unitPrice} {$orderItem->currency} each (Total: {$totalCost})",
            'created_at' => $receiveDate,
            'updated_at' => $receiveDate,
        ]);

        // Update inventory quantity
        $inventory->increment('qty_available', $receivedQty);
    }

    /**
     * @param  array<int, array{order_item: InventoryOrderDetail, received_qty: int|float|string, unit_price: int|float|string|null, already_received: int}>  $receivedItems
     */
    private function createReceivingNonConformity(InventoryOrder $order, array $receivedItems, Request $request): VAPNonConformity
    {
        $user = $request->user();
        $departmentId = collect($receivedItems)
            ->map(fn (array $entry) => $entry['order_item']->item?->department_id)
            ->filter()
            ->first();

        if ($departmentId === null && method_exists($user, 'departments')) {
            $departmentId = $user->departments()->value('departments.id');
        }

        $lines = collect($receivedItems)->map(function (array $entry): string {
            $item = $entry['order_item']->item;
            $warehouse = $entry['order_item']->warehouse;

            return sprintf(
                '- %s | recepcionado: %s | total pedido: %s | já recebido antes: %s | armazém: %s',
                $item?->name ?? 'Item desconhecido',
                $entry['received_qty'],
                $entry['order_item']->qty,
                $entry['already_received'],
                $warehouse?->name ?? 'N/D'
            );
        })->implode("\n");

        $description = trim((string) $request->string('non_conformity_description'));
        $notes = trim((string) $request->string('notes'));
        $reason = trim((string) $request->string('reason'));
        $severity = $request->input('non_conformity_severity', 'medium');

        return VAPNonConformity::query()->create([
            'department_id' => $departmentId,
            'nc_number' => (new VAPNonConformity)->generateNcNumber(),
            'title' => trim((string) $request->string('non_conformity_title')),
            'description' => trim($description."\n\nContexto da recepção:\n".$lines),
            'status' => 'opened',
            'severity' => $severity,
            'category' => 'quality',
            'batch_number' => $order->reference ?: (string) $order->seq,
            'reported_by' => $user?->name ?? 'Sistema',
            'reported_by_id' => $user?->id,
            'reported_at' => $request->input('receive_date', now()->toDateString()),
            'due_date' => now()->addDays(in_array($severity, ['high', 'critical'], true) ? 7 : 14),
            'occurrence_area' => 'procurement_receipt',
            'comments' => trim(collect([$reason !== '' ? 'Motivo: '.$reason : null, $notes !== '' ? 'Observações: '.$notes : null])->filter()->implode("\n")),
            'evidence' => json_encode([
                'order_id' => $order->id,
                'order_reference' => $order->reference,
                'supplier_id' => $order->supplier_id,
                'supplier_name' => $order->supplier?->name,
                'receive_date' => $request->input('receive_date'),
                'items' => collect($receivedItems)->map(fn (array $entry) => [
                    'order_item_id' => $entry['order_item']->id,
                    'inventory_item_id' => $entry['order_item']->item_id,
                    'warehouse_id' => $entry['order_item']->warehouse_id,
                    'received_qty' => (float) $entry['received_qty'],
                    'ordered_qty' => (float) $entry['order_item']->qty,
                    'unit_price' => $entry['unit_price'] !== null ? (float) $entry['unit_price'] : null,
                ])->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    /**
     * @return array<int, array{count:int,open_count:int,latest_severity:?string,latest_status:?string}>
     */
    private function receptionNonConformityLookup(Collection $orderIds): array
    {
        $orderIds = $orderIds->filter()->unique()->values();

        if ($orderIds->isEmpty()) {
            return [];
        }

        $records = VAPNonConformity::query()
            ->where('occurrence_area', 'procurement_receipt')
            ->where(function ($query) use ($orderIds) {
                foreach ($orderIds as $orderId) {
                    $query->orWhere('evidence', 'like', '%"order_id":'.$orderId.'%');
                }
            })
            ->orderByDesc('reported_at')
            ->get(['id', 'status', 'severity', 'evidence', 'reported_at']);

        $grouped = [];

        foreach ($records as $record) {
            $evidence = json_decode((string) $record->evidence, true);
            $orderId = (int) data_get($evidence, 'order_id');

            if ($orderId === 0) {
                continue;
            }

            if (! isset($grouped[$orderId])) {
                $grouped[$orderId] = [
                    'count' => 0,
                    'open_count' => 0,
                    'latest_severity' => $record->severity,
                    'latest_status' => $record->status,
                ];
            }

            $grouped[$orderId]['count']++;

            if (! in_array($record->status, ['closed', 'resolved'], true)) {
                $grouped[$orderId]['open_count']++;
            }
        }

        return $grouped;
    }

    /**
     * Update item's last purchase price.
     */
    private function updateItemPurchasePrice($itemId, $unitPrice)
    {
        $item = InventoryItem::find($itemId);
        if ($item) {
            $item->update([
                'last_purchase_price' => $unitPrice,
            ]);
        }
    }

    /**
     * Update overall order status based on items.
     */
    private function updateOrderStatus(InventoryOrder $order)
    {
        $items = $order->items()->with('item')->get();

        if ($items->count() === 0) {
            $order->update(['status' => InventoryOrderTrackingStatus::CANCELLED]);

            return;
        }

        $allReceived = true;
        $allCancelled = true;
        $anyPartiallyReceived = false;
        $anyReceived = false;

        foreach ($items as $item) {
            $receivedQty = $item->received_qty ?? $this->getReceivedQuantity($item);

            // Update item status based on received quantity
            if ($receivedQty >= $item->qty) {
                $item->status = InventoryOrderItemStatus::RECEIVED;
                $anyReceived = true;
                $allCancelled = false;
            } elseif ($receivedQty > 0) {
                $item->status = InventoryOrderItemStatus::PARTIALLY_RECEIVED;
                $anyPartiallyReceived = true;
                $anyReceived = true;
                $allReceived = false;
                $allCancelled = false;
            } elseif ($item->status === InventoryOrderItemStatus::CANCELLED) {
                $allReceived = false;
            } else {
                $allReceived = false;
                $allCancelled = false;
            }

            $item->save();
        }

        // Determine order status
        if ($allReceived) {
            $order->status = InventoryOrderTrackingStatus::RECEIVED;
        } elseif ($allCancelled) {
            $order->status = InventoryOrderTrackingStatus::CANCELLED;
        } elseif ($anyPartiallyReceived || $anyReceived) {
            $order->status = InventoryOrderTrackingStatus::PARTIALLY_RECEIVED;
        }
        // If no items have been received, status remains as set by user

        $order->save();
    }
    // private function updateOrderStatus(InventoryOrder $order)
    // {
    //     $items = $order->items()->with('item')->get();

    //     if ($items->count() === 0) {
    //         $order->update(['status' => InventoryOrderTrackingStatus::CANCELLED]);
    //         return;
    //     }

    //     $allReceived = true;
    //     $allCancelled = true;
    //     $anyPartiallyReceived = false;
    //     $anyReceived = false;

    //     foreach ($items as $item) {
    //         $receivedQty = $this->getReceivedQuantity($item);

    //         // Update item status based on received quantity
    //         if ($receivedQty >= $item->qty) {
    //             $item->status = InventoryOrderItemStatus::RECEIVED;
    //             $anyReceived = true;
    //             $allCancelled = false;
    //         } elseif ($receivedQty > 0) {
    //             $item->status = InventoryOrderItemStatus::PARTIALLY_RECEIVED;
    //             $anyPartiallyReceived = true;
    //             $anyReceived = true;
    //             $allReceived = false;
    //             $allCancelled = false;
    //         } elseif ($item->status === InventoryOrderItemStatus::CANCELLED) {
    //             $allReceived = false;
    //         } else {
    //             $allReceived = false;
    //             $allCancelled = false;
    //         }

    //         $item->save();
    //     }

    //     // Determine order status
    //     if ($allReceived) {
    //         $order->status = InventoryOrderTrackingStatus::RECEIVED;
    //     } elseif ($allCancelled) {
    //         $order->status = InventoryOrderTrackingStatus::CANCELLED;
    //     } elseif ($anyPartiallyReceived) {
    //         $order->status = InventoryOrderTrackingStatus::PARTIALLY_RECEIVED;
    //     } elseif ($anyReceived) {
    //         $order->status = InventoryOrderTrackingStatus::PARTIALLY_RECEIVED;
    //     }
    //     // If no items have been received, status remains as set by user

    //     $order->save();
    // }

    /**
     * Export order as PDF.
     */
    public function exportPdf(InventoryOrder $order)
    {
        // Load the order with all necessary relationships
        $order->load([
            'supplier',
            'user',
            'items' => function ($query) {
                $query->with(['item', 'warehouse']);
            },
        ]);

        // Calculate received quantity for each item
        $order->items->each(function ($item) {
            // Get received quantity from both field and transactions
            $receivedQtyFromTransactions = InventoryTransaction::where('item_id', $item->item_id)
                ->whereHas('type', function ($query) {
                    $query->where('code', 'RECEIPT');
                })
                ->whereHas('inventory', function ($query) use ($item) {
                    $query->where('warehouse_id', $item->warehouse_id);
                })
                ->where('notes', 'LIKE', '%Order #'.$item->id.'%')
                ->sum('qty');

            $item->received_qty = max((int) $item->received_qty, (int) $receivedQtyFromTransactions);
            $item->pending_qty = $item->qty - $item->received_qty;
        });

        // Calculate totals
        $totalItems = $order->items->count();
        $totalQty = $order->items->sum('qty');
        $totalReceived = $order->items->sum('received_qty');
        $totalPending = $totalQty - $totalReceived;
        $totalAmount = $order->items->sum('total_price');

        // Format dates
        $orderDate = $order->date ? Carbon::parse($order->date)->format('d/m/Y') : 'N/A';
        $createdDate = $order->created_at ? Carbon::parse($order->created_at)->format('d/m/Y H:i') : 'N/A';

        // Status mapping for display
        $statusMap = [
            'PENDING' => 'Pendente',
            'APPROVED' => 'Aprovado',
            'ORDERED' => 'Pedido',
            'PARTIALLY_RECEIVED' => 'Recebido Parcialmente',
            'RECEIVED' => 'Recebido',
            'CANCELLED' => 'Cancelado',
        ];
        $orderStatusValue = $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status;
        $orderStatus = $statusMap[$orderStatusValue] ?? ($orderStatusValue ?: 'N/A');

        // Prepare data for PDF
        $data = [
            'order' => $order,
            'orderDate' => $orderDate,
            'createdDate' => $createdDate,
            'orderStatus' => $orderStatus,
            'totalItems' => $totalItems,
            'totalQty' => $totalQty,
            'totalReceived' => $totalReceived,
            'totalPending' => $totalPending,
            'totalAmount' => $totalAmount,
            'companyName' => config('app.name', 'LIMS System'),
            'companyAddress' => config('app.address', ''),
            'companyPhone' => config('app.phone', ''),
            'companyEmail' => config('app.email', ''),
            'printedDate' => now()->format('d/m/Y H:i'),
            'printedBy' => auth()->user()->name ?? 'System',
        ];

        // Generate PDF
        $pdf = PDF::loadView('exports.order', $data);

        // Set PDF options
        // $pdf->setOption('default_font', 'dejavusans'); // Supports UTF-8
        // $pdf->setOption('margin_top', 20);
        // $pdf->setOption('margin_bottom', 20);
        // $pdf->setOption('margin_left', 15);
        // $pdf->setOption('margin_right', 15);

        // Download PDF with a filename
        $filename = 'Pedido_'.($order->seq ?? $order->id).'_'.now()->format('Ymd_His').'.pdf';

        return PdfResponse::inline($pdf, $filename);
    }
}
