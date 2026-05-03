<?php

namespace App\Metrics;

use App\Models\Sample;
use App\Models\CollectionProduct;
use App\Models\Analysis;
use App\Models\Result;
use Illuminate\Support\Facades\DB;

class SampleMetrics
{
    public function getGeneralMetrics()
    {
        $duration = DB::table('analysis')
                    ->whereNotNull('init_date')
                    ->whereNotNull('end_date')
                    ->whereNull('deleted_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(DAY, init_date, end_date)) as avg_time, COUNT(*) as total, MIN(TIMESTAMPDIFF(DAY, init_date, end_date)) as min_time, MAX(TIMESTAMPDIFF(DAY, init_date, end_date)) as max_time')
                    // ->value('avg_time');
                    ->first();

        return $duration;
    }

    public function getResultResponseTimeByParameter($unit = 'MINUTE', $per_page = 5, $search = null)
    {
        $validUnits = ['SECOND', 'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'YEAR'];

        $averageApprovalTimes = DB::table('results')
            ->join('parameters', 'results.parameter_id', '=', 'parameters.id')
            ->whereNotNull('approved_date')
            ->where('parameters.name', 'LIKE', '%' . $search . '%')
            ->select(
                'parameters.name',
                'parameters.optimal_analysis_time',
                DB::raw("FORMAT(AVG(TIMESTAMPDIFF($unit, inserted_date, approved_date)), 2) as avg_time")
            )
            // ->where('parameters.name', 'LIKE', '%' . $search . '%')
            // ->groupBy('parameters.name')
            ->groupBy('parameters.name', 'parameters.name', 'parameters.optimal_analysis_time')
            ->paginate($per_page);

        // foreach ($averageApprovalTimes as $time) {
        //     echo "Parameter: {$time->name} - Average Approval Time: " . number_format($time->avg_time, 2) . " minutes\n";
        // }
        return $averageApprovalTimes;

    }

    public function getResultResponseTimeByParameterBasedOnAnalysis()
    {
        return 'Not implemented yet';
    }
}