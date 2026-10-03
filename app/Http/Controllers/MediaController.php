<?php

namespace App\Http\Controllers;

use App\Http\Requests\MediaStudioUploadRequest;
use App\Http\Resources\GestlabMediaResource;
use App\Models\GestlabMedia;
use App\Models\User;
use App\Services\StaffAccountAccess;
use App\Support\ReportStudioAssetLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MediaController extends Controller
{
    private function administrator(): User
    {
        $actor = User::query()->find(auth()->id());
        abort_unless($actor && app(StaffAccountAccess::class)->isSystemAdministrator($actor), 403);

        return $actor;
    }

    public function index(): Response
    {
        $this->administrator();

        return Inertia::render('Media/Index', [
            'record' => GestlabMediaResource::collection(GestlabMedia::with('author')->type(request('fileType'))
                ->month(request('month'))->search(request('term'))->latest('id')->paginate(10)),
            'fields' => [], 'model' => GestlabMedia::MENU_NAME,
            'fileTypes' => GestlabMedia::select('mime_type')->distinct()->get()->map(fn (GestlabMedia $media): array => [
                'value' => $media->file_type, 'label' => trans('gestlab.general.labels.files.types.'.$media->file_type),
            ])->unique('value')->values(),
            'months' => GestlabMedia::selectRaw("distinct to_char(created_at, '01-MM-YYYY') as value, to_char(created_at, 'Month YYYY') as label")->get(),
            'query' => request()->only(['fileType', 'month', 'term']),
        ]);
    }

    public function create(): Response
    {
        $this->administrator();

        return Inertia::render('Media/Create');
    }

    public function store(MediaStudioUploadRequest $request, ReportStudioAssetLibrary $assetLibrary): JsonResponse
    {
        $actor = $this->administrator();
        $validated = $request->validated();
        $file = $validated['file'];
        $kind = filled($validated['studio_asset_context'] ?? null)
            ? ($validated['studio_asset_kind'] ?? ReportStudioAssetLibrary::DEFAULT_STUDIO_UPLOAD_KIND) : null;
        $path = null;
        try {
            $media = DB::transaction(function () use ($file, $kind, $actor, &$path): GestlabMedia {
                $media = GestlabMedia::query()->create([
                    'name' => $file->getClientOriginalName(), 'file_name' => Str::uuid().'.'.($file->extension() ?: 'image'),
                    'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'author_id' => $actor->id,
                    'disk' => 'local', 'studio_asset_kind' => $kind,
                    'studio_asset_source' => $kind ? ReportStudioAssetLibrary::sourceForKind($kind) : null,
                ]);
                abort_unless($media->exists && $media->id, 409);
                $path = $media->path;
                abort_unless($file->storeAs(dirname($path), $media->file_name, 'local') === $path, 409);
                abort_unless(hash_file('sha256', $file->getRealPath()) === hash_file('sha256', Storage::disk('local')->path($path)), 409);
                $this->administrator();

                return $media;
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return response()->json([
            'id' => $media->id, 'preview_url' => $media->preview_url,
            'media' => [
                'id' => $media->id, 'name' => $media->name, 'mime_type' => $media->mime_type, 'file_type' => $media->file_type,
                'size' => $media->size, 'path' => $media->path,
                'studio_asset_kind' => $media->studio_asset_kind, 'studio_asset_source' => $media->studio_asset_source,
            ],
            'asset' => $assetLibrary->assetForMedia($media, $kind ?: 'gallery_image', $media->studio_asset_source ?: 'Upload do studio'),
        ]);
    }

    public function download(int $id): StreamedResponse
    {
        $this->administrator();
        $media = GestlabMedia::withTrashed()->findOrFail($id);

        return Storage::disk($media->disk)->download($media->path, $media->file_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function personal(int $id): Media
    {
        $actor = User::query()->find(auth()->id());
        abort_unless($actor && $actor->is_active && $actor->hasVerifiedEmail() && ! request()->session()->has('impersonate'), 403);
        $media = Media::findOrFail($id);
        abort_unless($media->model_type === (new User)->getMorphClass() && in_array($media->collection_name, ['signature', 'exports'], true), 404);
        abort_unless(($media->collection_name === 'signature' && (int) $media->model_id === $actor->id)
            || app(StaffAccountAccess::class)->isSystemAdministrator($actor), 403);

        return $media;
    }
}
