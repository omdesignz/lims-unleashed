<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
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
    
    p { margin: 0pt; }
    
    /* Page Layout - Adjusted for better spacing */
    @page {
        margin: 30mm 15mm 40mm 15mm;
        header: other-pages-header;
        footer: page-footer;
        margin-header: 10mm;
        margin-footer: 15mm;
        background-image: url("{!! public_path() . '/images/oficio_template.svg'!!}") no-repeat 0 0;
        background-image-resize: 6;
    }
    
    @page :first {    
        header: page-header;
        footer: page-footer;
        margin-top: 120mm;
        margin-bottom: 40mm;
    }
    
    /* Visual System Classes */
    .h1 {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
    }
    
    .h2 {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 6px;
    }
    
    .h3 {
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 5px;
    }
    
    .h4 {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
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
    
    /* Observations Box */
    .observations-box {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 15px;
        margin: 15px 0;
        font-size: 10px;
        color: #6b7280;
        line-height: 1.4;
        border-left: 4px solid #1e3a8a;
    }
    
    .observations-title {
        font-size: 11px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
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
    
    /* Header Separator */
    .header-separator {
        border-bottom: 1px solid #1e3a8a;
        margin: 8px 0;
    }
    
    /* Logo Styling */
    .logo-container {
        text-align: center;
        padding: 10px 0;
    }
    
    .logo-img {
        width: 60px;
        height: auto;
    }
    
    /* Report Info Badge */
    .report-badge {
        display: inline-block;
        padding: 8px 16px;
        background: linear-gradient(to right, #1e3a8a, #1e40af);
        color: white;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px;
        margin: 5px 0;
    }
    
    /* Main Content Area */
    .main-content {
        margin-top: 20px;
        page-break-inside: avoid;
    }
</style>
</head>
<body>
<sethtmlpagefooter name="page-footer" value="on" show-this-page="1" />
<sethtmlpageheader name="page-header" value="on" show-this-page="1" />

<htmlpageheader name="page-header">
    <!-- First Page Header -->
    <div class="logo-container">
        <img src="{!! public_path() . '/images/SVG/vap_light.svg'!!}" class="logo-img" alt="VAP Soluções">
    </div>
    
    <div style="text-align: center; margin: 15px 0 25px 0;">
        <div class="h1" style="text-decoration: underline; margin-bottom: 10px;">
            RELATÓRIO DE ANÁLISE
        </div>
        <div class="h3" style="color: #1e3a8a;">
            Analysis Report
        </div>
        <div style="margin-top: 15px;">
            <span class="report-badge">
                RELATÓRIO Nº: {{ $model->code }}
            </span>
        </div>
    </div>
    
    <!-- Client Information -->
    <div class="info-section">
        <div class="section-header">
            Informações do Cliente
        </div>
        <div class="section-content">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%" style="vertical-align: top; padding-right: 15px;">
                        <div style="margin-bottom: 12px;">
                            <div class="label">Cliente / Client</div>
                            <div class="value highlight-value">
                                {!! mb_strtoupper($model->collection->customer->name ?? 'N/A') !!}
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <div class="label">NIF / Tax ID</div>
                            <div class="value">
                                {!! $model->collection->warehouse->nif ?? 'N/A' !!}
                            </div>
                        </div>
                    </td>
                    
                    <td width="50%" style="vertical-align: top; padding-left: 15px; border-left: 1px solid #e5e7eb;">
                        <div style="margin-bottom: 12px;">
                            <div class="label">Endereço / Address</div>
                            <div class="value">
                                {!! $model->collection->warehouse->address ?? 'N/A' !!}
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <div class="label">Contacto / Contact</div>
                            <div class="value">
                                {!! $model->collection->warehouse->primary_phone ?? 'N/A' !!}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Sample Information -->
    <div class="info-section">
        <div class="section-header">
            Informações da Amostra
        </div>
        <div class="section-content">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%" style="vertical-align: top; padding-right: 15px;">
                        <div style="margin-bottom: 12px;">
                            <div class="label">Produto / Product</div>
                            <div class="value highlight-value">
                                {!! mb_strtoupper($model->collection->product->name ?? 'N/A') !!}
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <div class="label">Data da Colheita</div>
                            <div class="value">
                                {!! Carbon\Carbon::parse($model->collection->collection_date)->format('d/m/Y') !!}
                            </div>
                            <div class="muted small-text">Collection Date</div>
                        </div>
                    </td>
                    
                    <td width="50%" style="vertical-align: top; padding-left: 15px; border-left: 1px solid #e5e7eb;">
                        <div style="margin-bottom: 12px;">
                            <div class="label">Marca Comercial</div>
                            <div class="value highlight-value">
                                {!! mb_strtoupper($model->collection->comercial_brand ?? 'N/A') !!}
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <div class="label">Lote / Batch</div>
                            <div class="value">
                                {!! $model->collection->lot ?? 'N/A' !!}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Observations Section -->
    <div class="observations-box">
        <div class="observations-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Observações / Observations
        </div>
        <div class="body-text">
            @include('PDFs.includes.analysisreport.templates_new_model.obs')
        </div>
    </div>
    
    <div class="header-separator"></div>
</htmlpageheader>

<htmlpageheader name="other-pages-header">
    <!-- Other Pages Header -->
    <div style="padding: 8px 0; border-bottom: 1px solid #1e3a8a; margin-bottom: 10px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="60%" style="vertical-align: middle;">
                    <div class="small-text" style="font-weight: 600; color: #1e3a8a;">
                        RELATÓRIO Nº: {{ $model->code }}
                    </div>
                    <div class="small-text muted">
                        Emitido em: {{ $model->created_at->format('d/m/Y') }}
                    </div>
                </td>
                <td width="40%" style="text-align: right; vertical-align: middle;">
                    <div class="small-text" style="font-weight: 500;">
                        DOC.AG010 • VAP Soluções
                    </div>
                </td>
            </tr>
        </table>
    </div>
</htmlpageheader>

<!-- MAIN CONTENT GOES HERE - OUTSIDE HEADER TAGS -->
<div class="main-content">
    <!-- Analysis Results Section -->
    <div class="section-title">RESULTADOS ANALÍTICOS</div>

    <div class="info-section">
        <div class="section-header">
            Dados de Análise Laboratorial
        </div>
        <div class="section-content" style="padding: 0;">
            <table class="data-table" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="padding: 12px 10px; text-align: left; border-bottom: 2px solid #1e3a8a;">
                            <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                                ENSAIO
                            </div>
                            <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                                Parameter
                            </div>
                        </th>
                        <th style="padding: 12px 10px; text-align: left; border-bottom: 2px solid #1e3a8a;">
                            <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                                MÉTODO
                            </div>
                            <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                                Method
                            </div>
                        </th>
                        <th style="padding: 12px 10px; text-align: center; border-bottom: 2px solid #1e3a8a;">
                            <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                                RESULTADO & U
                            </div>
                            <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                                Results and Uncertainty
                            </div>
                        </th>
                        <th style="padding: 12px 10px; text-align: center; border-bottom: 2px solid #1e3a8a;">
                            <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                                UNIDADE
                            </div>
                            <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                                Units
                            </div>
                        </th>
                        <th colspan="2" style="padding: 12px 10px; text-align: center; border-bottom: 2px solid #1e3a8a;">
                            <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                                LIMITES DE REFERÊNCIA
                            </div>
                            <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                                Reference Limits
                            </div>
                        </th>
                    </tr>
                    <tr style="background-color: #f9fafb;">
                        <td colspan="6" style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb;">
                            <div class="small-text" style="font-weight: 500; color: #1e3a8a;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                Referência: {{ $model->collection->code->results->pluck('ref_val_origin')->unique()->implode(', ') }}
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    @if($model->collection->code->results->count() > 0)
                        @foreach($model->collection->code->samples as $key => $sample)
                            @if($sample->results)
                                @foreach($sample->results as $result)
                                    <tr>
                                        <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle;">
                                            <div class="value" style="font-weight: 500; font-size: 11px;">
                                                {!! str_replace('º', '°', $result->parameter_label) !!}
                                            </div>
                                        </td>
                                        <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle;">
                                            <div class="value" style="font-size: 10px; color: #6b7280;">
                                                {!! $result->protocol_label !!}
                                            </div>
                                        </td>
                                        <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; text-align: center; vertical-align: middle;">
                                            <div class="value" style="font-weight: 600; font-size: 11px; color: #1e3a8a;">
                                                @include("PDFs.includes.analysisreport.results.{$result->type_id}", ['result' => $result])
                                            </div>
                                        </td>
                                        <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; text-align: center; vertical-align: middle;">
                                            <div class="value" style="font-size: 10px; font-weight: 500;">
                                                {!! $result->unit_label !!}
                                            </div>
                                        </td>
                                        <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; text-align: center; vertical-align: middle; background-color: #f9fafb;">
                                            <div class="value" style="font-size: 10px;">
                                                @if($result->min_ref_value)
                                                    <div style="font-weight: 500;">{{ $result->min_ref_value }}</div>
                                                    <div class="muted small-text" style="font-size: 9px;">Mínimo</div>
                                                @else
                                                    <span class="muted small-text">-</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; text-align: center; vertical-align: middle; background-color: #f9fafb;">
                                            <div class="value" style="font-size: 10px;">
                                                @if($result->max_ref_value)
                                                    <div style="font-weight: 500;">
                                                        {{ $result->max_ref_value }}
                                                        @if($result->ref_val_origin)
                                                            <span class="muted small-text" style="font-size: 9px; margin-left: 2px;">
                                                                ({{ substr($result->ref_val_origin, 0, 1) }})
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="muted small-text" style="font-size: 9px;">Máximo</div>
                                                @else
                                                    <span class="muted small-text">-</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" style="padding: 20px; text-align: center; border-bottom: 1px solid #f3f4f6;">
                                        <div class="small-text muted" style="font-style: italic;">
                                            Sem resultados disponíveis para esta amostra
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center;">
                                <div class="small-text muted" style="font-style: italic;">
                                    Nenhum resultado analítico disponível
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
            
            <!-- Legend for Reference Codes -->
            @if($model->collection->code->results->whereNotNull('ref_val_origin')->count() > 0)
            <div style="margin-top: 15px; padding: 10px 15px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
                <div class="small-text" style="font-weight: 600; color: #374151; margin-bottom: 5px;">
                    Legenda das Referências:
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    @php
                        $references = $model->collection->code->results
                            ->whereNotNull('ref_val_origin')
                            ->pluck('ref_val_origin')
                            ->unique()
                            ->map(function($ref) {
                                return [
                                    'code' => substr($ref, 0, 1),
                                    'full' => $ref
                                ];
                            });
                    @endphp
                    
                    @foreach($references as $ref)
                    <div style="display: inline-flex; align-items: center; gap: 4px;">
                        <span style="display: inline-block; padding: 2px 6px; background-color: #f0f9ff; color: #1e40af; font-size: 9px; font-weight: 500; border-radius: 3px; border: 1px solid #dbeafe;">
                            ({{ $ref['code'] }})
                        </span>
                        <span class="small-text" style="color: #6b7280;">
                            {{ $ref['full'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Results Conclusion -->
    <div style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
        <div style="text-align: center;">
            <div class="h4" style="color: #1e3a8a; margin-bottom: 5px;">
                FIM DOS RESULTADOS ANALÍTICOS
            </div>
            <div class="small-text muted">
                End of analytical results
            </div>
            
            @if($model->collection->code->results->count() > 0)
            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #e5e7eb;">
                <div class="small-text" style="display: inline-block; margin-right: 20px;">
                    <span style="font-weight: 600; color: #374151;">Total de Parâmetros:</span>
                    <span class="value highlight-value" style="margin-left: 5px;">{{ $model->collection->code->results->count() }}</span>
                </div>
                <div class="small-text" style="display: inline-block;">
                    <span style="font-weight: 600; color: #374151;">Data de Análise:</span>
                    <span class="value" style="margin-left: 5px;">
                        {{ $model->collection->code->results->first()->updated_at->format('d/m/Y') ?? 'N/A' }}
                    </span>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<!-- END MAIN CONTENT -->

<htmlpagefooter name="page-footer">
    <!-- Footer Content -->
    <div style="border-top: 2px solid #e5e7eb; margin-top: 20px; padding-top: 15px;">
        <!-- Signatures -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr>
                <td width="100%" style="text-align: center;">
                    <div style="margin-bottom: 15px;">
                        @if ($model?->collection?->code?->results?->first()?->approved_signature?->signature_url)
                        <div style="margin-bottom: 5px;">
                            <img src="{{ $model?->collection?->code?->results?->first()?->approved_signature?->signature_url }}" alt="Signature" style="width: 60px; height: auto;">
                        </div>
                        @else
                        <div style="margin-bottom: 5px; padding: 10px;">
                            <div style="border-bottom: 1px solid #374151; width: 100px; margin: 0 auto;"></div>
                        </div>
                        @endif
                        <div class="small-text">
                            <strong>{{ $model?->collection?->code?->results?->first()?->approved_by ?? '_________________' }}</strong><br>
                            <span class="muted">Autorizado Por / Authorized By</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Fine Print -->
        @if($model?->fine_print)
        <div style="margin: 15px 0; padding: 10px; background-color: #f9fafb; border-radius: 4px;">
            <div class="small-text" style="color: #6b7280; line-height: 1.4;">
                {!! $model?->fine_print !!}
            </div>
        </div>
        @endif
        
        <!-- Page Number -->
        <div style="text-align: right; margin: 10px 0;">
            <div class="small-text" style="color: #6b7280; font-weight: 500;">
                {{ $model?->code }} • Página {PAGENO} de {nb}
            </div>
        </div>
        
        <!-- Company Information -->
        {{-- <div style="border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: 10px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="70%" style="vertical-align: top;">
                        <div class="small-text" style="line-height: 1.4;">
                            <strong style="color: #1e3a8a;">VAP Soluções</strong><br>
                            Quarteirão - Prédio Nº 10<br>
                            (+244) XXXXXXXXX - XXXXXXXXX<br>
                            geral@vapsolucoes.ao<br>
                            www.vapsolucoes.ao<br>
                            Luanda - Angola
                        </div>
                    </td>
                    <td width="30%" style="text-align: right; vertical-align: top;">
                        <img src="{!! public_path() . '/images/SVG/vap_light.svg'!!}" style="width: 40px; height: auto;" alt="VAP Logo">
                    </td>
                </tr>
            </table>
        </div> --}}
        
        <!-- Legal Notes -->
        <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin: 10px 0; font-size: 9px; color: #6b7280; line-height: 1.3;">
            <div style="margin-bottom: 8px;">
                <span style="display: inline-block; width: 20px; font-weight: 600; color: #1e3a8a;">1</span>
                <span class="body-text">"Ensaios realizados nas instalações permanentes do Laboratório"</span>
            </div>
            <div style="margin-bottom: 8px;">
                <span style="display: inline-block; width: 20px; font-weight: 600; color: #1e3a8a;">2</span>
                <span class="body-text">"Os resultados do presente relatório referem-se aos itens ensaiados"</span>
            </div>
            <div style="margin-bottom: 8px;">
                <span style="display: inline-block; width: 20px; font-weight: 600; color: #1e3a8a;">3</span>
                <span class="body-text">"Este relatório não pode ser reproduzido, a não ser na integra, sem a aprovação do Laboratório"</span>
            </div>
            <div style="margin-bottom: 8px;">
                <span style="display: inline-block; width: 20px; font-weight: 600; color: #1e3a8a;">4</span>
                <span class="body-text">"Quando identificado como responsável da colheita o Cliente, todas as informações referentes à amostra são da sua responsabilidade"</span>
            </div>
            <div style="margin-bottom: 8px;">
                <span style="display: inline-block; width: 20px; font-weight: 600; color: #1e3a8a;">5</span>
                <span class="body-text">"A avaliação da conformidade face aos valores de referencia indicados e de acordo com a regra de decisão previamente acordada"</span>
            </div>
            <div style="margin-bottom: 8px;">
                <span style="display: inline-block; width: 20px; font-weight: 600; color: #1e3a8a;">6</span>
                <span class="body-text">"Opiniões e interpretações expressas neste relatório não estão incluidas no âmbito"</span>
            </div>
            <div>
                <span style="display: inline-block; width: 20px; font-weight: 600; color: #1e3a8a;">7</span>
                <span class="body-text">"Quando identificado como responsável da colheita, o laboratório, o método utilizado é o PO005"</span>
            </div>
        </div>
    </div>
</htmlpagefooter>

</body>
</html>