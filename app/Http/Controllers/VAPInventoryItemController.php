<?php

namespace App\Http\Controllers;

use App\Exports\InventoryItemsExport;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemType;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\InventoryUnit;
use App\Models\ItemCategory;
use App\Models\ItemStatus;
use App\Models\ReagentConsumption;
use App\Models\User;
use App\Support\DuplicateSubmissionGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\MediaStream;

class VAPInventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with(['category', 'unit', 'type', 'supplier', 'status'])
            ->withSum('inventory', 'qty_available')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('internal_code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->type_id, function ($query, $typeId) {
                $query->where('type_id', $typeId);
            })
            ->when($request->status_id, function ($query, $statusId) {
                $query->where('status_id', $statusId);
            })
            ->when($request->supplier_id, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc');

        return Inertia::render('VAPInventory/Items/Index', [
            'items' => $query->paginate($request->per_page ?? 20)->withQueryString(),
            'filters' => $request->only(['search', 'category_id', 'type_id', 'status_id', 'supplier_id', 'sort_by', 'sort_direction']),
            'categories' => ItemCategory::active()->get(),
            'types' => InventoryItemType::active()->get(),
            'statuses' => ItemStatus::active()->get(),
            'suppliers' => InventoryItemSupplier::active()->get(),
            'units' => InventoryUnit::active()->get(),
            'stats' => [
                'total_items' => InventoryItem::count(),
                'equipment_count' => InventoryItem::equipment()->count(),
                'reagents_count' => InventoryItem::reagents()->count(),
                'consumables_count' => InventoryItem::consumables()->count(),
                'items_needing_calibration' => InventoryItem::whereNotNull('next_calibration_date')
                    ->where('next_calibration_date', '<', now()->addDays(30))
                    ->count(),
                'items_on_metrology_hold' => InventoryItem::query()
                    ->get()
                    ->where('metrology_status', 'hold')
                    ->count(),
                'expired_reagents' => InventoryItem::reagents()
                    ->whereNotNull('reagent_expiry_date')
                    ->where('reagent_expiry_date', '<', now())
                    ->count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('VAPInventory/Items/Create', [
            'categories' => ItemCategory::active()->get(),
            'types' => InventoryItemType::active()->get(),
            'statuses' => ItemStatus::active()->get(),
            'allStatuses' => ItemStatus::active()->get(),
            'suppliers' => InventoryItemSupplier::active()->get(),
            'units' => InventoryUnit::active()->get(),
            'warehouses' => InventoryItemWarehouse::with('location')->active()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:item_categories,id',
            'type_id' => 'nullable|exists:i_types,id',
            'unit_id' => 'nullable|exists:i_units,id',
            'supplier_id' => 'nullable|exists:i_suppliers,id',
            'status_id' => 'nullable|exists:item_statuses,id',
            'barcode' => 'nullable|string|max:255|unique:i_items,barcode',
            'serial_number' => 'nullable|string|max:255',
            'standard_cost' => 'nullable|numeric|min:0',
            'last_purchase_price' => 'nullable|numeric|min:0',
            'model' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'internal_code' => 'nullable|string|max:255',
            'reagent_expiry_date' => 'nullable|date',
            'reagent_open_date' => 'nullable|date|after_or_equal:today',
            'next_calibration_date' => 'nullable|date|after_or_equal:today',
            'last_calibration_date' => 'nullable|date',
            'metrological_uncertainty_value' => 'nullable|numeric|min:0',
            'metrological_uncertainty_unit' => 'nullable|string|max:50',
            'metrological_traceability_reference' => 'nullable|string|max:255',
            'metrology_review_due_at' => 'nullable|date',
            'metrology_notes' => 'nullable|string',
            'reorder_qty' => 'nullable|numeric|min:0',
            'packed_depth' => 'nullable|numeric|min:0',
            'packed_width' => 'nullable|numeric|min:0',
            'packed_height' => 'nullable|numeric|min:0',
            'packed_weight' => 'nullable|numeric|min:0',
            'has_safety_documentation' => 'boolean',
            'refrigerated' => 'boolean',
            'description' => 'nullable|string',
            'acceptance_criteria' => 'nullable|string',
            'obs' => 'nullable|string',
            'warehouses' => 'nullable|array',
            'warehouses.*.id' => 'required|exists:i_warehouses,id',
            'warehouses.*.qty_available' => 'required|integer|min:0',
            'warehouses.*.min_stock_level' => 'nullable|integer|min:0',
            'warehouses.*.reorder_point' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $item = InventoryItem::create($request->except('warehouses'));

            // Create inventory records for each warehouse
            if ($request->has('warehouses')) {
                foreach ($request->warehouses as $warehouse) {
                    Inventory::create([
                        'item_id' => $item->id,
                        'warehouse_id' => $warehouse['id'],
                        'qty_available' => $warehouse['qty_available'],
                        'min_stock_level' => $warehouse['min_stock_level'] ?? 0,
                        'reorder_point' => $warehouse['reorder_point'] ?? 0,
                        'category_id' => $item->category_id,
                        'status' => 'AVAILABLE',
                    ]);
                }
            }

            // Add Possible Documents
            if (request()->hasFile('documents')) {

                $fileAdders = $item
                    ->addMultipleMediaFromRequest(['documents'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('documents');
                    });
            }

            DB::commit();

            return redirect()->route('vap-inventory.items.index')
                ->with('success', 'Item de inventário criado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Não foi possível criar o item de inventário.')
                ->withInput();
        }
    }

    public function show(InventoryItem $item)
    {
        $item->load([
            'category',
            'unit',
            'type',
            'supplier',
            'status',
            'inventory.warehouse.location',
            'transactions' => function ($query) {
                $query->latest()->limit(50);
            },
            'transactions.type',
            'transactions.user',
            'transactions.warehouse',
            'orders' => function ($query) {
                $query->latest()->limit(20);
            },
            'orders.supplier',
            'transfers' => function ($query) {
                $query->latest()->limit(20);
            },
            'transfers.source',
            'transfers.destination',
            'reagentConsumptions' => function ($query) {
                $query->latest()->limit(20);
            },
        ]);

        $warehouseStockSeries = $item->inventory
            ->map(fn ($inventory) => (float) $inventory->qty_available)
            ->values();

        $warehouseStockLabels = $item->inventory
            ->map(fn ($inventory) => $inventory->warehouse?->name ?? 'Armazém')
            ->values();

        $lowStockWarehouses = $item->inventory->filter(function ($inventory) {
            return (float) $inventory->qty_available <= (float) ($inventory->reorder_point ?? 0);
        })->count();

        return Inertia::render('VAPInventory/Items/Show', [
            'item' => $item,
            'inventory' => $item->inventory,
            'recentTransactions' => $item->transactions,
            'recentOrders' => $item->orders,
            'recentTransfers' => $item->transfers,
            'recentConsumptions' => $item->reagentConsumptions,
            'totalStock' => $item->total_stock,
            'isReagent' => $item->is_reagent,
            'isExpired' => $item->is_expired,
            'daysToExpiry' => $item->days_to_expiry,
            'needsCalibration' => $item->needs_calibration,
            'calibrationStatus' => $item->calibration_status,
            'metrologyStatus' => $item->metrology_status,
            'isMetrologicallyReady' => $item->is_metrologically_ready,
            'documents' => $item->getInventoryItemDocuments()->map(function ($file) {
                return [
                    'id' => $file->id,
                    'uuid' => $file->uuid,
                    'name' => $file->name,
                    'file_name' => $file->file_name,
                    'original_url' => $file->getUrl(), // Optional: Add a thumbnail if applicable
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'extension' => $file->extension,
                    'order' => $file->order,
                ];
            }),
            'charts' => [
                'stock_distribution' => [
                    'labels' => $warehouseStockLabels,
                    'series' => $warehouseStockSeries,
                ],
                'activity_mix' => [
                    'labels' => ['Transações', 'Pedidos', 'Transferências', 'Consumos'],
                    'series' => [
                        $item->transactions->count(),
                        $item->orders->count(),
                        $item->transfers->count(),
                        $item->reagentConsumptions->count(),
                    ],
                ],
                'compliance_pulse' => [
                    'labels' => ['Estoque total', 'Armazéns críticos', 'Dias até caducar', 'Prontidão metrológica'],
                    'series' => [
                        (float) $item->total_stock,
                        $lowStockWarehouses,
                        max((int) ($item->days_to_expiry ?? 0), 0),
                        $item->is_metrologically_ready ? 1 : 0,
                    ],
                ],
            ],
        ]);
    }

    public function edit(InventoryItem $item)
    {
        $item->load(['inventory.warehouse']);

        return Inertia::render('VAPInventory/Items/Edit', [
            'item' => $item,
            'categories' => ItemCategory::active()->get(),
            'types' => InventoryItemType::active()->get(),
            'statuses' => ItemStatus::active()->get(),
            'allStatuses' => ItemStatus::active()->get(),
            'suppliers' => InventoryItemSupplier::active()->get(),
            'units' => InventoryUnit::active()->get(),
            'warehouses' => InventoryItemWarehouse::with('location')->active()->get(),
            'documents' => $item->getInventoryItemDocuments()->map(function ($file) {
                return [
                    'id' => $file->id,
                    'uuid' => $file->uuid,
                    'name' => $file->name,
                    'file_name' => $file->file_name,
                    'original_url' => $file->getUrl(), // Optional: Add a thumbnail if applicable
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'extension' => $file->extension,
                    'order' => $file->order,
                ];
            }),
        ]);
    }

    public function update(Request $request, InventoryItem $item)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'internal_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('i_items', 'internal_code')->ignore($item->id),
            ],
            'category_id' => 'required|exists:item_categories,id',
            'type_id' => 'nullable|exists:i_types,id',
            'unit_id' => 'nullable|exists:i_units,id',
            'supplier_id' => 'nullable|exists:i_suppliers,id',
            'status_id' => 'nullable|exists:item_statuses,id',
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('i_items', 'barcode')->ignore($item->id),
            ],
            'serial_number' => 'nullable|string|max:255',
            'standard_cost' => 'nullable|numeric|min:0',
            'last_purchase_price' => 'nullable|numeric|min:0',
            'model' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'resolution' => 'nullable|string|max:255',
            'precision' => 'nullable|string|max:255',
            'range' => 'nullable|string|max:255',
            'firmware' => 'nullable|string|max:255',
            'software' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'acceptance_criteria' => 'nullable|string',
            'obs' => 'nullable|string',
            'reagent_expiry_date' => 'nullable|date',
            'reagent_open_date' => 'nullable|date',
            'next_calibration_date' => 'nullable|date|after_or_equal:today',
            'last_calibration_date' => 'nullable|date',
            'metrological_uncertainty_value' => 'nullable|numeric|min:0',
            'metrological_uncertainty_unit' => 'nullable|string|max:50',
            'metrological_traceability_reference' => 'nullable|string|max:255',
            'metrology_review_due_at' => 'nullable|date',
            'metrology_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $item->update($request->except('warehouses'));

            // Update inventory records
            if ($request->has('warehouses')) {
                $existingWarehouseIds = $item->inventory->pluck('warehouse_id')->toArray();
                $newWarehouseIds = collect($request->warehouses)->pluck('id')->toArray();

                // Remove inventory records for warehouses not in new list
                Inventory::where('item_id', $item->id)
                    ->whereNotIn('warehouse_id', $newWarehouseIds)
                    ->delete();

                foreach ($request->warehouses as $warehouse) {
                    Inventory::updateOrCreate(
                        [
                            'item_id' => $item->id,
                            'warehouse_id' => $warehouse['id'],
                        ],
                        [
                            'qty_available' => $warehouse['qty_available'],
                            'min_stock_level' => $warehouse['min_stock_level'] ?? 0,
                            'reorder_point' => $warehouse['reorder_point'] ?? 0,
                            'category_id' => $item->category_id,
                            'status' => 'AVAILABLE',
                        ]
                    );
                }
            }

            // Add Possible Documents
            if ($request->documents) {

                $fileAdders = $item
                    ->addMultipleMediaFromRequest(['documents'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('documents');
                    });
            }

            DB::commit();

            return redirect()->route('vap-inventory.items.show', $item)
                ->with('success', 'Item de inventário atualizado com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Não foi possível atualizar o item de inventário.')
                ->withInput();
        }
    }

    public function destroy(InventoryItem $item)
    {
        try {
            $item->delete();

            return redirect()->route('vap-inventory.items.index')
                ->with('success', 'Item de inventário eliminado com sucesso.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Não foi possível eliminar o item de inventário.');
        }
    }

    public function adjustStock(Request $request, InventoryItem $item, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        // dd(request()->all());

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:i_warehouses,id',
            'adjustment_type' => 'required|in:add,remove,set',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'batch_id' => 'nullable|exists:i_inventory_batches,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'vap-inventory-adjust-stock', array_merge($validated, [
            'item_id' => $item->id,
        ]), 30)) {
            return response()->json([
                'error' => 'Uma solicitação idêntica de ajuste de estoque já está a ser processada.',
            ], 429);
        }

        try {
            DB::beginTransaction();

            $inventory = Inventory::where('item_id', $item->id)
                ->where('warehouse_id', $validated['warehouse_id'])
                ->first();

            if (! $inventory) {
                return response()->json(['error' => 'Item not found in specified warehouse'], 404);
            }

            $oldQuantity = $inventory->qty_available;

            switch ($validated['adjustment_type']) {
                case 'add':
                    $newQuantity = $oldQuantity + $validated['quantity'];
                    break;
                case 'remove':
                    $newQuantity = $oldQuantity - $validated['quantity'];
                    break;
                case 'set':
                    $newQuantity = $validated['quantity'];
                    break;
            }

            if ($newQuantity < 0) {
                return response()->json(['error' => 'Cannot have negative stock'], 422);
            }

            $inventory->qty_available = $newQuantity;
            $inventory->save();

            // Record transaction
            $transactionType = match ($validated['adjustment_type']) {
                'add' => 'stock_adjustment_add',
                'remove' => 'stock_adjustment_remove',
                'set' => 'stock_adjustment_set',
            };

            Log::info($transactionType, [
                'inventory' => $inventory,
            ]);

            $transactionTypeModel = InventoryTransactionType::firstOrCreate(
                ['code' => $transactionType],
                [
                    'name' => ucfirst(str_replace('_', ' ', $transactionType)),
                    'description' => 'Automatically registered inventory stock adjustment type.',
                ]
            );

            InventoryTransaction::create([
                'inventory_id' => $inventory->id,
                'user_id' => auth()->id(),
                'warehouse_id' => $validated['warehouse_id'],
                'item_id' => $item->id,
                'type_id' => $transactionTypeModel->id,
                'batch_id' => $validated['batch_id'] ?? null,
                'qty' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
                'reason' => $validated['reason'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to adjust stock: '.$e->getMessage()], 500);
        }
    }

    public function calibrationSchedule(Request $request)
    {
        $query = InventoryItem::with(['category', 'type'])
            ->whereNotNull('next_calibration_date')
            ->when($request->status, function ($query, $status) {
                if ($status === 'overdue') {
                    $query->where('next_calibration_date', '<', now());
                } elseif ($status === 'due_soon') {
                    $query->whereBetween('next_calibration_date', [now(), now()->addDays(30)]);
                } elseif ($status === 'upcoming') {
                    $query->where('next_calibration_date', '>', now()->addDays(30));
                }
            })
            ->orderBy('next_calibration_date');

        return Inertia::render('VAPInventory/Calibration/Schedule', [
            'items' => $query->paginate($request->per_page ?? 20)->withQueryString(),
            'filters' => $request->only(['status', 'sort_by', 'sort_direction']),
            'stats' => [
                'total_due' => InventoryItem::whereNotNull('next_calibration_date')
                    ->where('next_calibration_date', '<', now())
                    ->count(),
                'due_soon' => InventoryItem::whereNotNull('next_calibration_date')
                    ->whereBetween('next_calibration_date', [now(), now()->addDays(30)])
                    ->count(),
                'total_scheduled' => InventoryItem::whereNotNull('next_calibration_date')->count(),
            ],
        ]);
    }

    public function reagentExpiryReport(Request $request)
    {
        $query = InventoryItem::with(['category', 'supplier'])
            ->reagents()
            ->whereNotNull('reagent_expiry_date')
            ->when($request->status, function ($query, $status) {
                if ($status === 'expired') {
                    $query->where('reagent_expiry_date', '<', now());
                } elseif ($status === 'expiring_soon') {
                    $query->whereBetween('reagent_expiry_date', [now(), now()->addDays(60)]);
                } elseif ($status === 'good') {
                    $query->where('reagent_expiry_date', '>', now()->addDays(60));
                }
            })
            ->orderBy('reagent_expiry_date');

        return Inertia::render('VAPInventory/Reagents/ExpiryReport', [
            'reagents' => $query->paginate($request->per_page ?? 20)->withQueryString(),
            'filters' => $request->only(['status', 'sort_by', 'sort_direction']),
            'stats' => [
                'expired' => InventoryItem::reagents()
                    ->whereNotNull('reagent_expiry_date')
                    ->where('reagent_expiry_date', '<', now())
                    ->count(),
                'expiring_soon' => InventoryItem::reagents()
                    ->whereNotNull('reagent_expiry_date')
                    ->whereBetween('reagent_expiry_date', [now(), now()->addDays(60)])
                    ->count(),
                'total_reagents' => InventoryItem::reagents()->count(),
            ],
        ]);
    }

    public function lowStockReport(Request $request)
    {
        $query = Inventory::with(['item.category', 'warehouse.location'])
            ->lowStock()
            ->where('qty_available', '>', 0)
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->severity === 'critical', function ($query) {
                $query->whereColumn('qty_available', '<=', 'min_stock_level');
            })
            ->when($request->severity === 'low', function ($query) {
                $query->whereColumn('qty_available', '>', 'min_stock_level');
            });

        $sortBy = match ($request->sort_by) {
            'current_stock' => 'qty_available',
            'reorder_point' => 'reorder_point',
            'item_name' => 'item_id',
            default => null,
        };

        if ($request->sort_by === 'severity' || $request->sort_by === null) {
            $query->orderByRaw(
                'CASE
                    WHEN qty_available <= min_stock_level THEN 0
                    WHEN qty_available <= reorder_point THEN 1
                    ELSE 2
                END'
            )->orderByRaw('qty_available / NULLIF(reorder_point, 0)');
        } elseif ($sortBy !== null) {
            $query->orderBy($sortBy);
        } else {
            $query->orderByRaw('qty_available / NULLIF(reorder_point, 0)');
        }

        $inventory = $query->get();

        $severityLabels = ['Sem stock', 'Crítico', 'Baixo'];
        $severitySeries = [
            Inventory::query()
                ->when($request->warehouse_id, fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
                ->when($request->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
                ->where('qty_available', '<=', 0)
                ->count(),
            $inventory->filter(fn ($item) => $item->qty_available <= $item->min_stock_level)->count(),
            $inventory->filter(fn ($item) => $item->qty_available > $item->min_stock_level)->count(),
        ];

        $warehouseExposure = $inventory
            ->groupBy(fn ($item) => $item->warehouse?->name ?: 'Sem armazém')
            ->map(fn ($items, $warehouse) => [
                'label' => $warehouse,
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $replenishmentGap = $inventory
            ->map(fn ($item) => [
                'label' => $item->item?->code ?: $item->item?->name ?: "Item #{$item->item_id}",
                'gap' => max((int) $item->reorder_point - (int) $item->qty_available, 0),
            ])
            ->sortByDesc('gap')
            ->take(8)
            ->values();

        return Inertia::render('VAPInventory/Reports/LowStock', [
            'inventory' => $query->paginate($request->per_page ?? 20)->withQueryString(),
            'filters' => $request->only(['warehouse_id', 'category_id', 'severity', 'sort_by']),
            'warehouses' => InventoryItemWarehouse::active()->get(),
            'categories' => ItemCategory::active()->get(),
            'charts' => [
                'severity_mix' => [
                    'labels' => $severityLabels,
                    'series' => $severitySeries,
                ],
                'warehouse_exposure' => [
                    'labels' => $warehouseExposure->pluck('label')->all(),
                    'series' => $warehouseExposure->pluck('count')->map(fn ($value) => (int) $value)->all(),
                ],
                'replenishment_gap' => [
                    'labels' => $replenishmentGap->pluck('label')->all(),
                    'series' => [
                        [
                            'name' => 'Gap para reabastecimento',
                            'data' => $replenishmentGap->pluck('gap')->map(fn ($value) => (int) $value)->all(),
                        ],
                    ],
                ],
            ],
            'stats' => [
                'total_low_stock' => $inventory->count(),
                'out_of_stock' => $severitySeries[0],
                'critical_stock' => $severitySeries[1],
                'total_items' => Inventory::query()
                    ->when($request->warehouse_id, fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
                    ->when($request->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
                    ->count(),
            ],
        ]);
    }

    // Add these methods to your VAPInventoryItemController class

    public function reagentConsumption(Request $request)
    {
        $query = ReagentConsumption::with([
            'item.category',
            'warehouse',
            'user',
        ])
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->when($request->item_id, function ($query, $itemId) {
                $query->where('reagent_id', $itemId);
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->user_id, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('reagent_name', 'like', "%{$search}%")
                        ->orWhere('used_by', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->sort_by ?? 'date', $request->sort_direction ?? 'desc');

        // Summary by item
        $summaryByItem = ReagentConsumption::select(
            'reagent_id',
            'reagent_name',
            DB::raw('SUM(quantity_used) as total_consumption'),
            DB::raw('COUNT(*) as usage_count'),
            DB::raw('AVG(quantity_used) as avg_per_use')
        )
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->groupBy('reagent_id', 'reagent_name')
            ->orderByDesc('total_consumption')
            ->get();

        // Summary by date
        $summaryByDate = ReagentConsumption::select(
            DB::raw('DATE(date) as date'),
            DB::raw('SUM(quantity_used) as total_consumption'),
            DB::raw('COUNT(*) as usage_count')
        )
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->groupBy(DB::raw('DATE(date)'))
            ->orderByDesc('date')
            ->get();

        // Summary by user
        $summaryByUser = ReagentConsumption::select(
            'used_by',
            DB::raw('SUM(quantity_used) as total_consumption'),
            DB::raw('COUNT(*) as usage_count')
        )
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->whereNotNull('used_by')
            ->groupBy('used_by')
            ->orderByDesc('total_consumption')
            ->get();

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();
        $days = $dateFrom->diffInDays($dateTo) ?: 1;

        $totalConsumption = ReagentConsumption::query()
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->sum('quantity_used');

        return Inertia::render('VAPInventory/Reagents/Consumption', [
            'consumptions' => $query->paginate($request->per_page ?? 50)->withQueryString(),
            'summaryByItem' => $summaryByItem,
            'summaryByDate' => $summaryByDate,
            'summaryByUser' => $summaryByUser,
            'filters' => $request->only(['date_from', 'date_to', 'item_id', 'warehouse_id', 'user_id', 'search', 'sort_by', 'sort_direction']),
            'items' => InventoryItem::reagents()->active()->get(['id', 'name', 'code']),
            'warehouses' => InventoryItemWarehouse::active()->get(['id', 'name']),
            'users' => User::whereHas('reagentConsumptions')->get(['id', 'name']),
            'stats' => [
                'total_consumption' => $totalConsumption,
                'total_uses' => $summaryByItem->sum('usage_count'),
                'avg_daily_consumption' => round($totalConsumption / $days, 2),
                'most_consumed_item' => $summaryByItem->first(),
                'most_active_user' => $summaryByUser->first(),
                'peak_consumption_day' => $summaryByDate->sortByDesc('total_consumption')->first(),
            ],
        ]);
    }

    public function createConsumption()
    {
        return Inertia::render('VAPInventory/Reagents/CreateConsumption', [
            'reagents' => InventoryItem::reagents()->with('inventory.warehouse')->active()->get(),
            'warehouses' => InventoryItemWarehouse::with('location')->active()->get(),
            'users' => User::active()->get(['id', 'name']),
        ]);
    }

    public function storeConsumption(Request $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        $validator = Validator::make($request->all(), [
            'reagent_id' => 'required|exists:i_items,id',
            'warehouse_id' => 'required|exists:i_warehouses,id',
            'quantity_used' => 'required|numeric|min:0.01',
            'used_by' => 'required|string|max:255',
            'date' => 'required|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'vap-inventory-store-consumption', $validated, 30)) {
            return redirect()->back()
                ->with('error', 'Já existe um registo idêntico de consumo em processamento.')
                ->withInput();
        }

        // Check if reagent exists and has enough stock
        $inventory = Inventory::where('item_id', $validated['reagent_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->first();

        if (! $inventory) {
            return redirect()->back()
                ->with('error', 'Reagent not found in specified warehouse')
                ->withInput();
        }

        if ($inventory->qty_available < $validated['quantity_used']) {
            return redirect()->back()
                ->with('error', 'Insufficient stock. Available: '.$inventory->qty_available)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Get reagent details
            $reagent = InventoryItem::find($validated['reagent_id']);

            // Create consumption record
            $consumption = ReagentConsumption::create([
                'date' => $validated['date'],
                'user_id' => auth()->id(),
                'reagent_id' => $validated['reagent_id'],
                'reagent_name' => $reagent->name,
                'quantity_used' => $validated['quantity_used'],
                'used_by' => $validated['used_by'],
                'used_at' => now(),
                'remarks' => $validated['remarks'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
            ]);

            // Update inventory
            $inventory->decrement('qty_available', $validated['quantity_used']);

            // Create transaction record
            $transactionType = InventoryTransactionType::where('code', 'consumption')->first();
            if ($transactionType) {
                $transaction = InventoryTransaction::create([
                    'inventory_id' => $inventory->id,
                    'user_id' => auth()->id(),
                    'warehouse_id' => $validated['warehouse_id'],
                    'item_id' => $validated['reagent_id'],
                    'type_id' => $transactionType->id,
                    'qty' => '-'.$validated['quantity_used'],
                    'reason' => 'Reagent consumption',
                    'notes' => $validated['remarks'] ?? null,
                ]);

                $consumption->update([
                    'inventory_transaction_id' => $transaction->id,
                ]);
            }

            DB::commit();

            return redirect()->route('vap-inventory.reagents.consumption.index')
                ->with('success', 'Consumo de reagente registado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Não foi possível registar o consumo do reagente.')
                ->withInput();
        }
    }

    public function consume(Request $request, InventoryItem $item, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        // Quick consume from item detail page
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:i_warehouses,id',
            'quantity_used' => 'required|numeric|min:0.01',
            'used_by' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'batch_id' => 'nullable|exists:i_inventory_batches,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'vap-inventory-consume', array_merge($validated, [
            'item_id' => $item->id,
        ]), 30)) {
            return response()->json([
                'error' => 'Um consumo idêntico deste reagente já está a ser processado.',
            ], 429);
        }

        if (! $item->is_reagent) {
            return response()->json(['error' => 'Item is not a reagent'], 422);
        }

        $inventory = Inventory::where('item_id', $item->id)
            ->where('warehouse_id', $validated['warehouse_id'])
            ->first();

        if (! $inventory) {
            return response()->json(['error' => 'Reagent not found in specified warehouse'], 404);
        }

        if ($inventory->qty_available < $validated['quantity_used']) {
            return response()->json(['error' => 'Insufficient stock. Available: '.$inventory->qty_available], 422);
        }

        try {
            DB::beginTransaction();

            // Create consumption record
            $consumption = ReagentConsumption::create([
                'date' => now()->format('Y-m-d'),
                'user_id' => auth()->id(),
                'reagent_id' => $item->id,
                'reagent_name' => $item->name,
                'quantity_used' => $validated['quantity_used'],
                'usage_type' => $request->usage_type ?? 'experiment',
                'project' => $request->project,
                'used_by' => $validated['used_by'],
                'used_at' => now(),
                'remarks' => $validated['remarks'] ?? null,
                'batch_id' => $validated['batch_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
            ]);

            // Update inventory
            $inventory->decrement('qty_available', $validated['quantity_used']);

            // Create transaction record
            $transactionType = InventoryTransactionType::where('code', 'consumption')->first();
            if ($transactionType) {
                $transaction = InventoryTransaction::create([
                    'inventory_id' => $inventory->id,
                    'user_id' => auth()->id(),
                    'warehouse_id' => $validated['warehouse_id'],
                    'item_id' => $item->id,
                    'type_id' => $transactionType->id,
                    'batch_id' => $validated['batch_id'] ?? null,
                    'qty' => '-'.$validated['quantity_used'],
                    'reason' => 'Reagent consumption',
                    'notes' => $validated['remarks'] ?? null,
                ]);

                $consumption->update([
                    'inventory_transaction_id' => $transaction->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reagent consumed successfully',
                'consumption' => $consumption,
                'new_quantity' => $inventory->fresh()->qty_available,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to consume reagent: '.$e->getMessage()], 500);
        }
    }

    public function showConsumption(ReagentConsumption $consumption)
    {
        $consumption->load(['item.category', 'warehouse.location', 'user']);

        return Inertia::render('VAPInventory/Reagents/ShowConsumption', [
            'consumption' => $consumption,
        ]);
    }

    public function destroyConsumption(ReagentConsumption $consumption)
    {
        try {
            DB::beginTransaction();

            // Get the inventory record
            $inventory = Inventory::where('item_id', $consumption->reagent_id)
                ->where('warehouse_id', $consumption->warehouse_id)
                ->first();

            if ($inventory) {
                // Restore the consumed quantity
                $inventory->increment('qty_available', $consumption->quantity_used);

                if ($consumption->inventory_transaction_id) {
                    InventoryTransaction::whereKey($consumption->inventory_transaction_id)->delete();
                } else {
                    InventoryTransaction::where('item_id', $consumption->reagent_id)
                        ->where('warehouse_id', $consumption->warehouse_id)
                        ->where('qty', '-'.$consumption->quantity_used)
                        ->where('reason', 'Reagent consumption')
                        ->whereDate('created_at', $consumption->created_at)
                        ->delete();
                }
            }

            // Delete the consumption record
            $consumption->delete();

            DB::commit();

            return redirect()->route('vap-inventory.reagents.consumption.index')
                ->with('success', 'Registo de consumo eliminado com sucesso. O stock foi reposto.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Não foi possível eliminar o registo de consumo.');
        }
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
        $item = InventoryItem::findOrFail(request()->integer('model_id'));

        Media::query()
            ->whereKey(request()->integer('id'))
            ->where('model_id', $item->id)
            ->firstOrFail()
            ->delete();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * Export all inventory items to an Excel file.
     *
     * @return Response
     */
    public function exportInventory(Request $request)
    {
        // dd(request()->all());

        // Validate the date parameters
        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
        ]);

        $startDate = $request->input('start');
        $endDate = $request->input('end');
        $categories = [1, 2, 3, 4];
        $category_id = $request->input('category_id');

        // dd($category_id);

        // Pass the date range to the export class
        return Excel::download(new InventoryItemsExport($startDate, $endDate, $categories, $category_id), 'inventory_items.xlsx');
    }
}
