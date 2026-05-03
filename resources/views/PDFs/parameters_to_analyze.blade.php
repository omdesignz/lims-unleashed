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
        
        p { margin: 0pt; }
        
        /* Page Layout */
        @page {
            margin: 15mm;
            header: page-header;
            footer: page-footer;
            margin-header: 10mm;
            margin-footer: 10mm;
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
        .document-header {
            text-align: center;
            margin-bottom: 25px;
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
        
        /* Work Sheet Specific Table */
        .worksheet-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1.5px solid #1e3a8a;
        }
        
        .worksheet-table th {
            background-color: #1e3a8a; /* Solid color, not gradient */
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 10px 6px;
            text-align: center;
            border: 1px solid #1d4ed8;
        }
        
        .worksheet-table td {
            font-size: 10px;
            color: #374151;
            padding: 10px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
            text-align: center;
        }
        
        .sample-cell {
            background-color: #f9fafb;
            text-align: left !important;
            border-right: 1.5px solid #1e3a8a;
            width: 20%;
        }
        
        .department-parameter-cell {
            background-color: #f0f9ff;
            text-align: left !important;
            padding-left: 10px !important;
        }
        
        .parameter-cell {
            min-width: 60px;
        }
        
        .dilution-cell {
            background-color: #f8fafc;
            color: #1e40af;
            font-size: 9px;
            font-weight: 500;
        }
        
        .applicable-cell {
            background-color: white;
        }
        
        .non-applicable-cell {
            background-color: #f9fafb;
            color: #9ca3af;
        }
        
        /* Department Summary Table */
        .department-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .department-table th {
            background-color: #1e3a8a;
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 10px;
            text-align: left;
            border: 1px solid #e5e7eb;
        }
        
        .department-table td {
            font-size: 11px;
            color: #374151;
            padding: 10px;
            border: 1px solid #f3f4f6;
            vertical-align: top;
        }
        
        .department-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }
        
        /* Barcode Styling */
        .barcode-container {
            text-align: center;
            padding: 5px;
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            display: inline-block;
        }
        
        /* Signature Lines */
        .signature-line {
            border-bottom: 1px solid #374151;
            padding-bottom: 15px;
            margin-bottom: 5px;
            width: 250px;
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
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        
        .status-complete {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        
        .status-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: #f9fafb;
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            margin: 30px 0;
        }
        
        /* Info Table Layout */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .info-table td {
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .info-label {
            font-size: 11px;
            font-weight: 500;
            color: #374151;
            width: 40%;
            background-color: #f9fafb;
        }
        
        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #1e3a8a;
            width: 60%;
        }
        
        /* Parameter List */
        .parameter-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 5px;
        }
        
        .parameter-tag {
            display: inline-block;
            padding: 2px 8px;
            background-color: #f0f9ff;
            color: #1e40af;
            font-size: 9px;
            font-weight: 500;
            border-radius: 4px;
            border: 1px solid #dbeafe;
        }
    </style>
</head>
<body>

<!-- Main Content -->
<div style="width: 100%; max-width: 100%;">

    <!-- Document Header -->
    <div class="document-header">
        <div class="h1" style="text-decoration: underline; margin-bottom: 10px;">
            FOLHA DE TRABALHO
        </div>
        <div class="h3" style="color: #1e3a8a;">
            Work Sheet
        </div>
    </div>

    <!-- Consolidated Information Table -->
    <table class="info-table">
        <tr>
            <td class="info-label">Data da Colheita</td>
            <td class="info-value">{!! Carbon\Carbon::parse($model->collection_date)->format('d/m/Y') !!}</td>
        </tr>
        <tr>
            <td class="info-label">Código da Amostra</td>
            <td class="info-value">{{ $model->code->code ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">Número de Amostras</td>
            <td class="info-value">{{ $model->code->samples->count() ?? 0 }}</td>
        </tr>
        <tr>
            <td class="info-label">Data de Geração</td>
            <td class="info-value">{!! Carbon\Carbon::now()->format('d/m/Y H:i') !!}</td>
        </tr>
    </table>

    <!-- Department Parameters Summary -->
    @if ($model->code->samples->count() > 0)
    <div class="section-title">PARÂMETROS POR DEPARTAMENTO</div>
    
    <div class="info-section">
        <div class="section-header">
            Resumo de Análises
        </div>
        <div class="section-content">
            <table class="department-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Departamento</th>
                        <th style="width: 70%;">Parâmetros a Analisar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($model->code->samples->groupBy(function($sample) {
                        return $sample->analysis->department->name;
                    }) as $departmentName => $samples)
                    <tr>
                        <td>
                            <div class="value highlight-value">{{ $departmentName }}</div>
                            <div class="small-text">Amostras: {{ $samples->count() }}</div>
                        </td>
                        <td>
                            @php
                                $departmentParameters = collect();
                                foreach ($samples as $sample) {
                                    foreach ($parameters->unique('id') as $parameter) {
                                        if ($sample->analysis->profile->parameters->contains('id', $parameter['id'])) {
                                            $departmentParameters->push($parameter);
                                        }
                                    }
                                }
                                $uniqueParameters = $departmentParameters->unique('id');
                            @endphp
                            
                            @if($uniqueParameters->count() > 0)
                            <div class="parameter-list">
                                @foreach($uniqueParameters as $param)
                                <span class="parameter-tag">
                                    {{ $param['code'] ?: $param['description'] }}
                                </span>
                                @endforeach
                            </div>
                            <div class="small-text" style="margin-top: 8px;">
                                Total: {{ $uniqueParameters->count() }} parâmetros
                            </div>
                            @else
                            <div class="small-text muted">Nenhum parâmetro atribuído</div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Main Work Sheet Section -->
    <div class="section-title">FOLHA DE ANÁLISE - ENTRADA DE RESULTADOS</div>
    
    @if ($model->code->samples->count() > 0)
    <div class="info-section" style="overflow-x: auto;">
        <div class="section-header">
            Parâmetros de Análise
        </div>
        <div class="section-content" style="padding: 0;">
            <table class="worksheet-table">
                <thead>
                    <tr>
                        <th style="width: 15%; text-align: left; padding-left: 15px;">
                            Amostra / Código
                        </th>
                        <th style="width: 10%; text-align: left; padding-left: 15px;">
                            Departamento
                        </th>
                        @foreach($parameters->unique('id') as $parameter)
                        @php
                            // Safely calculate colspan
                            $newDilutions = $parameter['new_dilutions'] ?? [];
                            $dilutionCount = 0;
                            
                            // Count total dilution entries
                            if (is_array($newDilutions) && !empty($newDilutions)) {
                                foreach ($newDilutions as $dilutionGroup) {
                                    if (is_array($dilutionGroup)) {
                                        $dilutionCount += count($dilutionGroup);
                                    }
                                }
                            }
                            
                            $colspan = $dilutionCount + 1; // +1 for the main parameter column
                        @endphp
                        <th colspan="{{ $colspan }}" class="parameter-cell">
                            {!! ($parameter['code'] == '' ? $parameter['description'] : $parameter['code']) !!}
                        </th>
                        @endforeach
                    </tr>
                    <tr>
                        <th style="background-color: #f9fafb; border-top: none;"></th>
                        <th style="background-color: #f9fafb; border-top: none;"></th>
                        @foreach($parameters->unique('id') as $parameter)
                        <th style="background-color: #f0f9ff; border-top: none; font-size: 9px;">
                            Resultado
                        </th>
                        @php
                            $newDilutions = $parameter['new_dilutions'] ?? [];
                        @endphp
                        @if (!empty($newDilutions) && is_array($newDilutions))
                            @foreach ($newDilutions as $dilutionGroup)
                                @if (is_array($dilutionGroup))
                                    @foreach ($dilutionGroup as $item)
                                        @if (is_array($item) && isset($item['ratio']))
                                        <th style="background-color: #f8fafc; border-top: none;" class="dilution-cell">
                                            Dil: {{ $item['ratio'] }}
                                        </th>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($model->code->samples as $sample)
                    <tr>
                        <td class="sample-cell">
                            <div style="margin-bottom: 5px;">
                                <div class="value" style="font-weight: 600; font-size: 11px;">
                                    {!! $model->code->code !!}
                                </div>
                            </div>
                            <div class="barcode-container">
                                <barcode code="{!!str_replace('/', '', $model->code->description) !!}" text="-1" height="0.26" />
                            </div>
                        </td>
                        
                        <td class="department-parameter-cell">
                            <div class="value" style="font-weight: 600; color: #1e3a8a;">
                                {!! $sample->analysis->department->name !!}
                            </div>
                            <div class="small-text" style="margin-top: 3px;">
                                @php
                                    $sampleParameters = collect();
                                    foreach ($parameters->unique('id') as $parameter) {
                                        if ($sample->analysis->profile->parameters->contains('id', $parameter['id'])) {
                                            $sampleParameters->push($parameter);
                                        }
                                    }
                                @endphp
                                Parâmetros: {{ $sampleParameters->count() }}
                            </div>
                        </td>
                        
                        @foreach($parameters->unique('id') as $parameter)
                            @php
                                $newDilutions = $parameter['new_dilutions'] ?? [];
                                $dilutionCount = 0;
                                
                                if (is_array($newDilutions) && !empty($newDilutions)) {
                                    foreach ($newDilutions as $dilutionGroup) {
                                        if (is_array($dilutionGroup)) {
                                            $dilutionCount += count($dilutionGroup);
                                        }
                                    }
                                }
                                $totalColspan = $dilutionCount + 1;
                            @endphp
                            
                            @if($sample->analysis->profile->parameters->contains('id', $parameter['id']))
                            <td class="applicable-cell" style="height: 60px;">
                                <!-- Empty cell for result entry -->
                            </td>
                            @if (!empty($newDilutions) && is_array($newDilutions))
                                @foreach ($newDilutions as $dilutionGroup)
                                    @if (is_array($dilutionGroup))
                                        @foreach ($dilutionGroup as $item)
                                            @if (is_array($item))
                                            <td class="applicable-cell" style="height: 60px; background-color: #f8fafc;">
                                                <!-- Empty cell for dilution entry -->
                                            </td>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                            @else
                            <td class="non-applicable-cell" colspan="{{ $totalColspan }}">
                                <div class="small-text" style="text-align: center;">
                                    -
                                </div>
                            </td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Legend Section -->
    <div style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
        <div class="h4" style="margin-bottom: 10px; color: #1e3a8a;">Legenda / Legend</div>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 15px; height: 15px; background-color: white; border: 1px solid #e5e7eb;"></div>
                <span class="small-text">Parâmetro a analisar / Parameter to analyze</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 15px; height: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb;"></div>
                <span class="small-text">Parâmetro não aplicável / Not applicable</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 15px; height: 15px; background-color: #f8fafc; border: 1px solid #dbeafe;"></div>
                <span class="small-text">Células de diluição / Dilution cells</span>
            </div>
        </div>
    </div>
    
    @else
    <!-- Empty State -->
    <div class="empty-state">
        <div class="h2" style="color: #6b7280; margin-bottom: 15px;">
            ⚠️ Amostra não colocada em análise
        </div>
        <div class="body-text" style="color: #6b7280; margin-bottom: 20px;">
            A amostra ainda não foi colocada em análise ou não possui dados de parâmetros associados.
        </div>
        <div class="status-badge status-pending">
            STATUS: AGUARDANDO ANÁLISE
        </div>
    </div>
    @endif

    <!-- Signatures Section -->
    <div class="info-section">
        <div class="section-header">
            Assinaturas e Validações
        </div>
        <div class="section-content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                <div>
                    <div class="form-label">Técnico de Microbiologia / Fisico-Química</div>
                    <div class="signature-line"></div>
                    <div class="small-text" style="margin-top: 5px;">
                        <span class="muted">Assinatura / Signature</span>
                    </div>
                </div>
                
                <div>
                    <div class="form-label">Técnico de Codificação</div>
                    <div class="signature-line"></div>
                    <div class="small-text" style="margin-top: 5px;">
                        <span class="muted">Assinatura / Signature</span>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <div class="form-label">Data de Validação</div>
                <div style="display: inline-block; padding: 8px 16px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <span class="value highlight-value">{{ now()->format('d/m/Y') }}</span>
                </div>
                <div class="muted small-text" style="margin-top: 5px;">
                    Validation Date
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Information -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
        <div class="small-text" style="text-align: center; line-height: 1.4;">
            <p>Documento gerado por sistema validado • Processado por programa validado</p>
            <p style="color: #1e3a8a; font-weight: 600; margin-top: 5px;">FOLHA DE TRABALHO OFICIAL - USO EXCLUSIVO DO LABORATÓRIO</p>
        </div>
    </div>

</div>

<sethtmlpagefooter name="page-footer" value="on" />

<htmlpagefooter name="page-footer">
    <div style="border-top: 1px solid #e5e7eb; font-size: 9pt; text-align: center; padding-top: 10px; margin-top: 15px;">
        <div class="small-text">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%" align="left">
                        <span class="muted">Referência: WS-{{ $model->id }}-{{ date('Y', strtotime($model->collection_date)) }}</span>
                    </td>
                    <td width="50%" align="right">
                        <span class="muted">Página {PAGENO} de {nb}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</htmlpagefooter>

</body>
</html>