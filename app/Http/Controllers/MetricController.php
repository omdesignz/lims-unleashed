<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Metrics\SampleMetrics;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\CollectionProduct;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class MetricController extends Controller
{
    public function index2()
    {
        $data = (new SampleMetrics())->getResultResponseTimeByParameter(request()->input('unit', 'MINUTE'), request()->input('per_page', 5), request()->input('search', ''));

        // QueryBuilder::for($data)->allowedFilters(['name', 'globalFilter']);

        return Inertia::render('Metrics/Index', [
            'record' => collect($data),
            'fields' => [
                [
                    'name' => 'name',
                    'value' => 'name',
                    'filter_field' => 'name',
                    'filterable' => true,
                    'type' => 'string',
                    'format' => '',
                    'filter' => '',
                ]
            ],
            'initialFilters' => request()->query('filter', ['name' => '', 'globalFilter' => '']),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
        ]);
    }

    public function index($startDate = null, $endDate = null)
    {
        $baseQuery = CollectionProduct::query();

        $invoiceQuery = Invoice::query();

        if (request()->input('date.start')) {
            $baseQuery->whereNull('deleted_at')->where('created_at', '>=', request()->input('date.start'));
            $invoiceQuery->whereNull('deleted_at')->where('created_at', '>=', request()->input('date.start'));
        }

        if (request()->input('date.end')) {
            $baseQuery->whereNull('deleted_at')->where('created_at', '<=', request()->input('date.end') . ' 23:59:59');
            $invoiceQuery->whereNull('deleted_at')->where('created_at', '<=', request()->input('date.end') . ' 23:59:59');
        }

        $totalRecords = $baseQuery->count();

        $totalInvoiceAmount = $invoiceQuery->where('status', 1)->where('status_code', '=', 'N')->sum('total');

        // Create a new query based on the initial filters
        $toBeFinalizedQuery = CollectionProduct::query();
        if (request()->input('date.start')) $toBeFinalizedQuery->where('created_at', '>=', request()->input('date.start'));
        if (request()->input('date.end')) $toBeFinalizedQuery->where('created_at', '<=', request()->input('date.end') . ' 23:59:59');
        $toBeFinalizedRecordsCount = $toBeFinalizedQuery->whereNull('deleted_at')->whereNull('analysis_end_date')->count();

        // Create another new query based on the initial filters
        $finalizedQuery = CollectionProduct::query();
        if (request()->input('date.start')) $finalizedQuery->where('created_at', '>=', request()->input('date.start'));
        if (request()->input('date.end')) $finalizedQuery->where('created_at', '<=', request()->input('date.end') . ' 23:59:59');
        $finalizedRecordsCount = $finalizedQuery->whereNull('deleted_at')->whereNotNull('analysis_end_date')->count();

        $averageResponseTimeQuery = CollectionProduct::query()
            ->whereNull('deleted_at')
            ->whereNotNull('analysis_start_date')
            ->whereNotNull('analysis_end_date');

        if (request()->input('date.start')) {
            $averageResponseTimeQuery->whereNull('deleted_at')->where('created_at', '>=', request()->input('date.start'));
        }

        if (request()->input('date.end')) {
            $averageResponseTimeQuery->whereNull('deleted_at')->where('created_at', '<=', request()->input('date.end') . ' 23:59:59');
        }

        $averageResponseTime = $averageResponseTimeQuery->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, analysis_start_date, analysis_end_date)) as average_seconds'))
            ->first();

        $averageResponseTimeFormatted = null;
        if ($averageResponseTime && $averageResponseTime->average_seconds !== null) {
            $totalSeconds = $averageResponseTime->average_seconds;
            $days = floor($totalSeconds / (60 * 60 * 24));
            $hours = floor(($totalSeconds % (60 * 60 * 24)) / (60 * 60));
            $minutes = floor(($totalSeconds % (60 * 60)) / 60);
            $seconds = $totalSeconds % 60;

            $parts = [];
            if ($days > 0) {
                $parts[] = $days . ' dias';
            }
            if ($hours > 0) {
                $parts[] = $hours . ' horas';
            }
            if ($minutes > 0) {
                $parts[] = $minutes . ' minutos';
            }
            if ($seconds > 0 || empty($parts)) {
                $parts[] = $seconds . ' segundos';
            }

            $averageResponseTimeFormatted = implode(', ', $parts);
        }

        // return [
        //     'total_records' => $totalRecords,
        //     'total_finalized_records' => $finalizedRecordsCount,
        //     'total_to_be_finalized_records' => $toBeFinalizedRecordsCount,
        //     'average_response_time' => $averageResponseTimeFormatted,
        // ];

        return Inertia::render('Metrics/Index', [
            'total_records' => $totalRecords,
            'total_finalized_records' => $finalizedRecordsCount,
            'total_to_be_finalized_records' => $toBeFinalizedRecordsCount,
            'average_response_time' => $averageResponseTimeFormatted,
            'total_invoice_amount' => $totalInvoiceAmount,
            'query' => request()->only(['date'])
        ]);
    }
}
