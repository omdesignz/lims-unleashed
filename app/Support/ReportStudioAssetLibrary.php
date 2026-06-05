<?php

namespace App\Support;

use App\Models\GestlabMedia;
use App\Models\User;

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
            'uploaded_asset' => 'Upload de ficheiro',
            'uploaded_background' => 'Fundos carregados',
            'uploaded_chart' => 'Gráficos carregados',
            'uploaded_signature' => 'Assinaturas carregadas',
            'uploaded_stamp' => 'Carimbos carregados',
            'uploaded_image' => 'Imagens carregadas',
            default => 'Upload do studio',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function assets(): array
    {
        return collect()
            ->merge($this->galleryImages())
            ->merge($this->profileSignatures())
            ->values()
            ->all();
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
            ->limit(60)
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
        $media->loadMissing('author:id,name');

        return [
            'id' => 'gallery-'.$media->id,
            'label' => $media->name,
            'kind' => $kind,
            'source' => $source,
            'url' => $media->preview_url,
            'pdf_url' => $media->preview_url,
            'mime_type' => $media->mime_type,
            'file_type' => $media->file_type,
            'size' => $media->size,
            'author' => $media->author?->name,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function profileSignatures(): array
    {
        return User::query()
            ->whereHas('media', fn ($query) => $query->where('collection_name', 'signature'))
            ->with(['media' => fn ($query) => $query->where('collection_name', 'signature')])
            ->orderBy('name')
            ->limit(80)
            ->get(['id', 'name'])
            ->map(function (User $user): ?array {
                $signatureUrl = $user->getFirstMediaUrl('signature');

                if ($signatureUrl === '') {
                    return null;
                }

                return [
                    'id' => 'signature-'.$user->id,
                    'label' => $user->name,
                    'kind' => 'profile_signature',
                    'source' => 'Assinaturas',
                    'url' => $signatureUrl,
                    'pdf_url' => $signatureUrl,
                    'mime_type' => 'image/png',
                    'author' => $user->name,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
