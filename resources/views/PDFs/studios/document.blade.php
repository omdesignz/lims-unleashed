<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0f172a;
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

        @if(! empty($backgroundImage))
        @page {
            background-image: url("{{ public_path($backgroundImage) }}");
            background-image-resize: 6;
        }
        @endif

        {!! $styles !!}
    </style>
</head>
<body>
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
