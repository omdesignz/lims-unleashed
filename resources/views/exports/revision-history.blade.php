<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Revisões ISO 17025</title>
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
            background: #0f766e;
            border-radius: 16px 16px 0 0;
            padding: 15px 17px;
        }

        .hero-body {
            color: #ccfbf1;
            padding: 12px 17px 15px;
        }

        .eyebrow {
            color: #99f6e4;
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
            color: #ccfbf1;
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

        .details-table,
        .data-table {
            border-collapse: collapse;
            width: 100%;
        }

        .details-table td {
            border-bottom: 1px solid #eef2f7;
            padding: 7px 6px;
            vertical-align: top;
            width: 50%;
        }

        .label {
            color: #64748b;
            display: block;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .value {
            color: #111827;
            display: block;
            font-size: 10px;
            font-weight: 700;
            margin-top: 3px;
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
            vertical-align: top;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f8fafc;
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
    @endphp

    <div class="hero">
        <div class="hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="eyebrow">{{ $labName }}</div>
                        <h1>Histórico de Revisões ISO 17025</h1>
                        <div>ISO 17025 revision history · Controlo documental e rastreabilidade</div>
                    </td>
                    <td class="meta">
                        Emitido em {{ $exportDate->format('d/m/Y H:i') }}<br>
                        Certificado: {{ $certificate->code ?? 'N/A' }}<br>
                        {{ $revisions->count() }} revisão(ões)
                    </td>
                </tr>
            </table>
        </div>
        <div class="hero-body">
            Evidência controlada das alterações, aprovações e vigência associadas ao certificado analítico.
        </div>
    </div>

    <div class="section">
        <div class="section-title">Resumo do Certificado</div>
        <table class="details-table">
            <tr>
                <td><span class="label">Código / Code</span><span class="value">{{ $certificate->code ?? 'N/A' }}</span></td>
                <td><span class="label">Versão actual / Current version</span><span class="value">{{ $certificate->current_version ?? '1.0' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Cliente / Customer</span><span class="value">{{ optional($certificate->customer)->name ?? 'N/A' }}</span></td>
                <td><span class="label">Armazém / Warehouse</span><span class="value">{{ optional($certificate->warehouse)->name ?? 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Revisões</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Versão</th>
                    <th>Tipo</th>
                    <th>Motivo</th>
                    <th>Criado por</th>
                    <th>Aprovado por</th>
                    <th>Vigência</th>
                    <th>Actual</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($revisions as $revision)
                    <tr>
                        <td>{{ $revision->revision_number }}</td>
                        <td>{{ $revision->version }}</td>
                        <td>{{ $revision->change_type }}</td>
                        <td>{{ $revision->change_reason }}</td>
                        <td>{{ optional($revision->createdBy)->name ?? 'N/A' }}</td>
                        <td>{{ optional($revision->approvedBy)->name ?? 'N/A' }}</td>
                        <td>{{ optional($revision->effective_date)?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                        <td>{{ $revision->is_current ? 'Sim / Yes' : 'Não / No' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center;">Sem revisões ISO registadas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Documento controlado gerado pelo sistema. Palavras-chave: {{ $settings->app_document_keywords ?: 'ISO 17025; revisão; certificado; rastreabilidade' }}.
    </div>
</body>
</html>
