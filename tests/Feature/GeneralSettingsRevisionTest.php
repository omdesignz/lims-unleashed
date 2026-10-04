<?php

namespace Tests\Feature;

use App\Actions\SaveGeneralSettings;
use App\Models\Permission;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\LaravelSettings\Events\SettingsSaved;
use Tests\TestCase;

class GeneralSettingsRevisionTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['view_settings', 'edit_settings'] as $name) {
            $this->actor->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($this->actor);
    }

    public function test_page_revision_matches_the_displayed_snapshot_without_disclosing_the_secret(): void
    {
        $settings = new GeneralSettings;
        $settings->getRepository()->updatePropertiesPayload('general', ['app_private_key' => 'FICTIONAL-REVISION-SECRET']);
        $revision = $settings->revision();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $revision);
        $this->get(route('generalsettings.index'))->assertSuccessful()
            ->assertDontSee('FICTIONAL-REVISION-SECRET', false)
            ->assertInertia(fn (Assert $page) => $page->where('settingsRevision', $revision)->missing('settings.app_private_key'));
    }

    #[DataProvider('invalidRevisions')]
    public function test_http_and_direct_saves_require_a_well_formed_revision(mixed $revision): void
    {
        $before = $this->rows();
        $input = ['app_name' => 'Must not save', 'settings_revision' => $revision];
        $this->post(route('generalsettings.update'), $input)->assertSessionHasErrors('settings_revision');
        $this->assertRevisionRejected($input);
        $this->assertSame($before, $this->rows());
    }

    /** @return array<string,array{mixed}> */
    public static function invalidRevisions(): array
    {
        return ['null' => [null], 'blank' => [''], 'short' => ['abc'], 'array' => [[]], 'nonhex' => [str_repeat('z', 64)]];
    }

    public function test_missing_revision_cannot_save_even_an_unchanged_payload(): void
    {
        $this->assertRevisionRejected(['app_name' => (new GeneralSettings)->app_name]);
    }

    #[DataProvider('interveningChanges')]
    public function test_stale_full_draft_is_rejected_atomically_including_secret_only_changes(string $field): void
    {
        $settings = new GeneralSettings;
        $payload = [...Arr::except($settings->toArray(), 'app_private_key'), 'settings_revision' => $settings->revision(),
            'app_name' => 'Stale draft name', 'app_private_key' => 'FICTIONAL-REJECTED-REPLACEMENT'];
        $settings->getRepository()->updatePropertiesPayload('general', [$field => 'Newly committed value']);
        $before = $this->rows();

        $this->from(route('generalsettings.index'))->post(route('generalsettings.update'), $payload)
            ->assertRedirect(route('generalsettings.index'))->assertSessionHasErrors('settings_revision')
            ->assertSessionHas('_old_input.app_name', 'Stale draft name')->assertSessionMissing('_old_input.app_private_key');

        $this->assertSame($before, $this->rows());
        $this->assertNotSame($payload['settings_revision'], (new GeneralSettings)->refresh()->revision());
        $this->assertRevisionRejected($payload);
        $this->assertSame($before, $this->rows());
    }

    /** @return array<string,array{string}> */
    public static function interveningChanges(): array
    {
        return ['ordinary field' => ['app_contact'], 'write-only key' => ['app_private_key']];
    }

    public function test_tampered_revision_cannot_authorize_a_changed_value(): void
    {
        $before = $this->rows();
        $this->assertRevisionRejected(['settings_revision' => str_repeat('0', 64), 'app_name' => 'Tampered save']);
        $this->assertSame($before, $this->rows());
    }

    public function test_successful_revision_refresh_and_identical_lost_response_retry_do_not_rewrite_rows(): void
    {
        $before = (new GeneralSettings)->revision();
        $data = ['settings_revision' => $before, 'app_name' => 'Saved revision'];
        app(SaveGeneralSettings::class)->handle($this->actor->id, $data);
        $saved = new GeneralSettings;
        $this->assertNotSame($before, $saved->revision());
        $this->assertFalse($saved->getRepository()->checkIfPropertyExists('general', 'settings_revision'));
        $rows = $this->rows();
        Event::fake([SettingsSaved::class]);

        app(SaveGeneralSettings::class)->handle($this->actor->id, $data);

        $this->assertSame($rows, $this->rows());
        Event::assertNotDispatched(SettingsSaved::class);
    }

    public function test_lock_state_changes_invalidate_an_older_draft(): void
    {
        $settings = new GeneralSettings;
        $revision = $settings->revision();
        $settings->lock('app_name');
        $this->assertRevisionRejected(['settings_revision' => $revision, 'app_slogan' => 'Older draft']);
    }

    /** @param array<string,mixed> $input */
    private function assertRevisionRejected(array $input): void
    {
        try {
            app(SaveGeneralSettings::class)->handle($this->actor->id, $input);
            $this->fail('Expected a revision conflict.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('settings_revision', $exception->errors());
        }
    }

    /** @return list<array<string,mixed>> */
    private function rows(): array
    {
        return DB::table('settings')->where('group', 'general')->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
    }
}
