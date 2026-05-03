<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Folder;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        $folder = $request->get('folder', 'uploads');

        // Get the contents of the requested folder
        // $directories = Storage::directories($folder);
        $files = Storage::files($folder);
        $currentFolder = $folder;

        $directories = Folder::first();

        return Inertia::render('Folders/Index', [
            'availableFolders' => $directories,
            'files' => $files,
            'currentFolder' => $currentFolder,
        ]);

        // return response()->json([
        //     'directories' => $directories,
        //     'files' => $files,
        // ]);
    }

    public function list(Request $request)
    {
        // Get the parent folder ID from the request, default is null (root folder)
        $parentFolderId = $request->input('parent_id', null);

        $folders = Folder::with('files','subfolders')->where('parent_id', $parentFolderId)->get();
        // $files = File::whereNull('deleted_at')->where('folder_id', $parentFolderId)->get();

        // You may also want to fetch files in the current folder
        $files = $parentFolderId 
            ? Folder::with('files', 'subfolders')->find($parentFolderId)->files // Files in the selected folder
            : [];

        return response()->json([
            'folders' => $folders,
            'files' => $files,
        ]);

        
        $parentFolderId = $request->get('parent_id', null);

        // Fetch folders with their children (subfolders)
        $folders = Folder::with('children')
            ->where('parent_id', $parentFolderId)
            ->get();

        

        return response()->json([
            'folders' => $folders,
            'files' => $files,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id', // nullable if it's a root folder
        ]);

        $folder = Folder::create([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        // Construct the folder path based on the parent folder
        $folderPath = $this->getFolderPath($folder);

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

    public function update(Request $request, Folder $folder)
    {
        $this->authorize('update', $folder); // Check if the user is authorized to rename the folder

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // $folderOldName = $folder->name;

        // $folder->update([
        //     'name' => $validated['name'],
        // ]);

        // // Find all files in the folder and update their path to reflect the new folder name

        // $files = File::where('folder_id', $folder->id)->get();

        // foreach ($files as $file) {
        //     $file->update([
        //         'path' => str_replace($folderOldName, $folder->name, $file->path),
        //     ]);
        // }

        // // Find the directory path of the folder and update the path of all files in that directory
        // $folderPath = Storage::path("uploads/{$folder->name}");

        $newFolderName = $request->input('name');
        $oldFolderPath = $this->getFolderPath($folder); // Get the full path of the folder
        $newFolderPath = $this->getNewFolderPath($folder, $newFolderName); // Create new folder path

        // Rename the directory in the storage
        if (Storage::exists($oldFolderPath)) {
            Storage::move($oldFolderPath, $newFolderPath);
        }

        // Update folder name in the database
        $folder->update(['name' => $newFolderName]);

        // Update the paths of files inside this folder and its subfolders
        $this->updateFilePaths($folder, $oldFolderPath, $newFolderPath);



        return redirect()->back()->with('success', 'Folder renamed successfully!');
    }

    public function show(Folder $folder)
    {
        $this->authorize('view', $folder);

        // Fetch all available folders except the current one 
        $availableFolders = Folder::with('files', 'subfolders')->where('id', '!=', $folder->id)->get();

        return Inertia::render('Folders/Show', [
            'folder' => $folder,
            'availableFolders' => $availableFolders,
        ]);
    }

    // Helper function to get the full folder path
    private function getFolderPath(Folder $folder)
    {
        // Construct the full path from parent folders
        $path = $folder->name;
        while ($folder->parent) {
            $folder = $folder->parent;
            $path = $folder->name . '/' . $path;
        }
        return 'uploads/' . $path;
    }

    // Helper function to delete all subfolders and files
    private function deleteFolderContents(Folder $folder)
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

    // Helper function to get the new folder path after renaming
    private function getNewFolderPath(Folder $folder, $newFolderName)
    {
        // Construct new path with the updated folder name
        $oldPath = $this->getFolderPath($folder);
        return preg_replace('/[^\/]+$/', $newFolderName, $oldPath);
    }

    // Helper function to update file paths
    private function updateFilePaths(Folder $folder, $oldFolderPath, $newFolderPath)
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

    public function destroy(Folder $folder)
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

        return response()->json([
            'success' => true,
            'message' => 'Folder and its contents deleted successfully!',
        ]);
    }

    public function move(Request $request, Folder $folder)
    {
        $request->validate([
            'target_folder_id' => 'nullable|exists:folders,id',
        ]);

        $targetFolderId = $request->input('target_folder_id');

        // Update folder's parent in the database
        $folder->update(['parent_id' => $targetFolderId]);

        // Get the old path and new path
        $oldFolderPath = $this->getFolderPath($folder);
        $newFolderPath = $this->getFolderPath(Folder::find($targetFolderId)) . '/' . $folder->name;

        // Move folder in the filesystem
        if (Storage::exists($oldFolderPath)) {
            Storage::move($oldFolderPath, $newFolderPath);
        }

        // Optionally: Update file paths of all files within this folder and subfolders 1
        $this->updateFilePaths($folder, $oldFolderPath, $newFolderPath);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }
}
