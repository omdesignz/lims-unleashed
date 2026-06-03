<?php

namespace App\Jobs;

use App\Models\ProficiencyTest;
use App\Support\ProficiencyTestNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckProficiencyTestDeadlines implements ShouldQueue
{
    use Queueable;

    public function handle(ProficiencyTestNotifier $notifier): void
    {
        ProficiencyTest::query()
            ->whereNot('status', 'closed')
            ->where(function ($query) {
                $query
                    ->whereDate('scheduled_at', '<=', now()->addDays(14))
                    ->orWhere(function ($fallbackQuery) {
                        $fallbackQuery
                            ->whereNull('scheduled_at')
                            ->whereDate('date', '<=', now()->addDays(14));
                    });
            })
            ->get()
            ->each(function (ProficiencyTest $test) use ($notifier) {
                if ($test->deadlineDate()?->isPast()) {
                    $notifier->notifyOverdue($test);

                    return;
                }

                $notifier->notifyDueSoon($test);
            });
    }
}
