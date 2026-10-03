<?php

namespace App\Support;

class LabelStudioLogoSource
{
    private const MAX_IMAGE_BYTES = 2_000_000;

    public function resolve(?string $source): ?string
    {
        if (! filled($source)) {
            return null;
        }

        $source = trim($source);

        if (strlen($source) > (int) ceil(self::MAX_IMAGE_BYTES * 4 / 3) + 100) {
            return null;
        }

        if (str_starts_with($source, 'data:')) {
            if (! preg_match('#\Adata:image/(?:png|jpeg);base64,([A-Za-z0-9+/]+={0,2})\z#D', $source, $matches)) {
                return null;
            }

            $bytes = base64_decode($matches[1], true);

            return is_string($bytes) ? $this->rasterDataUri($bytes) : null;
        }

        $relativePath = ltrim($source, '/');

        if ($relativePath === '' || str_contains($relativePath, ':') || str_contains($relativePath, '\\')
            || in_array('..', explode('/', $relativePath), true)) {
            return null;
        }

        $resolvedPath = realpath(public_path($relativePath));
        $publicRoot = realpath(public_path());
        $publicDiskRoot = realpath(storage_path('app/public'));

        if ($resolvedPath === false || $publicRoot === false || ! is_file($resolvedPath)) {
            return null;
        }

        $isPublicAsset = str_starts_with($resolvedPath, $publicRoot.DIRECTORY_SEPARATOR);
        $isPublicDiskAsset = str_starts_with($relativePath, 'storage/')
            && $publicDiskRoot !== false
            && str_starts_with($resolvedPath, $publicDiskRoot.DIRECTORY_SEPARATOR);

        if (! $isPublicAsset && ! $isPublicDiskAsset) {
            return null;
        }

        $size = filesize($resolvedPath);

        if ($size === false || $size > self::MAX_IMAGE_BYTES) {
            return null;
        }

        $bytes = file_get_contents($resolvedPath);

        return is_string($bytes) ? $this->rasterDataUri($bytes) : null;
    }

    private function rasterDataUri(string $bytes): ?string
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_IMAGE_BYTES) {
            return null;
        }

        $image = @getimagesizefromstring($bytes);
        $mime = $image['mime'] ?? null;

        if (! in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
