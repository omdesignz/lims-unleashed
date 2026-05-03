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
        }
        
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
            {{-- Position QR code if set --}}
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
            {{-- Position barcode if set --}}
            @if($label->barcode_position)
                top: {{ $label->barcode_position['top'] ?? 0 }}mm;
                left: {{ $label->barcode_position['left'] ?? 0 }}mm;
                width: {{ $label->barcode_width ?? 30 }}mm;
                height: {{ $label->barcode_height ?? 10 }}mm;
            @endif
        }
        
        .logo {
            position: absolute;
            {{-- Position logo if set --}}
            @if($label->logo_position)
                top: {{ $label->logo_position['top'] ?? 0 }}mm;
                left: {{ $label->logo_position['left'] ?? 0 }}mm;
                width: {{ $label->logo_size ?? 15 }}mm;
                height: {{ $label->logo_size ?? 15 }}mm;
            @endif
        }
        
        .debug-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 6px;
            color: #999;
            text-align: center;
            padding: 1mm;
            border-top: 0.5px dashed #ccc;
            background-color: rgba(255,255,255,0.9);
        }
    </style>
</head>
<body>
    <div class="label-container">
        @if($label->has_qr_code)
        <div class="qr-code">
            <!-- QR Code placeholder - you'd use a QR code library in production -->
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
        
        <div class="debug-info">
            {{ $label->name }} | {{ $label->width }}x{{ $label->height }}mm | Preview
        </div>
    </div>
</body>
</html>