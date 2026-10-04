<?php

namespace App\Actions;

use App\Http\Requests\GeneralSettingsRequest;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

class SaveGeneralSettings
{
    public function __construct(private readonly Container $container, private readonly SettingsCacheFactory $cacheFactory) {}

    /** @param array<string,mixed> $input */
    public function handle(int $actorId, array $input): void
    {
        $repository = (new GeneralSettings)->getRepository();
        abort_unless($repository instanceof DatabaseSettingsRepository, 409, 'A configuração requer armazenamento transaccional.');
        abort_if($this->cacheFactory->build(GeneralSettings::repository())->isEnabled(), 409,
            'A configuração transaccional não permite cache de valores.');
        $connection = $repository->getBuilder()->getConnection();
        abort_unless($connection->getName() === (new User)->getConnection()->getName(), 409,
            'A configuração e a autorização requerem a mesma ligação transaccional.');

        try {
            $connection->transaction(function () use ($repository, $actorId, $input): void {
                $repository->getBuilder()->where('group', GeneralSettings::group())->orderBy('id')->lockForUpdate()->get();
                $this->authorize($actorId);
                $request = new GeneralSettingsRequest;
                $data = Validator::make($request->normalizeSettings($input), $request->rules(), [], $request->attributes())->validate();
                $revision = $data['settings_revision'];
                unset($data['settings_revision']);
                $settings = (new GeneralSettings)->refresh();
                $before = $settings->toArray();
                $expected = [...$before, ...$data];

                if (! hash_equals($settings->revision(), $revision) && $expected !== $before) {
                    throw ValidationException::withMessages([
                        'settings_revision' => 'As definições mudaram noutra sessão. O rascunho foi mantido. Copie o que quiser conservar e descarte-o para carregar a versão actual.',
                    ]);
                }

                foreach ($settings->getLockedProperties() as $name) {
                    abort_if(($expected[$name] ?? null) !== ($before[$name] ?? null), 409,
                        'Uma definição seleccionada está bloqueada. Nenhuma alteração foi guardada.');
                }
                if ($expected !== $before) {
                    $settings->fill($data)->save();
                }

                abort_unless((new GeneralSettings)->toArray() === $expected, 409,
                    'Não foi possível confirmar as definições guardadas.');
                $this->authorize($actorId);
            }, 3);
        } finally {
            $this->container->forgetInstance(GeneralSettings::class);
        }
    }

    private function authorize(int $actorId): void
    {
        $actor = User::query()->where('is_active', true)->whereNotNull('email_verified_at')->lockForUpdate()->find($actorId);
        abort_unless($actor?->can('edit_settings'), 403);
    }
}
