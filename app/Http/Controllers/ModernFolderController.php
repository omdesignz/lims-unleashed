<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyFolderDownloadJob;
use App\Models\ModernFolder;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\File;
use App\Notifications\FolderDownloadReadyNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ModernFolderController extends Controller
{
    public function index()
    {
        return Inertia::render('ModernFolders/Index', [
            'folders' => ModernFolder::with('files.user')->whereIsRoot()->get(),
            // 'folders' => ModernFolder::with('children')->get()->toTree(),
            'breadcrumbs' => Breadcrumbs::render('modern-folders.index'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:modern_folders,id', // nullable if it's a root folder
        ]);

        $folder = ModernFolder::create([
            'name' => $validated['name'],
            'slug' => str()->slug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        // Construct the folder path based on the parent folder
        $folderPath = $this->getFolderPath($folder);

        $folder->update([
            'path' => $folderPath,
        ]);

        // Create the folder in the filesystem
        if (!Storage::exists($folderPath)) {
            Storage::makeDirectory($folderPath);
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    public function show(ModernFolder $folder)
    {
        return Inertia::render('ModernFolders/Show', [
            'folder' => $folder->load('children.files.user', 'files.user'),
            'breadcrumbs' => Breadcrumbs::generate('modern-folders.index', $folder),
        ]);
    }

    // Helper function to get the full folder path
    private function getFolderPath(ModernFolder $folder)
    {
        // Construct the full path from parent folders
        $path = $folder->name;
        while ($folder->parent) {
            $folder = $folder->parent;
            $path = $folder->name . '/' . $path;
        }
        return 'uploads/' . $path;
    }

    public function update(Request $request, ModernFolder $folder)
    {
        // $this->authorize('update', $folder); // Check if the user is authorized to rename the folder

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $newFolderName = $validated['name'];
        $oldFolderPath = $this->getFolderPath($folder); // Get the full path of the folder
        $newFolderPath = $this->getNewFolderPath($folder, $newFolderName); // Create new folder path

        // Rename the directory in the storage
        if (Storage::exists($oldFolderPath)) {
            Storage::move($oldFolderPath, $newFolderPath);
        }

        // Update folder name in the database
        $folder->update(['name' => $newFolderName, 'slug' => str()->slug($newFolderName), 'path' => $newFolderPath]);

        // Update the paths of files inside this folder and its subfolders
        $this->updateFilePaths($folder, $oldFolderPath, $newFolderPath);



        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    // Helper function to get the new folder path after renaming
    private function getNewFolderPath(ModernFolder $folder, $newFolderName)
    {
        // Construct new path with the updated folder name
        $oldPath = $this->getFolderPath($folder);
        return preg_replace('/[^\/]+$/', $newFolderName, $oldPath);
    }

    // Helper function to update file paths
    private function updateFilePaths(ModernFolder $folder, $oldFolderPath, $newFolderPath)
    {
        // Find all files in this folder and its subfolders
        $files = File::where('folder_id', $folder->id)->get();

        foreach ($files as $file) {
            // Update the file path in the filesystem
            $oldFilePath = $file->path;
            $newFilePath = str_replace($oldFolderPath, $newFolderPath, $oldFilePath);

            if (Storage::exists($oldFilePath)) {
                Storage::move($oldFilePath, $newFilePath);
            }

            // Update the file path in the database
            $file->update(['path' => $newFilePath]);
        }

        // Recursively update the paths of subfolders and their files
        foreach ($folder->subfolders as $subfolder) {
            $subOldFolderPath = $this->getFolderPath($subfolder);
            $subNewFolderPath = str_replace($oldFolderPath, $newFolderPath, $subOldFolderPath);
            $this->updateFilePaths($subfolder, $subOldFolderPath, $subNewFolderPath);
        }
    }

    public function destroy(ModernFolder $folder)
    {
        // Get the full path of the folder in the filesystem
        $folderPath = $this->getFolderPath($folder);

        // Recursively delete all subfolders and their files
        $this->deleteFolderContents($folder);

        // Finally, delete the folder itself from the filesystem and the database
        if (Storage::exists($folderPath)) {
            Storage::deleteDirectory($folderPath);
        }

        $folder->delete(); // Delete folder from the database

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    // Helper function to delete all subfolders and files
    private function deleteFolderContents(ModernFolder $folder)
    {
        // Delete all files in this folder from the filesystem and database
        $files = File::where('folder_id', $folder->id)->get();
        foreach ($files as $file) {
            if (Storage::exists($file->path)) {
                Storage::delete($file->path); // Delete the file from the filesystem
            }
            $file->delete(); // Delete the file from the database
        }

        // Recursively delete subfolders and their contents
        foreach ($folder->children as $subfolder) {
            $this->deleteFolderContents($subfolder); // Recursion for subfolders
            $subfolder->delete(); // Delete the subfolder from the database
        }
    }

    public function move(Request $request, ModernFolder $folder)
    {

        // $folder = ModernFolder::find($request->origin_folder_id);
        $newFolder = ModernFolder::find($request->destination_folder_id['value']);

        // dd($newFolder);

        // dd($folder, $newFolder);

        $oldFolderPath = $folder->path;
        // $newFolderPath = $this->getFolderPath($newFolder);
        $newFolderPath = $this->getNewFolderPath($newFolder, $newFolder->name . '/' . $folder->name);

        if (!is_null($folder->parent_id) && Storage::exists($oldFolderPath) && $folder->path !== $newFolderPath) {
            Storage::move($oldFolderPath, $newFolderPath);
        } else {
            Storage::move($oldFolderPath, $newFolderPath);
        }

        // Update folder path in the database
        $folder->update([
            'path' => $newFolderPath,
            'parent_id' => $newFolder->id,
        ]);

        // Update the path of files and folders inside this folder and its subfolders

        $this->updateFilePaths($folder, $oldFolderPath, $newFolderPath);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    public function getFolder() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("modern_folders")
                ->select('modern_folders.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('path','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    private function getNewFilePath(File $file, $folder)
    {
        // Construct new path with the updated folder name
        $oldPath = $file->path;
        $newFolderPath = $this->getFolderPath($folder);
        return $newFolderPath . '/' . $file->name . '.' . $file->extension;

    }

     // Share a folder with one or more users
     public function share(Request $request, ModernFolder $folder)
     {
         $validated = $request->validate([
             'user_ids' => 'required|array',
             'user_ids.*' => 'exists:users,id', // Validate that each user ID exists
         ]);
 
         // Attach users to the folder
         $folder->sharedWithUsers()->syncWithoutDetaching($validated['user_ids']);
 
         return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
     }

     public function unshare(Request $request, ModernFolder $folder)
        {
            $validated = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            // Detach users from the folder
            $folder->sharedWithUsers()->detach($validated['user_ids']);

            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.record_successfully_created'),
                ]
            ]);
        }


    public function download(ModernFolder $folder)
    {

        $folderPath = storage_path('app/' . $folder->path); // Adjust as needed based on your folder structure

        // Calculate the folder size
        $folderSize = $this->getFolderSize(storage_path("app/{$folder->path}")); // Ensure the path is correct

        // Define a size threshold (e.g., 100 MB)
        $sizeThreshold = 100 * 1024 * 1024;


        if ($folderSize > $sizeThreshold) {
            // Dispatch a background job to generate the ZIP
            NotifyFolderDownloadJob::dispatch($folder, auth()->user());
    
            return response()->json([
                'message' => 'Your folder is too large to download directly. A ZIP file will be created, and you will be notified once it is ready.'
            ]);
        }

        // Generate the ZIP file on the fly for smaller folders
        $zipFilePath = $this->createZipFile($folder);

        if (!$zipFilePath) {
            return response()->json(['message' => 'Unable to create ZIP file.'], 500);
        }

        // Generate the public URL
        $publicUrl = asset('storage/temp/' . basename($zipFilePath));

        return response()->json(['download_url' => $publicUrl], 200);

        // NotifyFolderDownloadJob::dispatch($folder, auth()->user());

        // return 'ok';

    }

    public function downloadZipped($file)
    {

        if (Storage::exists('temp/' . $file)) {
            return Storage::download('temp/' . $file);
        }

        return response()->json(['error' => 'File not found'], 404);
    }

    protected function createZipFile($folder)
    {
        // Define a path in the public storage folder
        $zipFileName = $folder->name . '_' . time() . '.zip';
        $zipFilePath = public_path("storage/temp/{$zipFileName}");

        // Ensure the temp directory exists in public storage
        if (!file_exists(public_path('storage/temp'))) {
            mkdir(public_path('storage/temp'), 0755, true);
        }

        // Create a new ZIP archive
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            return false; // Return false if the ZIP file could not be created
        }

        // Add folder contents to the ZIP
        $this->addFolderToZip(storage_path("app/{$folder->path}"), $zip);

        // Close the ZIP archive
        $zip->close();

        ob_end_clean();

        // Return the public path to the ZIP file
        return $zipFilePath;
    }


    private function createZip($folderPath, $zipFullPath)
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            if (Storage::exists($folderPath)) {
                $this->addFolderToZip($folderPath, $zip);

            } else {
                
            }
            $zip->close();

            ob_end_clean();
        } else {
            throw new \Exception('Could not create zip file.');
        }
    }


    // Add folder contents to the ZIP file based on the database path
    private function addFolderToZipUsingDatabasePath($folderPath, ZipArchive $zip, $basePath = '')
    {
        $files = scandir($folderPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $folderPath . '/' . $file;

            if (is_dir($filePath)) {
                // Recursively add subfolders
                $this->addFolderToZipUsingDatabasePath($filePath, $zip, $basePath . $file . '/');
            } else {
                // Add file to ZIP
                $zip->addFile($filePath, $basePath . $file);
            }
        }
    }

    protected function getFolderSize($path)
    {
        $size = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            $size += $file->getSize();
        }

        return $size;
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
