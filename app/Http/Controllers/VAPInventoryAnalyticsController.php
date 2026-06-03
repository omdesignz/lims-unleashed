<?php

namespace App\Http\Controllers;

use App\Exports\InventoryReportExport;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\ItemCategory;
use App\Models\ReagentConsumption;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use PDF;

class VAPInventoryAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $categories = ItemCategory::active()->get();
        $warehouses = InventoryItemWarehouse::with('location')->active()->get();

        // Call the internal logic method instead of the Request-bound one
        $initialData = $this->fetchAnalyticsData([
            'dateRange' => '30d',
            'categoryId' => $request->category_id,
            'warehouseId' => $request->warehouse_id,
        ]);

        return inertia('VAPInventory/Analytics/Index', [
            'categories' => $categories,
            'warehouses' => $warehouses,
            'initialData' => $initialData,
        ]);
    }

    /**
     * Public API endpoint for AJAX/Inertia refreshes
     */
    public function getAnalyticsData(Request $request)
    {
        // Pass the request input as an array to the logic method
        $data = $this->fetchAnalyticsData($request->all());

        return response()->json($data);
    }

    public function getRealtimeData(Request $request): JsonResponse
    {
        $data = $this->fetchAnalyticsData([
            'dateRange' => $request->input('dateRange', '30d'),
            'startDate' => $request->input('startDate'),
            'endDate' => $request->input('endDate'),
            'categoryId' => $request->input('categoryId', $request->input('category_id')),
            'warehouseId' => $request->input('warehouseId', $request->input('warehouse_id')),
        ]);

        return response()->json([
            ...$data,
            'refreshedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * Internal logic method that handles the actual data processing
     */
    private function fetchAnalyticsData(array $filters)
    {
        // Wrap filters in a collection or object for easier access if preferred,
        // or just access the array keys.
        $dateRange = $this->getDateRange(
            $filters['dateRange'] ?? '30d',
            $filters['startDate'] ?? null,
            $filters['endDate'] ?? null
        );

        $catId = $filters['categoryId'] ?? null;
        $whId = $filters['warehouseId'] ?? null;

        return [
            'consumptionTrend' => $this->getConsumptionTrendData($dateRange, $catId, $whId),
            'stockDistribution' => $this->getStockDistributionData($catId, $whId),
            'monthlyComparison' => $this->getMonthlyComparisonData($dateRange, $catId),
            'topReagents' => $this->getTopReagentsData($dateRange, $whId),
            'consumptionHistory' => $this->getConsumptionHistory($dateRange, $catId, $whId),
            'metrics' => $this->getMetrics($dateRange, $catId, $whId),
        ];
    }

    private function getDateRange($range, $startDate = null, $endDate = null)
    {
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();
        $start = $startDate ? Carbon::parse($startDate) : match ($range) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            '1y' => $end->copy()->subYear(),
            default => $end->copy()->subDays(30),
        };

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function getConsumptionTrendData($dateRange, $categoryId = null, $warehouseId = null)
    {
        $query = ReagentConsumption::with(['item', 'warehouse'])
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->whereHas('item', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            })
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            });

        // Group by day
        return $query->select(
            DB::raw('DATE(date) as date'),
            DB::raw('SUM(quantity_used) as quantity')
        )
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'quantity' => (float) $item->quantity,
                ];
            });
    }

    private function getStockDistributionData($categoryId = null, $warehouseId = null)
    {
        return Inventory::query()
            // Join with categories to get the names for the chart labels
            ->leftJoin('item_categories', 'inventory.category_id', '=', 'item_categories.id')

            // Filter by parameters
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('inventory.category_id', $categoryId);
            })
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->where('inventory.warehouse_id', $warehouseId);
            })

            // Select only what the chart needs
            ->select(
                'item_categories.name as category_name',
                DB::raw('SUM(inventory.qty_available) as total_quantity')
            )

            // Group by name and ID (ID ensures uniqueness, name is for the label)
            ->groupBy('item_categories.name', 'item_categories.id')
            ->orderByDesc('total_quantity')
            ->get()
            ->map(function ($row) {
                return [
                    'category' => $row->category_name ?? 'Uncategorized',
                    'quantity' => (int) $row->total_quantity,
                ];
            });
    }

    private function getMonthlyComparisonData($dateRange, $categoryId = null)
    {
        $currentYear = $dateRange['end']->year;
        $previousYear = $currentYear - 1;

        $query = ReagentConsumption::query()
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->whereHas('item', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            });

        // Current year data
        $currentYearData = $query->clone()
            ->whereYear('date', $currentYear)
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(quantity_used) as total')
            )
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Previous year data
        $previousYearData = $query->clone()
            ->whereYear('date', $previousYear)
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(quantity_used) as total')
            )
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create()->month($i)->format('M');
            $current = $currentYearData->get($i)->total ?? 0;
            $previous = $previousYearData->get($i)->total ?? 0;

            $months[] = [
                'month' => $monthName,
                'current' => (float) $current,
                'previous' => (float) $previous,
            ];
        }

        return $months;
    }

    private function getTopReagentsData($dateRange, $warehouseId = null)
    {
        $query = ReagentConsumption::with(['item'])
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            });

        return $query->select(
            'reagent_id',
            'reagent_name',
            DB::raw('SUM(quantity_used) as consumption')
        )
            ->groupBy('reagent_id', 'reagent_name')
            ->orderByDesc('consumption')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->reagent_id,
                    'name' => $item->reagent_name,
                    'consumption' => (float) $item->consumption,
                ];
            });
    }

    private function getConsumptionHistory($dateRange, $categoryId = null, $warehouseId = null)
    {
        return ReagentConsumption::with(['item.inventory', 'warehouse'])
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->whereHas('item', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            })
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->orderByDesc('date')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                $stockRecord = $item->item?->inventory()
                    ->where('warehouse_id', $item->warehouse_id)
                    ->first();

                $sparklineData = ReagentConsumption::where('reagent_id', $item->reagent_id)
                    ->whereBetween('date', [now()->subDays(7), now()])
                    ->orderBy('date')
                    ->get()
                    ->groupBy(fn ($c) => $c->date->format('Y-m-d'))
                    ->map(fn ($group) => $group->sum('quantity_used'))
                    ->values()
                    ->toArray();

                $sevenDayTotal = ReagentConsumption::where('reagent_id', $item->reagent_id)
                    ->whereBetween('date', [now()->subDays(7), now()])
                    ->sum('quantity_used');

                $dailyAvg = $sevenDayTotal / 7;

                // Avoid division by zero
                $daysRemaining = $dailyAvg > 0 && $stockRecord
                    ? floor($stockRecord->qty_available / $dailyAvg)
                    : null;

                return [
                    'id' => $item->id,
                    'date' => $item->date,
                    'reagent_name' => $item->reagent_name,
                    'quantity_used' => (float) $item->quantity_used,
                    'used_by' => $item->used_by,
                    'remarks' => $item->remarks,
                    'item' => $item->item,
                    'warehouse' => $item->warehouse,
                    'current_stock' => $stockRecord?->qty_available ?? 0,
                    'min_level' => $stockRecord?->min_stock_level ?? 1,
                    'sparkline' => $sparklineData ?: [0, 0, 0, 0, 0, 0, 0],
                    'daily_avg' => $dailyAvg,
                    'days_remaining' => $daysRemaining,
                    'predicted_out_date' => $daysRemaining !== null ? now()->addDays($daysRemaining)->format('Y-m-d') : null,
                ];
            });
    }

    private function getMetrics($dateRange, $categoryId = null, $warehouseId = null)
    {
        // 1. BASE QUERY
        $inventoryBase = Inventory::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId));

        // 2. REORDER ALERTS (Low Stock)
        $reorderItems = (clone $inventoryBase)
            ->whereRaw('qty_available <= min_stock_level')
            ->where('qty_available', '>', 0)
            ->with('item')
            ->get();

        // 3. EXPIRING ALERTS (Items where reagent_expiry_date is within 30 days)
        $expiringItems = (clone $inventoryBase)
            ->whereHas('item', function ($query) {
                $query->whereNotNull('reagent_expiry_date')
                    ->where('reagent_expiry_date', '>', now())
                    ->where('reagent_expiry_date', '<=', now()->addDays(30));
            })
            ->with('item')
            ->get();

        // 4. CRITICAL ALERTS (Empty stock OR already expired)
        $criticalItems = (clone $inventoryBase)
            ->where(function ($q) {
                $q->where('qty_available', 0)
                    ->orWhereHas('item', function ($query) {
                        $query->whereNotNull('reagent_expiry_date')
                            ->where('reagent_expiry_date', '<=', now());
                    });
            })
            ->with('item')
            ->get();

        // 5. CONSUMPTION TRENDS
        $currentConsumption = ReagentConsumption::whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->sum('quantity_used');

        $daysDiff = $dateRange['start']->diffInDays($dateRange['end']);
        $prevStart = (clone $dateRange['start'])->subDays($daysDiff);
        $prevEnd = (clone $dateRange['start'])->subDay();

        $previousConsumption = ReagentConsumption::whereBetween('date', [$prevStart, $prevEnd])
            ->sum('quantity_used');

        $usageChange = $previousConsumption > 0
            ? (($currentConsumption - $previousConsumption) / $previousConsumption) * 100
            : 0;

        // 6. FINANCIAL VALUE
        $totalValue = (clone $inventoryBase)
            ->join('i_items', 'inventory.item_id', '=', 'i_items.id')
            ->value(DB::raw('SUM(inventory.qty_available * i_items.unit_cost)')) ?? 0;

        return [
            'totalConsumption' => (float) $currentConsumption,
            'monthlyConsumption' => (float) ReagentConsumption::whereMonth('date', now()->month)->sum('quantity_used'),
            'dailyAverage' => $daysDiff > 0 ? round($currentConsumption / $daysDiff, 2) : $currentConsumption,
            'usageChange' => round($usageChange, 1),

            'reorderAlerts' => $reorderItems->count(),
            'criticalAlerts' => $criticalItems->count(),
            'expiringAlerts' => $expiringItems->count(),

            'inventoryValue' => (float) $totalValue,

            'reagentsValue' => (float) $totalValue,

            'equipmentValue' => (float) $totalValue,
            'itemsNeedingCalibration' => InventoryItem::query()
                ->whereNotNull('next_calibration_date')
                ->where('next_calibration_date', '<=', now())
                ->count(),

            'alertDetails' => [
                'reorder' => $reorderItems->map(fn ($i) => $i->item->name)->take(5),
                'expiring' => $expiringItems->map(fn ($i) => $i->item->name.' ('.$i->item->reagent_expiry_date->format('d M').')')->take(5),
                'critical' => $criticalItems->map(fn ($i) => $i->item->name)->take(5),
            ],

            'total_items' => Inventory::count(),

            'supplierPerformance' => collect($this->getSupplierPerformanceData())->map(function ($item) {
                return [
                    'supplier' => $item->supplier,
                    'on_time_rate' => (float) $item->on_time_rate,
                    'avg_delay' => (float) $item->avg_delay,
                ];
            })->toArray(),
        ];
    }

    public function exportChart(Request $request)
    {
        $request->validate([
            'chartType' => 'required|in:consumption,stock,monthly,topReagents',
            'format' => 'required|in:png,pdf,csv',
        ]);

        $data = $this->fetchAnalyticsData($request->all());
        $filename = 'chart_export_'.time().'.'.$request->format;

        switch ($request->format) {
            case 'pdf':
                $pdf = PDF::loadView('exports.chart', [
                    'chartType' => $request->chartType,
                    'data' => $data,
                    'filters' => $request->all(),
                    'settings' => app(GeneralSettings::class),
                    'generated_at' => now(),
                ]);

                return $pdf->download($filename);

            case 'csv':
                $csvData = $this->convertToCsv($data, $request->chartType);

                return response($csvData)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

            default:
                // For PNG, you would need a server-side chart generation library
                // This is a placeholder - you might want to use something like mPDF with charts
                return response()->json(['message' => 'PNG export not implemented'], 501);
        }
    }

    private function convertToCsv($data, $chartType)
    {
        $rows = [];

        switch ($chartType) {
            case 'consumption':
                $rows[] = ['Date', 'Quantity'];
                foreach ($data['consumptionTrend'] as $item) {
                    $rows[] = [$item['date'], $item['quantity']];
                }
                break;

            case 'stock':
                $rows[] = ['Category', 'Quantity'];
                foreach ($data['stockDistribution'] as $item) {
                    $rows[] = [$item['category'], $item['quantity']];
                }
                break;

            case 'monthly':
                $rows[] = ['Month', 'Current Year', 'Previous Year'];
                foreach ($data['monthlyComparison'] as $item) {
                    $rows[] = [$item['month'], $item['current'], $item['previous']];
                }
                break;

            case 'topReagents':
                $rows[] = ['Reagent', 'Consumption'];
                foreach ($data['topReagents'] as $item) {
                    $rows[] = [$item['name'], $item['consumption']];
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

    // public function generateReport(Request $request)
    // {
    //     $request->validate([
    //         'reportType' => 'required|in:consumption,stock,expiry',
    //         'format' => 'required|in:pdf,excel',
    //     ]);

    //     $dateRange = $this->getDateRange(
    //         $request->dateRange,
    //         $request->startDate,
    //         $request->endDate
    //     );

    //     switch ($request->reportType) {
    //         case 'consumption':
    //             $data = $this->getConsumptionReportData($dateRange, $request->categoryId, $request->warehouseId);
    //             $view = 'reports.consumption';
    //             break;

    //         case 'stock':
    //             $data = $this->getStockReportData($request->categoryId, $request->warehouseId);
    //             $view = 'reports.stock';
    //             break;

    //         case 'expiry':
    //             $data = $this->getExpiryReportData();
    //             $view = 'reports.expiry';
    //             break;
    //     }

    //     if ($request->format === 'pdf') {
    //         $pdf = PDF::loadView($view, [
    //             'data' => $data,
    //             'filters' => $request->all(),
    //             'dateRange' => $dateRange,
    //         ])->setPaper('a4', 'landscape');

    //         return $pdf->download($request->reportType . '_report_' . date('Y-m-d') . '.pdf');
    //     }

    //     // For Excel, you would use Laravel Excel package
    //     return response()->json(['message' => 'Excel export not implemented'], 501);
    // }

    public function generateReport(Request $request)
    {
        $request->validate([
            'reportType' => 'required|in:consumption,stock,expiry,calibration,comprehensive',
            'format' => 'required|in:pdf,excel,csv',
        ]);

        $dateRange = $this->getDateRange($request->dateRange, $request->startDate, $request->endDate);

        // Get data based on type (using your existing methods)
        $data = match ($request->reportType) {
            'consumption' => $this->getConsumptionReportData($dateRange, $request->categoryId, $request->warehouseId),
            'stock' => $this->getStockReportData($request->categoryId, $request->warehouseId),
            'expiry' => $this->getExpiryReportData(),
            'calibration' => $this->getCalibrationReportData(),
            'comprehensive' => $this->getComprehensiveReportData($dateRange, $request->categoryId, $request->warehouseId),
        };

        $filename = $request->reportType.'_report_'.now()->format('Y-m-d');

        if ($request->format === 'excel') {
            return Excel::download(
                new InventoryReportExport($data, $request->reportType),
                $filename.'.xlsx'
            );
        }

        if ($request->format === 'csv') {
            return response(
                $this->convertReportDataToCsv($data, $request->reportType)
            )->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'.csv"');
        }

        if ($request->format === 'pdf') {
            $view = View::exists('reports.'.$request->reportType)
                ? 'reports.'.$request->reportType
                : 'reports.inventory-generic';

            $html = View::make($view, [
                'data' => $data,
                'filters' => $request->all(),
                'dateRange' => $dateRange,
                'reportType' => $request->reportType,
            ])->render();

            $pdf = new Mpdf([
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 45, // Enough room for the fixed header
                'margin_bottom' => 20,
                'format' => 'A4',
            ]);

            $pdf->SetTitle('VAP Inventory Report');
            $pdf->WriteHTML($html);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->Output('', 'S');
            }, $request->reportType.'_report.pdf');
        }
    }

    private function getCalibrationReportData(): array
    {
        return [
            'due' => InventoryItem::whereNotNull('next_calibration_date')
                ->where('next_calibration_date', '<', now())
                ->get(),
            'dueSoon' => InventoryItem::whereNotNull('next_calibration_date')
                ->whereBetween('next_calibration_date', [now(), now()->addDays(30)])
                ->get(),
            'scheduled' => InventoryItem::whereNotNull('next_calibration_date')
                ->orderBy('next_calibration_date')
                ->limit(100)
                ->get(),
        ];
    }

    private function getComprehensiveReportData(array $dateRange, $categoryId = null, $warehouseId = null): array
    {
        return [
            'metrics' => $this->getMetrics($dateRange, $categoryId, $warehouseId),
            'consumption' => $this->getConsumptionReportData($dateRange, $categoryId, $warehouseId),
            'stock' => $this->getStockReportData($categoryId, $warehouseId),
            'expiry' => $this->getExpiryReportData(),
            'calibration' => $this->getCalibrationReportData(),
        ];
    }

    private function convertReportDataToCsv(array $data, string $reportType): string
    {
        $output = fopen('php://temp', 'w+');

        match ($reportType) {
            'consumption' => $this->writeConsumptionCsv($output, $data),
            'stock' => $this->writeStockCsv($output, $data),
            'expiry' => $this->writeExpiryCsv($output, $data),
            'calibration' => $this->writeCalibrationCsv($output, $data),
            'comprehensive' => $this->writeComprehensiveCsv($output, $data),
        };

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }

    private function writeConsumptionCsv($output, array $data): void
    {
        fputcsv($output, ['Date', 'Quantity']);

        foreach ($data['trend'] ?? [] as $item) {
            fputcsv($output, [$item['date'] ?? '', $item['quantity'] ?? 0]);
        }
    }

    private function writeStockCsv($output, array $data): void
    {
        fputcsv($output, ['Category', 'Quantity']);

        foreach ($data['distribution'] ?? [] as $item) {
            fputcsv($output, [$item['category'] ?? '', $item['quantity'] ?? 0]);
        }
    }

    private function writeExpiryCsv($output, array $data): void
    {
        fputcsv($output, ['Section', 'Item', 'Expiry Date']);

        foreach (['expired', 'expiringSoon', 'expiringNextMonth'] as $section) {
            foreach ($data[$section] ?? [] as $item) {
                fputcsv($output, [
                    $section,
                    $item->name ?? '',
                    optional($item->reagent_expiry_date)->format('Y-m-d'),
                ]);
            }
        }
    }

    private function writeCalibrationCsv($output, array $data): void
    {
        fputcsv($output, ['Section', 'Equipment', 'Next Calibration Date']);

        foreach (['due', 'dueSoon', 'scheduled'] as $section) {
            foreach ($data[$section] ?? [] as $item) {
                fputcsv($output, [
                    $section,
                    $item->name ?? '',
                    optional($item->next_calibration_date)->format('Y-m-d'),
                ]);
            }
        }
    }

    private function writeComprehensiveCsv($output, array $data): void
    {
        fputcsv($output, ['Metric', 'Value']);

        foreach ($data['metrics'] ?? [] as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            fputcsv($output, [$key, $value]);
        }
    }

    private function getConsumptionReportData($dateRange, $categoryId = null, $warehouseId = null)
    {
        return [
            'trend' => $this->getConsumptionTrendData($dateRange, $categoryId, $warehouseId),
            'topItems' => $this->getTopReagentsData($dateRange, $warehouseId),
            'monthlyComparison' => $this->getMonthlyComparisonData($dateRange, $categoryId),
            'metrics' => $this->getMetrics($dateRange, $categoryId, $warehouseId),
        ];
    }

    private function getStockReportData($categoryId = null, $warehouseId = null)
    {
        return [
            'distribution' => $this->getStockDistributionData($categoryId, $warehouseId),
            'lowStock' => Inventory::with(['item', 'warehouse'])
                ->lowStock()
                ->when($categoryId, function ($query) use ($categoryId) {
                    $query->where('category_id', $categoryId);
                })
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                })
                ->get(),
            'outOfStock' => Inventory::with(['item', 'warehouse'])
                ->outOfStock()
                ->when($categoryId, function ($query) use ($categoryId) {
                    $query->where('category_id', $categoryId);
                })
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                })
                ->get(),
        ];
    }

    private function getExpiryReportData()
    {
        return [
            'expired' => InventoryItem::reagents()
                ->whereNotNull('reagent_expiry_date')
                ->where('reagent_expiry_date', '<', Carbon::now())
                ->with(['category', 'inventory.warehouse'])
                ->get(),
            'expiringSoon' => InventoryItem::reagents()
                ->whereNotNull('reagent_expiry_date')
                ->whereBetween('reagent_expiry_date', [Carbon::now(), Carbon::now()->addDays(60)])
                ->with(['category', 'inventory.warehouse'])
                ->get(),
            'expiringNextMonth' => InventoryItem::reagents()
                ->whereNotNull('reagent_expiry_date')
                ->whereBetween('reagent_expiry_date', [Carbon::now()->addDays(61), Carbon::now()->addDays(90)])
                ->with(['category', 'inventory.warehouse'])
                ->get(),
        ];
    }

    public function createProcurementDraft(Request $request)
    {
        // 1. Get all items currently below or at min_stock_level
        $lowStockInventory = Inventory::with('item.supplier')
            ->whereRaw('qty_available <= min_stock_level')
            ->get();

        if ($lowStockInventory->isEmpty()) {
            return back()->with('error', 'No items require restocking at this time.');
        }

        // 2. Group by Supplier to create separate orders
        $groupedBySupplier = $lowStockInventory->groupBy(fn ($inv) => $inv->item->supplier_id);

        DB::transaction(function () use ($groupedBySupplier) {
            foreach ($groupedBySupplier as $supplierId => $items) {
                // Create the Order Header
                $order = InventoryOrder::create([
                    'date' => now(),
                    'user_id' => auth()->id(),
                    'supplier_id' => $supplierId,
                    'status' => 'DRAFT',
                    // 'reference' => 'AUTO-' . now()->format('YmdHis'),
                    'obs' => 'Automated restock suggestion based on analytics.',
                    'order_year' => now()->year,
                ]);

                foreach ($items as $inv) {
                    // Calculation logic: Order enough to reach 3x min_level
                    $suggestedQty = ($inv->min_stock_level * 3) - $inv->qty_available;

                    InventoryOrderDetail::create([
                        'order_id' => $order->id,
                        'item_id' => $inv->item_id,
                        'warehouse_id' => $inv->warehouse_id,
                        'qty' => max($suggestedQty, 1),
                        'status' => 'PENDING',
                        'expected_date' => now()->addDays(7), // Default lead time
                    ]);
                }
            }
        });

        return back()->with('success', 'Procurement drafts created successfully.');
    }

    private function getSupplierPerformanceData()
    {
        return DB::table('i_order_details as od')
            ->join('i_orders as o', 'od.order_id', '=', 'o.id')
            ->join('i_suppliers as s', 'o.supplier_id', '=', 's.id')
            ->whereNotNull('od.actual_date')
            ->whereNotNull('od.expected_date')
            ->select(
                's.name as supplier',
                DB::raw('COUNT(od.id) as total_deliveries'),
                // Calculate percentage of items received on or before expected date
                DB::raw('SUM(CASE WHEN od.actual_date <= od.expected_date THEN 1 ELSE 0 END) * 100.0 / COUNT(od.id) as on_time_rate'),
                // Calculate average days of delay
                DB::raw('AVG(DATEDIFF(od.actual_date, od.expected_date)) as avg_delay')
            )
            ->groupBy('s.id', 's.name')
            ->orderBy('on_time_rate', 'desc')
            ->get();
    }

    public function getDashboardSummary()
    {
        // Items physically out of stock or expired
        $critical = Inventory::where('qty_available', 0)
            ->orWhereHas('item', fn ($q) => $q->where('reagent_expiry_date', '<=', now()))
            ->count();

        // Items below reorder point
        $toOrder = Inventory::whereRaw('qty_available <= min_stock_level')
            ->where('qty_available', '>', 0)
            ->count();

        // Active orders that are past their expected_date
        $delayedOrders = InventoryOrderDetail::where('status', '!=', 'RECEIVED')
            ->where('expected_date', '<', now())
            ->whereNull('actual_date')
            ->count();

        return response()->json([
            'critical' => $critical,
            'toOrder' => $toOrder,
            'delayed' => $delayedOrders,
        ]);
    }
}
