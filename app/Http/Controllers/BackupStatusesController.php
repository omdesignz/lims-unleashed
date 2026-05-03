<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Spatie\Backup\Helpers\Format;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;
use Spatie\Backup\Config\MonitoredBackupsConfig;

class BackupStatusesController extends Controller
{
    public function index()
    {
        abort_if( !auth()->user()->can('view_settings'), 403, '');

        return Cache::remember('backup-statuses', now()->addSeconds(4), function () {
            return BackupDestinationStatusFactory::createForMonitorConfig(MonitoredBackupsConfig::fromArray(config('backup.monitor_backups')))
                ->map(function (BackupDestinationStatus $backupDestinationStatus) {
                    return [
                        'name' => $backupDestinationStatus->backupDestination()->backupName(),
                        'disk' => $backupDestinationStatus->backupDestination()->diskName(),
                        'reachable' => $backupDestinationStatus->backupDestination()->isReachable(),
                        'healthy' => $backupDestinationStatus->isHealthy(),
                        'amount' => $backupDestinationStatus->backupDestination()->backups()->count(),
                        'newest' => $backupDestinationStatus->backupDestination()->newestBackup()
                            ? $backupDestinationStatus->backupDestination()->newestBackup()->date()->diffForHumans()
                            : __('No backups present'),
                        'usedStorage' => Format::humanReadableSize($backupDestinationStatus->backupDestination()->usedStorage()),
                    ];
                })
                ->values()
                ->toArray();
        });
    }
}
