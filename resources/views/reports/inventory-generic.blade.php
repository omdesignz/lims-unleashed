<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Relatório de Inventário</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @page {
            margin: 16mm 14mm;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .hero {
            background: #0f172a;
            border-radius: 16px 16px 10px 10px;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .hero-top {
            background: #334155;
            border-radius: 16px 16px 0 0;
            padding: 15px 17px;
        }

        .hero-body {
            color: #e2e8f0;
            padding: 12px 17px 15px;
        }

        .eyebrow {
            color: #cbd5e1;
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
            color: #e2e8f0;
            font-size: 9px;
            text-align: right;
            white-space: nowrap;
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

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #0f172a;
            border: 1px solid #0f172a;
            color: #ffffff;
            font-size: 8px;
            padding: 8px 6px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #e2e8f0;
            padding: 7px 6px;
            vertical-align: top;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .muted {
            color: #64748b;
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
        $title = ucfirst(str_replace('_', ' ', $reportType ?? 'inventory'));
    @endphp

    <div class="hero">
        <div class="hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="eyebrow">{{ $labName }}</div>
                        <h1>Relatório de {{ $title }}</h1>
                        <div>Inventory operational report · Relatório operacional de inventário</div>
                    </td>
                    <td class="meta">
                        Emitido em {{ now()->format('d/m/Y H:i') }}<br>
                        @if(!empty($dateRange['start']) || !empty($dateRange['end']))
                            {{ optional($dateRange['start'] ?? null)->format('d/m/Y') }} - {{ optional($dateRange['end'] ?? null)->format('d/m/Y') }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        <div class="hero-body">
            Documento controlado para apoio à gestão de stock, consumo, validade, calibração e rastreabilidade.
        </div>
    </div>

    @foreach(($data ?? []) as $section => $value)
        <div class="section">
            <div class="section-title">{{ ucfirst(str_replace('_', ' ', $section)) }}</div>

            @php
                $normalized = $value instanceof \Illuminate\Support\Collection ? $value : collect(is_array($value) ? $value : []);
                $first = $normalized->first();
            @endphp

            @if($normalized->isNotEmpty() && (is_array($first) || is_object($first)))
                @php
                    $firstArray = is_array($first) ? $first : (method_exists($first, 'toArray') ? $first->toArray() : get_object_vars($first));
                    $headings = array_slice(array_keys($firstArray), 0, 8);
                @endphp
                <table>
                    <thead>
                        <tr>
                            @foreach($headings as $heading)
                                <th>{{ ucfirst(str_replace('_', ' ', $heading)) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($normalized as $row)
                            @php $rowArray = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : get_object_vars($row)); @endphp
                            <tr>
                                @foreach($headings as $heading)
                                    @php $cell = $rowArray[$heading] ?? null; @endphp
                                    <td>{{ is_scalar($cell) || is_null($cell) ? ($cell ?? 'N/A') : json_encode($cell, JSON_UNESCAPED_UNICODE) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif(is_array($value) && count($value) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($value as $itemKey => $itemValue)
                            <tr>
                                <td>{{ $itemKey }}</td>
                                <td>{{ is_scalar($itemValue) || is_null($itemValue) ? ($itemValue ?? 'N/A') : json_encode($itemValue, JSON_UNESCAPED_UNICODE) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">{{ is_scalar($value) ? $value : 'Sem registos para esta secção.' }}</p>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Documento controlado gerado pelo sistema. Palavras-chave: {{ $settings->app_document_keywords ?: 'inventário; relatório; rastreabilidade; ISO 17025' }}.
    </div>
</body>
</html>
