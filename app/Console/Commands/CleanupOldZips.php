<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldZips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:old-zips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old ZIP files after a given number of days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = 7; // Files older than 7 days
        $files = Storage::allFiles('downloads');

        foreach ($files as $file) {
            if (Storage::lastModified($file) < now()->subDays($days)->timestamp) {
                Storage::delete($file);
            }
        }

        $this->info('Old ZIP files cleaned up successfully.');
    }
}
