<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    /* Base Styles - Aligned with Visual System */
    body {
        font-family: 'Inter', Arial, sans-serif;
        font-size: 11px;
        margin: 0;
        padding: 0;
        color: #374151;
        line-height: 1.5;
        background-color: white;
    }
    
    p { margin: 0pt; }
    
    /* Page Layout */
    @page {
        margin: 60mm 15mm 15mm 15mm;
        header: page-header;
        footer: page-footer;
        margin-header: 10mm;
        margin-footer: 10mm;
        background-image: url("{!! public_path() . '/images/oficio_template.svg'!!}") no-repeat 0 0;
        background-image-resize: 6;
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
        margin: 20px 0 15px 0;
        padding-bottom: 8px;
        border-bottom: 1px solid #e5e7eb;
        position: relative;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
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
    
    /* Checkbox Styling */
    .checkbox-container {
        display: inline-block;
        margin-right: 15px;
        margin-bottom: 8px;
    }
    
    .checkbox-label {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        color: #374151;
        cursor: pointer;
    }
    
    .checkbox-input {
        width: 14px;
        height: 14px;
        margin-right: 6px;
        border: 1.5px solid #1e3a8a;
        border-radius: 3px;
        position: relative;
    }
    
    /* Info Row Component */
    .info-row {
        margin-bottom: 12px;
    }
    
    .info-row:last-child {
        margin-bottom: 0;
    }
    
    /* Observations Box */
    .observations-box {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 15px;
        margin: 15px 0;
        border-left: 4px solid #1e3a8a;
    }
    
    /* Signature Lines */
    .signature-line {
        border-bottom: 1px solid #374151;
        padding-bottom: 15px;
        margin-bottom: 5px;
        width: 100%;
    }
    
    /* Header Table */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        border: 1.5px solid #111827;
        margin-bottom: 20px;
    }
    
    .header-table td {
        padding: 8px;
        border: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    
    /* Form Field */
    .form-field {
        margin-bottom: 15px;
    }
    
    .form-label {
        display: block;
        font-size: 11px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 4px;
    }
    
    /* Page break control for main content */
    .main-content {
        margin-top: 20px;
    }
</style>
</head>
<body>
<sethtmlpagefooter name="page-footer" value="on" />
<sethtmlpageheader name="page-header" value="on" />

<htmlpageheader name="page-header">
    <!-- Official Header -->
    <table class="header-table">
        <tr>
            <td rowspan="4" style="border: 1.5px solid #111827; text-align: center; width: 33%;">
                <img src="{{ public_path() . '/images/SVG/sncqa_logo.png' }}" style="width: 80px; height: auto;" alt="VAP Logo">
            </td>
            <td style="border-right: 1px solid #e5e7eb; border-top: 1px solid #e5e7eb; border-left: 1px solid #e5e7eb; padding: 8px; width: 33%;">
                <div class="h3" style="color: #1e3a8a; font-weight: 700;">
                    <b>SERVIÇO NACIONAL DE CONTROLO DE QUALIDADE DE ALIMENTOS </b>
                </div>
            </td>
            <td rowspan="3" style="border-right: 1px solid #e5e7eb; border-top: 1px solid #e5e7eb; padding: 8px; text-align: left; width: 33%;">
                <div class="small-text">
                    <div><span class="label">Código:</span> <span class="value">-</span></div>
                    <div><span class="label">Edição:</span> <span class="value">-</span></div>
                    <div><span class="label">Rev.</span> <span class="value">-</span></div>
                    <div style="margin-top: 8px;"><span class="label">Página:</span> <span class="value">{PAGENO} de {nb}</span></div>
                </div>
            </td>
        </tr>
        <tr>
            <td style="border-right: 1px solid #e5e7eb; border-left: 1px solid #e5e7eb; padding: 8px; text-align: center;">
                <!-- Spacer row -->
            </td>
        </tr>
        <tr>
            <td style="border-right: 1px solid #e5e7eb; border-left: 1px solid #e5e7eb; padding: 8px; text-align: center;">
                <!-- Spacer row -->
            </td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #e5e7eb; text-align: center; padding: 8px;">
                <div class="h3" style="color: #111827;">
                    <b>LABORATÓRIO CENTRAL AGRO-ALIMENTAR DE LUANDA </b>
                </div>
            </td>
            <td style="border: 1.5px solid #111827; padding: 8px; font-size: 12px; text-align: center; font-weight: 600; color: #1e3a8a;">
                RECEPÇÃO DE AMOSTRAS E REGISTRO
            </td>
        </tr>
    </table>
    
    <!-- Document Title -->
    <div style="text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #1e3a8a;">
        <h1 class="h1" style="text-decoration: underline;font-weight: bold;">TERMO DE COLHEITA DE AMOSTRAS</h1>
    </div>
</htmlpageheader>

<!-- MAIN CONTENT STARTS HERE (outside htmlpageheader) -->
<div class="main-content">
    <!-- Sampling Reasons Section -->
    <div class="info-section">
        <div class="section-header">
            <b>Razões da Amostragem</b>
        </div>
        <div class="section-content">
            <div class="form-field">
                <div class="form-label">Selecione as razões para a amostragem:</div>
                <div style="margin-top: 10px;">
                    @foreach ($reasons as $reason)
                    <div class="checkbox-container">
                        <span class="checkbox-label">
                            <span class="checkbox-input" style="{{ in_array($reason->id, $model->collection->reasons->pluck('id')->toArray()) ? 'background-color: #1e3a8a;' : '' }}"></span>
                            {{ $reason->name }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Requester Information Section -->
    <div class="section-title">DADOS DA ENTIDADE REQUERENTE</div>
    
    <div class="info-section">
        <div class="section-header">
            <b>Informações do Requisitante</b>
        </div>
        <div class="section-content">
            <table class="two-column-table">
                <tr>
                    <!-- Left Column -->
                    <td class="column-cell left-column">
                        <div class="info-row">
                            <div class="label">Requisitante</div>
                            <div class="value highlight-value">
                                {!! mb_strtoupper($model->collection->customer->name) !!}
                            </div>
                            <div class="muted small-text">Requester</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="label">Email</div>
                            <div class="value">
                                {!! $model->collection->warehouse->email !!}
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="label">Email para Envio de Cobrança</div>
                            <div class="value">
                                {!! $model->collection->warehouse->invoicing_email !!}
                            </div>
                            <div class="muted small-text">Invoicing Email</div>
                        </div>
                    </td>
                    
                    <!-- Right Column -->
                    <td class="column-cell right-column">
                        <div class="info-row">
                            <div class="label">NIF</div>
                            <div class="value highlight-value">
                                {!! $model->collection->warehouse->nif !!}
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="label">Telefone</div>
                            <div class="value">
                                {!! $model->collection->warehouse->primary_phone !!}
                                @if($model->collection->warehouse->alternative_phone)
                                / {!! $model->collection->warehouse->alternative_phone !!}
                                @endif
                            </div>
                            <div class="muted small-text">Phone</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="label">Endereço</div>
                            <div class="value">
                                {!! $model->collection->warehouse->address !!}
                            </div>
                            <div class="muted small-text">Address</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Sample Data Section -->
    <div class="section-title">DADOS DA AMOSTRA</div>

    <div class="info-section">
        <div class="section-header">
            <b>Detalhes da Amostra</b>
        </div>
        <div class="section-content">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Designação</th>
                        <th>Marca Comercial</th>
                        <th>Data de Colheita</th>
                        <th>Data de Produção</th>
                        <th>Data de Expiração</th>
                        <th>Temperatura</th>
                        <th>Lote</th>
                        <th>Tipo de Embalagem</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="value" style="font-weight: 600;">{!! mb_strtoupper($model->product->name) !!}</div>
                        </td>
                        <td>
                            <div class="value">{!! mb_strtoupper($model->comercial_brand) !!}</div>
                        </td>
                        <td>
                            <div class="value">{!! mb_strtoupper($model->collection_date) !!}</div>
                        </td>
                        <td>
                            <div class="value">{!! mb_strtoupper($model->production_date) !!}</div>
                        </td>
                        <td>
                            <div class="value">{!! mb_strtoupper($model->expiry_date) !!}</div>
                        </td>
                        <td>
                            <div class="value">{!! mb_strtoupper($model->temperature_value) !!}</div>
                        </td>
                        <td>
                            <div class="value">{!! mb_strtoupper($model->lot) !!}</div>
                        </td>
                        <td>
                            <div class="value">{!! mb_strtoupper($model->packaging->name) !!}</div>
                        </td>
                        <td>
                            <div class="value highlight-value">{!! mb_strtoupper($model->qty) !!}</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Observations Section -->
    <div class="info-section">
        <div class="section-header">
            <b>Observações</b>
        </div>
        <div class="section-content">
            <div class="observations-box">
                <div class="label" style="margin-bottom: 8px;">Observações:</div>
                <div class="value">{{ $model->obs }}</div>
            </div>
        </div>
    </div>

    <!-- Legal Information Section -->
    <div class="info-section">
        <div class="section-header">
            <b>Informações Importantes</b>
        </div>
        <div class="section-content">
            <div class="body-text">
                <p><strong>A amostragem foi efectuada em triplicado, o qual (<span style="color: #1e3a8a;">2</span>) amostras vão para o laboratório e (<span style="color: #1e3a8a;">1</span>) fica com a entidade requerente como fiel depositário para efeito de contra-análise.</strong></p>
                <p class="muted"><em>The sampling was carried out in triplicate, of which (<span style="color: #1e3a8a;">2</span>) samples go to the laboratory and (<span style="color: #1e3a8a;">1</span>) remains with the requesting entity as a faithful depository for counter-analysis purposes.</em></p>
            </div>
        </div>
    </div>

    <!-- Signature Section -->
    <div class="info-section">
        <div class="section-header">
            <b>Assinaturas e Datas</b>
        </div>
        <div class="section-content">
            <div style="margin-bottom: 25px;">
                <div class="form-field">
                    <div class="form-label">Nome do requerente ou representante legal</div>
                    <div class="signature-line"></div>
                    <div class="small-text" style="margin-top: 5px; display: flex; justify-content: space-between;">
                        <span class="muted">Telefone: _________________________</span>
                        <span class="muted">Data: ______/______/_______</span>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="form-field">
                    <div class="form-label">Nome do Responsável pela colheita ou recepção de amostras</div>
                    <div class="signature-line"></div>
                    <div class="small-text" style="margin-top: 5px; display: flex; justify-content: space-between;">
                        <span class="muted">Telefone: _________________________</span>
                        <span class="muted">Data: ______/______/_______</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Information -->
    <div style="margin-top: 30px; padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
        <table class="two-column-table">
            <tr>
                <td class="column-cell left-column">
                    <div class="info-row">
                        <div class="label">Número de Referência</div>
                        <div class="value highlight-value">
                            COL-{{ $model->id }}-{{ date('Y', strtotime($model->collection_date)) }}
                        </div>
                    </div>
                </td>
                <td class="column-cell right-column">
                    <div class="info-row">
                        <div class="label">Data de Processamento</div>
                        <div class="value">{!! Carbon\Carbon::now()->format('d/m/Y H:i') !!}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
<!-- END MAIN CONTENT -->

<htmlpagefooter name="page-footer">
    <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
        <div class="small-text">
            <p>Documento gerado por sistema validado • Processado por programa validado</p>
            <p style="color: #1e3a8a; font-weight: 600; margin-top: 5px;">TERMO OFICIAL - COLHEITA DE AMOSTRAS</p>
        </div>
    </div>
</htmlpagefooter>

</body>
</html>