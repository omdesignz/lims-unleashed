<?php

namespace App\Support;

use App\Models\GestlabMedia;
use App\Models\User;
use App\Services\StaffAccountAccess;
use Illuminate\Support\Facades\Storage;

class ReportStudioAssetLibrary
{
    public const DEFAULT_STUDIO_UPLOAD_KIND = 'uploaded_image';

    public const STUDIO_UPLOAD_KINDS = [
        'uploaded_asset',
        'uploaded_background',
        'uploaded_chart',
        'uploaded_image',
        'uploaded_signature',
        'uploaded_stamp',
    ];

    /**
     * @return array<int, string>
     */
    public static function studioUploadKinds(): array
    {
        return self::STUDIO_UPLOAD_KINDS;
    }

    public static function sourceForKind(string $kind): string
    {
        return match ($kind) {
            'uploaded_asset' => 'Carregamento de ficheiro',
            'uploaded_background' => 'Fundos carregados',
            'uploaded_chart' => 'Gráficos carregados',
            'uploaded_signature' => 'Assinaturas carregadas',
            'uploaded_stamp' => 'Carimbos carregados',
            'uploaded_image' => 'Imagens carregadas',
            default => 'Carregamento do estúdio',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function assets(): array
    {
        $actor = User::query()->find(auth()->id());

        return $actor && app(StaffAccountAccess::class)->isSystemAdministrator($actor) ? $this->galleryImages() : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function galleryImages(): array
    {
        return GestlabMedia::query()
            ->with('author:id,name')
            ->type('image')
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(fn (GestlabMedia $media): array => $this->assetForMedia(
                $media,
                $media->studio_asset_kind ?: 'gallery_image',
                $media->studio_asset_source ?: ($media->studio_asset_kind ? self::sourceForKind($media->studio_asset_kind) : 'Galeria')
            ))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function assetForMedia(GestlabMedia $media, string $kind = 'gallery_image', string $source = 'Galeria'): array
    {
        $actor = User::query()->find(auth()->id());
        abort_unless($actor && app(StaffAccountAccess::class)->isSystemAdministrator($actor), 403);
        $media->loadMissing('author:id,name');
        $disk = Storage::disk($media->disk);
        $pdfSource = $media->file_type === 'image' && $disk->exists($media->path) && $disk->size($media->path) <= 5 * 1024 * 1024
            ? 'data:'.$media->mime_type.';base64,'.base64_encode($disk->get($media->path)) : null;

        return [
            'id' => 'gallery-'.$media->id,
            'label' => $media->name,
            'kind' => $kind,
            'source' => $source,
            'url' => $media->preview_url,
            'pdf_url' => $pdfSource,
            'mime_type' => $media->mime_type,
            'file_type' => $media->file_type,
            'size' => $media->size,
            'author' => $media->author?->name,
        ];
    }
}
