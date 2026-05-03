<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\File;
use App\Models\Folder;
use App\Notifications\ZipReadyNotification;

class ZipFilesJob implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $fileIds;
    protected $folderIds;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $fileIds, array $folderIds = [])
    {
        $this->user = $user;
        $this->fileIds = $fileIds;
        $this->folderIds = $folderIds;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $zipFileName = 'files_' . now()->timestamp . '.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            // Add selected files to the ZIP
            $files = File::whereIn('id', $this->fileIds)->get();

            foreach ($files as $file) {

                if (Storage::exists($file->path)) {
                    $filePath = storage_path('app/' . $file->path);
                    $zip->addFile($filePath, basename($filePath));
                } else {
                    
                }
            }

            // Add selected folders to the ZIP
            if (!empty($this->folderIds)) {
                $folders = Folder::whereIn('id', $this->folderIds)->get();
              
                foreach ($folders as $folder) {
                    $this->addFolderToZip($folder, $zip);
                }
            }

            $zip->close();

            ob_end_clean();

            // Notify the user that the ZIP is ready
            Notification::send($this->user, new ZipReadyNotification($zipFileName));
        }
    }

    private function addFolderToZip(Folder $folder, ZipArchive $zip, $folderPath = '')
    {
        foreach ($folder->files as $file) {
            $filePath = storage_path('app/' . $file->path);

            if (Storage::exists($file->path)) {
                $zip->addFile($filePath, $folderPath . '/' . $folder->name . '/' . basename($filePath));
            } else {
                
            }
        }

        foreach ($folder->subfolders as $subfolder) {
            $this->addFolderToZip($subfolder, $zip, $folderPath . '/' . $folder->name);
        }
    }
}
