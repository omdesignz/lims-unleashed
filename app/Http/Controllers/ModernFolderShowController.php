<?php

namespace App\Http\Controllers;

use App\Models\ModernFolder;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModernFolderShowController extends Controller
{
    public function __invoke(ModernFolder $folder)
    {
        return Inertia::render('ModernFolders/Show', [
            'folder' => $folder->load('children'),
            'breadcrumbs' => Breadcrumbs::generate('modern-folders.index', $folder),
        ]);
    }
}
