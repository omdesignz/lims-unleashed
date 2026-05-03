<?php

namespace App\Jobs;

use App\Models\Occurrence;
use App\Models\User; // Assuming your admin user model is 'User'
use App\Notifications\PastDueOccurrenceNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

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
     *
     * @return void
     */
    public function handle()
    {
        $pastDueOccurrences = Occurrence::where('implementation_date', '<', Carbon::now())
            ->where('status_id', '!=', 2) // Optional: Exclude completed occurrences
            ->get();

        // Assuming you have a way to identify the admin user(s)
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        foreach ($pastDueOccurrences as $occurrence) {
            Notification::send($admins, new PastDueOccurrenceNotification($occurrence));
        }
    }
}