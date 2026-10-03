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
        <meta name="theme-color" content="#061f46">
        <meta name="robots" content="noindex">
        <title>@yield('title') · {{ $errorAppName }}</title>
        <link rel="icon" type="image/svg+xml" href="/brand/favicon.svg">
        {{-- Self-contained on purpose: an error page must render even when the asset build is unavailable. --}}
        <style>
            @font-face { font-family: "Inter"; src: url("/brand/fonts/inter-latin-400-normal.woff2") format("woff2"); font-weight: 400; font-display: swap; }
            @font-face { font-family: "Inter"; src: url("/brand/fonts/inter-latin-500-normal.woff2") format("woff2"); font-weight: 500 700; font-display: swap; }
            @font-face { font-family: "JetBrains Mono"; src: url("/brand/fonts/jetbrains-mono-latin-400-normal.woff2") format("woff2"); font-weight: 400; font-display: swap; }
            :root { --canvas: #e6e9ed; --panel: #ffffff; --border: #e6e9ed; --border-strong: #d7dbe0; --text: #111827; --muted: #4b5563; --soft: #6b7482; --action: #0757b5; --mark-light: block; --mark-dark: none; }
            @media (prefers-color-scheme: dark) {
                :root { --canvas: #07090d; --panel: #161b23; --border: #262d3a; --border-strong: #384252; --text: #f3f5f7; --muted: #b4bcc8; --soft: #8b95a3; --action: #70b5ff; --mark-light: none; --mark-dark: block; }
            }
            *, *::before, *::after { box-sizing: border-box; }
            html { font-family: "Inter", "Segoe UI", Arial, sans-serif; font-synthesis: none; -webkit-font-smoothing: antialiased; }
            body { display: flex; min-height: 100vh; min-height: 100dvh; flex-direction: column; margin: 0; background: var(--canvas); color: var(--text); }
            .bar { display: flex; width: 100%; max-width: 66rem; align-items: center; gap: 0.75rem; margin: 0 auto; padding: 1.25rem 1.5rem; }
            .bar img { width: 54px; height: auto; }
            .bar .light { display: var(--mark-light); }
            .bar .dark { display: var(--mark-dark); }
            .bar span.rule { width: 1px; height: 1.25rem; background: var(--border-strong); }
            .bar strong { font-size: 0.9375rem; font-weight: 500; letter-spacing: -0.015em; }
            main { display: flex; flex: 1; align-items: center; padding: 0.5rem 1rem 3rem; }
            .sheet { width: 100%; max-width: 40rem; margin: 0 auto; border: 1px solid var(--border); border-radius: 0.875rem; background: var(--panel); padding: 3rem 2.5rem; box-shadow: 0 1px 2px rgb(6 31 70 / 0.04), 0 18px 40px -24px rgb(6 31 70 / 0.22); }
            .code { margin: 0; color: var(--soft); font-family: "JetBrains Mono", ui-monospace, monospace; font-size: 0.8125rem; }
            h1 { margin: 0.75rem 0 0; font-size: 1.875rem; font-weight: 500; letter-spacing: -0.03em; line-height: 1.15; text-wrap: balance; }
            .copy { margin: 1rem 0 0; color: var(--muted); font-size: 0.9375rem; line-height: 1.75; }
            .actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 2rem; }
            .button { display: inline-flex; min-height: 2.25rem; align-items: center; border: 1px solid var(--border-strong); border-radius: 0.375rem; background: var(--panel); padding: 0 0.8125rem; color: var(--text); font: 500 0.8125rem/1.25rem "Inter", "Segoe UI", Arial, sans-serif; text-decoration: none; cursor: pointer; transition: scale 150ms cubic-bezier(0.23, 1, 0.32, 1); }
            .button:active { scale: 0.96; }
            .button:focus-visible { outline: 2px solid var(--action); outline-offset: 2px; }
            .button.primary { border-color: transparent; background: #0757b5; color: #ffffff; }
            @media (max-width: 639px) { main { align-items: stretch; padding: 0; } .sheet { border: 0; border-radius: 0; padding: 2.5rem 1.5rem; box-shadow: none; } }
            @media (prefers-reduced-motion: reduce) { .button { transition: none; } }
        </style>
    </head>
    <body data-error-page="vap">
        <header class="bar">
            <img class="light" src="/brand/svg/VAP_Master.svg" alt="VAP Sistemas" width="54" height="23">
            <img class="dark" src="/brand/svg/VAP_Master_White.svg" alt="VAP Sistemas" width="54" height="23">
            <span class="rule" aria-hidden="true"></span>
            <strong>{{ $errorAppName }}</strong>
        </header>
        <main>
            <section class="sheet">
                <p class="code">Erro @yield('code')</p>
                <h1>@yield('message')</h1>
                <p class="copy">@yield('description')</p>
                <div class="actions">
                    <a class="button primary" href="{{ url('/') }}">Ir para a página inicial</a>
                    <button class="button" type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.assign('{{ url('/') }}')">Voltar à página anterior</button>
                </div>
            </section>
        </main>
    </body>
</html>
