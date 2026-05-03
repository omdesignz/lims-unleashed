<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        /* Base Styles - Aligned with Visual System */
        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #374151;
            line-height: 1.5;
            background-color: white;
        }
        
        /* Page Layout */
        @page {
            margin: 75mm 15mm 25mm 15mm;
            header: page-header;
            footer: page-footer;
            margin-header: 10mm;
            margin-footer: 10mm;
        }
        
        @page :first {
            margin-top: 75mm;
        }
        
        /* Visual System Classes */
        .h1 {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        
        .h2 {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 3px;
        }
        
        .h3 {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 3px;
        }
        
        .body-text {
            font-size: 11px;
            color: #374151;
            line-height: 1.4;
        }
        
        .small-text {
            font-size: 10px;
            color: #6b7280;
            line-height: 1.3;
        }
        
        .label {
            font-size: 11px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 2px;
        }
        
        .value {
            font-size: 12px;
            color: #111827;
            font-weight: 500;
        }
        
        .highlight-value {
            color: #1e3a8a;
            font-weight: 600;
        }
        
        .muted {
            color: #6b7280;
            font-style: italic;
        }
        
        /* Document Structure */
        .document-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1e3a8a;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            text-align: center;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            position: relative;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: linear-gradient(to right, #1e3a8a, #1e40af);
            border-radius: 2px;
        }
        
        .info-section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .section-header {
            background: linear-gradient(to right, #1e3a8a, #1e40af);
            color: white;
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .section-content {
            padding: 20px;
        }
        
        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .data-table th {
            background-color: #1e3a8a;
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 10px 8px;
            text-align: left;
            border: none;
        }
        
        .data-table td {
            font-size: 11px;
            color: #374151;
            padding: 10px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover td {
            background-color: #f9fafb;
        }
        
        /* Two Column Table Layout */
        .two-column-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .two-column-table td {
            vertical-align: top;
            padding: 0;
        }
        
        .column-cell {
            width: 50%;
        }
        
        .left-column {
            padding-right: 15px;
        }
        
        .right-column {
            padding-left: 15px;
            border-left: 1px solid #e5e7eb;
        }
        
        /* Info Row Component */
        .info-row {
            margin-bottom: 12px;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        /* Status Indicators */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            border: 1px solid;
        }
        
        .status-valid {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        
        /* Certificate Badge */
        .certificate-badge {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(to right, #1e3a8a, #1e40af);
            color: white;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            margin: 5px 0;
        }
        
        /* Legal Notice */
        .legal-notice {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            font-size: 9px;
            color: #6b7280;
            line-height: 1.4;
            border-left: 4px solid #1e3a8a;
        }
        
        /* Signature Area */
        .signature-area {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            position: relative;
        }
        
        .signature-line {
            border-bottom: 1px solid #374151;
            padding-bottom: 15px;
            margin-bottom: 5px;
            width: 200px;
        }
        
        /* Watermark */
        .watermark {
            position: fixed;
            left: 15mm;
            top: -75mm;
            opacity: 0.03;
            z-index: -1;
            pointer-events: none;
        }
        
        /* Page Break Control */
        .page-break {
            page-break-before: always;
        }
        
        /* QR Code Styling */
        .qr-container {
            text-align: center;
            padding: 10px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            display: inline-block;
        }
        
        /* Total Summary */
        .total-summary {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
            text-align: center;
        }
        
        .total-label {
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        
        .total-value {
            font-size: 14px;
            font-weight: 700;
            color: #1e3a8a;
        }
    </style>
</head>
<body>

<htmlpageheader name="page-header">
    <!-- Official Letterhead -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <!-- Logo and Institution Name -->
                    <tr>
                        <td align="center" style="padding-bottom: 10px;">
                            <img src="{!! public_path() . '/images/aocrest.svg' !!}" style="width: 60px; height: 72px;" alt="Logo">
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 4px;">
                            <div class="h3" style="color: #6b7280;">REPÚBLICA DE ANGOLA</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 4px;">
                            <div class="h2" style="color: #1e3a8a;">MINISTÉRIO DA AGRICULTURA E FLORESTAS</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 4px;">
                            <div class="h3">SERVIÇO NACIONAL DE CONTROLO DA QUALIDADE DOS ALIMENTOS</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <div class="body-text" style="font-weight: 500;">
                                LABORATÓRIO CENTRAL AGRO-ALIMENTAR DE LUANDA
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <!-- Certificate Header -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(to right, #1e3a8a, #1e40af); border-radius: 8px; margin-bottom: 15px;">
        <tr>
            <td style="padding: 15px; text-align: center;">
                <div class="h1" style="color: white; margin-bottom: 6px;">
                    CERTIFICADO FITOSSANITÁRIO PARA EXPORTAÇÃO
                </div>
                <div class="small-text" style="color: rgba(255,255,255,0.9); font-weight: 500;">
                    Exportation Phytosanitary Certificate
                </div>
            </td>
        </tr>
    </table>
    
    <!-- Certificate Number Badge -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <div class="certificate-badge">
                    CERTIFICADO Nº: {{ $model->cert_no }}
                </div>
            </td>
        </tr>
    </table>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 10px; margin-top: 15px; font-size: 8pt;">
        <tr>
            <td>
                <div class="small-text">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="50%">
                                <div class="qr-container">
                                    <barcode code="{!! url('/verify?id=' . $model->id . '&type=expcertificate' . '&doc_no=' . $model->cert_no) !!}" type="QR" size="0.5" error="M" />
                                </div>
                            </td>
                            <td width="50%" align="right" valign="bottom">
                                <div style="padding-top: 5px;">
                                    Página {PAGENO} de {nb}
                                </div>
                                <div style="margin-top: 3px;">
                                    Documento gerado: {!! Carbon\Carbon::now()->format('d/m/Y H:i') !!}
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</htmlpagefooter>

<!-- Watermark -->
{{-- <div class="watermark">
    <img src="{{ public_path() . '/img/logo.svg' }}" style="width: 120px; height: auto;" alt="Watermark">
</div> --}}

<!-- Main Content -->
<div style="width: 100%; max-width: 100%;">

    <!-- Origin and Destination Section -->
    <div class="section-title">Origem e Destino</div>
    
    <div class="info-section">
        <div class="section-header">
            Informações da Rota
        </div>
        <div class="section-content">
            <table class="two-column-table">
                <tr>
                    <!-- Left Column: Origin -->
                    <td class="column-cell left-column">
                        <div class="info-row">
                            <div class="label">Origem / Origin</div>
                            <div class="value highlight-value">
                                {{ $model->country_origin?->name }}
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="label">Cidade de Origem</div>
                            <div class="value">
                                {{ $model->city_origin }}
                            </div>
                            <div class="muted small-text">City of Origin</div>
                        </div>
                    </td>
                    
                    <!-- Right Column: Destination -->
                    <td class="column-cell right-column">
                        <div class="info-row">
                            <div class="label">Destino / Destination</div>
                            <div class="value highlight-value">
                                {{ $model->country_destination?->name }}
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="label">Cidade de Destino</div>
                            <div class="value">
                                {{ $model->city_destination }}
                            </div>
                            <div class="muted small-text">City of Destination</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Exporter Information Section -->
    <div class="section-title">Informações do Exportador</div>
    
    <div class="info-section">
        <div class="section-header">
            Dados do Exportador
        </div>
        <div class="section-content">
            <table class="two-column-table">
                <tr>
                    <!-- Left Column: Company Information -->
                    <td class="column-cell left-column">
                        <div class="info-row">
                            <div class="label">Nome da Empresa</div>
                            <div class="value highlight-value">
                                {{ $model->exporter->name }}
                            </div>
                            <div class="muted small-text">Company Name</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="label">NIF / Tax ID</div>
                            <div class="value">
                                {{ $model->exporter_warehouse->nif ?? 'N/A' }}
                            </div>
                        </div>
                    </td>
                    
                    <!-- Right Column: Address and Transport -->
                    <td class="column-cell right-column">
                        <div class="info-row">
                            <div class="label">Endereço</div>
                            <div class="value">
                                {{ $model->exporter_warehouse->address }}<br>
                                {{ $model->exporter_warehouse->municipality }}
                            </div>
                            <div class="muted small-text">Address</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="label">Tipo de Transporte</div>
                            <div class="value highlight-value">
                                {{ $model->trans_type->name }}
                            </div>
                            <div class="muted small-text">Transportation Type</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Products Section -->
    <div class="section-title">Produtos para Exportação</div>
    
    <div class="info-section">
        <div class="section-header">
            Detalhes dos Produtos
        </div>
        <div class="section-content">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produto / Product</th>
                        <th>Quantidade (kg/l)<br><span class="muted">Quantity</span></th>
                        <th>Observações<br><span class="muted">Observations</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($model->items as $item)
                    <tr>
                        <td>
                            <div class="value" style="font-weight: 600;">{{ $item->product->description }}</div>
                        </td>
                        <td style="text-align: center;">
                            <div class="value">{{ $item->qty }}</div>
                        </td>
                        <td>
                            <div class="value">{{ $item->obs }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Total Summary -->
            <div class="total-summary">
                <div class="total-label">TOTAL GERAL (kg/l)</div>
                <div class="total-value">{{ number_format($model->items->sum('qty'), 2) }}</div>
                <div class="muted small-text" style="margin-top: 4px;">Total Quantity</div>
            </div>
        </div>
    </div>

    <!-- Legal Authorization -->
    <div class="info-section">
        <div class="section-header">
            Autorização Oficial
        </div>
        <div class="section-content">
            <div class="legal-notice">
                <div class="h3" style="margin-bottom: 10px; color: #1e3a8a;">Declaração de Autorização</div>
                <div class="body-text">
                    <p><strong>O Ministério da Agricultura e Florestas autoriza o portador deste documento a exportar os produtos acima citados, nas quantidades especificadas, sendo que a quantidade total não poderá exceder os 15 kg (quinze quilogramas).</strong></p>
                    <p><em>The Ministry of Agriculture and Forests authorizes the bearer of this document to export the aforementioned products, in the specified quantities, with the total quantity not exceeding 15 kg (fifteen kilograms).</em></p>
                </div>
                
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                    <div class="status-badge status-valid">
                        LIMITE MÁXIMO: 15 kg
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expedition and Signature Section -->
    <div class="signature-area">
        <table class="two-column-table">
            <tr>
                <!-- Left Column: Expedition Details -->
                <td class="column-cell left-column">
                    <div class="info-row">
                        <div class="label">Data de Expedição</div>
                        <div class="value highlight-value" style="font-size: 13px;">
                            {!! Carbon\Carbon::parse($model->expedition_date)->format('d/m/Y') !!}
                        </div>
                        <div class="muted small-text">Expedition Date</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="label">Local de Expedição</div>
                        <div class="value highlight-value" style="font-size: 13px;">
                            Luanda, Angola
                        </div>
                        <div class="muted small-text">Expedition Location</div>
                    </div>
                </td>
                
                <!-- Right Column: Operator Information -->
                <td class="column-cell right-column">
                    <div class="info-row">
                        <div class="label">Operador / Operator</div>
                        <div class="value">{!! $model->user->full_name !!}</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="label">Data de Processamento</div>
                        <div class="value">{!! Carbon\Carbon::now()->format('d/m/Y H:i') !!}</div>
                        <div class="muted small-text">Processing Date & Time</div>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Signatures -->
        <div style="text-align: center; margin-top: 30px;">
            <div class="h3" style="margin-bottom: 25px; color: #1e3a8a;">
                Assinatura Autorizada / Authorized Signature
            </div>
            
            <div style="display: flex; justify-content: center; align-items: flex-start; margin-top: 20px;">
                <div style="text-align: center; width: 250px;">
                    <div class="signature-line"></div>
                    <div class="small-text" style="margin-top: 5px;">Assinatura / Signature</div>
                    
                    <div style="margin-top: 25px;">
                        <img src="{!! public_path() . '/images/stamp_wgaspar.svg' !!}" style="width: 80px; height: auto;" alt="Official Stamp">
                        <div class="small-text" style="margin-top: 5px;">
                            <strong>Wladimira Gaspar</strong><br>
                            Autorizado por / Authorized by
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Information -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
        <div class="small-text" style="text-align: center; line-height: 1.4;">
            <p>Documento gerado por sistema validado • Processado por programa validado</p>
            <p style="color: #1e3a8a; font-weight: 600; margin-top: 5px;">DOCUMENTO OFICIAL - USO EXCLUSIVO DA OPERAÇÃO DE EXPORTAÇÃO</p>
        </div>
    </div>

</div>

</body>
</html>