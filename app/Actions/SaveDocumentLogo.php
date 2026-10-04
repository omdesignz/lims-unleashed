<?php

namespace App\Actions;

use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;
use Throwable;

/**
 * Replaces or removes the logo printed on generated documents. The file is
 * written before the setting changes and the previous file is deleted only
 * once the new setting is committed, so a failure never leaves the setting
 * pointing at a missing file.
 */
class SaveDocumentLogo
{
    public const DISK = 'public';

    public const DIRECTORY = 'branding';

    public function __construct(private readonly Container $container) {}

    public function store(int $actorId, UploadedFile $file): string
    {
        $path = $file->store(self::DIRECTORY, self::DISK);
        abort_if($path === false, 500, 'Não foi possível guardar o logótipo.');

        try {
            $previous = $this->replace($actorId, $path);
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);

            throw $exception;
        }

        $this->deleteFile($previous);

        return $path;
    }

    public function remove(int $actorId): void
    {
        $this->deleteFile($this->replace($actorId, null));
    }

    /** Sets the logo path and returns the one it replaced. */
    private function replace(int $actorId, ?string $path): ?string
    {
        $repository = (new GeneralSettings)->getRepository();
        abort_unless($repository instanceof DatabaseSettingsRepository, 409, 'A configuração requer armazenamento transaccional.');

        try {
            return $repository->getBuilder()->getConnection()->transaction(function () use ($repository, $actorId, $path): ?string {
                $repository->getBuilder()->where('group', GeneralSettings::group())->orderBy('id')->lockForUpdate()->get();
                $actor = User::query()->where('is_active', true)->whereNotNull('email_verified_at')->lockForUpdate()->find($actorId);
                abort_unless($actor?->can('edit_settings'), 403);

                $settings = (new GeneralSettings)->refresh();
                abort_if(in_array('app_document_logo', $settings->getLockedProperties(), true), 409,
                    'O logótipo dos documentos está bloqueado. Nenhuma alteração foi guardada.');
                $previous = $settings->app_document_logo;
                $settings->app_document_logo = $path;
                $settings->save();

                return $previous;
            }, 3);
        } finally {
            $this->container->forgetInstance(GeneralSettings::class);
        }
    }

    private function deleteFile(?string $path): void
    {
        if (filled($path) && str_starts_with($path, self::DIRECTORY.'/')) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
