<?php

namespace App\Jobs;

use App\Models\Occurrence;
use App\Models\User; // Assuming your admin user model is 'User'
use App\Support\NotificationTemplateService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPastDueOccurrences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationTemplateService $templates): void
    {
        $pastDueOccurrences = Occurrence::where('implementation_date', '<', Carbon::now())
            ->where('status_id', '!=', 2) // Optional: Exclude completed occurrences
            ->get();

        foreach ($pastDueOccurrences as $occurrence) {
            $templates->notifyPermission('quality.occurrence.overdue', [
                'document_number' => $occurrence->occurrence_no,
                'document_url' => route('occurrences.show', $occurrence),
            ]);
        }
    }
}
