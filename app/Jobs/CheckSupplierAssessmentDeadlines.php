<?php

namespace App\Jobs;

use App\Models\InventorySupplierAssessment;
use App\Support\SupplierAssessmentNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSupplierAssessmentDeadlines implements ShouldQueue
{
    use Queueable;

    public function handle(SupplierAssessmentNotifier $notifier): void
    {
        InventorySupplierAssessment::query()
            ->with('supplier:id,name')
            ->where('is_active', true)
            ->whereNotNull('next_review_at')
            ->whereDate('next_review_at', '<=', now()->addDays(14))
            ->lazyById(100)
            ->each(function (InventorySupplierAssessment $assessment) use ($notifier): void {
                if ($assessment->next_review_at?->isPast()) {
                    $notifier->notifyOverdue($assessment);

                    return;
                }

                $notifier->notifyDueSoon($assessment);
            });

        InventorySupplierAssessment::query()
            ->with('supplier:id,name')
            ->where('risk_level', 'critical')
            ->where('is_active', true)
            ->lazyById(100)
            ->each(fn (InventorySupplierAssessment $assessment) => $notifier->notifyCriticalRisk($assessment));
    }
}
