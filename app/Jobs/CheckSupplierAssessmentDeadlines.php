<?php

namespace App\Jobs;

use App\Models\InventorySupplierAssessment;
use App\Models\User;
use App\Support\SupplierAssessmentNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSupplierAssessmentDeadlines implements ShouldQueue
{
    use Queueable;

    public function handle(SupplierAssessmentNotifier $notifier): void
    {
        $systemUser = User::query()
            ->role('admin')
            ->whereNotNull('email_verified_at')
            ->first() ?? User::query()->whereNotNull('email_verified_at')->first();

        if (! $systemUser) {
            return;
        }

        InventorySupplierAssessment::query()
            ->with('supplier:id,name')
            ->whereNotNull('next_review_at')
            ->whereDate('next_review_at', '<=', now()->addDays(14))
            ->get()
            ->each(function (InventorySupplierAssessment $assessment) use ($notifier, $systemUser) {
                if ($assessment->next_review_at?->isPast()) {
                    $notifier->notifyOverdue($assessment, $systemUser);
                    return;
                }

                $notifier->notifyDueSoon($assessment, $systemUser);
            });

        InventorySupplierAssessment::query()
            ->with('supplier:id,name')
            ->where('risk_level', 'critical')
            ->where('is_active', true)
            ->get()
            ->each(fn (InventorySupplierAssessment $assessment) => $notifier->notifyCriticalRisk($assessment, $systemUser));
    }
}
