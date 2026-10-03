<?php

namespace App\Services;

use App\Models\Result;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class LaboratoryResultSignatures
{
    /** @param Collection<int, Result> $results @return Collection<int, Media> */
    public function lockEvidence(Collection $results): Collection
    {
        return Media::query()->where('model_type', (new Result)->getMorphClass())->whereIn('model_id', $results->keys())
            ->orderBy('id')->lockForUpdate()->toBase()->get()->mapWithKeys(function (object $row): array {
                $media = new Media;
                $media->setRawAttributes((array) $row, true);
                $media->exists = true;

                return [$media->id => $media];
            });
    }

    /** @param Collection<int, Result> $results @param Collection<int, Media> $evidence @param array<int, Media> $staged */
    public function assertEvidence(Collection $results, Collection $evidence, array $staged = []): void
    {
        $rows = Media::query()->where(function (Builder $query) use ($results, $evidence): void {
            $query->where('model_type', (new Result)->getMorphClass())->whereIn('model_id', $results->keys());
            if ($evidence->isNotEmpty()) {
                $query->orWhereIn('id', $evidence->keys());
            }
        })->orderBy('id')->toBase()->get()->keyBy('id');
        if ($rows->keys()->sort()->values()->all() !== $evidence->keys()->sort()->values()->all()) {
            throw new LogicException('Persisted signature set differs from the intended signature set.');
        }
        foreach ($evidence as $id => $intended) {
            $stored = new Media;
            $stored->setRawAttributes((array) $rows[$id], true);
            foreach (array_keys($intended->getAttributes()) as $field) {
                $expected = $intended->getAttribute($field);
                $actual = $stored->getAttribute($field);
                if ($expected instanceof DateTimeInterface) {
                    $expected = $expected->format('Y-m-d H:i:s.u');
                }
                if ($actual instanceof DateTimeInterface) {
                    $actual = $actual->format('Y-m-d H:i:s.u');
                }
                if ($expected !== $actual) {
                    throw new LogicException('Persisted signature evidence differs from the intended signature.');
                }
            }
        }
        foreach ($staged as $signature) {
            $disk = Storage::disk($signature->disk);
            $path = $signature->getPathRelativeToRoot();
            if (! $disk->exists($path) || ! hash_equals((string) $signature->getCustomProperty('signature_sha256'), hash('sha256', $disk->get($path)))) {
                throw new LogicException('Persisted signature file differs from the intended signature.');
            }
        }
    }

    /** @return array{bytes: string, mime: string, hash: string} */
    public function prepare(User $operator, ?string $signature): array
    {
        if (filled($signature)) {
            $encoded = str_contains($signature, ',') ? Str::after($signature, ',') : $signature;
            $bytes = base64_decode($encoded, true);
        } else {
            $media = $operator->getFirstMedia('signature');
            $bytes = $media && Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())
                ? Storage::disk($media->disk)->get($media->getPathRelativeToRoot()) : false;
        }

        $mime = is_string($bytes) ? (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) : false;
        if (! is_string($bytes) || ! in_array($mime, ['image/png', 'image/jpeg'], true)
            || strlen($bytes) > (int) config('media-library.max_file_size') || @getimagesizefromstring($bytes) === false) {
            throw ValidationException::withMessages(['signature' => 'É necessária uma assinatura válida em PNG ou JPEG.']);
        }

        return ['bytes' => $bytes, 'mime' => $mime, 'hash' => hash('sha256', $bytes)];
    }

    /**
     * @param  array{bytes: string, mime: string, hash: string}  $signature
     * @param  array<int, Media>  $staged
     * @param  Collection<int, Media>  $evidence
     */
    public function replace(Result $result, string $collection, array $signature, array &$staged, Collection $evidence): void
    {
        $token = (string) Str::uuid();
        $pendingCollection = 'pending_result_signature_'.$token;
        $fileName = $collection.'-'.$result->id.'-'.$token.($signature['mime'] === 'image/png' ? '.png' : '.jpg');
        $diskName = (string) config('media-library.disk_name');
        $temporary = tempnam(sys_get_temp_dir(), 'result-signature-');
        if ($temporary === false) {
            throw new \RuntimeException('Não foi possível preparar a assinatura.');
        }
        try {
            File::put($temporary, $signature['bytes']);
            $media = $result->addMedia($temporary)
                ->usingFileName($fileName)
                ->withAttributes(['uuid' => $token])
                ->withCustomProperties(['signature_sha256' => $signature['hash']])
                ->toMediaCollection($pendingCollection, $diskName);
            if (! $media->exists || ! $media->getKey()) {
                throw new LogicException('Signature evidence was not persisted.');
            }
            $stagedSignature = clone $media;
            $stagedSignature->setCustomProperty('signature_sha256', $signature['hash']);
            $staged[] = $stagedSignature;
            $intended = clone $media;
            $intended->forceFill(['model_type' => $result->getMorphClass(), 'model_id' => $result->id,
                'collection_name' => $pendingCollection, 'file_name' => $fileName, 'uuid' => $token,
                'mime_type' => $signature['mime'], 'size' => strlen($signature['bytes']), 'disk' => $diskName,
                'custom_properties' => ['signature_sha256' => $signature['hash']]]);
            $evidence[$media->id] = $intended;
        } catch (Throwable $exception) {
            foreach (Media::query()->where('uuid', $token)->toBase()->get() as $row) {
                $failed = new Media;
                $failed->setRawAttributes((array) $row, true);
                app(Filesystem::class)->removeAllFiles($failed);
            }
            Storage::disk($diskName)->delete($fileName);
            throw new LogicException('Signature evidence was not persisted.', 0, $exception);
        } finally {
            File::delete($temporary);
        }

        $previous = $result->media()->where('collection_name', $collection)->lockForUpdate()->get();
        $retiredCollection = 'retired_result_signature_'.$token;
        foreach ($previous as $old) {
            $this->saveEvidence($old, $retiredCollection, $evidence);
        }
        $this->saveEvidence($media, $collection, $evidence);
        $result->unsetRelation('media');

        DB::afterCommit(function () use ($previous, $retiredCollection): void {
            foreach ($previous as $old) {
                try {
                    $old->newQuery()->whereKey($old->id)->where('collection_name', $retiredCollection)->first()?->delete();
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        });
    }

    /** @param Collection<int, Media> $evidence */
    private function saveEvidence(Media $media, string $collection, Collection $evidence): void
    {
        if (! $evidence->has($media->id)) {
            throw new LogicException('The prior signature differs from the locked signature set.');
        }
        $intended = clone $evidence->get($media->id);
        $attributes = ['collection_name' => $collection, 'updated_at' => now()->format($media->getDateFormat())];
        $intended->forceFill($attributes);
        $media->forceFill($attributes);
        if (! Media::withoutTimestamps(fn (): bool => $media->save())) {
            throw new LogicException('Signature evidence was not persisted.');
        }
        $evidence[$media->id] = $intended;
    }

    /** @param array<int, Media> $staged */
    public function discard(array $staged): void
    {
        foreach ($staged as $media) {
            if (! $media->exists || ! $media->getKey()) {
                continue;
            }
            app(Filesystem::class)->removeAllFiles($media);
        }
    }
}
