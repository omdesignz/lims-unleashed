<?php

namespace App\Listeners;

use App\Models\CollectionProduct;
use App\Models\LabCode;
use App\Models\VAPSampleEntry;
use App\Services\ControlChartFeed;
use Throwable;

/**
 * When results of a quality control sample are approved, its control charts
 * receive them. Runs after the approval is committed; a failure here is
 * reported and never undoes the approval.
 */
class FeedControlCharts
{
    public function __construct(private readonly ControlChartFeed $feed) {}

    public function handle(object $event): void
    {
        $code = $event->code ?? null;

        if (! $code instanceof LabCode) {
            return;
        }

        try {
            $product = CollectionProduct::query()->with('product:id,is_control_material')->find($code->collection_id);
            $labId = VAPSampleEntry::query()->where('collection_product_id', $code->collection_id)->value('lab_id');

            if ($product?->product?->is_control_material && $labId) {
                $this->feed->feedMaterial((int) $labId, (int) $product->product_id);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
