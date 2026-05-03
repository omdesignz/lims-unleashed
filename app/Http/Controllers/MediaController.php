<?php

namespace App\Http\Controllers;

use App\Http\Resources\GestlabMediaResource;
use App\Models\GestlabMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MediaController extends Controller
{
    //

    public function index()
    {
        $media = GestlabMediaResource::collection(
            GestlabMedia::with('author')
                ->type(request('fileType'))
                ->month(request('month'))
                ->search(request('term'))
                ->paginate(10)
        );
 
        $fileTypes = GestlabMedia::selectRaw('distinct mime_type')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->file_type,
                    'label' => trans('gestlab.general.labels.files.types.' . $item->file_type)
                ];
            })->unique('value')->values();
 
        $months = DB::table('media')
            ->selectRaw('distinct DATE_FORMAT(created_at, "01-%m-%Y") as value, DATE_FORMAT(created_at, "%M %Y") as label')
            ->orderByDesc('value')
            ->get();
 
        return Inertia::render('Media/Index', [
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.files.file'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.files.author_id'),
                    'value' => 'author_id'
                ],
                [
                    'name' => trans('gestlab.general.labels.files.created_at'),
                    'value' => 'created_at'
                ]
            ],
            'model' => GestlabMedia::MENU_NAME,
            'record' => $media,
            'fileTypes' => $fileTypes,
            'months' => $months,
            'query' => request()->all(['fileType', 'month', 'term'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Media/Create', []);
    }

    public function store()
    {
        request()->validate([
            'file' => ['file', 'max:512000']
        ], [
            'max' => 'File cannot be larger than 512MB.'
        ]);
 
        $file = request()->file('file');
 
        $media = GestlabMedia::create([
            'name' => $file->getClientOriginalName(),
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'author_id' => auth()->id()
        ]);
 
        $directory = "media/{$media->created_at->format('Y/m/d')}/{$media->id}";
        $file->storeAs($directory, $media->file_name, 'public');
 
        return [
            'id' => $media->id,
            'preview_url' => $media->preview_url
        ];
    }

    public function destroy()
    {
        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
 
        foreach (GestlabMedia::find(request('recordIds')) as $media) {
            $media->delete();
            Storage::disk('public')->delete($media->path);
        }
 
        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }
}
