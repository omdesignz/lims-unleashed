<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Config\BackupConfig;
use Spatie\Backup\Config\NotificationsConfig;
use Spatie\Backup\Config\MonitoredBackupsConfig;
use Spatie\Backup\Config\CleanupConfig;


class CreateBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $option;
    // protected $config;

    /**
     * Create a new job instance.
     */
    public function __construct($option = '')
    {
        $this->option = $option;
        // $this->config = $config;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // $config = Config::rebind();

        $backupJob = BackupJobFactory::createFromConfig(Config::fromArray(config('backup')));

        if ($this->option === 'only-db') {
            $backupJob->dontBackupFilesystem();
        }

        if ($this->option === 'only-files') {
            $backupJob->dontBackupDatabases();
        }

        if (! empty($this->option)) {
            $prefix = str_replace('_', '-', $this->option).'-';

            $backupJob->setFilename($prefix.date('Y-m-d-H-i-s').'.zip');
        }

        $backupJob->run();
    }
}
