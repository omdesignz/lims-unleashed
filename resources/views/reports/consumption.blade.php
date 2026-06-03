<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Relatório de Consumo de Inventário</title>
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
            background: #2563eb;
            border-radius: 16px 16px 0 0;
            padding: 15px 17px;
        }

        .hero-body {
            color: #dbeafe;
            padding: 12px 17px 15px;
        }

        .eyebrow {
            color: #bfdbfe;
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
            color: #dbeafe;
            font-size: 9px;
            text-align: right;
            white-space: nowrap;
        }

        .stats-table,
        .data-table {
            border-collapse: collapse;
            width: 100%;
        }

        .stats-table td {
            background: #f8fafc;
            border: 1px solid #dbe4f0;
            padding: 9px;
            width: 33.33%;
        }

        .stat-label {
            color: #64748b;
            display: block;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .stat-value {
            color: #0f172a;
            display: block;
            font-size: 13px;
            font-weight: 800;
            margin-top: 3px;
        }

        .section {
            background: #ffffff;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            margin-top: 14px;
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

        .text-right {
            text-align: right;
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
<body class="pdf-document report-document">
    @php
        $settings = app(\App\Settings\GeneralSettings::class);
        $labName = $settings->app_client_lab_name ?: ($settings->app_name ?: config('app.name'));
        $metrics = collect($data['metrics'] ?? []);
        $topItems = collect($data['topItems'] ?? $data['topReagents'] ?? []);
        $totalConsumption = (float) $metrics->get('totalConsumption', 0);
    @endphp

    <div class="hero">
        <div class="hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="eyebrow">{{ $labName }}</div>
                        <h1>Relatório de Consumo de Inventário</h1>
                        <div>Inventory consumption report · Reagentes e materiais</div>
                    </td>
                    <td class="meta">
                        {{ $dateRange['start']->format('d/m/Y') }} - {{ $dateRange['end']->format('d/m/Y') }}<br>
                        Emitido em {{ now()->format('d/m/Y H:i') }}
                    </td>
                </tr>
            </table>
        </div>
        <div class="hero-body">
            Indicadores de consumo para apoiar reposição, gestão de validade, rastreabilidade e continuidade operacional.
        </div>
    </div>

    <table class="stats-table">
        <tr>
            <td><span class="stat-label">Consumo total</span><span class="stat-value">{{ number_format($totalConsumption, 2, ',', '.') }}</span></td>
            <td><span class="stat-label">Média diária</span><span class="stat-value">{{ number_format((float) $metrics->get('dailyAverage', 0), 2, ',', '.') }}</span></td>
            <td><span class="stat-label">Valor inventário</span><span class="stat-value">AOA {{ number_format((float) $metrics->get('inventoryValue', 0), 2, ',', '.') }}</span></td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Reagentes com Maior Consumo</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reagente</th>
                    <th class="text-right">Quantidade consumida</th>
                    <th class="text-right">% do total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topItems as $item)
                    @php $consumption = (float) ($item['consumption'] ?? 0); @endphp
                    <tr>
                        <td>{{ $item['name'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($consumption, 2, ',', '.') }}</td>
                        <td class="text-right">{{ $totalConsumption > 0 ? number_format(($consumption / $totalConsumption) * 100, 1, ',', '.') : '0,0' }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center;">Sem dados de consumo para o período selecionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Documento controlado gerado pelo sistema. Palavras-chave: {{ $settings->app_document_keywords ?: 'inventário; consumo; rastreabilidade; ISO 17025' }}.
    </div>
</body>
</html>
