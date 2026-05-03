<?php

namespace App\Listeners;

use App\Events\AnalysisResultsValidated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\CollectionProduct;
use App\Models\QualityCertificate;

class GenerateAnalysisReportDocument implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AnalysisResultsValidated $event): void
    {
        
        $collection = CollectionProduct::with('code.results', 'product', 'end_result', 'collection.warehouse.customer')->find($event->result->code->collection_id);

        QualityCertificate::create([
            'user_id' => $event->user_id,          
            'collection_id' => $collection->id,
            'customer_id' => $collection->customer_id,
            'warehouse_id' => $collection->warehouse_id,
            'product_id' => $collection->product_id,
            'cl_id' => $event->result->code->id,
            'code' => now('Africa/Luanda')->format('YmdHis'),
            'obs' => $collection->obs,
            'status' => $collection->status,
            'file_path' => null,
        ]);

    }
}
