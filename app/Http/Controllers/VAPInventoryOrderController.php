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
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\SampleLaboratoryAccess;
use App\Support\InventoryQuantity;
use App\Support\PdfResponse;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PDF;

class VAPInventoryOrderController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly LaboratoryWorkflowMutationAccess $mutationAccess,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $query = InventoryOrder::forLaboratory($labId)->with(['supplier'])
            ->withCount(['items as items_count'])
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
        $sortBy = in_array($request->input('sort_by'), ['created_at', 'date', 'reference'], true) ? $request->input('sort_by') : 'created_at';
        $sortDirection = $request->input('sort_direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Get stats for the dashboard
        $stats = [
            'total_orders' => InventoryOrder::forLaboratory($labId)->count(),
            'pending_orders' => InventoryOrder::forLaboratory($labId)->whereRaw('upper(status) = ?', ['PENDING'])->count(),
            'orders_today' => InventoryOrder::forLaboratory($labId)->whereDate('date', today())->count(),
            'total_value' => InventoryOrder::forLaboratory($labId)->whereRaw('upper(status) <> ?', ['CANCELLED'])
                ->sum('total_amount'),
            'open_items' => InventoryOrderDetail::query()->whereRaw('upper(status) in (?, ?, ?)', ['PENDING', 'ORDERED', 'PARTIALLY_RECEIVED'])
                ->where('lab_id', $labId)
                ->count(),
            'by_status' => InventoryOrder::forLaboratory($labId)
                ->toBase()
                ->selectRaw('upper(status) as status_key, count(*) as aggregate')
                ->groupByRaw('upper(status)')
                ->pluck('aggregate', 'status_key')
                ->map(fn ($count): int => (int) $count),
        ];

        $orders = $query->paginate(15)->withQueryString();
        $nonConformitiesAvailable = $this->nonConformitiesAvailable();
        $receptionNonConformityLookup = $this->receptionNonConformityLookup($orders->getCollection()->pluck('id'), $nonConformitiesAvailable);

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
            'nonConformitiesAvailable' => $nonConformitiesAvailable,
            'receivingAbilities' => $this->receivingAbilities(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $items = InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())->active()->with(['category', 'unit', 'inventory'])
            ->select('id', 'name', 'code', 'category_id', 'unit_id', 'last_purchase_price', 'standard_cost')
            // ->where('is_active', true)
            ->get();

        $suppliers = $this->supplierOptions();

        $warehouses = InventoryItemWarehouse::where('lab_id', $this->laboratoryAccess->activeLabId())->select('id', 'name')
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
        $labId = $this->laboratoryAccess->activeLabId();
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
            'order_items.*.item_id' => ['required', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNotNull('unit_id')->whereNull('deleted_at')],
            'order_items.*.qty' => 'required|numeric|decimal:0,4|min:0.0001',
            'order_items.*.warehouse_id' => ['required', 'integer', Rule::exists('i_warehouses', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'order_items.*.expected_date' => 'nullable|date|after_or_equal:date',
            'order_items.*.unit_price' => 'required|numeric|min:0|decimal:0,4',
            'order_items.*.status' => ['nullable', Rule::enum(InventoryOrderItemStatus::class)],
        ]);

        $supplier = InventoryItemSupplier::findOrFail($request->supplier_id);
        $supplierAssessmentBlocker = $this->supplierAssessmentBlocker($supplier);

        if ($supplierAssessmentBlocker !== null) {
            return redirect()->back()
                ->withInput()
                ->with('error', $supplierAssessmentBlocker);
        }

        DB::beginTransaction();

        try {
            $currency = $request->currency ?? $supplier->currency ?? 'USD';

            // Create the order
            $order = InventoryOrder::create([
                'lab_id' => $labId,
                'date' => $request->date,
                'user_id' => auth()->id(),
                'supplier_id' => $request->supplier_id,
                'order_year' => now()->format('Y'),
                'obs' => $request->obs,
                'status' => InventoryOrderTrackingStatus::from($request->status),
                'currency' => $currency,
            ]);

            // Create order items
            foreach ($request->order_items as $item) {
                $unitPrice = $item['unit_price'];

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

                // Update item's last purchase price
                $this->updateItemPurchasePrice($item['item_id'], $unitPrice);
            }

            // Update order total amount
            $order->update(['total_amount' => $order->items()->sum('total_price')]);

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
        $this->ensureOwnedOrder($order);
        $order->load([
            'supplier',
            'user',
            'items' => function ($query) {
                $query->with(['item.unit', 'warehouse']);
            },
        ]);

        // Calculate received quantity for each item from both field and transactions
        $order->items->each(function ($item) {
            $item->received_qty = $this->getReceivedQuantity($item);

            // Update item status based on received quantity
            if (InventoryQuantity::compare($item->received_qty, $item->qty) >= 0) {
                $item->status = InventoryOrderItemStatus::RECEIVED;
            } elseif (InventoryQuantity::compare($item->received_qty, '0') > 0) {
                $item->status = InventoryOrderItemStatus::PARTIALLY_RECEIVED;
            }
        });

        // Calculate order summary
        $order->item_count = $order->items->count();
        $order->total_amount = $order->items->sum('total_price');
        $order->received_amount = $order->items->sum(function ($item) {
            if ($item->unit_price && $item->received_qty) {
                return $item->unit_price * $item->received_qty;
            }

            return 0;
        });

        $nonConformitiesAvailable = $this->nonConformitiesAvailable();
        $receptionNonConformitySummary = $this->receptionNonConformityLookup(collect([$order->id]), $nonConformitiesAvailable)[$order->id] ?? [
            'count' => 0,
            'open_count' => 0,
            'latest_severity' => null,
            'latest_status' => null,
        ];

        $order->setRelation('supplier', $this->decorateSupplierWithAssessment($order->supplier));
        $order->setAttribute('reception_non_conformity_summary', $receptionNonConformitySummary);

        $supplierScore = (int) data_get($order->supplier, 'latest_assessment.total_score', 0);
        $daysSinceCreation = max((int) $order->created_at?->startOfDay()->diffInDays(now()->startOfDay()), 0);
        $pendingItemCount = $order->items->filter(fn ($item): bool => InventoryQuantity::compare($item->received_qty ?? '0', $item->qty) < 0)->count();
        $receivedItemCount = $order->items->filter(fn ($item): bool => InventoryQuantity::compare($item->received_qty ?? '0', '0') > 0)->count();
        $unreceivedItemCount = $order->item_count - $receivedItemCount;

        return Inertia::render('VAPInventory/Orders/Show', [
            'order' => $order,
            'nonConformitiesAvailable' => $nonConformitiesAvailable,
            'receivingAbilities' => $this->receivingAbilities(),
            'charts' => [
                'reception_progress' => [
                    'labels' => ['Linhas pedidas', 'Com entrada', 'Sem entrada'],
                    'series' => [$order->item_count, $receivedItemCount, $unreceivedItemCount],
                ],
                'item_status_mix' => [
                    'labels' => ['Itens pendentes', 'Itens parciais', 'Itens completos'],
                    'series' => [
                        $order->items->filter(fn ($item): bool => InventoryQuantity::compare($item->received_qty ?? '0', '0') === 0)->count(),
                        $order->items->filter(fn ($item): bool => InventoryQuantity::compare($item->received_qty ?? '0', '0') > 0 && InventoryQuantity::compare($item->received_qty, $item->qty) < 0)->count(),
                        $order->items->filter(fn ($item): bool => InventoryQuantity::compare($item->received_qty ?? '0', $item->qty) >= 0)->count(),
                    ],
                ],
                'governance_summary' => [
                    'labels' => ['Score fornecedor', $nonConformitiesAvailable ? 'NC abertas' : 'NC indisponíveis', 'Dias em curso', 'Linhas pendentes'],
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
        $this->ensureOwnedOrder($order);
        // Only allow editing of pending or approved orders
        if (! in_array($order->status, [InventoryOrderTrackingStatus::PENDING, InventoryOrderTrackingStatus::APPROVED])) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Apenas os pedidos pendentes ou aprovados podem ser editados.');
        }

        $order->load([
            'items.item',
            'items.warehouse' => fn ($query) => $query
                ->where('lab_id', $order->lab_id)
                ->select('id', 'name'),
        ]);

        // Get received quantity for each item
        $order->items->each(function ($item) {
            $item->received_qty = $this->getReceivedQuantity($item);
        });

        $items = InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())->active()->with(['category', 'unit'])
            ->select('id', 'name', 'code', 'category_id', 'unit_id', 'last_purchase_price', 'standard_cost')
            ->get();

        $suppliers = $this->supplierOptions();

        $warehouses = InventoryItemWarehouse::where('lab_id', $this->laboratoryAccess->activeLabId())->active()->select('id', 'name')
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
        $this->ensureOwnedOrder($order);
        $labId = $this->laboratoryAccess->activeLabId();
        // Only allow updating of pending or approved orders
        if (! in_array($order->status, [InventoryOrderTrackingStatus::PENDING, InventoryOrderTrackingStatus::APPROVED])) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Apenas os pedidos pendentes ou aprovados podem ser actualizados.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:i_suppliers,id',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'status' => ['required', Rule::enum(InventoryOrderTrackingStatus::class)],
            'obs' => 'nullable|string|max:500',
            'currency' => 'nullable|string|size:3',
            'order_items' => 'required|array|min:1',
            'order_items.*.id' => [
                'bail',
                'nullable',
                'integer',
                'distinct',
                Rule::exists('i_order_details', 'id')
                    ->where('order_id', $order->id)
                    ->whereNull('deleted_at'),
            ],
            'order_items.*.item_id' => ['required', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNotNull('unit_id')->whereNull('deleted_at')],
            'order_items.*.qty' => 'required|numeric|decimal:0,4|min:0.0001',
            'order_items.*.warehouse_id' => ['required', 'integer', Rule::exists('i_warehouses', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'order_items.*.expected_date' => 'nullable|date|after_or_equal:date',
            'order_items.*.unit_price' => 'required|numeric|min:0|decimal:0,4',
            'order_items.*.received_qty' => 'nullable|numeric|decimal:0,4|min:0',
        ]);

        $supplier = InventoryItemSupplier::findOrFail($request->supplier_id);
        $supplierAssessmentBlocker = $this->supplierAssessmentBlocker($supplier);

        if ($supplierAssessmentBlocker !== null) {
            return redirect()->back()
                ->withInput()
                ->with('error', $supplierAssessmentBlocker);
        }

        DB::beginTransaction();

        try {
            // Get supplier currency if changed
            $currency = $request->currency ?? $order->currency;

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

            // Update or create order items
            foreach ($request->order_items as $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = $order->items()->findOrFail($itemData['id']);

                    $receivedQty = $this->getReceivedQuantity($item);

                    // Check if item has been received
                    if ($receivedQty > 0) {
                        // Can't change item if it has been received
                        if ($item->item_id != $itemData['item_id'] ||
                            $item->warehouse_id != $itemData['warehouse_id']) {
                            throw new \Exception('Não é possível alterar o artigo ou o armazém de artigos já recebidos.');
                        }
                    }

                    // Check quantity is not less than received quantity
                    if ($itemData['qty'] < $receivedQty) {
                        throw new \Exception('A quantidade não pode ser inferior à quantidade recebida.');
                    }

                    $unitPrice = $itemData['unit_price'];

                    $item->update([
                        'item_id' => $itemData['item_id'],
                        'qty' => $itemData['qty'],
                        'unit_price' => $unitPrice,
                        'warehouse_id' => $itemData['warehouse_id'],
                        'expected_date' => $itemData['expected_date'] ?? null,
                        'currency' => $currency,
                    ]);

                    $updatedItemIds[] = $itemData['id'];

                    // Update item's last purchase price if changed
                    if ($item->wasChanged('unit_price')) {
                        $this->updateItemPurchasePrice($itemData['item_id'], $unitPrice);
                    }
                } else {
                    // Create new item
                    $unitPrice = $itemData['unit_price'];

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

                    // Update item's last purchase price
                    $this->updateItemPurchasePrice($itemData['item_id'], $unitPrice);
                }
            }

            // Delete items that were removed
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (! empty($itemsToDelete)) {
                // Check if any of these items have been received
                foreach ($itemsToDelete as $itemId) {
                    $item = $order->items()->findOrFail($itemId);
                    $receivedQty = $this->getReceivedQuantity($item);

                    if ($receivedQty > 0) {
                        throw new \Exception('Não é possível eliminar artigos já recebidos.');
                    }
                }

                $order->items()->whereIn('id', $itemsToDelete)->delete();
            }

            // Update order total amount
            $order->update(['total_amount' => $order->items()->sum('total_price')]);

            // Update overall order status if items have been received
            $this->updateOrderStatus($order);

            DB::commit();

            $response = redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('success', 'Pedido de compra actualizado com sucesso.');

            if ($warning = $this->supplierAssessmentWarning($supplier)) {
                $response->with('warning', $warning);
            }

            return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Não foi possível actualizar o pedido de compra.');
        }
    }

    private function ensureOwnedOrder(InventoryOrder $order): void
    {
        abort_unless($order->lab_id === $this->laboratoryAccess->activeLabId(), 404);
    }

    private function supplierOptions()
    {
        return InventoryItemSupplier::query()
            ->active()
            ->with(['assessments' => function ($query) {
                $query->where('lab_id', $this->laboratoryAccess->activeLabId())->latest('assessment_date');
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
            ? $supplier->assessments->where('lab_id', $this->laboratoryAccess->activeLabId())->sortByDesc('assessment_date')->first()
            : $supplier->assessments()->where('lab_id', $this->laboratoryAccess->activeLabId())->latest('assessment_date')->first();

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
        $assessment = $supplier?->assessments()->where('lab_id', $this->laboratoryAccess->activeLabId())->latest('assessment_date')->first();

        if (! $assessment) {
            return null;
        }

        if (in_array($assessment->status, ['rejected', 'suspended'], true)) {
            return 'O fornecedor seleccionado está bloqueado por avaliação de desempenho. Actualize a avaliação antes de emitir a encomenda.';
        }

        if ($assessment->risk_level === 'critical' && ! $assessment->approved_supplier) {
            return 'O fornecedor seleccionado está com risco crítico e sem aprovação activa. Reavalie o fornecedor antes de prosseguir.';
        }

        return null;
    }

    private function supplierAssessmentWarning(?InventoryItemSupplier $supplier): ?string
    {
        $assessment = $supplier?->assessments()->where('lab_id', $this->laboratoryAccess->activeLabId())->latest('assessment_date')->first();

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
        $this->ensureOwnedOrder($order);
        // Only allow deletion of pending or cancelled orders
        if (! in_array($order->status, [InventoryOrderTrackingStatus::PENDING, InventoryOrderTrackingStatus::CANCELLED])) {
            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('error', 'Apenas pedidos pendentes ou cancelados podem ser eliminados.');
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
                ->with('error', 'Não é possível eliminar um pedido com artigos já recepcionados.');
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
        $this->ensureOwnedOrder($order);
        abort_if($request->hasSession() && $request->session()->has('impersonate'), 403);
        $receipt = $request->validate([
            'request_id' => ['required', 'uuid'],
            'items' => 'required|array|min:1',
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists('i_order_details', 'id')->where('order_id', $order->id)->whereNull('deleted_at')],
            'items.*.received_qty' => 'required|numeric|decimal:0,4|min:0.0001',
            'items.*.unit_price' => 'nullable|numeric|min:0|decimal:0,4',
            'receive_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'register_non_conformity' => 'nullable|boolean',
            'non_conformity_title' => 'nullable|string|max:255|required_if:register_non_conformity,true',
            'non_conformity_description' => 'nullable|string|required_if:register_non_conformity,true',
            'non_conformity_severity' => 'nullable|in:low,medium,high,critical',
        ]);

        if ($request->boolean('register_non_conformity') && ! $this->nonConformitiesAvailable()) {
            throw ValidationException::withMessages([
                'register_non_conformity' => 'O registo de não conformidades está indisponível. A recepção não foi alterada.',
            ]);
        }

        DB::beginTransaction();

        try {
            $this->authorizeReceiving($request, (int) $order->lab_id);
            $order = InventoryOrder::query()->where('lab_id', $order->lab_id)->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $fingerprint = hash('sha256', json_encode($receipt, JSON_THROW_ON_ERROR));
            $history = $order->receipt_history ?? [];
            $previous = collect($history)->firstWhere('request_id', $receipt['request_id']);
            if ($previous !== null) {
                if ($previous['actor_id'] !== $request->user()->id || ! hash_equals($previous['fingerprint'], $fingerprint)) {
                    throw ValidationException::withMessages(['request_id' => 'Esta referência de recepção já foi usada com outros dados. Confirme o estado do pedido.']);
                }
                $this->authorizeReceiving($request, (int) $order->lab_id);
                DB::commit();

                return redirect()->route('vap-inventory.orders.show', $order->id)
                    ->with('success', 'Esta recepção já foi registada. As quantidades não foram repetidas.');
            }
            if (! in_array($order->status, [InventoryOrderTrackingStatus::ORDERED, InventoryOrderTrackingStatus::PARTIALLY_RECEIVED], true)) {
                throw ValidationException::withMessages(['items' => 'Este pedido já não pode ser recepcionado.']);
            }
            $orderItems = $order->items()->whereKey(array_column($request->items, 'id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($orderItems->count() !== count($request->items)) {
                throw ValidationException::withMessages(['items' => 'Um item do pedido já não está disponível. Actualize a página.']);
            }
            $materials = InventoryItem::forLaboratory($order->lab_id)
                ->whereKey($orderItems->pluck('item_id'))->whereNotNull('unit_id')
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $warehouses = InventoryItemWarehouse::query()->where('lab_id', $order->lab_id)
                ->whereKey($orderItems->pluck('warehouse_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($orderItems as $orderItem) {
                if (! $materials->has($orderItem->item_id) || ! $warehouses->has($orderItem->warehouse_id)) {
                    throw ValidationException::withMessages(['items' => 'Um material ou armazém já não está disponível neste laboratório. A recepção não foi alterada.']);
                }
                $orderItem->setRelation('item', $materials->get($orderItem->item_id));
                $orderItem->setRelation('warehouse', $warehouses->get($orderItem->warehouse_id));
            }
            $receivedItems = [];

            foreach ($request->items as $itemData) {
                $orderItem = $orderItems->get($itemData['id']);

                // Use provided unit price or fallback to order price
                $unitPrice = $itemData['unit_price'] ?? $orderItem->unit_price;

                // Check if quantity is valid
                $alreadyReceived = $this->getReceivedQuantity($orderItem);
                $newReceivedQty = InventoryQuantity::fromScaled(InventoryQuantity::toScaled($itemData['received_qty']));
                $totalReceived = InventoryQuantity::add($alreadyReceived, $newReceivedQty);

                if (InventoryQuantity::compare($totalReceived, $orderItem->qty) > 0) {
                    throw ValidationException::withMessages(['items' => "A quantidade excede a encomendada para {$orderItem->item->name}."]);
                }

                // Update inventory stock with cost
                $this->updateInventoryStock($orderItem, $newReceivedQty, $unitPrice, $request->receive_date, $request->reason, $request->notes);

                // Calculate new total received quantity
                $newTotalReceived = $totalReceived;

                // Update item's received_qty field
                $orderItem->received_qty = $newTotalReceived;

                // Update item status based on received quantity
                if (InventoryQuantity::compare($newTotalReceived, $orderItem->qty) >= 0) {
                    $orderItem->status = InventoryOrderItemStatus::RECEIVED;
                    $orderItem->actual_date = $request->receive_date;
                } elseif (InventoryQuantity::compare($newTotalReceived, '0') > 0) {
                    $orderItem->status = InventoryOrderItemStatus::PARTIALLY_RECEIVED;
                    $orderItem->actual_date = $request->receive_date;
                }

                if (! $orderItem->save()) {
                    throw new \RuntimeException('Receipt line persistence was rejected.');
                }

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

            $history[] = [
                'request_id' => $receipt['request_id'],
                'fingerprint' => $fingerprint,
                'actor_id' => $request->user()->id,
                'recorded_at' => now()->toIso8601String(),
                'non_conformity_id' => $createdNonConformity?->id,
            ];
            $order->receipt_history = $history;
            if (! $order->save()) {
                throw new \RuntimeException('Receipt replay evidence persistence was rejected.');
            }

            $this->authorizeReceiving($request, (int) $order->lab_id);
            DB::commit();

            $response = redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('success', 'Artigos do pedido recepcionados com sucesso.');

            if ($createdNonConformity !== null) {
                $response->with('warning', 'Foi registada uma não conformidade de recepção: '.$createdNonConformity->nc_number.'.');
            }

            return $response;
        } catch (ValidationException|AuthorizationException $e) {
            DB::rollBack();

            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            throw ValidationException::withMessages([
                'items' => 'Não foi possível registar a recepção dos itens do pedido.',
            ]);
        }
    }

    private function authorizeReceiving(Request $request, int $labId): void
    {
        $this->mutationAccess->operator($request->user()->id, $labId, 'edit_iorders');
        if ($request->boolean('register_non_conformity')) {
            $this->mutationAccess->operator($request->user()->id, $labId, 'add_occurrences');
        }
    }

    /** @return array{receive: bool, register_non_conformity: bool} */
    private function receivingAbilities(): array
    {
        $user = request()->user();

        return ['receive' => $user->can('edit_iorders'),
            'register_non_conformity' => $user->can('edit_iorders') && $user->can('add_occurrences')];
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
        $this->ensureOwnedOrder($order);

        return DB::transaction(function () use ($order) {
            $order = InventoryOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($order->status, [
                InventoryOrderTrackingStatus::PENDING,
                InventoryOrderTrackingStatus::APPROVED,
                InventoryOrderTrackingStatus::ORDERED,
            ], true)) {
                return redirect()->route('vap-inventory.orders.show', $order->id)
                    ->with('error', 'Apenas os pedidos pendentes, aprovados ou encomendados podem ser cancelados.');
            }

            $items = $order->items()->lockForUpdate()->get();

            if ($items->contains(fn (InventoryOrderDetail $item): bool => InventoryQuantity::compare($this->getReceivedQuantity($item), '0') > 0)) {
                return redirect()->route('vap-inventory.orders.show', $order->id)
                    ->with('error', 'Não é possível cancelar um pedido com artigos já recebidos.');
            }

            $order->update(['status' => InventoryOrderTrackingStatus::CANCELLED]);
            $order->items()->update(['status' => InventoryOrderItemStatus::CANCELLED]);

            return redirect()->route('vap-inventory.orders.show', $order->id)
                ->with('success', 'Pedido cancelado com sucesso.');
        });
    }

    /** Get the quantity recorded by the canonical receiving action. */
    private function getReceivedQuantity(InventoryOrderDetail $orderItem): string
    {
        return InventoryQuantity::fromScaled(max(0, InventoryQuantity::toScaled($orderItem->received_qty ?? '0')));
    }

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
            if (! $inventory->exists) {
                throw new \RuntimeException('Receipt stock creation was rejected.');
            }
        }

        // Get receipt transaction type
        $receiptType = InventoryTransactionType::where('code', 'RECEIPT')->first();

        if (! $receiptType) {
            $receiptType = InventoryTransactionType::create([
                'name' => 'Recepção',
                'code' => 'RECEIPT',
                'description' => 'Recepção de existências provenientes de pedidos de compra',
            ]);
            if (! $receiptType->exists) {
                throw new \RuntimeException('Receipt transaction type creation was rejected.');
            }
        }

        // Calculate total cost
        $totalCost = $unitPrice * $receivedQty;

        // Create transaction record with cost
        $movement = InventoryTransaction::create([
            'inventory_id' => $inventory->id,
            'user_id' => auth()->id(),
            'warehouse_id' => $orderItem->warehouse_id,
            'item_id' => $orderItem->item_id,
            'type_id' => $receiptType->id,
            'qty' => $receivedQty,
            'reason' => $reason ?: 'Recepção do pedido',
            'notes' => $notes ?: "Recebidas {$receivedQty} unidades do pedido #{$orderItem->order->reference}, ao preço unitário de {$unitPrice} {$orderItem->currency} (total: {$totalCost})",
            'created_at' => $receiveDate,
            'updated_at' => $receiveDate,
        ]);
        if (! $movement->exists) {
            throw new \RuntimeException('Receipt ledger persistence was rejected.');
        }

        // Update inventory quantity
        if ($inventory->increment('qty_available', $receivedQty) !== 1) {
            throw new \RuntimeException('Receipt stock increment was rejected.');
        }
    }

    /**
     * @param  array<int, array{order_item: InventoryOrderDetail, received_qty: int|float|string, unit_price: int|float|string|null, already_received: string}>  $receivedItems
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

        $nonConformity = VAPNonConformity::query()->create([
            'lab_id' => $order->lab_id,
            'department_id' => $departmentId,
            'nc_number' => (new VAPNonConformity)->generateNcNumber((int) $order->lab_id),
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
        if (! $nonConformity->exists) {
            throw new \RuntimeException('Receipt nonconformity persistence was rejected.');
        }

        return $nonConformity;
    }

    /**
     * @return array<int, array{count:int,open_count:int,latest_severity:?string,latest_status:?string}>
     */
    private function receptionNonConformityLookup(Collection $orderIds, bool $nonConformitiesAvailable): array
    {
        $orderIds = $orderIds->filter()->unique()->values();

        if ($orderIds->isEmpty() || ! $nonConformitiesAvailable) {
            return [];
        }

        $records = VAPNonConformity::query()
            ->where('lab_id', $this->laboratoryAccess->activeLabId())
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

    private function nonConformitiesAvailable(): bool
    {
        return Schema::hasTable('v_non_conformities');
    }

    /**
     * Update item's last purchase price.
     */
    private function updateItemPurchasePrice($itemId, $unitPrice)
    {
        $item = InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())->find($itemId);
        if ($item) {
            if (! $item->update([
                'last_purchase_price' => $unitPrice,
            ])) {
                throw new \RuntimeException('Purchase price persistence was rejected.');
            }
        }
    }

    /**
     * Update overall order status based on items.
     */
    private function updateOrderStatus(InventoryOrder $order)
    {
        $items = $order->items()->with('item')->get();

        if ($items->count() === 0) {
            if (! $order->update(['status' => InventoryOrderTrackingStatus::CANCELLED])) {
                throw new \RuntimeException('Order status persistence was rejected.');
            }

            return;
        }

        $allReceived = true;
        $allCancelled = true;
        $anyPartiallyReceived = false;
        $anyReceived = false;

        foreach ($items as $item) {
            $receivedQty = $item->received_qty ?? $this->getReceivedQuantity($item);

            // Update item status based on received quantity
            if (InventoryQuantity::compare($receivedQty, $item->qty) >= 0) {
                $item->status = InventoryOrderItemStatus::RECEIVED;
                $anyReceived = true;
                $allCancelled = false;
            } elseif (InventoryQuantity::compare($receivedQty, '0') > 0) {
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

            if (! $item->save()) {
                throw new \RuntimeException('Order line status persistence was rejected.');
            }
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

        if (! $order->save()) {
            throw new \RuntimeException('Order status persistence was rejected.');
        }
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
        $this->ensureOwnedOrder($order);
        // Load the order with all necessary relationships
        $order->load([
            'supplier',
            'user',
            'items' => function ($query) {
                $query->with(['item.unit', 'warehouse']);
            },
        ]);

        // Calculate received quantity for each item
        $order->items->each(function ($item) {
            $item->received_qty = $this->getReceivedQuantity($item);
            $item->pending_qty = InventoryQuantity::subtract($item->qty, $item->received_qty);
        });

        // Count lines; adding quantities with different units has no meaning.
        $totalItems = $order->items->count();
        $receivedLineCount = $order->items->filter(fn ($item): bool => InventoryQuantity::compare($item->received_qty ?? '0', '0') > 0)->count();
        $pendingLineCount = $order->items->filter(fn ($item): bool => InventoryQuantity::compare($item->received_qty ?? '0', $item->qty) < 0)->count();
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
            'receivedLineCount' => $receivedLineCount,
            'pendingLineCount' => $pendingLineCount,
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
