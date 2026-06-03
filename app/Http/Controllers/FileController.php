<?php

namespace App\Http\Controllers;

use App\Jobs\ZipFilesJob;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\Folder;
use App\Models\ModernFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use ZipArchive;

class FileController extends Controller
{
    public function index()
    {
        return Inertia::render('Files/Index', []);
    }

    public function filesAndFolders()
    {
        // Get the root directory contents (you can modify this to fetch subfolders if needed)
        $directories = Storage::directories('uploads');
        $files = Storage::files('uploads');

        // Return as JSON
        return response()->json([
            'directories' => $directories,
            'files' => $files,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $request->validate([
            'file' => 'required|file|max:10240', // Maximum file size of 10 MB
            'folder_id' => 'nullable|exists:modern_folders,id', // Optional folder reference
        ]);

        $file = $request->file('file');
        $folderId = $request->input('folder_id', null); // Root folder if not provided

        // Store the file in the storage
        // $filePath = $file->store('uploads');

        $filePath = $folderId
            ? ModernFolder::findOrFail($folderId)->path.'/'
            : 'uploads/';

        // Storage::putFileAs($filePath, $file, $file->getClientOriginalName());

        Storage::putFileAs($filePath, $file, $file->getClientOriginalName());

        // Save file metadata in the database
        $fileRecord = File::create([
            'name' => $file->getClientOriginalName(),
            'path' => $filePath.$file->getClientOriginalName(),
            'folder_id' => $folderId,
            'user_id' => auth()->id(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
        ]);

        // Save file to storage
        // Storage::putFileAs($filePath, $file);

        // return response()->json([
        //     'success' => true,
        //     'file' => $fileRecord
        // ]);
        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);
    }

    public function update(Request $request, File $file)
    {
        // $this->authorize('update', $file); // Check if the user is authorized to rename the file

        $validated = $request->validate([
            // 'file' => 'required|file',
            'name' => 'required|string|max:255',
        ]);

        // $file = File::find($request->file);

        // Store current version as a new entry in `file_versions`
        $file->versions()->create([
            'path' => $file->path, // Path of the current file
            'name' => $file->name, // Current file name
            'extension' => pathinfo($file->path, PATHINFO_EXTENSION), // Get the extension
            'size' => $file->size,
        ]);

        // Handle new file upload and update the file entry
        // $newFilePath = $request->file('file')->store('files');

        $oldPath = $file->path;

        $file->update([
            // 'path' => $newFilePath,
            'name' => $validated['name'],
            // 'size' => $request->file('file')->getSize(),
            'path' => preg_replace('/[^\/]+$/', $validated['name'], $oldPath).'.'.$file->extension,
            // 'path' => $this->getNewFilePath($file, $folder),
        ]);

        // Change file name in storage
        Storage::move($oldPath, $file->path);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function revertToVersion(File $file, FileVersion $version)
    {
        $this->authorize('update', $file);

        // Move current file to a new version
        $file->versions()->create([
            'path' => $file->path,
            'name' => $file->name,
            'extension' => pathinfo($file->path, PATHINFO_EXTENSION),
            'size' => $file->size,
        ]);

        // Revert to selected version
        $file->update([
            'path' => $version->path,
            'name' => $version->name,
            'size' => $version->size,
        ]);

        return redirect()->back()->with('success', 'File reverted to previous version!');
    }

    public function versions(File $file)
    {
        $this->authorize('view', $file);

        $versions = $file->versions()->orderBy('created_at', 'desc')->get();

        return Inertia::render('FileVersions', [
            'versions' => $versions,
        ]);
    }

    public function move(Request $request)
    {

        $files = File::whereIn('id', $request->input('files'))->get();
        $folder = ModernFolder::find($request->folder_id['value']);

        foreach ($files as $file) {

            // Move Storage File
            $filePath = $file->path;
            $newFilePath = $this->getNewFilePath($file, $folder);

            if (Storage::exists($filePath) && $file->path !== $newFilePath) {
                Storage::move($filePath, $newFilePath);
            }

            // Update file path in the database
            $file->update([
                'path' => $newFilePath,
                'folder_id' => $folder->id,
            ]);

        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function zipAndDownload($fileIds, $folderIds = [])
    {
        $zipFileName = 'files_'.now()->timestamp.'.zip';
        $zipFilePath = storage_path('app/public/'.$zipFileName);

        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Add files to the ZIP
            $files = File::whereIn('id', $fileIds)->get();
            foreach ($files as $file) {

                if (Storage::exists($file->path)) {
                    $filePath = storage_path('app/'.$file->path);
                    $zip->addFile($filePath, basename($filePath));
                } else {

                }

            }

            // Add folders and their contents to the ZIP
            if (! empty($folderIds)) {
                $folders = ModernFolder::whereIn('id', $folderIds)->get();
                foreach ($folders as $folder) {
                    $this->addFolderToZip($folder, $zip);
                }
            }

            $zip->close();

            ob_end_clean();

            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } else {
            return response()->json(['error' => 'Unable to create zip file'], 500);
        }

    }

    private function addFolderToZip(ModernFolder $folder, ZipArchive $zip, $folderPath = '')
    {
        foreach ($folder->files as $file) {
            $filePath = storage_path('app/'.$file->path);

            if (Storage::exists($file->path)) {
                $zip->addFile($filePath, $folderPath.'/'.$folder->name.'/'.basename($filePath));
            } else {

            }
        }

        foreach ($folder->subfolders as $subfolder) {
            $this->addFolderToZip($subfolder, $zip, $folderPath.'/'.$folder->name);
        }
    }

    public function downloadZip(Request $request)
    {
        $validated = $request->validate([
            'file_ids' => 'required|array',
            'folder_ids' => 'nullable|array',
        ]);

        // dd($request->all());

        $totalSize = File::whereIn('id', $validated['file_ids'])->sum('size');

        // dd($totalSize);

        // Define the threshold for using background jobs (e.g., 100MB)
        if ($totalSize > 100 * 1024 * 1024) { // 100MB in bytes
            ZipFilesJob::dispatch(auth()->user(), $validated['file_ids'], $validated['folder_ids'] ?? []);

            return redirect()->back()->with('success', 'Your ZIP file is being prepared. You will be notified once it is ready.');
        }

        // For smaller files, zip and download directly
        return $this->zipAndDownload($validated['file_ids'], $validated['folder_ids'] ?? []);
    }

    public function uploadFolder(Request $request)
    {
        $validated = $request->validate([
            'folder_id' => 'nullable|exists:modern_folders,id',
            'files' => 'required|array',
            'files.*' => 'file|max:10240', // Limit individual file size (optional)
            'relative_paths' => 'required|array', // Relative paths for folder structure
        ]);

        $files = $validated['files'];
        $relativePaths = $validated['relative_paths'];

        $folder = ModernFolder::findOrFail($validated['folder_id']);

        $parentFolderPath = $this->getFolderPath($folder);

        foreach ($files as $index => $file) {
            $relativePath = $relativePaths[$index];
            $folderPath = dirname($relativePath);
            $folderParts = explode('/', $folderPath);

            // Recursively create or fetch the folders in the database
            $parentFolderId = $validated['folder_id'] ?? null;
            foreach ($folderParts as $folderName) {
                $folder = ModernFolder::firstOrCreate([
                    'name' => $folderName,
                    'slug' => str()->slug($folderName),
                    'parent_id' => $parentFolderId,
                    'user_id' => auth()->id(),
                ]);

                $folder->update([
                    'path' => $folder ? $parentFolderPath.'/'.$folderName : "uploads/{$folderName}",
                ]);

                $parentFolderId = $folder->id;
            }

            // Store the file in the correct folder in the storage
            // $destinationPath = "uploads/{$relativePath}";
            $destinationPath = $folder ? $parentFolderPath.'/'.$relativePath : "uploads/{$relativePath}";
            Storage::putFileAs(dirname($destinationPath), $file, $file->getClientOriginalName());

            // Save file metadata in the database
            File::create([
                'name' => $file->getClientOriginalName(),
                'path' => $destinationPath,
                'folder_id' => $parentFolderId,
                'user_id' => auth()->id(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ]);
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);
    }

    public function downloadFile(Request $request)
    {
        $file = $request->get('file');

        if (Storage::exists($file)) {
            return Storage::download($file);
        }

        return response()->json(['error' => 'File not found'], 404);
    }

    public function destroy(Request $request)
    {
        $file = File::find($request->file);

        if ($file && $file->user_id == auth()->id()) {
            $file->delete();

            if (Storage::exists($file->path)) {
                Storage::delete($file->path);
            }

            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.record_successfully_deleted'),
                ],
            ]);
        }

    }

    private function getNewFilePath(File $file, $folder)
    {
        // Construct new path with the updated folder name
        $oldPath = $file->path;
        $newFolderPath = $this->getFolderPath($folder);

        return $newFolderPath.'/'.$file->name.'.'.$file->extension;

    }

    // Helper function to get the full folder path
    private function getFolderPath(ModernFolder $folder)
    {
        // Construct the full path from parent folders
        $path = $folder->name;
        while ($folder->parent) {
            $folder = $folder->parent;
            $path = $folder->name.'/'.$path;
        }

        return 'uploads/'.$path;
    }
}
