<?php

namespace App\Jobs;

use App\Models\Occurrence;
use App\Support\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPastDueOccurrences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(NotificationTemplateService $templates): void
    {
        foreach (Occurrence::query()->with('lab')->whereDate('implementation_date', '<', today())
            ->whereNull('date_resolved')->whereNull('date_closed')->lazyById(100) as $occurrence) {
            $templates->notifyPermission('quality.occurrence.overdue', [
                'lab_id' => $occurrence->lab_id,
                'lab_name' => $occurrence->lab?->name,
                'document_number' => $occurrence->occurrence_no,
                'document_url' => route('occurrences.show', $occurrence),
            ]);
        }
    }
}
