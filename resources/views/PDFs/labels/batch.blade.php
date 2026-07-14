<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: {{ $label->text_color }};
            background: transparent;
            font-family: DejaVu Sans, sans-serif;
            font-size: {{ $label->font_size }}px;
        }

        .labels-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: {{ $spacing }}mm;
            table-layout: fixed;
        }

        .label-cell {
            width: {{ 100 / max($columns, 1) }}%;
            padding: 0;
            vertical-align: top;
        }

        .label-item {
            position: relative;
            width: {{ $label->width }}mm;
            height: {{ $label->height }}mm;
            overflow: hidden;
            border: {{ $label->border_width }}px solid {{ $label->border_color }};
            background: {{ $label->background_color }};
            page-break-inside: avoid;
        }

        .content {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            padding: 2mm;
            text-align: {{ $label->text_alignment }};
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .qr-code {
            position: absolute;
            top: {{ $label->qr_code_position['top'] ?? 0 }}mm;
            left: {{ $label->qr_code_position['left'] ?? 0 }}mm;
            width: {{ $label->qr_code_size ?? 10 }}mm;
            height: {{ $label->qr_code_size ?? 10 }}mm;
        }

        .barcode {
            position: absolute;
            top: {{ $label->barcode_position['top'] ?? 0 }}mm;
            left: {{ $label->barcode_position['left'] ?? 0 }}mm;
            width: {{ $label->barcode_width ?? 30 }}mm;
            height: {{ $label->barcode_height ?? 10 }}mm;
            overflow: hidden;
            text-align: center;
            font-size: 6px;
        }

        .logo {
            position: absolute;
            top: {{ $label->logo_position['top'] ?? 0 }}mm;
            left: {{ $label->logo_position['left'] ?? 0 }}mm;
            width: {{ $label->logo_size ?? 15 }}mm;
            height: {{ $label->logo_size ?? 15 }}mm;
        }

        .qr-code img,
        .barcode img,
        .logo img {
            width: 100%;
            height: 100%;
        }

        .barcode img,
        .logo img {
            object-fit: contain;
        }

        @if($include_cutouts)
        .cut-mark {
            position: absolute;
            z-index: 5;
            width: 2mm;
            height: 0.3mm;
            background: #111827;
        }

        .cut-mark-tl { top: 0; left: 0; }
        .cut-mark-tr { top: 0; right: 0; }
        .cut-mark-bl { bottom: 0; left: 0; }
        .cut-mark-br { right: 0; bottom: 0; }
        @endif
    </style>
</head>
<body>
    @foreach(array_chunk($data, max($columns * $rows, 1)) as $page)
        <table class="labels-grid">
            <tbody>
                @foreach(array_chunk($page, max($columns, 1)) as $row)
                    <tr>
                        @foreach($row as $item)
                            <td class="label-cell">
                                <div class="label-item">
                                    @if($include_cutouts)
                                        <div class="cut-mark cut-mark-tl"></div>
                                        <div class="cut-mark cut-mark-tr"></div>
                                        <div class="cut-mark cut-mark-bl"></div>
                                        <div class="cut-mark cut-mark-br"></div>
                                    @endif

                                    @if($label->has_qr_code && !empty($item['qr_code_image']))
                                        <div class="qr-code">
                                            <img src="{{ $item['qr_code_image'] }}" alt="Código QR">
                                        </div>
                                    @endif

                                    @if($label->has_barcode && !empty($item['barcode_content']))
                                        <div class="barcode">
                                            @if(!empty($item['barcode_image']))
                                                <img src="{{ $item['barcode_image'] }}" alt="{{ $item['barcode_content'] }}">
                                            @else
                                                {{ $item['barcode_content'] }}
                                            @endif
                                        </div>
                                    @endif

                                    @if($logo_src)
                                        <div class="logo">
                                            <img src="{{ $logo_src }}" alt="Logótipo do laboratório">
                                        </div>
                                    @endif

                                    <div class="content">{{ $item['content'] }}</div>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(!$loop->last)
            <pagebreak />
        @endif
    @endforeach
</body>
</html>
