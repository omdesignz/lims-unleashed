<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\BrandTheme;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The laboratory's own colours over the default palette of the interface.
 */
class BrandThemeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_default_palette_adds_nothing_to_the_stylesheet(): void
    {
        $this->assertSame('', BrandTheme::css($this->settingsWith(BrandTheme::DEFAULT_COLOURS)));
        $this->assertSame('', BrandTheme::css($this->settingsWith(['primary' => '#0757B5', 'secondary' => '#061F46', 'accent' => '#087CF0'])));

        // A colour that is not a six-digit hex is never printed: the default stands in for it.
        $this->assertSame('', BrandTheme::css($this->settingsWith(['primary' => 'red;}body{display:none', 'secondary' => null, 'accent' => '#fff'])));
        $this->assertSame(BrandTheme::DEFAULT_COLOURS, BrandTheme::colours(null));
    }

    public function test_a_palette_sets_the_action_colours_of_both_themes(): void
    {
        $palette = BrandTheme::PALETTES['clinical'];
        $css = BrandTheme::css($this->settingsWith($palette));

        [$light, $dark] = explode("\n", $css);

        $this->assertStringStartsWith('html:root{', $light);
        $this->assertStringContainsString('--pl-accent:#0f6e56;--pl-accent-ink:#ffffff;', $light);
        $this->assertStringContainsString('--pl-accent-hover:#0a2c24;', $light);
        $this->assertStringContainsString('--pl-fg:#0a2c24;', $light);
        $this->assertStringContainsString('--primary-500-rgb:15 110 86;', $light);
        $this->assertStringContainsString('--brand-primary:#0f6e56;', $light);

        // The dark theme, nested dark panels and the sign-in panel take the dark set.
        $this->assertStringStartsWith("html:root[data-theme='dark'],html.dark:root,html:root .dark,html:root .auth-sheet{", $dark);
        $this->assertStringContainsString('--pl-accent:#12a37f;--pl-accent-ink:#04101f;', $dark);
        $this->assertDoesNotMatchRegularExpression('/[^#0-9a-z:;,\-\s\[\]=\'.{}]/', $css);
    }

    public function test_every_colour_is_made_readable_whatever_is_chosen(): void
    {
        $choices = [
            ...array_values(BrandTheme::PALETTES),
            ['primary' => '#ffe600', 'secondary' => '#ffe600', 'accent' => '#fff8b0'],
            ['primary' => '#ffffff', 'secondary' => '#ffffff', 'accent' => '#ffffff'],
            ['primary' => '#000000', 'secondary' => '#000000', 'accent' => '#000000'],
            ['primary' => '#7f7f7f', 'secondary' => '#808080', 'accent' => '#0a0a0a'],
        ];

        foreach ($choices as $choice) {
            $colours = ['primary' => $choice['primary'], 'secondary' => $choice['secondary'], 'accent' => $choice['accent']];
            $light = BrandTheme::lightTokens($colours);
            $dark = BrandTheme::darkTokens($colours);
            $label = implode(' ', $colours);

            // Filled controls stand out from the page and their label reads on them.
            $this->assertGreaterThanOrEqual(3.0, BrandTheme::contrast($light['--pl-accent'], '#ffffff'), $label);
            $this->assertGreaterThanOrEqual(4.5, BrandTheme::contrast($light['--pl-accent'], $light['--pl-accent-ink']), $label);
            $this->assertGreaterThanOrEqual(4.5, BrandTheme::contrast($light['--pl-accent-hover'], $light['--pl-accent-hover-ink']), $label);
            $this->assertGreaterThanOrEqual(4.5, BrandTheme::contrast($light['--pl-band'], $light['--pl-band-ink']), $label);
            $this->assertGreaterThanOrEqual(4.5, BrandTheme::contrast($dark['--pl-accent'], '#070f1c'), $label);
            $this->assertGreaterThanOrEqual(4.5, BrandTheme::contrast($dark['--pl-accent'], $dark['--pl-accent-ink']), $label);

            // Links and accent text read on the page itself.
            $this->assertGreaterThanOrEqual(4.5, BrandTheme::contrast($light['--pl-accent-text'], '#ffffff'), $label);
            $this->assertGreaterThanOrEqual(7.0, BrandTheme::contrast($dark['--pl-accent-text'], '#070f1c'), $label);

            // Body text takes the secondary colour only when it is dark enough.
            if (isset($light['--pl-fg'])) {
                $this->assertGreaterThanOrEqual(12.0, BrandTheme::contrast($light['--pl-fg'], '#ffffff'), $label);
            }
        }

        $this->assertArrayNotHasKey('--pl-fg', BrandTheme::lightTokens(['primary' => '#ffe600', 'secondary' => '#ffe600', 'accent' => '#fff8b0']));
    }

    public function test_documents_print_their_accent_readable_on_white_paper(): void
    {
        $this->assertSame('#0757b5', BrandTheme::documentAccent($this->settingsWith(BrandTheme::DEFAULT_COLOURS)));

        $yellow = $this->settingsWith(['primary' => '#ffe600', 'secondary' => '#111111', 'accent' => '#ffe600']);
        $accent = BrandTheme::documentAccent($yellow);
        $this->assertGreaterThanOrEqual(4.5, BrandTheme::contrast($accent, '#ffffff'));

        app()->instance(GeneralSettings::class, $yellow);
        $stylesheet = view('PDFs.partials.premium-document-style')->render();
        $this->assertStringContainsString('solid '.$accent.';', $stylesheet);
        $this->assertStringNotContainsString('#ffe600', $stylesheet);
    }

    public function test_the_page_carries_the_laboratorys_colours_from_its_first_paint(): void
    {
        $this->get('/login')->assertOk()->assertSee('<style id="brand-theme"></style>', false);

        app()->instance(GeneralSettings::class, $this->settingsWith(BrandTheme::PALETTES['bordeaux']));
        $css = BrandTheme::css(app(GeneralSettings::class));

        $this->get('/login')
            ->assertOk()
            ->assertSee('<style id="brand-theme">'.$css.'</style>', false)
            ->assertInertia(fn (Assert $page) => $page->where('settings.brand_css', $css));
    }

    public function test_settings_offer_the_palettes_and_accept_only_known_ones(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin)
            ->get(route('generalsettings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('brandPalettes', count(BrandTheme::PALETTES))
                ->where('brandPalettes.0.value', 'corporate')
                ->where('brandPalettes.0.primary', BrandTheme::DEFAULT_COLOURS['primary'])
            );

        $this->assertSame(['corporate', 'clinical', 'executive', 'vibrant', 'bordeaux', 'ocean', 'custom'], BrandTheme::presetValues());

        foreach (BrandTheme::PALETTES as $palette) {
            foreach (['primary', 'secondary', 'accent'] as $colour) {
                $this->assertMatchesRegularExpression('/\A#[0-9a-f]{6}\z/', $palette[$colour]);
            }
        }
    }

    /**
     * @param  array{primary: ?string, secondary: ?string, accent: ?string}  $colours
     */
    private function settingsWith(array $colours): GeneralSettings
    {
        $settings = clone app(GeneralSettings::class);
        $settings->app_primary_color = $colours['primary'];
        $settings->app_secondary_color = $colours['secondary'];
        $settings->app_accent_color = $colours['accent'];

        return $settings;
    }
}
