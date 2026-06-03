<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: {{ (float) $options['paper_width'] }}mm {{ (float) $options['paper_height'] }}mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background: #ffffff;
            color: {{ $label->text_color }};
            font-family: "DejaVu Sans", "Noto Sans", Arial, sans-serif;
            font-size: {{ (int) $label->font_size }}px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .label-sheet {
            display: grid;
            grid-template-columns: repeat({{ (int) $options['columns'] }}, {{ (float) $label->width }}mm);
            grid-auto-rows: {{ (float) $label->height }}mm;
            gap: {{ (float) $options['spacing'] }}mm;
            align-content: start;
            justify-content: start;
            min-height: {{ (float) $options['paper_height'] }}mm;
            padding: {{ (float) $options['margin'] }}mm;
        }

        .label-card {
            position: relative;
            width: {{ (float) $label->width }}mm;
            height: {{ (float) $label->height }}mm;
            overflow: hidden;
            break-inside: avoid;
            page-break-inside: avoid;
            border: {{ (int) $label->border_width }}px solid {{ $label->border_color }};
            background: {{ $label->background_color }};
        }

        .label-content {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: {{ $label->text_alignment }};
            padding: 2mm;
            text-align: {{ $label->text_alignment }};
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.18;
        }

        .label-logo,
        .label-qr,
        .label-barcode {
            position: absolute;
            z-index: 2;
        }

        .label-logo {
            @if($label->logo_position)
                top: {{ (float) ($label->logo_position['top'] ?? 0) }}mm;
                left: {{ (float) ($label->logo_position['left'] ?? 0) }}mm;
            @else
                top: 2mm;
                right: 2mm;
            @endif
            width: {{ (float) ($label->logo_size ?? 15) }}mm;
            height: {{ (float) ($label->logo_size ?? 15) }}mm;
        }

        .label-logo img,
        .label-qr img,
        .label-barcode img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .label-qr {
            @if($label->qr_code_position)
                top: {{ (float) ($label->qr_code_position['top'] ?? 0) }}mm;
                left: {{ (float) ($label->qr_code_position['left'] ?? 0) }}mm;
            @else
                top: 2mm;
                left: 2mm;
            @endif
            width: {{ (float) ($label->qr_code_size ?? 10) }}mm;
            height: {{ (float) ($label->qr_code_size ?? 10) }}mm;
        }

        .label-barcode {
            @if($label->barcode_position)
                top: {{ (float) ($label->barcode_position['top'] ?? 0) }}mm;
                left: {{ (float) ($label->barcode_position['left'] ?? 0) }}mm;
            @else
                right: 2mm;
                bottom: 2mm;
            @endif
            width: {{ (float) ($label->barcode_width ?? 30) }}mm;
            height: {{ (float) ($label->barcode_height ?? 10) }}mm;
        }

        .barcode-fallback {
            display: grid;
            width: 100%;
            height: 100%;
            place-items: center;
            border: 1px solid #0f172a;
            color: #0f172a;
            font-size: 7px;
            letter-spacing: .06em;
        }

        @if($options['include_cutouts'])
            .label-card::before {
                content: "";
                position: absolute;
                inset: 0;
                z-index: 4;
                border: .35mm dashed rgba(15, 23, 42, .55);
                pointer-events: none;
            }

            .cut-mark {
                position: absolute;
                z-index: 5;
                width: 3mm;
                height: .45mm;
                background: #0f172a;
            }

            .cut-mark-tl {
                top: 0;
                left: 0;
            }

            .cut-mark-tr {
                top: 0;
                right: 0;
            }

            .cut-mark-bl {
                bottom: 0;
                left: 0;
            }

            .cut-mark-br {
                right: 0;
                bottom: 0;
            }
        @endif

        .debug-label {
            position: absolute;
            right: 1mm;
            bottom: .7mm;
            left: 1mm;
            z-index: 6;
            color: #64748b;
            font-size: 6px;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="label-sheet">
        @foreach($items as $item)
            <article class="label-card">
                @if($options['include_cutouts'])
                    <span class="cut-mark cut-mark-tl"></span>
                    <span class="cut-mark cut-mark-tr"></span>
                    <span class="cut-mark cut-mark-bl"></span>
                    <span class="cut-mark cut-mark-br"></span>
                @endif

                @if($logoSrc)
                    <div class="label-logo">
                        <img src="{{ $logoSrc }}" alt="Logo">
                    </div>
                @endif

                @if($label->has_qr_code && $item['qr_code_image'])
                    <div class="label-qr">
                        <img src="{{ $item['qr_code_image'] }}" alt="QR Code">
                    </div>
                @endif

                @if($label->has_barcode && $item['barcode_content'])
                    <div class="label-barcode">
                        @if($item['barcode_image'])
                            <img src="{{ $item['barcode_image'] }}" alt="{{ $item['barcode_content'] }}">
                        @else
                            <div class="barcode-fallback">{{ $item['barcode_content'] }}</div>
                        @endif
                    </div>
                @endif

                <div class="label-content">{{ $item['content'] }}</div>

                @if(! empty($options['debug_label']))
                    <div class="debug-label">{{ $options['debug_label'] }}</div>
                @endif
            </article>
        @endforeach
    </main>
</body>
</html>
