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
        }
        
        .label-container {
            position: relative;
            width: {{ $label->width }}mm;
            height: {{ $label->height }}mm;
            background-color: {{ $label->background_color }};
            border: {{ $label->border_width }}px solid {{ $label->border_color }};
            overflow: hidden;
            page-break-inside: avoid;
            @if($include_cutouts)
                margin: {{ $margin }}mm;
            @endif
        }
        
        @if($include_cutouts)
        /* Cutout marks */
        .cutout {
            position: absolute;
            background-color: #000;
        }
        
        .cutout-top-left {
            top: -{{ $margin }}mm;
            left: -{{ $margin }}mm;
            width: 3mm;
            height: 1mm;
        }
        
        .cutout-top-right {
            top: -{{ $margin }}mm;
            right: -{{ $margin }}mm;
            width: 3mm;
            height: 1mm;
        }
        
        .cutout-bottom-left {
            bottom: -{{ $margin }}mm;
            left: -{{ $margin }}mm;
            width: 3mm;
            height: 1mm;
        }
        
        .cutout-bottom-right {
            bottom: -{{ $margin }}mm;
            right: -{{ $margin }}mm;
            width: 3mm;
            height: 1mm;
        }
        
        /* Dashed outline for cutting */
        .cutting-line {
            position: absolute;
            border: 0.3px dashed #666;
            pointer-events: none;
        }
        
        .cutting-line-top {
            top: -{{ $margin }}mm;
            left: -{{ $margin }}mm;
            right: -{{ $margin }}mm;
            height: 0;
        }
        
        .cutting-line-bottom {
            bottom: -{{ $margin }}mm;
            left: -{{ $margin }}mm;
            right: -{{ $margin }}mm;
            height: 0;
        }
        
        .cutting-line-left {
            top: -{{ $margin }}mm;
            left: -{{ $margin }}mm;
            bottom: -{{ $margin }}mm;
            width: 0;
        }
        
        .cutting-line-right {
            top: -{{ $margin }}mm;
            right: -{{ $margin }}mm;
            bottom: -{{ $margin }}mm;
            width: 0;
        }
        
        /* Crop marks */
        .crop-mark {
            position: absolute;
            width: 5mm;
            height: 1mm;
            background-color: #000;
        }
        
        .crop-mark-tl {
            top: -{{ $margin }}mm;
            left: -{{ $margin }}mm;
        }
        
        .crop-mark-tr {
            top: -{{ $margin }}mm;
            right: -{{ $margin }}mm;
        }
        
        .crop-mark-bl {
            bottom: -{{ $margin }}mm;
            left: -{{ $margin }}mm;
        }
        
        .crop-mark-br {
            bottom: -{{ $margin }}mm;
            right: -{{ $margin }}mm;
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
    </style>
</head>
<body>
    <div style="position: relative; display: inline-block;">
        @if($include_cutouts)
        <!-- Crop marks -->
        <div class="crop-mark crop-mark-tl"></div>
        <div class="crop-mark crop-mark-tr"></div>
        <div class="crop-mark crop-mark-bl"></div>
        <div class="crop-mark crop-mark-br"></div>
        
        <!-- Cutting lines -->
        <div class="cutting-line cutting-line-top"></div>
        <div class="cutting-line cutting-line-bottom"></div>
        <div class="cutting-line cutting-line-left"></div>
        <div class="cutting-line cutting-line-right"></div>
        @endif
        
        <div class="label-container">
            @if($label->has_qr_code && $qr_content && $qr_code_image)
            <div class="qr-code">
                <img src="{{ $qr_code_image }}" alt="QR Code" style="width: 100%; height: 100%;">
            </div>
            @endif
            
            @if($label->has_barcode && $barcode_content)
            <div class="barcode">
                <!-- Barcode generation would go here -->
                <div style="border: 1px solid #000; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 8px;">
                    {{ $barcode_content }}
                </div>
            </div>
            @endif
            
            @if($label->logo_path)
            <div class="logo">
                <!-- Logo display would go here -->
                <div style="border: 1px dashed #ccc; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 8px;">
                    LOGO
                </div>
            </div>
            @endif
            
            <div class="content">
                {{ $content }}
            </div>
        </div>
    </div>
</body>
</html>