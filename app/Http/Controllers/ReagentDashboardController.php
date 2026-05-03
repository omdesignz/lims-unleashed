<?php

namespace App\Http\Controllers;

use App\Models\ReagentConsumption;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReagentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'this_month');
        $reagentId = $request->query('reagent_id') ?? null;
        $alertSensitivity = $request->query('alert_sensitivity', 20); // Default 20%
        $movingAverageLength = $request->query('moving_average_length', 3); // Default 3 days

        // Define date ranges
        switch ($filter) {
            case '7_days':
                $currentStart = Carbon::now()->subDays(7);
                $previousStart = Carbon::now()->subDays(14);
                break;
            case '30_days':
                $currentStart = Carbon::now()->subDays(30);
                $previousStart = Carbon::now()->subDays(60);
                break;
            case 'this_month':
                $currentStart = Carbon::now()->startOfMonth();
                $previousStart = Carbon::now()->subMonth()->startOfMonth();
                break;
            case 'custom':
                $currentStart = Carbon::parse($request->query('start_date', Carbon::now()->subDays(30)));
                $previousStart = $currentStart->copy()->subDays($currentStart->diffInDays(Carbon::now()));
                break;
            default:
                $currentStart = Carbon::now()->subDays(30);
                $previousStart = Carbon::now()->subDays(60);
        }

        $query = ReagentConsumption::query();

        if ($reagentId) {
            $query->where('reagent_id', $reagentId);
        }

        // Fetch daily consumption for both periods
        $currentConsumptions = $query->selectRaw('DATE(used_at) as date, SUM(quantity_used) as total')
        // ->where('used_at', '>=', $currentStart)
        ->groupByRaw('DATE(used_at)') // Group by the raw SQL expression
        ->orderBy('date')
        ->get()
        ->keyBy('date');

        $previousConsumptions = $query->selectRaw('DATE(used_at) as date, SUM(quantity_used) as total')
        ->whereBetween('used_at', [$previousStart, $currentStart])
        ->groupByRaw('DATE(used_at)') // Group by the raw SQL expression
        ->orderBy('date')
        ->get()
        ->keyBy('date');

        // Calculate percentage change and moving average
        $dates = $currentConsumptions->keys()->merge($previousConsumptions->keys())->unique()->sort();
        $percentageChanges = [];
        $movingAverage = [];
        $alerts = [];

        foreach ($dates as $index => $date) {
            $currentTotal = $currentConsumptions[$date]->total ?? 0;
            $previousTotal = $previousConsumptions[$date]->total ?? 0;
            // $change = $previousTotal > 0 ? (($currentTotal - $previousTotal) / $previousTotal) * 100 : 0;
            $change = $previousTotal > 0 ? (($currentTotal - $previousTotal) / $previousTotal) * 100 : ($currentTotal > 0 ? 100 : 0); // From 0 to non-zero → assume 100% spike
            $percentageChanges[] = round($change, 2);

            // Calculate moving average
            if ($index >= $movingAverageLength - 1) {
                $avg = array_sum(array_slice($percentageChanges, $index - $movingAverageLength + 1, $movingAverageLength)) / $movingAverageLength;
                $movingAverage[] = round($avg, 2);
            } else {
                $movingAverage[] = 0; // No moving average for less than the required number of days
            }

            // Add alert for drastic changes
            if (abs($change) >= $alertSensitivity) {
                $alerts[] = ['date' => $date, 'change' => round($change, 2)];
            }
        }

        return response()->json([
            'labels' => $dates,
            'series' => [
                ['name' => 'Mudança (%)', 'data' => $percentageChanges],
                ['name' => 'Média (%)', 'data' => $movingAverage],
            ],
            'alerts' => $alerts,
        ]);
    }
}
