<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        body {
            font-family: {!! $fontFamily ?? 'DejaVu Sans, sans-serif' !!};
            font-size: 11px;
            color: #0f172a;
        }

        .studio-canvas-page {
            min-height: {{ $canvasPageMinHeight ?? 253 }}mm;
        }

        .studio-canvas-page-1 {
            min-height: {{ $firstCanvasPageMinHeight ?? $canvasPageMinHeight ?? 215 }}mm;
        }

        @page {
            margin-top: {{ $margins['top'] }}mm;
            margin-right: {{ $margins['right'] }}mm;
            margin-bottom: {{ $margins['bottom'] }}mm;
            margin-left: {{ $margins['left'] }}mm;
            header: default-header;
            footer: default-footer;
        }

        @page :first {
            margin-top: {{ $margins['first_top'] }}mm;
            margin-right: {{ $margins['right'] }}mm;
            margin-bottom: {{ $margins['bottom'] }}mm;
            margin-left: {{ $margins['left'] }}mm;
            header: first-page-header;
            footer: default-footer;
        }

        @php
            $resolvedBackgroundImage = $resolvedBackgroundImage ?? null;

            if (! $resolvedBackgroundImage && ! empty($backgroundImage)) {
                $backgroundSource = trim((string) $backgroundImage);
                $backgroundScheme = parse_url($backgroundSource, PHP_URL_SCHEME);
                $backgroundPath = parse_url($backgroundSource, PHP_URL_PATH);
                $backgroundHost = parse_url($backgroundSource, PHP_URL_HOST);
                $allowedBackgroundHosts = array_filter([
                    parse_url((string) config('app.url'), PHP_URL_HOST),
                    request()->getHost(),
                ]);
                $backgroundPublicPath = is_string($backgroundPath) ? ltrim($backgroundPath, '/') : '';
                $isPublicAsset = str_starts_with($backgroundPublicPath, 'storage/')
                    || str_starts_with($backgroundPublicPath, 'images/');
                $isSameHostAsset = $isPublicAsset
                    && (
                        ! is_string($backgroundHost)
                        || $backgroundHost === ''
                        || in_array($backgroundHost, $allowedBackgroundHosts, true)
                    );
                $isDataImageBackground = str_starts_with($backgroundSource, 'data:image/');
                $isUnsafeBackgroundScheme = is_string($backgroundScheme)
                    && $backgroundScheme !== ''
                    && ! in_array(strtolower($backgroundScheme), ['http', 'https', 'file', 'data'], true);
                $isSafeExternalBackground = filter_var($backgroundSource, FILTER_VALIDATE_URL)
                    && is_string($backgroundScheme)
                    && in_array(strtolower($backgroundScheme), ['http', 'https', 'file'], true);

                if ($isDataImageBackground) {
                    $resolvedBackgroundImage = $backgroundSource;
                } elseif ($isUnsafeBackgroundScheme || strtolower((string) $backgroundScheme) === 'data') {
                    $resolvedBackgroundImage = null;
                } elseif (is_file($backgroundSource)) {
                    $resolvedBackgroundImage = $backgroundSource;
                } elseif ($isSameHostAsset) {
                    $resolvedBackgroundImage = public_path($backgroundPublicPath);
                } elseif ($isSafeExternalBackground) {
                    $resolvedBackgroundImage = $backgroundSource;
                } else {
                    $resolvedBackgroundImage = public_path(ltrim($backgroundSource, '/'));
                }
            }

            $resolvedBackgroundImageCssUrl = $resolvedBackgroundImage
                ? \App\Support\ReportStudioCssEscaper::quotedString((string) $resolvedBackgroundImage)
                : null;
        @endphp

        @if($resolvedBackgroundImageCssUrl)
        @page {
            background-image: url("{!! $resolvedBackgroundImageCssUrl !!}");
            background-image-resize: 6;
            background-position: {{ $backgroundPosition ?? 'center center' }};
            background-repeat: {{ $backgroundRepeat ?? 'no-repeat' }};
            background-size: {{ $backgroundSize ?? 'cover' }};
        }
        @endif

        {!! $styles !!}
    </style>
</head>
<body class="pdf-document studio-document">
    <sethtmlpageheader name="first-page-header" value="on" show-this-page="1" />
    <sethtmlpageheader name="default-header" value="on" show-this-page="1" />
    <sethtmlpagefooter name="default-footer" value="on" show-this-page="1" />

    <htmlpageheader name="first-page-header">
        {!! $firstPageHeader !!}
    </htmlpageheader>

    <htmlpageheader name="default-header">
        {!! $defaultHeader !!}
    </htmlpageheader>

    <htmlpagefooter name="default-footer">
        {!! $footerHtml !!}
    </htmlpagefooter>

    {!! $bodyHtml !!}
</body>
</html>
