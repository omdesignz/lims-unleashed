<?php

namespace App\Http\Controllers;

use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Tasks\Cleanup\CleanupJob;
use Spatie\Backup\Tasks\Cleanup\CleanupStrategy;

class CleanBackupsController extends Controller
{
    public function __invoke(CleanupStrategy $strategy)
    {
        abort_if( !auth()->user()->can('edit_settings'), 403, '');

        $backupDestinations = BackupDestinationFactory::createFromArray(config('backup.backup'));

        $cleanupJob = new CleanupJob($backupDestinations, $strategy);

        $cleanupJob->run();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }
}
