<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Exportação Analítica de Inventário</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @page {
            margin: 16mm 14mm;
        }

        body {
            color: #111827;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
        }

        .hero {
            background: #0f172a;
            border-radius: 16px 16px 10px 10px;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .hero-top {
            background: #4338ca;
            border-radius: 16px 16px 0 0;
            padding: 15px 17px;
        }

        .hero-body {
            color: #ddd6fe;
            padding: 12px 17px 15px;
        }

        .eyebrow {
            color: #c4b5fd;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }

        h1 {
            color: #ffffff;
            font-size: 21px;
            margin: 4px 0 2px;
        }

        .meta {
            color: #ddd6fe;
            font-size: 9px;
            text-align: right;
            white-space: nowrap;
        }

        .metric-grid {
            border-collapse: collapse;
            margin-bottom: 14px;
            width: 100%;
        }

        .metric-grid td {
            background: #f8fafc;
            border: 1px solid #dbe4f0;
            padding: 9px;
            width: 25%;
        }

        .metric-label {
            color: #64748b;
            display: block;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .metric-value {
            color: #0f172a;
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-top: 3px;
        }

        .section {
            background: #ffffff;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            margin-bottom: 13px;
            padding: 13px;
        }

        .section-title {
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 9px;
            padding-bottom: 7px;
        }

        .data-table {
            border-collapse: collapse;
            width: 100%;
        }

        .data-table th {
            background: #0f172a;
            border: 1px solid #0f172a;
            color: #ffffff;
            font-size: 8px;
            letter-spacing: .4px;
            padding: 8px 6px;
            text-align: left;
            text-transform: uppercase;
        }

        .data-table td {
            border: 1px solid #e2e8f0;
            padding: 7px 6px;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .note-box {
            background: #f8fafc;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            color: #334155;
            line-height: 1.55;
            padding: 10px 12px;
        }

        .footer {
            border-top: 1px solid #dbe4f0;
            color: #64748b;
            font-size: 8px;
            line-height: 1.5;
            margin-top: 18px;
            padding-top: 9px;
            text-align: center;
        }
    </style>
</head>
<body class="pdf-document">
    @php
        $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
        $generated_at = $generated_at ?? now();
        $labName = $settings->app_client_lab_name ?: ($settings->app_name ?: config('app.name'));
        $metrics = collect($data['metrics'] ?? []);
        $chartTitles = [
            'consumption' => 'Tendência de consumo',
            'stock' => 'Distribuição de stock',
            'monthly' => 'Comparativo mensal',
            'topReagents' => 'Reagentes mais consumidos',
        ];
        $title = $chartTitles[$chartType] ?? 'Exportação analítica';
        $rows = match ($chartType) {
            'consumption' => collect($data['consumptionTrend'] ?? [])->map(fn ($row) => [
                'Data' => $row['date'] ?? 'N/A',
                'Quantidade' => number_format((float) ($row['quantity'] ?? 0), 2, ',', '.'),
            ]),
            'stock' => collect($data['stockDistribution'] ?? [])->map(fn ($row) => [
                'Categoria' => $row['category'] ?? 'N/A',
                'Quantidade' => number_format((float) ($row['quantity'] ?? 0), 2, ',', '.'),
            ]),
            'monthly' => collect($data['monthlyComparison'] ?? [])->map(fn ($row) => [
                'Mês' => $row['month'] ?? 'N/A',
                'Ano actual' => number_format((float) ($row['current'] ?? 0), 2, ',', '.'),
                'Ano anterior' => number_format((float) ($row['previous'] ?? 0), 2, ',', '.'),
            ]),
            'topReagents' => collect($data['topReagents'] ?? [])->map(fn ($row) => [
                'Reagente' => $row['name'] ?? 'N/A',
                'Consumo' => number_format((float) ($row['consumption'] ?? 0), 2, ',', '.'),
            ]),
            default => collect(),
        };
        $headers = $rows->first() ? array_keys($rows->first()) : [];
    @endphp

    <div class="hero">
        <div class="hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="eyebrow">{{ $labName }}</div>
                        <h1>{{ $title }}</h1>
                        <div>Inventory analytics export · Consumo, stock e alertas operacionais</div>
                    </td>
                    <td class="meta">
                        Emitido em {{ $generated_at->format('d/m/Y H:i') }}<br>
                        Tipo: {{ $chartType }}<br>
                        {{ $rows->count() }} registo(s)
                    </td>
                </tr>
            </table>
        </div>
        <div class="hero-body">
            Exportação controlada dos indicadores de inventário para apoiar rastreabilidade, planeamento de compras, validade de reagentes e continuidade operacional.
        </div>
    </div>

    <table class="metric-grid">
        <tr>
            <td><span class="metric-label">Consumo total</span><span class="metric-value">{{ number_format((float) $metrics->get('totalConsumption', 0), 2, ',', '.') }}</span></td>
            <td><span class="metric-label">Média diária</span><span class="metric-value">{{ number_format((float) $metrics->get('dailyAverage', 0), 2, ',', '.') }}</span></td>
            <td><span class="metric-label">Alertas reposição</span><span class="metric-value">{{ (int) $metrics->get('reorderAlerts', 0) }}</span></td>
            <td><span class="metric-label">Alertas críticos</span><span class="metric-value">{{ (int) $metrics->get('criticalAlerts', 0) }}</span></td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Dados exportados</div>
        @if($rows->isNotEmpty())
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($headers as $header)
                                <td>{{ $row[$header] ?? 'N/A' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="note-box">Sem dados para os filtros selecionados.</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Filtros aplicados</div>
        <div class="note-box">
            @forelse(collect($filters ?? [])->filter(fn ($value) => filled($value)) as $key => $value)
                <strong>{{ $key }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}@if(! $loop->last) · @endif
            @empty
                Sem filtros adicionais.
            @endforelse
        </div>
    </div>

    <div class="footer">
        Documento controlado gerado pelo sistema. Palavras-chave: {{ $settings->app_document_keywords ?: 'inventário; consumo; stock; ISO 17025; rastreabilidade' }}.<br>
        Controlled inventory analytics evidence generated by {{ $labName }}.
    </div>
</body>
</html>
