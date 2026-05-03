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
        
        .labels-grid {
            display: grid;
            grid-template-columns: repeat({{ $columns }}, 1fr);
            grid-template-rows: repeat({{ $rows }}, 1fr);
            gap: {{ $spacing }}mm;
            width: 100%;
            height: 100%;
        }
        
        .label-item {
            position: relative;
            width: {{ $label->width }}mm;
            height: {{ $label->height }}mm;
            background-color: {{ $label->background_color }};
            border: {{ $label->border_width }}px solid {{ $label->border_color }};
            overflow: hidden;
            page-break-inside: avoid;
        }
        
        @if($include_cutouts)
        /* Perforation lines between labels */
        .perforation-line-horizontal {
            position: absolute;
            left: 0;
            right: 0;
            border-top: 1px dashed #999;
            height: 0;
        }
        
        .perforation-line-vertical {
            position: absolute;
            top: 0;
            bottom: 0;
            border-left: 1px dashed #999;
            width: 0;
        }
        
        /* Cut marks for individual labels */
        .cut-mark {
            position: absolute;
            background-color: #000;
            width: 2mm;
            height: 0.5mm;
        }
        
        .cut-mark-tl {
            top: -{{ $spacing/2 }}mm;
            left: -{{ $spacing/2 }}mm;
        }
        
        .cut-mark-tr {
            top: -{{ $spacing/2 }}mm;
            right: -{{ $spacing/2 }}mm;
        }
        
        .cut-mark-bl {
            bottom: -{{ $spacing/2 }}mm;
            left: -{{ $spacing/2 }}mm;
        }
        
        .cut-mark-br {
            bottom: -{{ $spacing/2 }}mm;
            right: -{{ $spacing/2 }}mm;
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
    <div class="labels-grid">
        @foreach($data as $index => $item)
            <div class="label-item" style="position: relative;">
                @if($include_cutouts && $loop->iteration <= $columns * $rows)
                <!-- Cut marks -->
                <div class="cut-mark cut-mark-tl"></div>
                <div class="cut-mark cut-mark-tr"></div>
                <div class="cut-mark cut-mark-bl"></div>
                <div class="cut-mark cut-mark-br"></div>
                @endif
                
                @if($label->has_qr_code && !empty($item['qr_content']))
                <div class="qr-code">
                    <!-- QR code would be generated here -->
                    <div style="border: 1px solid #000; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 6px;">
                        QR
                    </div>
                </div>
                @endif
                
                @if($label->has_barcode && !empty($item['barcode_content']))
                <div class="barcode">
                    <div style="border: 1px solid #000; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 6px;">
                        {{ substr($item['barcode_content'], 0, 10) }}
                    </div>
                </div>
                @endif
                
                @if($label->logo_path)
                <div class="logo">
                    <div style="border: 1px dashed #ccc; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 6px;">
                        LOGO
                    </div>
                </div>
                @endif
                
                <div class="content">
                    {{ $item['content'] }}
                </div>
            </div>
        @endforeach
    </div>
    
    @if($include_cutouts)
    <!-- Perforation lines -->
    <style>
        @for($i = 1; $i < $columns; $i++)
            .perforation-line-vertical-{{ $i }} {
                left: calc((100% / {{ $columns }}) * {{ $i }} - ({{ $spacing }}mm / 2));
            }
        @endfor
        
        @for($i = 1; $i < $rows; $i++)
            .perforation-line-horizontal-{{ $i }} {
                top: calc((100% / {{ $rows }}) * {{ $i }} - ({{ $spacing }}mm / 2));
            }
        @endfor
    </style>
    
    @for($i = 1; $i < $columns; $i++)
        <div class="perforation-line-vertical perforation-line-vertical-{{ $i }}"></div>
    @endfor
    
    @for($i = 1; $i < $rows; $i++)
        <div class="perforation-line-horizontal perforation-line-horizontal-{{ $i }}"></div>
    @endfor
    @endif
</body>
</html>