<?php

namespace Tests\Feature;

use App\Actions\SaveGeneralSettings;
use App\Models\Permission;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Events\SettingsSaved;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GeneralSettingsPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $this->actor->givePermissionTo(Permission::findOrCreate('edit_settings', 'web'));
    }

    public function test_stale_scoped_instance_cannot_overwrite_omitted_settings_or_secret(): void
    {
        $stale = app(GeneralSettings::class);
        $stale->toArray();
        $repository = $stale->getRepository();
        $repository->updatePropertiesPayload('general', ['app_private_key' => 'NEW-FICTIONAL-KEY', 'app_contact' => 'latest contact']);

        $this->save(['app_slogan' => 'Updated slogan', 'app_private_key' => '   ']);

        $fresh = app(GeneralSettings::class);
        $this->assertNotSame($stale, $fresh);
        $this->assertSame('NEW-FICTIONAL-KEY', $fresh->app_private_key);
        $this->assertSame('latest contact', $fresh->app_contact);
        $this->assertSame('Updated slogan', $fresh->app_slogan);
    }

    public function test_noop_does_not_dispatch_save_events_or_change_rows(): void
    {
        $settings = new GeneralSettings;
        $before = $this->rows();
        Event::fake([SavingSettings::class, SettingsSaved::class]);

        $this->save(['app_name' => $settings->app_name]);

        $this->assertSame($before, $this->rows());
        Event::assertNotDispatched(SavingSettings::class);
        Event::assertNotDispatched(SettingsSaved::class);
    }

    public function test_locked_property_rejects_the_entire_patch(): void
    {
        (new GeneralSettings)->lock('app_name');
        $before = $this->rows();
        $this->assertRejected(fn () => $this->save([
            'app_name' => 'Locked replacement', 'app_slogan' => 'Must roll back',
        ]), 409);
        $this->assertSame($before, $this->rows());
    }

    #[DataProvider('saveEvents')]
    public function test_event_exception_rolls_back_every_setting_and_discards_mutated_instance(string $event): void
    {
        $before = (new GeneralSettings)->toArray();
        $rows = $this->rows();
        Event::listen($event, static function (): void {
            throw new RuntimeException('Simulated settings failure.');
        });
        try {
            $this->save(['app_name' => 'Must roll back', 'app_private_key' => 'FAILED-FICTIONAL-KEY']);
            $this->fail('Expected save failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated settings failure.', $exception->getMessage());
        }
        $this->assertSame($rows, $this->rows());
        $this->assertSame($before, app(GeneralSettings::class)->toArray());
    }

    /** @return array<string,array{class-string}> */
    public static function saveEvents(): array
    {
        return ['before write' => [SavingSettings::class], 'after write' => [SettingsSaved::class]];
    }

    public function test_persisted_corruption_is_detected_and_rolled_back(): void
    {
        $before = $this->rows();
        Event::listen(SettingsSaved::class, static function (SettingsSaved $event): void {
            $event->settings->getRepository()->updatePropertiesPayload('general', ['app_name' => 'Unexpected persisted value']);
        });
        $this->assertRejected(fn () => $this->save(['app_name' => 'Intended value']), 409);
        $this->assertSame($before, $this->rows());
    }

    public function test_permission_loss_during_save_rolls_back_configuration_and_local_revocation(): void
    {
        $before = $this->rows();
        Event::listen(SettingsSaved::class, function (): void {
            $this->actor->revokePermissionTo('edit_settings');
        });
        $this->assertRejected(fn () => $this->save(['app_name' => 'Must roll back']), 403);
        $this->assertSame($before, $this->rows());
        $this->assertTrue($this->actor->fresh()->can('edit_settings'));
    }

    #[DataProvider('ineligibleActors')]
    public function test_direct_action_requires_current_eligible_actor(string $state): void
    {
        if ($state === 'permission') {
            $this->actor->revokePermissionTo('edit_settings');
        } elseif ($state === 'inactive') {
            $this->actor->update(['is_active' => false]);
        } else {
            $this->actor->update(['email_verified_at' => null]);
        }
        $before = $this->rows();
        $this->assertRejected(fn () => $this->save(['app_name' => 'Forbidden']), 403);
        $this->assertSame($before, $this->rows());
    }

    /** @return array<string,array{string}> */
    public static function ineligibleActors(): array
    {
        return ['revoked permission' => ['permission'], 'inactive' => ['inactive'], 'unverified' => ['unverified']];
    }

    public function test_direct_action_uses_http_validation_normalization_and_allowlist(): void
    {
        $before = $this->rows();
        try {
            $this->save(['app_email' => 'bad email', 'app_private_key' => []]);
            $this->fail('Expected validation failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('app_email', $exception->errors());
            $this->assertArrayHasKey('app_private_key', $exception->errors());
        }
        $this->assertSame($before, $this->rows());
        $this->save(['app_name' => '  Normalized  ', 'app_contact' => ' ', 'unknown_setting' => 'forged']);
        $settings = new GeneralSettings;
        $this->assertSame('Normalized', $settings->app_name);
        $this->assertNull($settings->app_contact);
        $this->assertFalse($settings->getRepository()->checkIfPropertyExists('general', 'unknown_setting'));
    }

    public function test_general_settings_do_not_cache_transaction_local_values_even_when_default_cache_is_enabled(): void
    {
        config(['settings.cache.enabled' => true, 'settings.cache.store' => 'array']);
        $factory = app(SettingsCacheFactory::class);
        $this->assertTrue($factory->build()->isEnabled());
        $this->assertFalse($factory->build(GeneralSettings::repository())->isEnabled());
        $before = (new GeneralSettings)->toArray();
        DB::beginTransaction();
        try {
            $this->save(['app_name' => 'Uncommitted settings']);
            $this->assertSame('Uncommitted settings', (new GeneralSettings)->app_name);
        } finally {
            DB::rollBack();
            app()->forgetInstance(GeneralSettings::class);
        }
        $this->assertSame($before, app(GeneralSettings::class)->toArray());
    }

    /** @param array<string,mixed> $data */
    private function save(array $data): void
    {
        app(SaveGeneralSettings::class)->handle($this->actor->id, ['settings_revision' => (new GeneralSettings)->refresh()->revision(), ...$data]);
    }

    /** @return list<array<string,mixed>> */
    private function rows(): array
    {
        return DB::table('settings')->where('group', 'general')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
    }

    private function assertRejected(callable $action, int $status): void
    {
        try {
            $action();
            $this->fail('Expected operation rejection.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }
}
