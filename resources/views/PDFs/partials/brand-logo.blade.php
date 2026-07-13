@php
    try {
        $brandSettings = $settings ?? app(\App\Settings\GeneralSettings::class);
        $brandLogoSource = $brandSettings->app_logo_url ?: null;
        $brandLogoAlt = $alt ?? ($brandSettings->app_client_lab_name ?: $brandSettings->app_name ?: config('app.name', 'Laboratory workspace'));
    } catch (\Throwable) {
        $brandLogoSource = null;
        $brandLogoAlt = $alt ?? config('app.name', 'LIMS Unleashed');
    }

    if (is_string($brandLogoSource) && str_starts_with($brandLogoSource, '/storage/')) {
        $brandLogoSource = public_path(ltrim($brandLogoSource, '/'));
    }

    $brandLogoWidth = $width ?? '15%';
    $brandLogoStyle = $style ?? null;
    $brandLogoInitials = collect(preg_split('/\s+/', trim((string) $brandLogoAlt)) ?: [])
        ->filter()
        ->take(2)
        ->map(static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

@if($brandLogoSource)
    <img src="{{ $brandLogoSource }}" width="{{ $brandLogoWidth }}" @if($brandLogoStyle) style="{{ $brandLogoStyle }}" @endif alt="{{ $brandLogoAlt }}">
@else
    <span style="display: inline-block; min-width: 36px; padding: 9px 8px; border-radius: 6px; background: {{ $documentPrimaryColor ?? '#143d37' }}; color: #ffffff; font-size: 12px; font-weight: 800; line-height: 1; text-align: center; {{ $brandLogoStyle }}">{{ $brandLogoInitials ?: 'LS' }}</span>
@endif
