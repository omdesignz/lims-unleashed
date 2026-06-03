@php
    try {
        $brandSettings = $settings ?? app(\App\Settings\GeneralSettings::class);
        $brandLogoSource = $brandSettings->app_logo_url ?: public_path('images/SVG/sncqa_logo.png');
        $brandLogoAlt = $alt ?? ($brandSettings->app_name ?: config('app.name', 'LIMS Unleashed'));
    } catch (Throwable) {
        $brandLogoSource = public_path('images/SVG/sncqa_logo.png');
        $brandLogoAlt = $alt ?? config('app.name', 'LIMS Unleashed');
    }

    if (is_string($brandLogoSource) && str_starts_with($brandLogoSource, '/storage/')) {
        $brandLogoSource = public_path(ltrim($brandLogoSource, '/'));
    }

    $brandLogoWidth = $width ?? '15%';
    $brandLogoStyle = $style ?? null;
@endphp

<img src="{{ $brandLogoSource }}" width="{{ $brandLogoWidth }}" @if($brandLogoStyle) style="{{ $brandLogoStyle }}" @endif alt="{{ $brandLogoAlt }}">
