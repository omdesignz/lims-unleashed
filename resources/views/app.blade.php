@php
    try {
        $whiteLabelSettings = app(\App\Settings\GeneralSettings::class);
        $whiteLabelAppName = $whiteLabelSettings->app_name ?: config('app.name', 'LIMS Unleashed');
        $whiteLabelLogoUrl = $whiteLabelSettings->app_logo_url ?: null;
        $whiteLabelPrimaryColor = $whiteLabelSettings->app_primary_color ?: '#061f46';
    } catch (\Throwable) {
        $whiteLabelAppName = config('app.name', 'LIMS Unleashed');
        $whiteLabelLogoUrl = null;
        $whiteLabelPrimaryColor = '#061f46';
    }
@endphp
<!DOCTYPE html>
<html class="h-full">
<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta name="application-name" content="{{ $whiteLabelAppName }}">
    <meta name="theme-color" content="{{ $whiteLabelPrimaryColor }}">
    <meta name="passkeys-authentication-options-url" content="{{ route('passkeys.authentication_options') }}">
    <meta name="passkeys-login-url" content="{{ route('passkeys.login') }}">
    <title>{{ $whiteLabelAppName }}</title>
    @if($whiteLabelLogoUrl)
        <link rel="icon" href="{{ $whiteLabelLogoUrl }}">
    @else
        <link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
        <link rel="icon" type="image/png" sizes="32x32" href="/brand/icons/favicon-32.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/brand/icons/apple-touch-icon.png">
    @endif
    <script>
        (function () {
            try {
                // Plano is light by default; dark is an explicit, remembered choice.
                var theme = window.localStorage.getItem('theme');
                var dark = theme === 'dark';

                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            } catch (error) {
                // Theme boot should never block the application shell.
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full">
@inertia
</body>
</html>
