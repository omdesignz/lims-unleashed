@php
    try {
        $errorAppName = app(\App\Settings\GeneralSettings::class)->app_name ?: config('app.name', 'LIMS Unleashed');
    } catch (\Throwable) {
        $errorAppName = config('app.name', 'LIMS Unleashed');
    }
@endphp
<!DOCTYPE html>
<html lang="pt-AO">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#087cf0">
        <meta name="robots" content="noindex">
        <title>@yield('title') · {{ $errorAppName }}</title>
        <link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
        {{-- Self-contained on purpose: an error page must render even when the asset build is unavailable.
             Plano: a flat VAP Blue band with an ink panel cut into it. --}}
        <style>
            @font-face { font-family: "TASA Orbiter"; src: url("/brand/fonts/tasa-orbiter-latin-wght-normal.woff2") format("woff2-variations"); font-weight: 400 800; font-display: swap; }
            @font-face { font-family: "Geist Mono"; src: url("/brand/fonts/geist-mono-latin-wght-normal.woff2") format("woff2-variations"); font-weight: 400 700; font-display: swap; }
            :root { --band: #087cf0; --band-ink: #04101f; --bg: #070f1c; --line: #1a2a43; --fg: #edf2f8; --muted: #8c9cb3; --accent: #087cf0; --accent-text: #70b5ff; --mono: "Geist Mono", ui-monospace, monospace; }
            *, *::before, *::after { box-sizing: border-box; margin: 0; border-radius: 0; }
            html { font-family: "TASA Orbiter", "Segoe UI", Arial, sans-serif; font-synthesis: none; -webkit-font-smoothing: antialiased; }
            body { display: flex; min-height: 100vh; min-height: 100dvh; flex-direction: column; background: var(--band); color: var(--band-ink); }
            .k { font: 500 11px/1.2 var(--mono); letter-spacing: 0.1em; text-transform: uppercase; }
            .bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 28px 100px 0; }
            .bar img { height: 32px; width: auto; }
            main { display: flex; flex: 1; align-items: flex-start; padding: 32px 100px 56px; }
            .sheet { width: 100%; max-width: 720px; background: var(--bg); color: var(--fg); padding: 64px 66px; }
            .sheet .k { color: var(--muted); }
            .row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 36px; }
            h1 { margin-top: 0; font-size: 52px; font-weight: 700; letter-spacing: -0.05em; line-height: 1.02; text-wrap: balance; }
            h1 .code { display: block; color: var(--accent-text); }
            .copy { margin-top: 18px; max-width: 52ch; color: var(--muted); font-size: 15px; line-height: 1.6; }
            .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 36px; }
            .button { display: inline-flex; min-height: 48px; align-items: center; justify-content: space-between; gap: 24px; border: 1px solid var(--fg); background: transparent; padding: 0 18px; color: var(--fg); font: 500 11px/1 var(--mono); letter-spacing: 0.1em; text-transform: uppercase; text-decoration: none; cursor: pointer; transition: background-color 120ms cubic-bezier(0.23, 1, 0.32, 1), color 120ms cubic-bezier(0.23, 1, 0.32, 1), scale 160ms cubic-bezier(0.23, 1, 0.32, 1); }
            .button:hover { background: var(--fg); color: var(--bg); }
            .button:active { scale: 0.97; }
            .button:focus-visible { outline: 2px solid var(--accent-text); outline-offset: 2px; }
            .button.primary { border-color: var(--accent); background: var(--accent); color: var(--band-ink); }
            .button.primary:hover { border-color: var(--accent-text); background: var(--accent-text); }
            @media (max-width: 1023px) { .bar { padding: 20px 24px 0; } main { padding: 24px; } }
            @media (max-width: 639px) { .bar { padding: 16px 16px 0; } main { padding: 16px 0 0; } .sheet { padding: 36px 20px 40px; } h1 { font-size: 38px; } }
            @media (prefers-reduced-motion: reduce) { .button { transition: none; } }
        </style>
    </head>
    <body data-error-page="vap">
        <header class="bar">
            <img src="/brand/svg/VAP_Small_Black.svg" alt="VAP Sistemas" width="76" height="32">
            <span class="k">LIMS · {{ $errorAppName }}</span>
        </header>
        <main>
            <section class="sheet">
                <div class="row"><span class="k">Erro @yield('code')</span><span class="k">{{ $errorAppName }}</span></div>
                <h1><span class="code">@yield('code').</span>@yield('message')</h1>
                <p class="copy">@yield('description')</p>
                <div class="actions">
                    <a class="button primary" href="{{ url('/') }}">Ir para o início <span aria-hidden="true">›</span></a>
                    <button class="button" type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.assign('{{ url('/') }}')">Voltar à página anterior</button>
                </div>
            </section>
        </main>
    </body>
</html>
