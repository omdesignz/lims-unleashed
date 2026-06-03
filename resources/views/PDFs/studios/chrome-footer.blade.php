<style>
    html,
    body {
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .chrome-pdf-footer {
        width: 100%;
        padding: 0 {{ $marginRight ?? 14 }}mm 0 {{ $marginLeft ?? 14 }}mm;
        font-family: {!! $fontFamily ?? 'Arial, DejaVu Sans, sans-serif' !!};
        font-size: 8px;
        color: #475569;
    }
</style>

<div class="chrome-pdf-footer">
    {!! $footerHtml !!}
</div>
