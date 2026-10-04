<?php

namespace App\Services;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class NonConformityEvidencePathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $uuid = $media->getCustomProperty('quality_evidence_uuid');
        if ($uuid === null) {
            return (new DefaultPathGenerator)->getPath($media);
        }
        abort_unless(is_string($uuid) && Str::isUuid($uuid) && $media->uuid === $uuid
            && str_starts_with($media->file_name, 'quality-evidence-'.$uuid.'.') && $media->disk === 'local',
            409, 'A identidade da evidência é inválida.');

        return 'quality-evidence/'.$uuid.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive-images/';
    }
}
