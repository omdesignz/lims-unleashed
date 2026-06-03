<?php

namespace App\Console;

use App\Jobs\CheckLaboratoryWorkflowStaleness;
use App\Jobs\CheckPastDueOccurrences;
use App\Jobs\CheckProficiencyTestDeadlines;
use App\Jobs\CheckSampleRetentionDeadlines;
use App\Jobs\CheckSupplierAssessmentDeadlines;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('backup:clean')->dailyAt('01:30');
        $schedule->command('backup:run')->dailyAt('02:00')->withoutOverlapping();
        $schedule->command('backup:monitor')->dailyAt('07:15');

        $schedule->job(new CheckPastDueOccurrences)->dailyAt('05:00');
        $schedule->job(new CheckSampleRetentionDeadlines)->dailyAt('06:00');
        $schedule->job(new CheckProficiencyTestDeadlines)->dailyAt('07:30');
        $schedule->job(new CheckSupplierAssessmentDeadlines)->dailyAt('08:00');
        $schedule->job(new CheckLaboratoryWorkflowStaleness)->twiceDaily(9, 15);

        $schedule->command('cleanup:old-zips')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
