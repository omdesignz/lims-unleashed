<?php

namespace App\Http\Controllers;

use App\Http\Resources\GestlabMediaResource;
use App\Models\GestlabMedia;
use App\Support\ReportStudioAssetLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
                    'label' => trans('gestlab.general.labels.files.types.'.$item->file_type),
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
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.files.author_id'),
                    'value' => 'author_id',
                ],
                [
                    'name' => trans('gestlab.general.labels.files.created_at'),
                    'value' => 'created_at',
                ],
            ],
            'model' => GestlabMedia::MENU_NAME,
            'record' => $media,
            'fileTypes' => $fileTypes,
            'months' => $months,
            'query' => request()->all(['fileType', 'month', 'term']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Media/Create', []);
    }

    public function edit(GestlabMedia $media): RedirectResponse
    {
        return redirect()
            ->route('media.index')
            ->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.general.labels.files.page_title'),
                ],
            ]);
    }

    public function store(Request $request, ReportStudioAssetLibrary $assetLibrary): JsonResponse
    {
        $fileRules = ['required', 'file', 'max:512000'];

        if ($request->filled('studio_asset_context')) {
            $fileRules[] = 'mimetypes:image/svg+xml,image/png,image/jpeg,image/webp,image/gif,image/avif';
        }

        $validated = $request->validate([
            'file' => $fileRules,
            'studio_asset_context' => ['nullable', 'string', Rule::in(['report_studio', 'proposal_studio'])],
            'studio_asset_kind' => ['nullable', 'string', Rule::in(ReportStudioAssetLibrary::studioUploadKinds())],
        ], [
            'file.required' => 'Seleccione um ficheiro para carregar.',
            'file.file' => 'O conteúdo carregado tem de ser um ficheiro válido.',
            'file.max' => 'O ficheiro não pode ter mais de 512 MB.',
            'file.mimetypes' => 'Use SVG, PNG, JPEG, WebP, GIF ou AVIF para media de estúdio.',
            'studio_asset_context.in' => 'O contexto de media do estúdio não é suportado.',
            'studio_asset_kind.in' => 'O tipo de media do estúdio não é suportado.',
        ]);

        $file = $validated['file'];
        $isStudioUpload = filled($validated['studio_asset_context'] ?? null);
        $studioAssetKind = $isStudioUpload
            ? ($validated['studio_asset_kind'] ?? ReportStudioAssetLibrary::DEFAULT_STUDIO_UPLOAD_KIND)
            : null;
        $studioAssetSource = $studioAssetKind ? ReportStudioAssetLibrary::sourceForKind($studioAssetKind) : null;

        $media = GestlabMedia::create([
            'name' => $file->getClientOriginalName(),
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'author_id' => auth()->id(),
            'studio_asset_kind' => $studioAssetKind,
            'studio_asset_source' => $studioAssetSource,
        ]);

        $directory = "media/{$media->created_at->format('Y/m/d')}/{$media->id}";
        $file->storeAs($directory, $media->file_name, 'public');

        return response()->json([
            'id' => $media->id,
            'preview_url' => $media->preview_url,
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'mime_type' => $media->mime_type,
                'file_type' => $media->file_type,
                'size' => $media->size,
                'path' => $media->path,
                'studio_asset_kind' => $media->studio_asset_kind,
                'studio_asset_source' => $media->studio_asset_source,
            ],
            'asset' => $assetLibrary->assetForMedia(
                $media,
                $studioAssetKind ?: 'gallery_image',
                $studioAssetSource ?: 'Upload do studio'
            ),
        ]);
    }

    public function destroy()
    {
        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);

        foreach (GestlabMedia::find(request('recordIds')) as $media) {
            $media->delete();
            Storage::disk('public')->delete($media->path);
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    public function update(Request $request, GestlabMedia $media): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $media->update($validated);

        return redirect()->route('media.index')->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function restore(): RedirectResponse
    {
        return redirect()->route('media.index')->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }
}
