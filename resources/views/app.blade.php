@php
    try {
        $whiteLabelSettings = app(\App\Settings\GeneralSettings::class);
        $whiteLabelAppName = $whiteLabelSettings->app_name ?: config('app.name', 'LIMS Unleashed');
        $whiteLabelLogoUrl = $whiteLabelSettings->app_logo_url ?: '/images/SVG/sncqa_logo.png';
        $whiteLabelPrimaryColor = $whiteLabelSettings->app_primary_color ?: '#143d37';
    } catch (Throwable) {
        $whiteLabelAppName = config('app.name', 'LIMS Unleashed');
        $whiteLabelLogoUrl = '/images/SVG/sncqa_logo.png';
        $whiteLabelPrimaryColor = '#143d37';
    }
@endphp
<!DOCTYPE html>
<html class="h-full bg-white dark:bg-gray-800">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta name="application-name" content="{{ $whiteLabelAppName }}">
    <meta name="theme-color" content="{{ $whiteLabelPrimaryColor }}">
    <meta name="passkeys-authentication-options-url" content="{{ route('passkeys.authentication_options') }}">
    <meta name="passkeys-login-url" content="{{ route('passkeys.login') }}">
    <title>{{ $whiteLabelAppName }}</title>
    <link rel="icon" type="image/png" href="{{ $whiteLabelLogoUrl }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <script>
        (function () {
            try {
                var theme = window.localStorage.getItem('theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (theme === 'dark' || (! theme && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (error) {
                // Theme boot should never block the application shell.
            }
        })();
    </script>
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full">
@inertia
</body>
</html>
