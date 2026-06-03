<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Comparação de Revisões ISO 17025</title>
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
            background: #7c3aed;
            border-radius: 16px 16px 0 0;
            padding: 15px 17px;
        }

        .hero-body {
            color: #ede9fe;
            padding: 12px 17px 15px;
        }

        .eyebrow {
            color: #ddd6fe;
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
            color: #ede9fe;
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

        .comparison-table,
        .data-table {
            border-collapse: collapse;
            width: 100%;
        }

        .comparison-table td {
            background: #f8fafc;
            border: 1px solid #dbe4f0;
            padding: 10px;
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
            font-size: 11px;
            font-weight: 800;
            margin-top: 3px;
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
                        <h1>Comparação de Revisões ISO 17025</h1>
                        <div>Revision comparison · Análise controlada de alterações</div>
                    </td>
                    <td class="meta">
                        Certificado: {{ $certificate->code ?? 'N/A' }}<br>
                        {{ $revisionA->version }} vs {{ $revisionB->version }}
                    </td>
                </tr>
            </table>
        </div>
        <div class="hero-body">
            Comparação estruturada para suportar decisão de aprovação, impacto técnico e rastreabilidade documental.
        </div>
    </div>

    <div class="section">
        <div class="section-title">Revisões Comparadas</div>
        <table class="comparison-table">
            <tr>
                <td>
                    <span class="label">Revisão A</span>
                    <span class="value">{{ $revisionA->version }}</span>
                    <br>{{ $revisionA->change_type }}<br>{{ $revisionA->change_reason }}
                </td>
                <td>
                    <span class="label">Revisão B</span>
                    <span class="value">{{ $revisionB->version }}</span>
                    <br>{{ $revisionB->change_type }}<br>{{ $revisionB->change_reason }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Diferenças</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Secção</th>
                    <th>Alteração</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($differences as $section => $changes)
                    <tr>
                        <td>{{ is_string($section) ? $section : 'Diferença' }}</td>
                        <td>
                            @if (is_array($changes))
                                <pre style="white-space: pre-wrap; margin: 0;">{{ json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                {{ $changes }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center;">Nenhuma diferença detectada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Documento controlado gerado pelo sistema. Palavras-chave: {{ $settings->app_document_keywords ?: 'ISO 17025; comparação; revisão; evidência' }}.
    </div>
</body>
</html>
