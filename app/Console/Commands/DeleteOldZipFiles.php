<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DeleteOldZipFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zip:cleanup {days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete ZIP files older than the specified number of days';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the number of days from the command argument (default to 7)
        $days = $this->argument('days');

        // Directory where ZIP files are stored (storage/app/public/)
        $zipDirectory = storage_path('app/public/');

        // Get all ZIP files in the directory
        $files = File::files($zipDirectory);

        foreach ($files as $file) {
            if ($file->getExtension() === 'zip') {
                // Get the file's last modified time
                $lastModified = File::lastModified($file->getRealPath());

                // If the file is older than the given number of days, delete it
                if (now()->diffInDays($lastModified) >= $days) {
                    File::delete($file->getRealPath());
                    $this->info("Deleted: {$file->getFilename()}");
                }
            }
        }

        $this->info('Old ZIP files cleaned up successfully.');
    }
}
