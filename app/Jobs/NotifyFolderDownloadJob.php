<?php

namespace App\Jobs;

use App\Notifications\FolderDownloadReadyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use ZipArchive;

class NotifyFolderDownloadJob implements ShouldQueue
{
    use Queueable;

    protected $folder;
    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct($folder, $user)
    {
        $this->folder = $folder;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $timestamp = now()->timestamp;
        // $zipFilePath = storage_path("app/temp/{$this->folder->name}_{$timestamp}.zip");
        $zipFilePath = public_path("storage/temp/{$this->folder->name}_{$timestamp}.zip");

        // Create the ZIP file
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
            $this->addFolderToZip(storage_path("app/{$this->folder->path}"), $zip);
            $zip->close();

            // ob_end_clean();

            // Generate the public URL

            // $zipFileUrl = URL::temporarySignedRoute(
            //     'public.download',
            //     now()->addMinutes(5),
            //     ['file' => $zipFilePath]
            // );

            // Generate the public URL
            $publicUrl = asset("storage/temp/" . basename($zipFilePath));

            // Notify the user
            $this->user->notify(new FolderDownloadReadyNotification($publicUrl));
        } else {
            // Handle errors
            \Log::error("Failed to create ZIP file for folder: {$this->folder->id}");
        }

        // Update folder with the ZIP path
        // $this->folder->zip_path = "temp/{$this->folder->name}_{$timestamp}.zip";
        // $this->folder->save();

        // $zipFilePath = storage_path("app/temp/{$this->folder->name}_{$timestamp}.zip");
    }

    protected function addFolderToZip($folderPath, $zip, $parentFolder = '')
    {
        $files = scandir($folderPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                $this->addFolderToZip($filePath, $zip, $parentFolder . $file . '/');
            } else {
                $zip->addFile($filePath, $parentFolder . $file);
            }
        }
    }
}