<?php

namespace App\Console\Commands;

use App\Support\NotificationTemplateCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:sync-templates')]
#[Description('Synchronize the configurable operational notification template catalog')]
class SyncNotificationTemplates extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(NotificationTemplateCatalog $catalog): int
    {
        $templates = $catalog->synchronize();
        $this->info("Synchronized {$templates->count()} notification templates.");

        return self::SUCCESS;
    }
}
