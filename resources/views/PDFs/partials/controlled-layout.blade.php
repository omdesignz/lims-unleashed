{{--
    The page of a controlled laboratory document drawn by mPDF.

    Expects:
      $documentTitle    what the document is, e.g. "Registo de Recepção de Amostra"
      $documentNumber   its unique number
      $controlRows      [[label, value], ...] lines of the control box under the title
      $issueDate        date of issue, d/m/Y
      $footerNotice     optional notice printed on every page
      $verification     optional text encoded as a QR code on the letterhead
      $landscape        optional, true for landscape pages

    The body goes in the "content" section, built from ControlledDocument blocks.
--}}
@php
    $controlledSettings = $settings ?? app(\App\Settings\GeneralSettings::class);
    $furnishings = \App\Support\ControlledDocument::furnishings(
        $controlledSettings,
        $documentTitle,
        (string) $documentNumber,
        $controlRows ?? [['N.º', (string) $documentNumber], ['Emissão', $issueDate]],
        $issueDate,
        $footerNotice ?? '',
        $verification ?? null,
        (string) ($documentRevision ?? '0'),
    );
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }} {{ $documentNumber }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @page {
            margin: 16mm 15mm 20mm 15mm;
            margin-header: 6mm;
            margin-footer: 8mm;
            header: doc-running;
            footer: doc-footer;
        }

        @page :first {
            margin-top: 40mm;
            header: doc-letterhead;
            footer: doc-footer;
        }

        @stack('document-styles')
    </style>
</head>
<body class="pdf-document">
    <htmlpageheader name="doc-letterhead">{!! $furnishings['letterhead'] !!}</htmlpageheader>
    <htmlpageheader name="doc-running">{!! $furnishings['running'] !!}</htmlpageheader>
    <htmlpagefooter name="doc-footer">{!! $furnishings['footer'] !!}</htmlpagefooter>

    @yield('content')
</body>
</html>
