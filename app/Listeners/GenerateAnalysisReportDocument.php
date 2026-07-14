<?php

namespace App\Listeners;

use App\Events\AnalysisResultsValidated;
use App\Models\CollectionProduct;
use App\Support\AnalysisReportService;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateAnalysisReportDocument implements ShouldQueue
{
    private AnalysisReportService $analysisReportService;

    /**
     * Create the event listener.
     */
    public function __construct(?AnalysisReportService $analysisReportService = null)
    {
        $this->analysisReportService = $analysisReportService ?? app(AnalysisReportService::class);
    }

    /**
     * Handle the event.
     */
    public function handle(AnalysisResultsValidated $event): void
    {

        $collection = CollectionProduct::with('code.results', 'product', 'end_result', 'collection.warehouse.customer')->find($event->result->code->collection_id);

        if ($collection) {
            $this->analysisReportService->ensureForCollectionProduct($collection, $event->user_id);
        }

    }
}
