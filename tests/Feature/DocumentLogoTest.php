<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\ControlledDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The logo printed on generated documents: uploaded from the settings page,
 * kept on the public disk and embedded in every letterhead.
 */
class DocumentLogoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_an_uploaded_logo_is_printed_on_the_letterhead_fitted_to_its_box(): void
    {
        $this->actingAs($this->user(true));

        $this->post(route('generalsettings.document-logo.update'), ['logo' => UploadedFile::fake()->image('marca.png', 400, 100)])
            ->assertRedirect()->assertSessionHasNoErrors();

        $path = app(GeneralSettings::class)->app_document_logo;
        $this->assertStringStartsWith('branding/', $path);
        Storage::disk('public')->assertExists($path);

        $logo = ControlledDocument::laboratoryLogoHtml(app(GeneralSettings::class));
        $this->assertStringContainsString('src="data:image/png;base64,', $logo);
        // 400 × 100 px fitted into 38 × 18 mm keeps its proportions.
        $this->assertStringContainsString('style="width:38mm; height:9.5mm;"', $logo);

        $furnishings = ControlledDocument::furnishings(app(GeneralSettings::class), 'Relatório', 'R-1', [['N.º', 'R-1']], '04/10/2026');
        $this->assertStringContainsString('<td class="doc-letterhead-logo"><img src="data:image/png;base64,', $furnishings['letterhead']);

        $this->get(route('generalsettings.index'))->assertInertia(fn (Assert $page) => $page
            ->where('documentLogoUrl', Storage::disk('public')->url($path))
            ->missing('settings.app_document_logo'));
    }

    public function test_replacing_the_logo_deletes_the_previous_file_and_removing_it_clears_the_letterhead(): void
    {
        $this->actingAs($this->user(true));
        $this->post(route('generalsettings.document-logo.update'), ['logo' => UploadedFile::fake()->image('primeira.jpg', 200, 200)]);
        $first = app(GeneralSettings::class)->app_document_logo;

        $this->post(route('generalsettings.document-logo.update'), ['logo' => UploadedFile::fake()->image('segunda.png', 120, 240)])
            ->assertSessionHasNoErrors();
        $second = app(GeneralSettings::class)->app_document_logo;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        $this->assertStringContainsString('style="width:9mm; height:18mm;"', ControlledDocument::laboratoryLogoHtml(app(GeneralSettings::class)));

        $this->delete(route('generalsettings.document-logo.destroy'))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNull(app(GeneralSettings::class)->app_document_logo);
        Storage::disk('public')->assertMissing($second);
        $this->assertSame('', ControlledDocument::laboratoryLogoHtml($this->withoutLogoUrl()));
    }

    public function test_a_missing_file_falls_back_to_the_brand_logo_or_to_nothing(): void
    {
        $settings = $this->withoutLogoUrl();
        $settings->app_document_logo = 'branding/apagado.png';
        $this->assertSame('', ControlledDocument::laboratoryLogoHtml($settings));

        $settings->app_logo_url = 'https://example.test/marca.png';
        $this->assertStringContainsString('src="https://example.test/marca.png"', ControlledDocument::laboratoryLogoHtml($settings));

        // A stored path never reaches outside the public disk.
        $settings->app_logo_url = null;
        $settings->app_document_logo = '../../.env';
        $this->assertSame('', ControlledDocument::laboratoryLogoHtml($settings));
    }

    /**
     * @return array<string, array{UploadedFile}>
     */
    public static function rejectedFiles(): array
    {
        return [
            'svg' => [UploadedFile::fake()->create('marca.svg', 4, 'image/svg+xml')],
            'pdf' => [UploadedFile::fake()->create('marca.pdf', 4, 'application/pdf')],
            'over 1 MB' => [UploadedFile::fake()->image('grande.png', 400, 400)->size(1500)],
            'too small' => [UploadedFile::fake()->image('pequeno.png', 10, 10)],
        ];
    }

    #[DataProvider('rejectedFiles')]
    public function test_only_small_png_or_jpeg_images_are_accepted(UploadedFile $file): void
    {
        $this->actingAs($this->user(true));

        $this->post(route('generalsettings.document-logo.update'), ['logo' => $file])->assertSessionHasErrors('logo');

        $this->assertNull(app(GeneralSettings::class)->app_document_logo);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_reader_cannot_change_the_logo(): void
    {
        $this->actingAs($this->user(false));

        $this->post(route('generalsettings.document-logo.update'), ['logo' => UploadedFile::fake()->image('marca.png', 100, 100)])->assertForbidden();
        $this->delete(route('generalsettings.document-logo.destroy'))->assertForbidden();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_saving_the_other_settings_keeps_the_logo(): void
    {
        $this->actingAs($this->user(true));
        $this->post(route('generalsettings.document-logo.update'), ['logo' => UploadedFile::fake()->image('marca.png', 100, 100)]);
        $path = app(GeneralSettings::class)->app_document_logo;

        $this->post(route('generalsettings.update'), ['settings_revision' => (new GeneralSettings)->revision(), 'app_slogan' => 'Outro slogan', 'app_document_logo' => null])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($path, app(GeneralSettings::class)->app_document_logo);
    }

    private function withoutLogoUrl(): GeneralSettings
    {
        $settings = clone app(GeneralSettings::class);
        $settings->app_logo_url = null;

        return $settings;
    }

    private function user(bool $admin): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        if ($admin) {
            $user->assignRole(Role::findOrCreate('admin', 'web'));
        } else {
            $user->givePermissionTo(Permission::findOrCreate('view_settings', 'web'));
        }

        return $user;
    }
}
