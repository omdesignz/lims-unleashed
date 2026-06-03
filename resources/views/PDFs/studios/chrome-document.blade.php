<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: {!! $fontFamily ?? 'Arial, DejaVu Sans, sans-serif' !!};
            font-size: 11px;
            color: #0f172a;
            margin: 0;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        img, svg, canvas {
            max-width: 100%;
        }

        table {
            break-inside: auto;
        }

        tr, img, svg, canvas, .studio-avoid-break, .apexcharts-canvas, .report-chart {
            break-inside: avoid;
        }

        .studio-first-page-header {
            margin-top: {{ $browserFirstPageTopOffset ?? 0 }}mm;
            margin-bottom: 18px;
        }

        .studio-first-page-shell {
            display: flex;
            min-height: {{ $canvasPageMinHeight ?? 253 }}mm;
            flex-direction: column;
        }

        .studio-first-page-content {
            display: flex;
            min-height: 0;
            flex: 1;
            flex-direction: column;
        }

        .studio-first-page-content > .studio-canvas-page-1 {
            min-height: 0;
            flex: 1;
        }

        .studio-canvas-page {
            min-height: {{ $canvasPageMinHeight ?? 253 }}mm;
        }

        .studio-canvas-page-1 {
            min-height: {{ $firstCanvasPageMinHeight ?? $canvasPageMinHeight ?? 215 }}mm;
        }

        .studio-page-break {
            break-before: page;
            page-break-before: always;
            height: 0;
            overflow: hidden;
        }

        .apexcharts-canvas,
        .apexcharts-svg,
        .report-chart,
        .report-chart svg,
        .report-chart img {
            display: block;
            max-width: 100%;
        }

        @if($resolvedBackgroundImage ?? null)
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            background-image: url("{{ $resolvedBackgroundImage }}");
            background-position: {{ $backgroundPosition ?? 'center center' }};
            background-repeat: {{ $backgroundRepeat ?? 'no-repeat' }};
            background-size: {{ $backgroundSize ?? 'cover' }};
        }
        @endif

        {!! $styles !!}
    </style>
</head>
@php
    $browserBodyPages = preg_split('/<pagebreak\b[^>]*\/?>/i', $bodyHtml ?? '') ?: [''];
    $browserFirstBodyHtml = array_shift($browserBodyPages) ?? '';
@endphp
<body class="pdf-document studio-document">
    @if(! empty($firstPageHeader))
        <section class="studio-first-page-shell">
            <header class="studio-first-page-header">
                {!! $firstPageHeader !!}
            </header>
            <div class="studio-first-page-content">
                {!! $browserFirstBodyHtml !!}
            </div>
        </section>
    @else
        {!! $browserFirstBodyHtml !!}
    @endif

    @foreach($browserBodyPages as $browserBodyPage)
        <div class="studio-page-break"></div>
        {!! $browserBodyPage !!}
    @endforeach
</body>
</html>
