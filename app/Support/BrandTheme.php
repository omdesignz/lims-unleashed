<?php

namespace App\Support;

use App\Settings\GeneralSettings;

/**
 * The laboratory's own colours, applied to the interface and the documents.
 *
 * Three colours are chosen in Settings (or one of the ready-made palettes).
 * From them come the action colours of the interface in the light and the dark
 * theme, each one adjusted until it can be read against the surface it sits on,
 * so no choice of colour can make a button or a link disappear.
 */
final class BrandTheme
{
    /** The palette the stylesheet itself carries. */
    public const DEFAULT_COLOURS = ['primary' => '#0757b5', 'secondary' => '#061f46', 'accent' => '#087cf0'];

    /**
     * Ready-made palettes, by the value kept in `app_theme_preset`.
     *
     * @var array<string, array{label: string, primary: string, secondary: string, accent: string}>
     */
    public const PALETTES = [
        'corporate' => ['label' => 'Azul VAP', 'primary' => '#0757b5', 'secondary' => '#061f46', 'accent' => '#087cf0'],
        'clinical' => ['label' => 'Verde clínico', 'primary' => '#0f6e56', 'secondary' => '#0a2c24', 'accent' => '#12a37f'],
        'executive' => ['label' => 'Grafite', 'primary' => '#334155', 'secondary' => '#0f172a', 'accent' => '#64748b'],
        'vibrant' => ['label' => 'Violeta', 'primary' => '#5b34c9', 'secondary' => '#1e1147', 'accent' => '#8b5cf6'],
        'bordeaux' => ['label' => 'Bordô', 'primary' => '#9f1f3a', 'secondary' => '#3b0a16', 'accent' => '#d9435f'],
        'ocean' => ['label' => 'Azul-petróleo', 'primary' => '#0b6b8a', 'secondary' => '#062a3a', 'accent' => '#1aa3c4'],
    ];

    /** The value of `app_theme_preset` when the colours were chosen by hand. */
    public const CUSTOM = 'custom';

    private const LIGHT_SURFACE = '#ffffff';

    private const DARK_SURFACE = '#070f1c';

    private const LIGHT_INK = '#ffffff';

    private const DARK_INK = '#04101f';

    /**
     * @return list<array{value: string, label: string, primary: string, secondary: string, accent: string}>
     */
    public static function palettes(): array
    {
        return collect(self::PALETTES)
            ->map(fn (array $palette, string $value): array => ['value' => $value, ...$palette])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function presetValues(): array
    {
        return [...array_keys(self::PALETTES), self::CUSTOM];
    }

    /**
     * The three colours in force, each a six-digit hex.
     *
     * @return array{primary: string, secondary: string, accent: string}
     */
    public static function colours(?GeneralSettings $settings): array
    {
        return [
            'primary' => self::hex($settings?->app_primary_color, self::DEFAULT_COLOURS['primary']),
            'secondary' => self::hex($settings?->app_secondary_color, self::DEFAULT_COLOURS['secondary']),
            'accent' => self::hex($settings?->app_accent_color, self::DEFAULT_COLOURS['accent']),
        ];
    }

    /**
     * The stylesheet that puts the laboratory's colours over the default ones.
     * Empty while the default palette is in force, so the default look is the
     * stylesheet's own and nothing else.
     */
    public static function css(?GeneralSettings $settings): string
    {
        $colours = self::colours($settings);

        if ($colours === self::DEFAULT_COLOURS) {
            return '';
        }

        $light = self::declarations([...self::paletteTokens($colours), ...self::lightTokens($colours)]);
        $dark = self::declarations(self::darkTokens($colours));

        return "html:root{{$light}}\n"
            ."html:root[data-theme='dark'],html.dark:root,html:root .dark,html:root .auth-sheet{{$dark}}";
    }

    /**
     * The colour a document prints its title rule and section numbers in: the
     * primary colour, darkened if needed to be read on white paper.
     */
    public static function documentAccent(?GeneralSettings $settings): string
    {
        return self::readableOn(self::colours($settings)['primary'], self::LIGHT_SURFACE, 4.5);
    }

    /**
     * @param  array{primary: string, secondary: string, accent: string}  $colours
     * @return array<string, string>
     */
    public static function lightTokens(array $colours): array
    {
        [$action, $actionInk] = self::filledOnLight($colours['primary']);
        [$hover, $hoverInk] = self::filledOnLight(
            strcasecmp($colours['secondary'], $colours['primary']) !== 0 ? $colours['secondary'] : self::mix($action, '#000000', 0.35)
        );
        [$band, $bandInk] = self::filledOnLight($colours['accent']);
        $tokens = [
            '--pl-accent' => $action,
            '--pl-accent-ink' => $actionInk,
            '--pl-accent-hover' => $hover,
            '--pl-accent-hover-ink' => $hoverInk,
            '--pl-accent-text' => self::readableOn($colours['primary'], self::LIGHT_SURFACE, 4.5),
            '--pl-band' => $band,
            '--pl-band-ink' => $bandInk,
        ];

        // Body text takes the secondary colour only when it is dark enough to read at length.
        if (self::contrast($colours['secondary'], self::LIGHT_SURFACE) >= 12.0) {
            $tokens['--pl-fg'] = $colours['secondary'];
        }

        return $tokens;
    }

    /**
     * @param  array{primary: string, secondary: string, accent: string}  $colours
     * @return array<string, string>
     */
    public static function darkTokens(array $colours): array
    {
        [$action, $actionInk] = self::filledOnDark($colours['accent']);
        $text = self::readableOn($colours['accent'], self::DARK_SURFACE, 7.0);

        return [
            '--pl-accent' => $action,
            '--pl-accent-ink' => $actionInk,
            '--pl-accent-hover' => $text,
            '--pl-accent-hover-ink' => self::DARK_INK,
            '--pl-accent-text' => $text,
        ];
    }

    /**
     * The tint scales the utility classes are built on.
     *
     * @param  array{primary: string, secondary: string, accent: string}  $colours
     * @return array<string, string>
     */
    public static function paletteTokens(array $colours): array
    {
        ['primary' => $primary, 'secondary' => $secondary, 'accent' => $accent] = $colours;

        $tokens = [
            '--brand-primary' => $primary,
            '--brand-on-primary' => self::contrast($primary, '#ffffff') >= self::contrast($primary, '#000000') ? '#ffffff' : '#000000',
            '--brand-secondary' => $secondary,
            '--brand-accent' => $accent,
        ];

        $primaryScale = [
            50 => self::mix($primary, '#ffffff', 0.92),
            100 => self::mix($primary, '#ffffff', 0.84),
            200 => self::mix($primary, '#ffffff', 0.72),
            300 => self::mix($primary, '#ffffff', 0.56),
            400 => self::mix($primary, '#ffffff', 0.24),
            500 => $primary,
            600 => self::mix($primary, '#000000', 0.08),
            700 => self::mix($primary, '#000000', 0.18),
            800 => self::mix($primary, $secondary, 0.55),
            900 => $secondary,
            950 => self::mix($secondary, '#000000', 0.28),
        ];
        $accentScale = [
            50 => self::mix($accent, '#ffffff', 0.92),
            100 => self::mix($accent, '#ffffff', 0.82),
            200 => self::mix($accent, '#ffffff', 0.66),
            300 => self::mix($accent, '#ffffff', 0.44),
            400 => self::mix($accent, '#ffffff', 0.16),
            500 => $accent,
            600 => self::mix($accent, '#000000', 0.16),
        ];

        foreach ($primaryScale as $step => $colour) {
            $tokens["--primary-{$step}-rgb"] = implode(' ', self::rgb($colour));
        }

        foreach ($accentScale as $step => $colour) {
            $tokens["--accent-{$step}-rgb"] = implode(' ', self::rgb($colour));
        }

        return $tokens;
    }

    /**
     * WCAG contrast ratio between two colours, from 1 to 21.
     */
    public static function contrast(string $first, string $second): float
    {
        $lighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * The colour itself when it already reads against the surface, otherwise
     * moved towards black or white, whichever the surface calls for, until it does.
     */
    public static function readableOn(string $colour, string $surface, float $ratio): string
    {
        $towards = self::luminance($surface) > 0.5 ? '#000000' : '#ffffff';
        $candidate = $colour;

        for ($step = 1; $step <= 20 && self::contrast($candidate, $surface) < $ratio; $step++) {
            $candidate = self::mix($colour, $towards, $step * 0.05);
        }

        return $candidate;
    }

    /**
     * A filled control on the light page: the colour and the ink of its label.
     * The colour is kept when a label reads on it and it stands out from the
     * page; otherwise it is darkened until a white label does.
     *
     * @return array{0: string, 1: string}
     */
    private static function filledOnLight(string $colour): array
    {
        if (self::contrast($colour, self::LIGHT_INK) >= 4.5) {
            return [$colour, self::LIGHT_INK];
        }

        if (self::contrast($colour, self::DARK_INK) >= 4.5 && self::contrast($colour, self::LIGHT_SURFACE) >= 3.0) {
            return [$colour, self::DARK_INK];
        }

        return [self::readableOn($colour, self::LIGHT_INK, 4.5), self::LIGHT_INK];
    }

    /**
     * A filled control on the dark page: lightened until it stands out from
     * the page and a dark label reads on it.
     *
     * @return array{0: string, 1: string}
     */
    private static function filledOnDark(string $colour): array
    {
        $fill = self::readableOn($colour, self::DARK_SURFACE, 4.5);

        return [self::readableOn($fill, self::DARK_INK, 4.5), self::DARK_INK];
    }

    private static function hex(?string $colour, string $fallback): string
    {
        return is_string($colour) && preg_match('/\A#[0-9a-fA-F]{6}\z/', $colour) === 1 ? strtolower($colour) : $fallback;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgb(string $colour): array
    {
        return [hexdec(substr($colour, 1, 2)), hexdec(substr($colour, 3, 2)), hexdec(substr($colour, 5, 2))];
    }

    private static function mix(string $colour, string $target, float $ratio): string
    {
        $from = self::rgb($colour);
        $to = self::rgb($target);

        return sprintf(
            '#%02x%02x%02x',
            ...array_map(fn (int $channel, int $other): int => max(0, min(255, (int) round($channel + ($other - $channel) * $ratio))), $from, $to)
        );
    }

    private static function luminance(string $colour): float
    {
        [$red, $green, $blue] = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, self::rgb($colour));

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private static function declarations(array $tokens): string
    {
        return collect($tokens)->map(fn (string $value, string $token): string => "{$token}:{$value};")->implode('');
    }
}
