<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #374151;
            line-height: 1.5;
        }
        
        @page {
            margin: 105mm 15mm 10mm 15mm;
            header: page-header;
            footer: page-footer;
            margin-header: 10mm;
            margin-footer: 10mm;
        }
        
        @page :first {
            margin-top: 105mm;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            text-align: center;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #1e3a8a;
        }
        
        .watermark {
            position: fixed;
            left: 15mm;
            top: -83.5mm;
            opacity: 0.05;
            z-index: -1;
        }
        
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
        
        .info-section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .section-header {
            background: #1e3a8a;
            color: white;
            padding: 12px 20px;
            font-size: 13px;
            font-weight: bold;
        }
        
        .section-content {
            padding: 20px;
        }
        
        /* Two column table layout - Works with mPDF */
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
        
        .info-row {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 12px;
            color: #111827;
        }
        
        .highlight-value {
            color: #1e3a8a;
            font-weight: 600;
        }
        
        .contact-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-top: 1px solid #f3f4f6;
            padding-top: 15px;
        }
        
        .contact-table td {
            width: 33.33%;
            padding: 5px 10px;
            vertical-align: top;
            border-right: 1px solid #f3f4f6;
        }
        
        .contact-table td:last-child {
            border-right: none;
        }
        
        .contact-label {
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .contact-value {
            font-size: 12px;
            font-weight: 600;
            color: #1e3a8a;
        }
        
        .document-section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .document-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .document-table tr {
            border-bottom: 1px solid #f3f4f6;
        }
        
        .document-table tr:last-child {
            border-bottom: none;
        }
        
        .document-label {
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            padding: 12px 15px;
            width: 40%;
            vertical-align: middle;
            background-color: #f9fafb;
        }
        
        .document-value {
            font-size: 12px;
            font-weight: 600;
            color: #1e3a8a;
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .signature-area {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #1e3a8a;
        }
        
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .product-table th {
            background: #1e3a8a;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 12px 10px;
            text-align: left;
        }
        
        .product-table td {
            font-size: 11px;
            color: #374151;
            padding: 12px 10px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .product-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-indicator {
            display: inline-block;
            padding: 4px 10px;
            background-color: #d1fae5;
            color: #065f46;
            font-size: 10px;
            font-weight: 600;
            border-radius: 4px;
            border: 1px solid #a7f3d0;
            margin-top: 15px;
        }
        
        .date-badge {
            display: inline-block;
            padding: 8px 15px;
            background-color: #f9fafb;
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        
        .document-status {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .status-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .status-label-cell {
            width: 60%;
            vertical-align: top;
        }
        
        .status-value-cell {
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
    </style>
</head>
<body>

<htmlpageheader name="page-header">
    {{-- <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" style="padding-bottom: 8px;">
                            <img src="{!! public_path() . '/images/aocrest.svg' !!}" style="width: 57px; height: 69px;" alt="">
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 3px;">
                            <div style="font-size: 13px; font-weight: 600; color: #1e3a8a;">VAP Soluções</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <div style="font-size: 15px; font-weight: 700; color: #111827;">{!! mb_strtoupper($settings->app_client_lab_name) !!}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table> --}}

    <div style="text-align: center; border-bottom:0.3mm solid black;padding-bottom: 0px" width="100%" >
        <center><img src="{!! public_path() . '/images/ao_crest.svg'!!}" width="8%"></center>
        <h6>REPÚBLICA DE ANGOLA</h6>
        <h6>MINISTÉRIO DA AGRICULTURA E FLORESTAS</h6>
        <h6>SERVIÇO NACIONAL DE CONTROLO DA QUALIDADE DOS ALIMENTOS</h6>
        <h6>LABORATÓRIO CENTRAL AGRO-ALIMENTAR DE LUANDA</h6>
    </div><br>
    
    <!-- Main Header -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #1e3a8a; color: white; border-radius: 8px; margin-bottom: 15px;">
        <tr>
            <td style="padding: 15px; text-align: center;">
                <div style="font-size: 16px; font-weight: 700; color: white; font-weight:bold;">
                    GUIA DE CONTRATAÇÃO {!! $model->guide_no !!}
                </div>
                <div style="font-size: 11px; color: rgba(255,255,255,0.9); margin-top: 5px; font-weight: 500;">
                    Documento Oficial de Solicitação de Serviços
                </div>
            </td>
        </tr>
    </table>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 10px;">
        <tr>
            <td>
                <div style="font-size: 8px; color: #6b7280; line-height: 1.4;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                Página {PAGENO} de {nb}
                            </td>
                            <td align="right">
                                {!! mb_strtoupper($settings->app_client_address) !!} | {!! mb_strtoupper($settings->app_client_email) !!} | {!! mb_strtoupper($settings->app_client_contact) !!}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</htmlpagefooter>

<!-- Watermark -->
<div class="watermark">
    <img src="{{ public_path() . '/images/SVG/sncqa_logo.png' }}" style="width: 120px; height: auto;" alt="VAP Solutions">
</div>

<!-- Main Content -->
<div style="width: 100%; max-width: 100%;">

    <!-- Applicant/Client Section - TWO COLUMN TABLE LAYOUT -->
    <div class="section-title">Solicitante / Cliente</div>
    
    <div class="info-section">
        <div class="section-header">
            Informações do Solicitante
        </div>
        <div class="section-content">
            <!-- Two Column Table Layout -->
            <table class="two-column-table">
                <tr>
                    <!-- Left Column -->
                    <td class="column-cell left-column">
                        <!-- Company Information -->
                        <div class="info-row">
                            <div class="info-label">Empresa / Estabelecimento</div>
                            <div class="info-value highlight-value">
                                {!! mb_strtoupper($model->customer->company) !!}
                            </div>
                        </div>
                        
                        <!-- NIF -->
                        <div class="info-row">
                            <div class="info-label">NIF</div>
                            <div class="info-value highlight-value">
                                {!! $model->nif !!}
                            </div>
                        </div>
                        
                        <!-- Phone -->
                        <div class="info-row">
                            <div class="info-label">Telefone</div>
                            <div class="info-value highlight-value">
                                {!! $model->contact !!}
                            </div>
                        </div>
                    </td>
                    
                    <!-- Right Column -->
                    <td class="column-cell right-column">
                        <!-- Collection Point -->
                        <div class="info-row">
                            <div class="info-label">Local de Recolha</div>
                            <div class="info-value">
                                {!! mb_strtoupper($model->collection_point) !!}
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value highlight-value">
                                {!! $model->email !!}
                            </div>
                        </div>
                        
                        <!-- Contract Date -->
                        <div class="info-row">
                            <div class="info-label">Data do Contrato</div>
                            <div class="info-value">
                                {!! $model->date !!}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            
            <!-- Status Indicator (Full width below columns) -->
            <div class="status-indicator">
                Cliente Activo • Cadastro válido até {!! Carbon\Carbon::parse($model->date)->addYear()->format('d/m/Y') !!}
            </div>
        </div>
    </div>

    <!-- Supporting Documentation Section -->
    <div class="section-title">Documentação de Suporte</div>
    
    <div class="info-section">
        <div class="section-header">
            Documentos de Referência
        </div>
        <div class="section-content">
            <!-- Documents Table -->
            <table class="document-table">
                <tr>
                    <td class="document-label">Porto / Aeroporto / Posto de Desembarque</td>
                    <td class="document-value">{!! mb_strtoupper($model->entry_point) !!}</td>
                </tr>
                <tr>
                    <td class="document-label">B/L ou Carta de Porte</td>
                    <td class="document-value">{!! mb_strtoupper($model->ref_no) !!}</td>
                </tr>
                <tr>
                    <td class="document-label">Nº do Documento Único (DU)</td>
                    <td class="document-value">{!! mb_strtoupper($model->du_no) !!}</td>
                </tr>
            </table>
            
            <!-- Document Status -->
            <div class="document-status">
                <table class="status-table">
                    <tr>
                        <td class="status-label-cell">
                            <div style="font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 4px;">Status dos Documentos</div>
                            <div style="font-size: 10px; color: #6b7280;">Documentação completa e válida</div>
                        </td>
                        <td class="status-value-cell">
                            <div class="status-indicator" style="background-color: #d1fae5; color: #065f46; border-color: #a7f3d0; margin: 0;">
                                Documentação Completa
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <pagebreak>

    <!-- Food Product Information Section -->
    <div class="section-title" page-break-before="always">Informação do Produto Alimentar</div>
    
    <div class="info-section" style="padding: 0;">
        <div class="section-header">
            Produtos para Análise
        </div>
        <div class="section-content" style="padding: 0;">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>País de Origem</th>
                        <th>Fabricante / Produtor</th>
                        <th>Marca</th>
                        <th>Lote</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($model->items as $item)
                    <tr>
                        <td>{!! mb_strtoupper($item->product->name) !!}</td>
                        <td>{!! mb_strtoupper($item->country->name) !!}</td>
                        <td>{!! mb_strtoupper($item->manufacturer) !!}</td>
                        <td>{!! mb_strtoupper($item->brand) !!}</td>
                        <td>{!! mb_strtoupper($item->lot) !!}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Legal Notices Section -->
    <div style="margin: 25px 0;">
        <div class="legal-notice">
            <div style="font-weight: 600; color: #111827; margin-bottom: 10px; font-size: 10px;">
                Termos e Condições Legais
            </div>
            <ol style="margin: 0; padding-left: 15px;">
                <li style="margin-bottom: 6px;">Nos termos do Decreto Presidencial nº179/18 de 2 de agosto, a VAP Soluções compromete-se em realizar as análises dos produtos descritos nesta Guia de Contratação.</li>
                <li style="margin-bottom: 6px;">A VAP Soluções obriga-se a efectuar as referidas análises e apresentar o correspondente Boletim de Análises, no prazo máximo de 15 dias.</li>
                <li>O presente documento não substitui o Boletim de Análises e não confere a certificação da qualidade do(s) produto(s).</li>
            </ol>
        </div>
    </div>

    <!-- Date and Signature Section -->
    <div class="signature-area">
        <table width="100%" style="margin-bottom: 30px;">
            <tr>
                <td width="50%" valign="top">
                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 5px;">Data do Documento</div>
                    <div class="date-badge">
                        Luanda, {!! $model->date !!}
                    </div>
                </td>
                <td width="50%" valign="top" align="right">
                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 5px;">Referência</div>
                    <div style="font-size: 12px; font-weight: 600; color: #1e3a8a; padding: 6px 12px; background-color: #f9fafb; border-radius: 4px; border: 1px solid #e5e7eb; display: inline-block;">
                        GUID-{!! $model->guide_no !!}
                    </div>
                </td>
            </tr>
        </table>
        
        <div style="text-align: center;">
            <div style="font-size: 13px; font-weight: 600; color: #1e3a8a; margin-bottom: 30px;">
                O Director Geral
            </div>
            
            <div style="display: inline-block; text-align: center;">
                <div style="width: 200px; margin: 0 auto;">
                    <div style="border-bottom: 2px solid #374151; padding-bottom: 15px; margin-bottom: 5px;"></div>
                    <div style="font-size: 10px; color: #6b7280;">Nome e Assinatura</div>
                    
                    <div style="border-bottom: 1px solid #374151; padding-bottom: 15px; margin: 25px auto 5px auto; width: 180px;"></div>
                    <div style="font-size: 10px; color: #6b7280;">Carimbo Oficial</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Information -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
        <div style="font-size: 9px; color: #6b7280; text-align: center; line-height: 1.4;">
            <p>Documento gerado por sistema validado N. <strong>{!! $settings->app_agt_validation_number !!}</strong></p>
            <p>Processado por: <strong>{!! $settings->app_name !!}</strong> • Data: <strong>{!! Carbon\Carbon::now()->format('d/m/Y H:i:s') !!}</strong></p>
            <p style="color: #1e3a8a; font-weight: 600; margin-top: 5px;">Documento Confidencial - Uso Exclusivo do Solicitante</p>
        </div>
    </div>

</div>

</body>
</html>