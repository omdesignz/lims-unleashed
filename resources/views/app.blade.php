<!DOCTYPE html>
<html class="h-full bg-white dark:bg-gray-800">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta name="passkeys-authentication-options-url" content="{{ route('passkeys.authentication_options') }}">
    <meta name="passkeys-login-url" content="{{ route('passkeys.login') }}">
    <link rel="icon" type="image/png" href="/images/SVG/sncqa_logo.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=titillium-web:400,400i,600,600i,700,700i" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full">
@inertia
</body>
</html>
