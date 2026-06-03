<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Necessidade {{ $need->reference }} - {{ $companyName }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
            background: #ffffff;
        }

        .page {
            padding: 28px 34px;
        }

        .hero {
            border: 1px solid #dbeafe;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            border-radius: 18px;
            padding: 22px 24px;
            margin-bottom: 20px;
        }

        .eyebrow {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #0369a1;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px 0;
        }

        .subtitle {
            font-size: 11px;
            color: #475569;
            margin: 0;
            line-height: 1.6;
        }

        .meta-table,
        .items-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px 0;
        }

        .section {
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
        }

        .meta-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .meta-table tr:last-child td {
            border-bottom: none;
        }

        .meta-label {
            width: 26%;
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .meta-value {
            color: #0f172a;
            font-weight: 600;
        }

        .items-table thead th {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 8px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            text-align: left;
        }

        .items-table tbody td {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 8px;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .item-name {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .item-code,
        .muted {
            font-size: 10px;
            color: #64748b;
        }

        .summary-table td {
            border: 1px solid #e2e8f0;
            padding: 12px;
            text-align: center;
        }

        .summary-number {
            display: block;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 6px;
        }

        .summary-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #0f172a;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .footer {
            margin-top: 24px;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body class="pdf-document report-document">
    <div class="page">
        <section class="hero">
            <div class="eyebrow">Need Dossier</div>
            <h1 class="title">{{ $need->reference }}</h1>
            <p class="subtitle">
                Documento executivo para aquisição interna, validação departamental e conversão controlada em pedido de compra.
            </p>
        </section>

        <section class="section">
            <h2 class="section-title">Contexto Operacional</h2>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Estado</td>
                    <td class="meta-value"><span class="badge">{{ $statusLabel }}</span></td>
                    <td class="meta-label">Necessário até</td>
                    <td class="meta-value">{{ optional($need->needed_by_date)->format('d/m/Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Departamento</td>
                    <td class="meta-value">{{ $need->department?->name ?: '—' }}</td>
                    <td class="meta-label">Laboratório</td>
                    <td class="meta-value">{{ $need->lab?->name ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Solicitante</td>
                    <td class="meta-value">{{ $need->requestedBy?->name ?: '—' }}</td>
                    <td class="meta-label">Aprovador</td>
                    <td class="meta-value">{{ $need->approvedBy?->name ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Pedido associado</td>
                    <td class="meta-value">{{ $need->inventoryOrder?->reference ?: 'Ainda não convertido' }}</td>
                    <td class="meta-label">Submetida em</td>
                    <td class="meta-value">{{ optional($need->submitted_at)->format('d/m/Y H:i') ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Justificação</td>
                    <td class="meta-value" colspan="3">{{ $need->justification ?: 'Sem justificação adicional.' }}</td>
                </tr>
                @if(filled($need->approval_notes))
                    <tr>
                        <td class="meta-label">Notas de aprovação</td>
                        <td class="meta-value" colspan="3">{{ $need->approval_notes }}</td>
                    </tr>
                @endif
            </table>
        </section>

        <section class="section">
            <h2 class="section-title">Itens Planeados</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 30%">Item</th>
                        <th style="width: 14%">Armazém</th>
                        <th style="width: 10%">Qtd. Solicitada</th>
                        <th style="width: 10%">Qtd. Aprovada</th>
                        <th style="width: 14%">Preço Estimado</th>
                        <th style="width: 22%">Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($need->items as $item)
                        <tr>
                            <td>
                                <div class="item-name">{{ $item->inventoryItem?->name ?: 'Item sem nome' }}</div>
                                <div class="item-code">{{ $item->inventoryItem?->code ?: 'Sem código' }}</div>
                            </td>
                            <td>{{ $item->warehouse?->name ?: 'A definir' }}</td>
                            <td>{{ $item->quantity_requested }}</td>
                            <td>{{ $item->quantity_approved ?: '—' }}</td>
                            <td>{{ $item->estimated_unit_price ? number_format((float) $item->estimated_unit_price, 2, ',', '.') : '—' }}</td>
                            <td class="muted">{{ $item->notes ?: 'Sem notas adicionais' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="section">
            <h2 class="section-title">Resumo Executivo</h2>
            <table class="summary-table">
                <tr>
                    <td>
                        <span class="summary-label">Linhas</span>
                        <span class="summary-number">{{ $need->items->count() }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Qtd. Solicitada</span>
                        <span class="summary-number">{{ $totalRequestedQuantity }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Qtd. Aprovada</span>
                        <span class="summary-number">{{ $totalApprovedQuantity ?: '—' }}</span>
                    </td>
                    <td>
                        <span class="summary-label">Montante Estimado</span>
                        <span class="summary-number">{{ number_format((float) $estimatedTotalAmount, 2, ',', '.') }}</span>
                    </td>
                </tr>
            </table>
        </section>

        <div class="footer">
            {{ $companyName }} · Documento emitido em {{ $printedDate }} por {{ $printedBy }}
        </div>
    </div>
</body>
</html>
