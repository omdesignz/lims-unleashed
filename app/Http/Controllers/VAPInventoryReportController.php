<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\ReagentConsumption;
use App\Models\ItemCategory;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Response;

class VAPInventoryReportController extends Controller
{
    public function stockMovement(Request $request)
    {
        $query = InventoryTransaction::with([
            'item.category',
            'warehouse.location',
            'type',
            'user'
        ])
        ->when($request->date_from, function ($query, $dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        })
        ->when($request->date_to, function ($query, $dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        })
        ->when($request->item_id, function ($query, $itemId) {
            $query->where('item_id', $itemId);
        })
        ->when($request->warehouse_id, function ($query, $warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })
        ->when($request->type_id, function ($query, $typeId) {
            $query->where('type_id', $typeId);
        })
        ->when($request->search, function ($query, $search) {
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->orWhereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        })
        ->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc');

        $movementTrend = InventoryTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_transactions'),
            DB::raw('SUM(CASE WHEN type_id IN (SELECT id FROM itransaction_types WHERE code IN ("stock_in", "stock_adjustment_add")) THEN CAST(qty AS SIGNED) ELSE 0 END) as total_in'),
            DB::raw('SUM(CASE WHEN type_id IN (SELECT id FROM itransaction_types WHERE code IN ("stock_out", "stock_adjustment_remove", "consumption")) THEN CAST(qty AS SIGNED) ELSE 0 END) as total_out')
        )
        ->when($request->date_from, function ($query, $dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        })
        ->when($request->date_to, function ($query, $dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        })
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date')
        ->get();

        $summary = $request->view === 'summary' ? $movementTrend->sortByDesc('date')->values() : null;

        $movementStats = $this->getMovementStats($request);

        $typeMix = InventoryTransaction::query()
            ->select('itransaction_types.code', DB::raw('COUNT(*) as total'))
            ->join('itransaction_types', 'itransactions.type_id', '=', 'itransaction_types.id')
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('itransactions.created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('itransactions.created_at', '<=', $dateTo);
            })
            ->when($request->item_id, function ($query, $itemId) {
                $query->where('itransactions.item_id', $itemId);
            })
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('itransactions.warehouse_id', $warehouseId);
            })
            ->when($request->type_id, function ($query, $typeId) {
                $query->where('itransactions.type_id', $typeId);
            })
            ->groupBy('itransaction_types.code')
            ->pluck('total', 'itransaction_types.code');

        return Inertia::render('VAPInventory/Reports/StockMovement', [
            'transactions' => $query->paginate($request->per_page ?? 50)->withQueryString(),
            'summary' => $summary,
            'charts' => [
                'direction_breakdown' => [
                    'labels' => ['Entradas', 'Saídas', 'Saldo'],
                    'series' => [
                        [
                            'name' => 'Movimento',
                            'data' => [
                                (float) $movementStats['total_in'],
                                (float) $movementStats['total_out'],
                                (float) $movementStats['net_movement'],
                            ],
                        ],
                    ],
                ],
                'type_mix' => [
                    'labels' => ['Entradas', 'Saídas', 'Consumo', 'Transferências'],
                    'series' => [
                        (int) ($typeMix['stock_in'] ?? 0) + (int) ($typeMix['stock_adjustment_add'] ?? 0),
                        (int) ($typeMix['stock_out'] ?? 0) + (int) ($typeMix['stock_adjustment_remove'] ?? 0),
                        (int) ($typeMix['consumption'] ?? 0),
                        (int) ($typeMix['transfer'] ?? 0),
                    ],
                ],
                'daily_activity' => [
                    'labels' => $movementTrend->pluck('date')->map(fn ($date) => (string) $date)->all(),
                    'series' => [
                        [
                            'name' => 'Entradas',
                            'data' => $movementTrend->pluck('total_in')->map(fn ($value) => (float) $value)->all(),
                        ],
                        [
                            'name' => 'Saídas',
                            'data' => $movementTrend->pluck('total_out')->map(fn ($value) => (float) $value)->all(),
                        ],
                        [
                            'name' => 'Transações',
                            'data' => $movementTrend->pluck('total_transactions')->map(fn ($value) => (int) $value)->all(),
                        ],
                    ],
                ],
            ],
            'filters' => $request->only(['date_from', 'date_to', 'item_id', 'warehouse_id', 'type_id', 'search', 'view', 'sort_by', 'sort_direction']),
            'items' => InventoryItem::active()->get(['id', 'name', 'code']),
            'warehouses' => InventoryItemWarehouse::active()->get(['id', 'name']),
            'categories' => ItemCategory::active()->get(),
            'stats' => $movementStats,
        ]);
    }

    private function getMovementStats($request)
    {
        $query = InventoryTransaction::query()
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            });

        $totalIn = (clone $query)->whereHas('type', function ($q) {
            $q->whereIn('code', ['stock_in', 'stock_adjustment_add']);
        })->sum(DB::raw('CAST(qty AS SIGNED)'));

        $totalOut = (clone $query)->whereHas('type', function ($q) {
            $q->whereIn('code', ['stock_out', 'stock_adjustment_remove', 'consumption']);
        })->sum(DB::raw('CAST(qty AS SIGNED)'));

        $netMovement = $totalIn - $totalOut;

        return [
            'total_transactions' => $query->count(),
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'net_movement' => $netMovement,
            'avg_daily_transactions' => $this->getAvgDailyTransactions($request),
            'most_active_item' => $this->getMostActiveItem($request),
            'most_active_user' => $this->getMostActiveUser($request),
        ];
    }

    private function getAvgDailyTransactions($request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();
        
        $days = $dateFrom->diffInDays($dateTo) ?: 1;
        
        $total = InventoryTransaction::query()
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->count();
        
        return round($total / $days, 2);
    }

    private function getMostActiveItem($request)
    {
        return InventoryTransaction::select(
            'item_id',
            DB::raw('COUNT(*) as transaction_count')
        )
        ->with('item:id,name')
        ->when($request->date_from, function ($query, $dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        })
        ->when($request->date_to, function ($query, $dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        })
        ->groupBy('item_id')
        ->orderByDesc('transaction_count')
        ->first();
    }

    private function getMostActiveUser($request)
    {
        return InventoryTransaction::select(
            'user_id',
            DB::raw('COUNT(*) as transaction_count')
        )
        ->with('user:id,name')
        ->when($request->date_from, function ($query, $dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        })
        ->when($request->date_to, function ($query, $dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        })
        ->groupBy('user_id')
        ->orderByDesc('transaction_count')
        ->first();
    }

    public function consumptionReport(Request $request)
    {
        $query = ReagentConsumption::with([
            'item.category',
            'warehouse',
            'user'
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
            $query->where('reagent_name', 'like', "%{$search}%")
                  ->orWhere('used_by', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
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

        return Inertia::render('VAPInventory/Reports/Consumption', [
            'consumptions' => $query->paginate($request->per_page ?? 50)->withQueryString(),
            'summaryByItem' => $summaryByItem,
            'summaryByDate' => $summaryByDate,
            'summaryByUser' => $summaryByUser,
            'charts' => [
                'item_consumption' => [
                    'labels' => $summaryByItem
                        ->take(8)
                        ->pluck('reagent_name')
                        ->map(fn ($name) => $name ?: 'Sem reagente')
                        ->values()
                        ->all(),
                    'series' => [
                        [
                            'name' => 'Consumo total',
                            'data' => $summaryByItem
                                ->take(8)
                                ->pluck('total_consumption')
                                ->map(fn ($value) => (float) $value)
                                ->values()
                                ->all(),
                        ],
                    ],
                ],
                'user_consumption' => [
                    'labels' => $summaryByUser
                        ->take(6)
                        ->pluck('used_by')
                        ->map(fn ($user) => $user ?: 'Sem utilizador')
                        ->values()
                        ->all(),
                    'series' => $summaryByUser
                        ->take(6)
                        ->pluck('total_consumption')
                        ->map(fn ($value) => (float) $value)
                        ->values()
                        ->all(),
                ],
                'daily_consumption' => [
                    'labels' => $summaryByDate
                        ->sortBy('date')
                        ->pluck('date')
                        ->map(fn ($date) => (string) $date)
                        ->values()
                        ->all(),
                    'series' => [
                        [
                            'name' => 'Consumo diário',
                            'data' => $summaryByDate
                                ->sortBy('date')
                                ->pluck('total_consumption')
                                ->map(fn ($value) => (float) $value)
                                ->values()
                                ->all(),
                        ],
                    ],
                ],
            ],
            'filters' => $request->only(['date_from', 'date_to', 'item_id', 'warehouse_id', 'user_id', 'search', 'sort_by', 'sort_direction']),
            'items' => InventoryItem::reagents()->active()->get(['id', 'name', 'code']),
            'warehouses' => InventoryItemWarehouse::active()->get(['id', 'name']),
            'users' => \App\Models\User::whereHas('reagentConsumptions')->get(['id', 'name']),
            'stats' => [
                'total_consumption' => $summaryByItem->sum('total_consumption'),
                'total_uses' => $summaryByItem->sum('usage_count'),
                'avg_daily_consumption' => $this->getAvgDailyConsumption($request),
                'most_consumed_item' => $summaryByItem->first(),
                'most_active_user' => $summaryByUser->first(),
                'peak_consumption_day' => $summaryByDate->sortByDesc('total_consumption')->first(),
            ],
        ]);
    }

    private function getAvgDailyConsumption($request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : Carbon::now()->subMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to) : Carbon::now();
        
        $days = $dateFrom->diffInDays($dateTo) ?: 1;
        
        $total = ReagentConsumption::query()
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->sum('quantity_used');
        
        return round($total / $days, 2);
    }

    public function inventoryValue(Request $request)
    {
        // Note: This requires adding a 'unit_price' field to inventory_items
        // For now, we'll use a placeholder value
        
        $query = Inventory::with([
            'item.category',
            'warehouse.location',
            'item'
        ])
        ->when($request->category_id, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        })
        ->when($request->warehouse_id, function ($query, $warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })
        ->when($request->search, function ($query, $search) {
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        })
        ->where('qty_available', '>', 0)
        ->orderBy($request->sort_by ?? 'qty_available', $request->sort_direction ?? 'desc');

        // Summary by category
        $summaryByCategory = Inventory::select(
            'item_categories.name as category_name',
            DB::raw('SUM(qty_available) as total_quantity'),
            DB::raw('COUNT(DISTINCT item_id) as unique_items'),
            DB::raw('SUM(qty_available * 100) as total_value') // Placeholder: $100 per unit
        )
        ->leftJoin('item_categories', 'inventory.category_id', '=', 'item_categories.id')
        ->when($request->warehouse_id, function ($query, $warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })
        ->groupBy('item_categories.name', 'item_categories.id')
        ->orderByDesc('total_value')
        ->get();

        // Summary by warehouse
        $summaryByWarehouse = Inventory::select(
            'i_warehouses.name as warehouse_name',
            DB::raw('SUM(qty_available) as total_quantity'),
            DB::raw('COUNT(DISTINCT item_id) as unique_items'),
            DB::raw('SUM(qty_available * 100) as total_value') // Placeholder: $100 per unit
        )
        ->leftJoin('i_warehouses', 'inventory.warehouse_id', '=', 'i_warehouses.id')
        ->when($request->category_id, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        })
        ->groupBy('i_warehouses.name', 'i_warehouses.id')
        ->orderByDesc('total_value')
        ->get();

        // Top valuable items
        $topValuableItems = Inventory::select(
            'item_id',
            DB::raw('SUM(qty_available) as total_quantity'),
            DB::raw('SUM(qty_available * 100) as total_value') // Placeholder: $100 per unit
        )
        ->with('item:id,name,code')
        ->when($request->category_id, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        })
        ->when($request->warehouse_id, function ($query, $warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })
        ->groupBy('item_id')
        ->orderByDesc('total_value')
        ->limit(10)
        ->get();

        $totalInventoryValue = $summaryByCategory->sum('total_value');

        return Inertia::render('VAPInventory/Reports/InventoryValue', [
            'inventory' => $query->paginate($request->per_page ?? 50)->withQueryString(),
            'summaryByCategory' => $summaryByCategory,
            'summaryByWarehouse' => $summaryByWarehouse,
            'topValuableItems' => $topValuableItems,
            'charts' => [
                'category_value_breakdown' => [
                    'labels' => $summaryByCategory
                        ->map(fn ($category) => $category->category_name ?: 'Sem categoria')
                        ->values()
                        ->all(),
                    'series' => [
                        [
                            'name' => 'Valor total',
                            'data' => $summaryByCategory
                                ->map(fn ($category) => (float) $category->total_value)
                                ->values()
                                ->all(),
                        ],
                    ],
                ],
                'warehouse_value_breakdown' => [
                    'labels' => $summaryByWarehouse
                        ->map(fn ($warehouse) => $warehouse->warehouse_name ?: 'Sem armazém')
                        ->values()
                        ->all(),
                    'series' => [
                        [
                            'name' => 'Valor total',
                            'data' => $summaryByWarehouse
                                ->map(fn ($warehouse) => (float) $warehouse->total_value)
                                ->values()
                                ->all(),
                        ],
                    ],
                ],
                'top_item_value' => [
                    'labels' => $topValuableItems
                        ->map(fn ($item) => $item->item?->code ?: $item->item?->name ?: "Item #{$item->item_id}")
                        ->values()
                        ->all(),
                    'series' => [
                        [
                            'name' => 'Valor total',
                            'data' => $topValuableItems
                                ->map(fn ($item) => (float) $item->total_value)
                                ->values()
                                ->all(),
                        ],
                    ],
                ],
            ],
            'filters' => $request->only(['category_id', 'warehouse_id', 'search', 'sort_by', 'sort_direction']),
            'categories' => ItemCategory::active()->get(),
            'warehouses' => InventoryItemWarehouse::active()->get(),
            'stats' => [
                'total_value' => $totalInventoryValue,
                'total_items' => Inventory::sum('qty_available'),
                'unique_items' => Inventory::distinct('item_id')->count('item_id'),
                'avg_item_value' => $totalInventoryValue / max(1, Inventory::distinct('item_id')->count('item_id')),
                'highest_value_category' => $summaryByCategory->first(),
                'highest_value_warehouse' => $summaryByWarehouse->first(),
            ],
        ]);
    }

    public function exportReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:stock_movement,consumption,inventory_value,low_stock',
            'format' => 'required|in:pdf,csv,excel',
            'filters' => 'nullable|array',
        ]);

        $filters = $request->filters ?? [];
        $fileName = $request->report_type . '_report_' . date('Y-m-d_H-i-s');

        switch ($request->report_type) {
            case 'stock_movement':
                $data = $this->getStockMovementData($filters);
                $view = 'reports.stock-movement';
                break;

            case 'consumption':
                $data = $this->getConsumptionReportData($filters);
                $view = 'reports.consumption';
                break;

            case 'inventory_value':
                $data = $this->getInventoryValueData($filters);
                $view = 'reports.inventory-value';
                break;

            case 'low_stock':
                $data = $this->getLowStockData($filters);
                $view = 'reports.low-stock';
                break;
        }

        if ($request->format === 'pdf') {
            $pdf = PDF::loadView($view, [
                'data' => $data,
                'filters' => $filters,
                'title' => ucwords(str_replace('_', ' ', $request->report_type)) . ' Report',
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => auth()->user()->name,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($fileName . '.pdf');
        }

        if ($request->format === 'csv') {
            $csvData = $this->convertToCsv($data, $request->report_type);
            
            return Response::make($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '.csv"');
        }

        // For Excel format, you would need to install maatwebsite/excel package
        return response()->json(['message' => 'Excel export requires additional setup'], 501);
    }

    private function getStockMovementData($filters)
    {
        $query = InventoryTransaction::with([
            'item.category',
            'warehouse',
            'type',
            'user'
        ]);

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        return [
            'transactions' => $query->orderBy('created_at', 'desc')->get(),
            'summary' => $this->getMovementStats((object) $filters),
        ];
    }

    private function getConsumptionReportData($filters)
    {
        $query = ReagentConsumption::with([
            'item.category',
            'warehouse',
            'user'
        ]);

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        if (isset($filters['item_id'])) {
            $query->where('reagent_id', $filters['item_id']);
        }
        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        $consumptions = $query->orderBy('date', 'desc')->get();

        $summaryByItem = $consumptions->groupBy('reagent_name')->map(function ($group) {
            return [
                'total_consumption' => $group->sum('quantity_used'),
                'usage_count' => $group->count(),
                'avg_per_use' => $group->avg('quantity_used'),
            ];
        })->sortByDesc('total_consumption');

        return [
            'consumptions' => $consumptions,
            'summaryByItem' => $summaryByItem,
            'total_consumption' => $consumptions->sum('quantity_used'),
            'total_uses' => $consumptions->count(),
        ];
    }

    private function getInventoryValueData($filters)
    {
        $query = Inventory::with([
            'item.category',
            'warehouse.location',
            'item'
        ])
        ->where('qty_available', '>', 0);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        $inventory = $query->orderBy('qty_available', 'desc')->get();

        $summaryByCategory = $inventory->groupBy('category.name')->map(function ($group) {
            return [
                'total_quantity' => $group->sum('qty_available'),
                'unique_items' => $group->unique('item_id')->count(),
                'total_value' => $group->sum('qty_available') * 100, // Placeholder
            ];
        })->sortByDesc('total_value');

        return [
            'inventory' => $inventory,
            'summaryByCategory' => $summaryByCategory,
            'total_value' => $inventory->sum('qty_available') * 100,
            'total_items' => $inventory->sum('qty_available'),
            'unique_items' => $inventory->unique('item_id')->count(),
        ];
    }

    private function getLowStockData($filters)
    {
        $query = Inventory::with([
            'item.category',
            'warehouse.location',
            'item'
        ])
        ->whereColumn('qty_available', '<=', 'reorder_point')
        ->where('qty_available', '>', 0);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $lowStock = $query->orderByRaw('qty_available / NULLIF(reorder_point, 0)')->get();

        $criticalStock = $lowStock->where('qty_available', '<=', DB::raw('min_stock_level'))->count();

        return [
            'lowStock' => $lowStock,
            'criticalCount' => $criticalStock,
            'totalLowStock' => $lowStock->count(),
            'totalValueAtRisk' => $lowStock->sum('qty_available') * 100, // Placeholder
        ];
    }

    private function convertToCsv($data, $reportType)
    {
        $rows = [];
        
        switch ($reportType) {
            case 'stock_movement':
                $rows[] = ['Date', 'Item', 'Category', 'Warehouse', 'Type', 'Quantity', 'User', 'Reason', 'Notes'];
                foreach ($data['transactions'] as $transaction) {
                    $rows[] = [
                        $transaction->created_at->format('Y-m-d H:i'),
                        $transaction->item->name,
                        $transaction->item->category->name ?? 'N/A',
                        $transaction->warehouse->name,
                        $transaction->type->name,
                        $transaction->qty,
                        $transaction->user->name,
                        $transaction->reason,
                        $transaction->notes,
                    ];
                }
                break;

            case 'consumption':
                $rows[] = ['Date', 'Reagent', 'Quantity Used', 'Used By', 'Warehouse', 'Remarks'];
                foreach ($data['consumptions'] as $consumption) {
                    $rows[] = [
                        $consumption->date,
                        $consumption->reagent_name,
                        $consumption->quantity_used,
                        $consumption->used_by,
                        $consumption->warehouse->name ?? 'N/A',
                        $consumption->remarks,
                    ];
                }
                break;

            case 'inventory_value':
                $rows[] = ['Item', 'Category', 'Warehouse', 'Quantity', 'Unit Value', 'Total Value'];
                foreach ($data['inventory'] as $item) {
                    $unitValue = 100; // Placeholder
                    $rows[] = [
                        $item->item->name,
                        $item->item->category->name ?? 'N/A',
                        $item->warehouse->name,
                        $item->qty_available,
                        $unitValue,
                        $item->qty_available * $unitValue,
                    ];
                }
                break;

            case 'low_stock':
                $rows[] = ['Item', 'Category', 'Warehouse', 'Current Stock', 'Reorder Point', 'Min Stock', 'Status'];
                foreach ($data['lowStock'] as $item) {
                    $status = $item->qty_available <= $item->min_stock_level ? 'CRITICAL' : 'LOW';
                    $rows[] = [
                        $item->item->name,
                        $item->item->category->name ?? 'N/A',
                        $item->warehouse->name,
                        $item->qty_available,
                        $item->reorder_point,
                        $item->min_stock_level,
                        $status,
                    ];
                }
                break;
        }

        $output = fopen('php://temp', 'w');
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function dashboardStats()
    {
        $today = today();
        $thirtyDaysAgo = today()->subDays(30);

        return response()->json([
            'stats' => [
                'total_items' => InventoryItem::count(),
                'total_stock_value' => Inventory::sum('qty_available') * 100, // Placeholder
                'low_stock_items' => Inventory::lowStock()->count(),
                'expiring_reagents' => InventoryItem::reagents()
                    ->whereNotNull('reagent_expiry_date')
                    ->where('reagent_expiry_date', '<=', $today->addDays(60))
                    ->count(),
                'today_consumption' => ReagentConsumption::whereDate('date', $today)->sum('quantity_used'),
                'monthly_consumption' => ReagentConsumption::whereBetween('date', [$thirtyDaysAgo, $today])->sum('quantity_used'),
                'pending_transfers' => InventoryItemTransfer::whereNull('received_date')->count(),
                'pending_orders' => \App\Models\InventoryOrder::where('status', 'pending')->count(),
            ],
            'recent_activity' => InventoryTransaction::with('item', 'user')
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'item' => $transaction->item->name,
                        'type' => $transaction->type->name,
                        'quantity' => $transaction->qty,
                        'user' => $transaction->user->name,
                        'time' => $transaction->created_at->diffForHumans(),
                    ];
                }),
            'top_consumed' => ReagentConsumption::select(
                'reagent_name',
                DB::raw('SUM(quantity_used) as total')
            )
            ->whereBetween('date', [$thirtyDaysAgo, $today])
            ->groupBy('reagent_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get(),
        ]);
    }
}
