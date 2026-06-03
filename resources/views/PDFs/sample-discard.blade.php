<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Certificado de Descarte de Amostra - {{ $discard->sample->code ?? $discard->id }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @page {
            margin: 18mm 16mm;
        }

        body {
            color: #1f2937;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
        }

        .document-hero {
            background: #111827;
            border-radius: 16px 16px 10px 10px;
            color: #ffffff;
            margin-bottom: 18px;
            padding: 0;
        }

        .document-hero-top {
            background: #b91c1c;
            border-radius: 16px 16px 0 0;
            padding: 16px 18px;
        }

        .document-hero-body {
            padding: 16px 18px 18px;
        }

        .hero-label {
            color: #fecaca;
            font-size: 9px;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .hero-title {
            font-size: 22px;
            font-weight: 800;
            margin: 5px 0 3px;
        }

        .hero-subtitle {
            color: #fee2e2;
            font-size: 10px;
        }

        .hero-meta {
            color: #fee2e2;
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
        }

        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 14px;
            color: #7f1d1d;
            font-weight: 800;
            margin: 16px 0 18px;
            padding: 12px 14px;
            text-align: center;
        }

        .barcode {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            color: #991b1b;
            font-family: monospace;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 4px;
            margin: 16px 0 18px;
            padding: 12px;
            text-align: center;
        }

        .section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            margin-bottom: 14px;
            padding: 14px;
        }

        .section-title {
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }

        .section-title span {
            color: #6b7280;
            display: block;
            font-size: 8px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .details-table {
            border-collapse: collapse;
            width: 100%;
        }

        .details-table td {
            border-bottom: 1px solid #f1f5f9;
            padding: 7px 6px;
            vertical-align: top;
            width: 50%;
        }

        .details-table tr:last-child td {
            border-bottom: 0;
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
            font-weight: 700;
            margin-top: 3px;
        }

        .note-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #334155;
            line-height: 1.55;
            padding: 10px 12px;
        }

        .method-badge {
            background: #fee2e2;
            border-radius: 999px;
            color: #991b1b;
            display: inline-block;
            font-size: 9px;
            font-weight: 800;
            margin-top: 5px;
            padding: 4px 9px;
            text-transform: uppercase;
        }

        .stamp {
            border: 3px solid #b91c1c;
            border-radius: 999px;
            color: #b91c1c;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.4;
            margin: 18px auto 6px;
            padding: 22px 10px;
            text-align: center;
            width: 128px;
        }

        .signature-table {
            border-collapse: collapse;
            margin-top: 22px;
            width: 100%;
        }

        .signature-table td {
            padding: 18px 12px 0;
            width: 50%;
        }

        .signature-line {
            border-top: 1px solid #94a3b8;
            color: #64748b;
            font-size: 9px;
            padding-top: 6px;
            text-align: center;
        }

        .footer {
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 9px;
            line-height: 1.5;
            margin-top: 22px;
            padding-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body class="pdf-document certificate-document">
    @php
        $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
        $labName = $settings->app_client_lab_name ?: ($settings->app_name ?: config('app.name'));
        $certificateCode = 'DISC-' . str_pad((string) $discard->id, 6, '0', STR_PAD_LEFT);
        $discardedAt = $discard->discarded_at ?: now();
        $sample = $discard->sample;
    @endphp

    <div class="document-hero">
        <div class="document-hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="hero-label">{{ $labName }}</div>
                        <div class="hero-title">Certificado de Descarte de Amostra</div>
                        <div class="hero-subtitle">Sample discard certificate · Retenção e eliminação controlada</div>
                    </td>
                    <td class="hero-meta">
                        <strong>{{ $certificateCode }}</strong><br>
                        Emitido em {{ $date }} · {{ $time }}<br>
                        Amostra: {{ $sample->code ?? 'N/A' }}
                    </td>
                </tr>
            </table>
        </div>
        <div class="document-hero-body">
            Este certificado documenta a eliminação permanente da amostra, mantendo rastreabilidade, responsável, método e data da acção.
        </div>
    </div>

    <div class="alert-box">
        AMOSTRA DESCARTADA DE FORMA PERMANENTE / SAMPLE PERMANENTLY DISCARDED
    </div>

    <div class="barcode">*{{ $certificateCode }}*</div>

    <div class="section">
        <div class="section-title">Informação do Descarte <span>Discard information</span></div>
        <table class="details-table">
            <tr>
                <td>
                    <span class="label">Método / Method</span>
                    <span class="value">{{ $discard->discard_method ?: 'N/A' }}</span>
                    <span class="method-badge">{{ $discard->discard_method ?: 'Método não informado' }}</span>
                </td>
                <td><span class="label">Quantidade / Quantity</span><span class="value">{{ $discard->qty ?: 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Data / Date</span><span class="value">{{ $discardedAt->format('d/m/Y H:i') }}</span></td>
                <td><span class="label">Responsável / Performed by</span><span class="value">{{ $discard->discardedBy->name ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Certificado / Certificate</span><span class="value">{{ $certificateCode }}</span></td>
                <td><span class="label">Estado documental / Document status</span><span class="value">Final / Final</span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Amostra Original <span>Original sample information</span></div>
        <table class="details-table">
            <tr>
                <td><span class="label">Código / Code</span><span class="value">{{ $sample->code ?? 'N/A' }}</span></td>
                <td><span class="label">Nome / Name</span><span class="value">{{ $sample->name ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Tipo / Type</span><span class="value">{{ $sample->sample_type ?? 'N/A' }}</span></td>
                <td><span class="label">Estado original / Original status</span><span class="value">{{ $sample->status ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Cliente / Customer</span><span class="value">{{ $sample->customer->name ?? 'N/A' }}</span></td>
                <td><span class="label">Recebida em / Received at</span><span class="value">{{ $sample?->received_at?->format('d/m/Y H:i') ?: 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Laboratório e Rastreabilidade <span>Laboratory and traceability</span></div>
        <table class="details-table">
            <tr>
                <td><span class="label">Laboratório / Laboratory</span><span class="value">{{ $discard->lab->name ?? ($sample->lab->name ?? $labName) }}</span></td>
                <td><span class="label">Departamento / Department</span><span class="value">{{ $discard->department->name ?? ($sample->department->name ?? 'N/A') }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Documento relacionado / Related record</span><span class="value">{{ $sample->code ?? 'N/A' }}</span></td>
                <td><span class="label">Palavras-chave / Keywords</span><span class="value">{{ $settings->app_document_keywords ?: 'rastreabilidade; retenção; descarte; ISO 17025' }}</span></td>
            </tr>
        </table>
        <div class="note-box" style="margin-top: 10px;">
            A eliminação deve estar alinhada com os procedimentos internos, requisitos de segurança, regras ambientais aplicáveis e cadeia de custódia da amostra.
        </div>
    </div>

    <table class="signature-table">
        <tr>
            <td><div class="signature-line">Executado por / Performed by: {{ $discard->discardedBy->name ?? 'N/A' }}</div></td>
            <td><div class="signature-line">Validação da qualidade / Quality validation</div></td>
        </tr>
    </table>

    <div class="stamp">
        DESCARTADA<br>
        {{ $discardedAt->format('d/m/Y') }}<br>
        FINAL
    </div>

    <div class="footer">
        Documento controlado gerado pelo sistema em {{ $date }} às {{ $time }}. Código de rastreabilidade: {{ $certificateCode }}.<br>
        This certificate confirms permanent discard according to laboratory procedures and must remain linked to the original sample record.
    </div>
</body>
</html>
