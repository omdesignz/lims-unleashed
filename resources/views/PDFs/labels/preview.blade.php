<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: {{ $label->font_size }}px;
            color: {{ $label->text_color }};
            background-color: transparent;
            position: relative;
        }
        
        .page-container {
            position: relative;
            width: {{ $label->width + 10 }}mm;
            height: {{ $label->height + 10 }}mm;
            padding: 5mm;
        }
        
        .label-container {
            position: relative;
            width: {{ $label->width }}mm;
            height: {{ $label->height }}mm;
            background-color: {{ $label->background_color }};
            border: {{ $label->border_width }}px solid {{ $label->border_color }};
            overflow: hidden;
            page-break-inside: avoid;
            margin: 0 auto;
        }
        
        @if($show_cutouts)
        /* Cutout marks */
        .cutout {
            position: absolute;
            background-color: #000;
        }
        
        .cutout-top-left {
            top: 0;
            left: 0;
            width: 3mm;
            height: 1mm;
        }
        
        .cutout-top-right {
            top: 0;
            right: 0;
            width: 3mm;
            height: 1mm;
        }
        
        .cutout-bottom-left {
            bottom: 0;
            left: 0;
            width: 3mm;
            height: 1mm;
        }
        
        .cutout-bottom-right {
            bottom: 0;
            right: 0;
            width: 3mm;
            height: 1mm;
        }
        
        /* Dashed outline for cutting */
        .cutting-line {
            position: absolute;
            border: 0.5px dashed #999;
            pointer-events: none;
        }
        
        .cutting-line-top {
            top: -1mm;
            left: 0;
            right: 0;
            height: 0;
        }
        
        .cutting-line-bottom {
            bottom: -1mm;
            left: 0;
            right: 0;
            height: 0;
        }
        
        .cutting-line-left {
            top: 0;
            left: -1mm;
            bottom: 0;
            width: 0;
        }
        
        .cutting-line-right {
            top: 0;
            right: -1mm;
            bottom: 0;
            width: 0;
        }
        @endif
        
        .content {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: {{ $label->text_alignment }};
            padding: 2mm;
            text-align: {{ $label->text_alignment }};
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .qr-code {
            position: absolute;
            width: {{ $label->qr_code_size ?? 10 }}mm;
            height: {{ $label->qr_code_size ?? 10 }}mm;
            @if($label->qr_code_position)
                top: {{ $label->qr_code_position['top'] ?? 0 }}mm;
                left: {{ $label->qr_code_position['left'] ?? 0 }}mm;
            @else
                top: 2mm;
                left: 2mm;
            @endif
        }
        
        .barcode {
            position: absolute;
            @if($label->barcode_position)
                top: {{ $label->barcode_position['top'] ?? 0 }}mm;
                left: {{ $label->barcode_position['left'] ?? 0 }}mm;
                width: {{ $label->barcode_width ?? 30 }}mm;
                height: {{ $label->barcode_height ?? 10 }}mm;
            @endif
        }
        
        .logo {
            position: absolute;
            @if($label->logo_position)
                top: {{ $label->logo_position['top'] ?? 0 }}mm;
                left: {{ $label->logo_position['left'] ?? 0 }}mm;
                width: {{ $label->logo_size ?? 15 }}mm;
                height: {{ $label->logo_size ?? 15 }}mm;
            @endif
        }
        
        .debug-info {
            position: absolute;
            bottom: -3mm;
            left: 0;
            right: 0;
            font-size: 6px;
            color: #999;
            text-align: center;
            padding: 1mm;
        }
    </style>
</head>
<body>
    <div class="page-container">
        @if($show_cutouts)
        <!-- Cutting lines -->
        <div class="cutting-line cutting-line-top"></div>
        <div class="cutting-line cutting-line-bottom"></div>
        <div class="cutting-line cutting-line-left"></div>
        <div class="cutting-line cutting-line-right"></div>
        
        <!-- Cutout marks -->
        <div class="cutout cutout-top-left"></div>
        <div class="cutout cutout-top-right"></div>
        <div class="cutout cutout-bottom-left"></div>
        <div class="cutout cutout-bottom-right"></div>
        @endif
        
        <div class="label-container">
            @if($label->has_qr_code)
            <div class="qr-code">
                <!-- QR Code placeholder -->
                <div style="border: 1px solid #000; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 8px;">
                    QR: {{ $sample_qr }}
                </div>
            </div>
            @endif
            
            @if($label->has_barcode)
            <div class="barcode">
                <!-- Barcode placeholder -->
                <div style="border: 1px solid #000; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 8px;">
                    BAR: {{ $sample_barcode }}
                </div>
            </div>
            @endif
            
            @if($label->logo_path)
            <div class="logo">
                <!-- Logo placeholder -->
                <div style="border: 1px dashed #ccc; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 8px;">
                    LOGO
                </div>
            </div>
            @endif
            
            <div class="content">
                {{ $sample_text }}
            </div>
        </div>
        
        <div class="debug-info">
            {{ $label->name }} | {{ $label->width }}x{{ $label->height }}mm | Pré-visualização
        </div>
    </div>
</body>
</html>