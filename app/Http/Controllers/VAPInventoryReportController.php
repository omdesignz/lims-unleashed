<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryOperationalReportRequest;
use App\Http\Resources\InventoryOperationalReportResource;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ReagentConsumption;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryCatalogueRead;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use PDF;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class VAPInventoryReportController extends Controller
{
    /** @var array<string,mixed> */
    private array $reportFilters = [];

    public function __construct(
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly InventoryCatalogueRead $catalogueRead,
        private readonly InventoryCatalogueAccess $catalogueAccess,
    ) {}

    /** @return Builder<InventoryItem> */
    private function items(int $labId): Builder
    {
        return $this->catalogueRead->items($labId, request()->user());
    }

    /** @return Builder<Inventory> */
    private function stock(int $labId): Builder
    {
        $query = $this->catalogueRead->filter($this->catalogueRead->stock($labId, request()->user()), $this->filters(), 'stock');
        if (request()->routeIs('vap-inventory.reports.inventory-value') || (request()->routeIs('vap-inventory.reports.export') && request()->input('report_type') === 'inventory_value')) {
            $query->where('qty_available', '>', 0);
        }

        return $query;
    }

    /** @return Builder<InventoryTransaction> */
    private function transactions(int $labId): Builder
    {
        return $this->catalogueRead->filter($this->catalogueRead->transactions($labId, request()->user()), $this->filters(), 'transaction');
    }

    /** @return Builder<ReagentConsumption> */
    private function consumptions(int $labId): Builder
    {
        return $this->catalogueRead->filter($this->catalogueRead->consumptions($labId, request()->user()), $this->filters(), 'consumption');
    }

    /** @return array<string,mixed> */
    private function filters(): array
    {
        return $this->reportFilters;
    }

    private function useFilters(InventoryOperationalReportRequest $request): void
    {
        $this->reportFilters = $request->routeIs('vap-inventory.reports.export')
            ? ($request->validated('filters') ?? []) : $request->validated();
    }

    public function stockMovement(InventoryOperationalReportRequest $request): InertiaResponse
    {
        $this->useFilters($request);
        $labId = $this->laboratoryAccess->activeLabId();
        $query = $this->transactions($labId)->with([
            'item' => fn ($items) => $items->withTrashed(),
            'item.category' => fn ($categories) => $categories->withTrashed()->select('id', 'name'),
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed(),
            'warehouse.location:id,name',
            'type' => fn ($types) => $types->withTrashed(),
            'user' => fn ($users) => $users->withTrashed()->select('id', 'name'),
        ])
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc');

        $typeTable = (new InventoryTransactionType)->getTable();
        $incomingPlaceholders = implode(', ', array_fill(0, count(InventoryTransaction::ADDITION_CODES), '?'));
        $outgoingPlaceholders = implode(', ', array_fill(0, count(InventoryTransaction::DEDUCTION_CODES), '?'));
        $movementTrend = $this->transactions($labId)->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_transactions'),
        )
            ->selectRaw("SUM(CASE WHEN type_id IN (SELECT id FROM {$typeTable} WHERE code IN ({$incomingPlaceholders})) THEN ABS(CAST(qty AS NUMERIC)) ELSE 0 END) as total_in", InventoryTransaction::ADDITION_CODES)
            ->selectRaw("SUM(CASE WHEN type_id IN (SELECT id FROM {$typeTable} WHERE code IN ({$outgoingPlaceholders})) THEN ABS(CAST(qty AS NUMERIC)) ELSE 0 END) as total_out", InventoryTransaction::DEDUCTION_CODES)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $summary = $request->view === 'summary' ? $movementTrend->sortByDesc('date')->values() : null;

        $movementStats = $this->getMovementStats();

        $typeMix = $this->transactions($labId)
            ->select('itransaction_types.code', DB::raw('COUNT(*) as total'))
            ->join('itransaction_types', 'itransactions.type_id', '=', 'itransaction_types.id')
            ->groupBy('itransaction_types.code')
            ->pluck('total', 'itransaction_types.code');

        return Inertia::render('VAPInventory/Reports/StockMovement', [
            'transactions' => $query->paginate($request->validated('per_page') ?? 50)->withQueryString()
                ->through(fn ($row): array => (new InventoryOperationalReportResource($row))->resolve($request)),
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
                        collect(InventoryTransaction::ADDITION_CODES)->sum(fn (string $code): int => (int) ($typeMix[$code] ?? 0)),
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
                            'name' => 'Transacções',
                            'data' => $movementTrend->pluck('total_transactions')->map(fn ($value) => (int) $value)->all(),
                        ],
                    ],
                ],
            ],
            'filters' => $request->only(['date_from', 'date_to', 'item_id', 'warehouse_id', 'type_id', 'search', 'view', 'sort_by', 'sort_direction']),
            'items' => $this->items($labId)->active()->get(['id', 'name', 'code']),
            'warehouses' => InventoryItemWarehouse::where('lab_id', $labId)->active()->get(['id', 'name']),
            'categories' => $this->catalogueAccess->categories($request->user(), 'view')->get(['id', 'name']),
            'stats' => $movementStats,
        ]);
    }

    /** @return array<string,mixed> */
    private function getMovementStats(): array
    {
        $query = $this->transactions($this->laboratoryAccess->activeLabId());

        $totalIn = (clone $query)->whereHas('type', function ($q) {
            $q->withTrashed()->whereIn('code', InventoryTransaction::ADDITION_CODES);
        })->sum(DB::raw('ABS(CAST(qty AS NUMERIC))'));

        $totalOut = (clone $query)->whereHas('type', function ($q) {
            $q->withTrashed()->whereIn('code', InventoryTransaction::DEDUCTION_CODES);
        })->sum(DB::raw('ABS(CAST(qty AS NUMERIC))'));

        $netMovement = $totalIn - $totalOut;

        return [
            'total_transactions' => $query->count(),
            'total_in' => (float) $totalIn,
            'total_out' => (float) $totalOut,
            'net_movement' => (float) $netMovement,
            'avg_daily_transactions' => $this->getAvgDailyTransactions(),
            'most_active_item' => $this->getMostActiveItem(),
            'most_active_user' => $this->getMostActiveUser(),
        ];
    }

    private function getAvgDailyTransactions(): float
    {
        $query = $this->transactions($this->laboratoryAccess->activeLabId());

        return $this->catalogueRead->dailyAverage($query, $this->filters(), 'created_at', precision: 2);
    }

    private function getMostActiveItem(): ?InventoryTransaction
    {
        return $this->transactions($this->laboratoryAccess->activeLabId())->select(
            'item_id',
            DB::raw('COUNT(*) as transaction_count')
        )
            ->with(['item' => fn ($items) => $items->withTrashed()->select('id', 'name')])
            ->groupBy('item_id')
            ->orderByDesc('transaction_count')
            ->first();
    }

    /** @return array{user_id: int|null, transaction_count: int, user: array{id: int, name: string}|null}|null */
    private function getMostActiveUser(): ?array
    {
        $activity = $this->transactions($this->laboratoryAccess->activeLabId())->select(
            'user_id',
            DB::raw('COUNT(*) as transaction_count')
        )
            ->with(['user' => fn ($users) => $users->withTrashed()->select('id', 'name')])
            ->groupBy('user_id')
            ->orderByDesc('transaction_count')
            ->first();

        return $activity === null ? null : [
            'user_id' => $activity->user_id,
            'transaction_count' => (int) $activity->transaction_count,
            'user' => $activity->user === null ? null : ['id' => $activity->user->id, 'name' => $activity->user->name],
        ];
    }

    public function consumptionReport(InventoryOperationalReportRequest $request): InertiaResponse
    {
        $this->useFilters($request);
        $labId = $this->laboratoryAccess->activeLabId();
        $query = $this->consumptions($labId)->unreversed()->with([
            'item' => fn ($items) => $items->withTrashed(),
            'item.category' => fn ($categories) => $categories->withTrashed()->select('id', 'name'),
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed()->select('id', 'name'),
            'user' => fn ($users) => $users->withTrashed()->select('id', 'name'),
        ])
            ->orderBy($request->sort_by ?? 'date', $request->sort_direction ?? 'desc');

        // Summary by item
        $summaryByItem = $this->consumptions($labId)->unreversed()->select(
            'reagent_id',
            'reagent_name',
            DB::raw('SUM(quantity_used) as total_consumption'),
            DB::raw('COUNT(*) as usage_count'),
            DB::raw('AVG(quantity_used) as avg_per_use')
        )
            ->groupBy('reagent_id', 'reagent_name')
            ->orderByDesc('total_consumption')
            ->get();

        // Summary by date
        $summaryByDate = $this->consumptions($labId)->unreversed()->select(
            DB::raw('DATE(date) as date'),
            DB::raw('SUM(quantity_used) as total_consumption'),
            DB::raw('COUNT(*) as usage_count')
        )
            ->groupBy(DB::raw('DATE(date)'))
            ->orderByDesc('date')
            ->get();

        // Summary by user
        $summaryByUser = $this->consumptions($labId)->unreversed()->select(
            'used_by',
            DB::raw('SUM(quantity_used) as total_consumption'),
            DB::raw('COUNT(*) as usage_count')
        )
            ->whereNotNull('used_by')
            ->groupBy('used_by')
            ->orderByDesc('total_consumption')
            ->get();

        return Inertia::render('VAPInventory/Reports/Consumption', [
            'consumptions' => $query->paginate($request->validated('per_page') ?? 50)->withQueryString()
                ->through(fn ($row): array => (new InventoryOperationalReportResource($row))->resolve($request)),
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
            'items' => $this->items($labId)->reagents()->active()->get(['id', 'name', 'code']),
            'warehouses' => InventoryItemWarehouse::where('lab_id', $labId)->active()->get(['id', 'name']),
            'users' => User::withTrashed()->whereIn('id', $this->consumptions($labId)->select('user_id'))->get(['id', 'name'])
                ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name]),
            'stats' => [
                'total_consumption' => $summaryByItem->sum('total_consumption'),
                'total_uses' => $summaryByItem->sum('usage_count'),
                'avg_daily_consumption' => $this->getAvgDailyConsumption(),
                'most_consumed_item' => $summaryByItem->first(),
                'most_active_user' => $summaryByUser->first(),
                'peak_consumption_day' => $summaryByDate->sortByDesc('total_consumption')->first(),
            ],
        ]);
    }

    private function getAvgDailyConsumption(): float
    {
        $query = $this->consumptions($this->laboratoryAccess->activeLabId())->unreversed();

        return $this->catalogueRead->dailyAverage($query, $this->filters(), 'date', 'quantity_used');
    }

    public function inventoryValue(InventoryOperationalReportRequest $request): InertiaResponse
    {
        $this->useFilters($request);
        $labId = $this->laboratoryAccess->activeLabId();
        $query = $this->stock($labId)->with([
            'item' => fn ($items) => $items->withTrashed(),
            'item.category' => fn ($categories) => $categories->withTrashed()->select('id', 'name'),
            'item.unit',
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed(),
            'warehouse.location:id,name',
        ])
            ->where('qty_available', '>', 0)
            ->orderBy($request->sort_by ?? 'qty_available', $request->sort_direction ?? 'desc');

        // Summary by category
        $summaryByCategory = $this->stock($labId)->select(
            'item_categories.name as category_name',
            DB::raw('COUNT(DISTINCT item_id) as unique_items'),
            DB::raw('SUM(inventory.qty_available * COALESCE(i_items.standard_cost, i_items.last_purchase_price, 0)) as total_value')
        )
            ->leftJoin('i_items', 'inventory.item_id', '=', 'i_items.id')
            ->leftJoin('item_categories', 'i_items.category_id', '=', 'item_categories.id')
            ->groupBy('item_categories.name', 'item_categories.id')
            ->orderByDesc('total_value')
            ->get();

        // Summary by warehouse
        $summaryByWarehouse = $this->stock($labId)->select(
            'i_warehouses.name as warehouse_name',
            DB::raw('COUNT(DISTINCT item_id) as unique_items'),
            DB::raw('SUM(inventory.qty_available * COALESCE(i_items.standard_cost, i_items.last_purchase_price, 0)) as total_value')
        )
            ->leftJoin('i_warehouses', 'inventory.warehouse_id', '=', 'i_warehouses.id')
            ->leftJoin('i_items', 'inventory.item_id', '=', 'i_items.id')
            ->groupBy('i_warehouses.name', 'i_warehouses.id')
            ->orderByDesc('total_value')
            ->get();

        // Top valuable items
        $topValuableItems = $this->stock($labId)->select(
            'item_id',
            DB::raw('SUM(qty_available) as total_quantity'),
            DB::raw('SUM(inventory.qty_available * COALESCE(i_items.standard_cost, i_items.last_purchase_price, 0)) as total_value')
        )
            ->with(['item' => fn ($items) => $items->withTrashed()->select('id', 'name', 'code')])
            ->leftJoin('i_items', 'inventory.item_id', '=', 'i_items.id')
            ->groupBy('item_id')
            ->orderByDesc('total_value')
            ->limit(10)
            ->get();

        $totalInventoryValue = $summaryByCategory->sum('total_value');

        return Inertia::render('VAPInventory/Reports/InventoryValue', [
            'inventory' => $query->paginate($request->validated('per_page') ?? 50)->withQueryString()
                ->through(fn ($row): array => (new InventoryOperationalReportResource($row))->resolve($request)),
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
                        ->map(fn ($item) => $item->item?->code ?: $item->item?->name ?: "Artigo #{$item->item_id}")
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
            'categories' => $this->catalogueAccess->categories($request->user(), 'view')->get(['id', 'name']),
            'warehouses' => InventoryItemWarehouse::where('lab_id', $labId)->active()->get(['id', 'name']),
            'stats' => [
                'total_value' => $totalInventoryValue,
                'stock_positions' => $this->stock($labId)->count(),
                'unique_items' => $this->stock($labId)->distinct('item_id')->count('item_id'),
                'avg_item_value' => $totalInventoryValue / max(1, $this->stock($labId)->distinct('item_id')->count('item_id')),
                'highest_value_category' => $summaryByCategory->first(),
                'highest_value_warehouse' => $summaryByWarehouse->first(),
            ],
        ]);
    }

    public function exportReport(InventoryOperationalReportRequest $request): HttpResponse
    {
        $this->useFilters($request);
        $filters = $request->validated('filters') ?? [];
        $reportLabels = [
            'stock_movement' => 'movimentos_de_existencias',
            'consumption' => 'consumo',
            'inventory_value' => 'valor_do_inventario',
            'low_stock' => 'existencias_baixas',
        ];
        $fileName = $reportLabels[$request->report_type].'_relatorio_'.date('Y-m-d_H-i-s');

        switch ($request->report_type) {
            case 'stock_movement':
                $data = $this->getStockMovementData();
                break;

            case 'consumption':
                $data = $this->getConsumptionReportData();
                break;

            case 'inventory_value':
                $data = $this->getInventoryValueData($filters);
                break;

            case 'low_stock':
                $data = $this->getLowStockData($filters);
                break;
        }

        $rows = $this->reportRows($data, $request->report_type);
        if ($request->format === 'pdf') {
            $pdf = PDF::loadView('reports.inventory-export', [
                'columns' => $rows[0],
                'rows' => array_slice($rows, 1),
                'title' => match ($request->report_type) {
                    'stock_movement' => 'Relatório de movimentos de existências',
                    'consumption' => 'Relatório de consumo',
                    'inventory_value' => 'Relatório do valor do inventário',
                    'low_stock' => 'Relatório de existências baixas',
                },
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => auth()->user()->name,
                'lab_name' => VAPLab::query()->whereKey($this->laboratoryAccess->activeLabId())->value('name'),
            ], [], ['format' => 'A4', 'orientation' => 'L']);

            return Response::make($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'.pdf"');
        }

        if ($request->format === 'csv') {
            $csvData = $this->convertToCsv($rows, $request->report_type);

            return Response::make($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'.csv"');
        }

        // For Excel format, you would need to install maatwebsite/excel package
        return response()->json(['message' => 'A exportação para Excel requer configuração adicional.'], 501);
    }

    /** @return array<string,mixed> */
    private function getStockMovementData(): array
    {
        $query = $this->transactions($this->laboratoryAccess->activeLabId())->with([
            'item' => fn ($items) => $items->withTrashed(),
            'item.category' => fn ($categories) => $categories->withTrashed()->select('id', 'name'),
            'item.unit',
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed()->select('id', 'name'),
            'type' => fn ($types) => $types->withTrashed(),
            'user' => fn ($users) => $users->withTrashed()->select('id', 'name'),
        ]);

        return [
            'transactions' => $query->orderBy('created_at', 'desc')->get(),
            'summary' => $this->getMovementStats(),
        ];
    }

    /** @return array<string,mixed> */
    private function getConsumptionReportData(): array
    {
        $query = $this->consumptions($this->laboratoryAccess->activeLabId())->unreversed()->with([
            'item' => fn ($items) => $items->withTrashed(),
            'item.category' => fn ($categories) => $categories->withTrashed()->select('id', 'name'),
            'item.unit',
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed()->select('id', 'name'),
            'user' => fn ($users) => $users->withTrashed()->select('id', 'name'),
        ]);

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

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    private function getInventoryValueData(array $filters): array
    {
        $query = $this->stock($this->laboratoryAccess->activeLabId())->with([
            'item' => fn ($items) => $items->withTrashed(),
            'item.category' => fn ($categories) => $categories->withTrashed()->select('id', 'name'),
            'item.unit',
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed(),
            'warehouse.location:id,name',
        ])
            ->where('qty_available', '>', 0);

        $inventory = $query->orderBy('qty_available', ($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc')->get();

        $summaryByCategory = $inventory->groupBy('item.category.name')->map(function ($group) {
            return [
                'unique_items' => $group->unique('item_id')->count(),
                'total_value' => $group->sum(fn (Inventory $item): float => $item->qty_available * (float) ($item->item?->standard_cost ?? $item->item?->last_purchase_price ?? 0)),
            ];
        })->sortByDesc('total_value');

        return [
            'inventory' => $inventory,
            'summaryByCategory' => $summaryByCategory,
            'total_value' => $inventory->sum(fn (Inventory $item): float => $item->qty_available * (float) ($item->item?->standard_cost ?? $item->item?->last_purchase_price ?? 0)),
            'stock_positions' => $inventory->count(),
            'unique_items' => $inventory->unique('item_id')->count(),
        ];
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    private function getLowStockData(array $filters): array
    {
        $query = $this->stock($this->laboratoryAccess->activeLabId())->with([
            'item' => fn ($items) => $items->withTrashed(),
            'item.category' => fn ($categories) => $categories->withTrashed()->select('id', 'name'),
            'item.unit',
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed(),
            'warehouse.location:id,name',
        ])
            ->whereColumn('qty_available', '<=', 'reorder_point')
            ->when(($filters['severity'] ?? null) === 'critical', fn (Builder $query): Builder => $query->whereColumn('qty_available', '<=', 'min_stock_level'))
            ->when(($filters['severity'] ?? null) === 'low', fn (Builder $query): Builder => $query->whereColumn('qty_available', '>', 'min_stock_level'));

        $lowStock = $query->orderByRaw('CASE WHEN qty_available <= 0 THEN 0 ELSE 1 END')
            ->orderByRaw('qty_available / NULLIF(reorder_point, 0)')->get();

        $criticalStock = $lowStock->filter(fn (Inventory $item): bool => (float) $item->qty_available > 0 && $item->qty_available <= $item->min_stock_level)->count();

        return [
            'lowStock' => $lowStock,
            'criticalCount' => $criticalStock,
            'totalLowStock' => $lowStock->count(),
            'totalValueAtRisk' => $lowStock->sum(fn (Inventory $item): float => $item->qty_available * (float) ($item->item?->standard_cost ?? $item->item?->last_purchase_price ?? 0)),
        ];
    }

    private function reportRows(array $data, string $reportType): array
    {
        $rows = [];

        switch ($reportType) {
            case 'stock_movement':
                $rows[] = ['Data', 'Artigo', 'Categoria', 'Armazém', 'Tipo', 'Quantidade', 'Unidade', 'Utilizador', 'Motivo', 'Notas'];
                foreach ($data['transactions'] as $transaction) {
                    $rows[] = [
                        $transaction->created_at->format('Y-m-d H:i'),
                        $transaction->item->name,
                        $transaction->item->category->name ?? 'N/D',
                        $transaction->warehouse->name,
                        $transaction->type?->name,
                        $transaction->qty,
                        $transaction->item?->unit?->code ?? 'N/D',
                        $transaction->user?->name,
                        $transaction->reason,
                        $transaction->notes,
                    ];
                }
                break;

            case 'consumption':
                $rows[] = ['Data', 'Reagente', 'Quantidade utilizada', 'Unidade', 'Utilizado por', 'Armazém', 'Observações'];
                foreach ($data['consumptions'] as $consumption) {
                    $rows[] = [
                        $consumption->date,
                        $consumption->reagent_name,
                        $consumption->quantity_used,
                        $consumption->item?->unit?->code ?? 'N/D',
                        $consumption->used_by,
                        $consumption->warehouse->name ?? 'N/D',
                        $consumption->remarks,
                    ];
                }
                break;

            case 'inventory_value':
                $rows[] = ['Artigo', 'Categoria', 'Armazém', 'Quantidade', 'Unidade', 'Valor unitário', 'Valor total'];
                foreach ($data['inventory'] as $item) {
                    $unitValue = (float) ($item->item?->standard_cost ?? $item->item?->last_purchase_price ?? 0);
                    $rows[] = [
                        $item->item->name,
                        $item->item->category->name ?? 'N/D',
                        $item->warehouse->name,
                        $item->qty_available,
                        $item->item?->unit?->code ?? 'N/D',
                        $unitValue,
                        $item->qty_available * $unitValue,
                    ];
                }
                break;

            case 'low_stock':
                $rows[] = ['Artigo', 'Categoria', 'Armazém', 'Existências actuais', 'Unidade', 'Ponto de reposição', 'Existências mínimas', 'Estado'];
                foreach ($data['lowStock'] as $item) {
                    $status = match (true) {
                        (float) $item->qty_available <= 0 => 'SEM EXISTÊNCIAS',
                        $item->qty_available <= $item->min_stock_level => 'CRÍTICO',
                        default => 'BAIXO',
                    };
                    $rows[] = [
                        $item->item->name,
                        $item->item->category->name ?? 'N/D',
                        $item->warehouse->name,
                        $item->qty_available,
                        $item->item?->unit?->code ?? 'N/D',
                        $item->reorder_point,
                        $item->min_stock_level,
                        $status,
                    ];
                }
                break;
        }

        return $rows;
    }

    private function convertToCsv(array $rows, string $reportType): string
    {
        $numericColumns = match ($reportType) {
            'stock_movement' => [5],
            'consumption' => [2],
            'inventory_value' => [3, 5, 6],
            'low_stock' => [3, 5, 6],
        };
        $output = fopen('php://temp', 'w');
        foreach ($rows as $index => $row) {
            foreach ($row as $column => $value) {
                if ($index > 0 && ! in_array($column, $numericColumns, true) && is_string($value) && preg_match('/^[\x00-\x20]*[=+@-]/', $value) === 1) {
                    $row[$column] = "'".$value;
                }
            }
            fputcsv($output, $row, ',', '"', '');
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function dashboardStats(InventoryOperationalReportRequest $request): JsonResponse
    {
        $this->useFilters($request);
        $labId = $this->laboratoryAccess->activeLabId();
        $today = today();
        $thirtyDaysAgo = today()->subDays(30);

        return response()->json([
            'stats' => [
                'total_items' => $this->items($labId)->count(),
                'total_stock_value' => (float) $this->stock($labId)
                    ->join('i_items', 'inventory.item_id', '=', 'i_items.id')
                    ->sum(DB::raw('inventory.qty_available * COALESCE(i_items.standard_cost, i_items.last_purchase_price, 0)')),
                'low_stock_items' => $this->stock($labId)->lowStock()->count(),
                'expiring_reagents' => $this->items($labId)->reagents()
                    ->whereNotNull('reagent_expiry_date')
                    ->where('reagent_expiry_date', '<=', $today->copy()->addDays(60))
                    ->count(),
                'today_consumption' => (float) $this->consumptions($labId)->unreversed()->whereDate('date', $today)->sum('quantity_used'),
                'monthly_consumption' => (float) $this->consumptions($labId)->unreversed()->whereBetween('date', [$thirtyDaysAgo, $today])->sum('quantity_used'),
                'pending_transfers' => $request->user()->can('view_itransfers') ? InventoryItemTransfer::where('lab_id', $labId)
                    ->whereHas('item', fn (Builder $items): Builder => $this->catalogueRead->constrainItems($items->withTrashed(), $labId, $request->user()))
                    ->whereNull('received_date')->count() : null,
                'pending_orders' => null,
            ],
            'recent_activity' => $request->user()->can('view_itransactions') ? $this->transactions($labId)->with(['item' => fn ($items) => $items->withTrashed()->select('id', 'name'), 'user' => fn ($users) => $users->withTrashed()->select('id', 'name'), 'type' => fn ($types) => $types->withTrashed()->select('id', 'name')])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'item' => $transaction->item->name,
                        'type' => $transaction->type?->name,
                        'quantity' => $transaction->qty,
                        'user' => $transaction->user?->name,
                        'time' => $transaction->created_at->diffForHumans(),
                    ];
                }) : [],
            'top_consumed' => $this->consumptions($labId)->unreversed()->select(
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
