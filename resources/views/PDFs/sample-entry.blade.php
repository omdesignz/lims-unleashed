<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Registo de Entrada de Amostra - {{ $sample->code }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @page {
            margin: 18mm 16mm;
        }

        body {
            color: #14213d;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
        }

        .document-hero {
            background: #0f172a;
            border-radius: 16px 16px 10px 10px;
            color: #ffffff;
            margin-bottom: 18px;
            padding: 0;
        }

        .document-hero-top {
            background: #1d4ed8;
            border-radius: 16px 16px 0 0;
            padding: 16px 18px;
        }

        .document-hero-body {
            padding: 16px 18px 18px;
        }

        .hero-label {
            color: #bfdbfe;
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
            color: #dbeafe;
            font-size: 10px;
        }

        .hero-meta {
            color: #dbeafe;
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
        }

        .barcode {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            color: #1e3a8a;
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
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            margin-bottom: 14px;
            padding: 14px;
        }

        .section-title {
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }

        .section-title span {
            color: #64748b;
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
            border-bottom: 1px solid #eef2f7;
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
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            color: #334155;
            line-height: 1.55;
            padding: 10px 12px;
        }

        .status-badge {
            background: #dbeafe;
            border-radius: 999px;
            color: #1e3a8a;
            display: inline-block;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .6px;
            margin-top: 8px;
            padding: 5px 10px;
            text-transform: uppercase;
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
            border-top: 1px solid #dbe4f0;
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
        $labDetails = collect([
            $settings->app_client_name,
            $settings->app_client_lab_province,
            $settings->app_contact,
            $settings->app_email,
        ])->filter()->implode(' · ');
        $documentCode = 'ENT-' . ($sample->code ?: str_pad((string) $sample->id, 6, '0', STR_PAD_LEFT));
        $intakeData = collect($sample->client_submitted_info ?? []);
        $resolvedProfiles = collect($intakeData->get('resolved_profiles', []));
        $requiredParameters = collect($intakeData->get('required_parameters', []));
        $conditioningLabels = [
            'accepted' => 'Aceite / Accepted',
            'restricted' => 'Aceite com restrições / Accepted with restrictions',
            'rejected' => 'Rejeitada ou em quarentena / Rejected or quarantined',
        ];
        $requestedServices = collect($sample->requested_services ?? [])
            ->map(fn ($service) => is_array($service) ? ($service['name'] ?? $service['label'] ?? implode(' - ', array_filter($service))) : $service)
            ->filter()
            ->implode('; ');
    @endphp

    <div class="document-hero">
        <div class="document-hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="hero-label">{{ $labName }}</div>
                        <div class="hero-title">Registo de Entrada de Amostra</div>
                        <div class="hero-subtitle">Sample entry record · Cadeia de custódia inicial</div>
                    </td>
                    <td class="hero-meta">
                        <strong>{{ $documentCode }}</strong><br>
                        Emitido em {{ $date }} · {{ $time }}<br>
                        Estado: {{ $sample->status ?: 'N/A' }}
                    </td>
                </tr>
            </table>
        </div>
        <div class="document-hero-body">
            Este documento regista a recepção, identificação, condições iniciais e rastreabilidade da amostra antes do processamento analítico.
            <div class="status-badge">{{ $sample->status ?: 'Por classificar' }}</div>
        </div>
    </div>

    <div class="barcode">*{{ $sample->code }}*</div>

    <div class="section">
        <div class="section-title">Informação da Amostra <span>Sample information</span></div>
        <table class="details-table">
            <tr>
                <td><span class="label">Nome / Name</span><span class="value">{{ $sample->name ?: 'N/A' }}</span></td>
                <td><span class="label">Tipo / Type</span><span class="value">{{ $sample->sample_type ?: 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Código / Code</span><span class="value">{{ $sample->code ?: 'N/A' }}</span></td>
                <td><span class="label">Recebida em / Received at</span><span class="value">{{ $sample->received_at?->format('d/m/Y H:i') ?: 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Produto de recolha / Collection product</span><span class="value">{{ $sample->collectionProduct->code ?? ($sample->collectionProduct->name ?? 'N/A') }}</span></td>
                <td><span class="label">Proposta / Proposal</span><span class="value">{{ $sample->proposal->code ?? ($sample->proposal_id ? '#'.$sample->proposal_id : 'N/A') }}</span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Cliente e Laboratório <span>Customer and laboratory</span></div>
        <table class="details-table">
            <tr>
                <td><span class="label">Cliente / Customer</span><span class="value">{{ $sample->customer->name ?? 'N/A' }}</span></td>
                <td><span class="label">Código do cliente / Customer code</span><span class="value">{{ $sample->customer->code ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Laboratório / Laboratory</span><span class="value">{{ $sample->lab->name ?? $labName }}</span></td>
                <td><span class="label">Departamento / Department</span><span class="value">{{ $sample->department->name ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Armazém / Warehouse</span><span class="value">{{ $sample->warehouse->name ?? 'N/A' }}</span></td>
                <td><span class="label">Embalagem / Packaging</span><span class="value">{{ $sample->packaging->name ?? 'N/A' }}</span></td>
            </tr>
        </table>
        @if($labDetails)
            <div class="note-box" style="margin-top: 10px;">{{ $labDetails }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Cronologia Analítica <span>Analysis timeline</span></div>
        <table class="details-table">
            <tr>
                <td><span class="label">Início / Start</span><span class="value">{{ $sample->analysis_start_date?->format('d/m/Y H:i') ?: 'Não iniciado / Not started' }}</span></td>
                <td><span class="label">Fim / End</span><span class="value">{{ $sample->analysis_end_date?->format('d/m/Y H:i') ?: 'Não concluído / Not completed' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Recolhida pelo laboratório / Collected by lab</span><span class="value">{{ $sample->collected_by_lab ? 'Sim / Yes' : 'Não / No' }}</span></td>
                <td><span class="label">Data de recolha / Collection date</span><span class="value">{{ $sample->collected_at?->format('d/m/Y H:i') ?: 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    @if($requestedServices)
        <div class="section">
            <div class="section-title">Serviços Solicitados <span>Requested services</span></div>
            <div class="note-box">{{ $requestedServices }}</div>
        </div>
    @endif

    @if($resolvedProfiles->isNotEmpty() || $requiredParameters->isNotEmpty())
        <div class="section">
            <div class="section-title">Âmbito Analítico Planeado <span>Planned analytical scope</span></div>
            <table class="details-table">
                <tr>
                    <td><span class="label">Perfis resolvidos / Resolved profiles</span><span class="value">{{ $resolvedProfiles->pluck('name')->filter()->implode(', ') ?: 'N/A' }}</span></td>
                    <td><span class="label">Parâmetros requeridos / Required parameters</span><span class="value">{{ $requiredParameters->count() }}</span></td>
                </tr>
            </table>
            @if($requiredParameters->isNotEmpty())
                <div class="note-box" style="margin-top: 10px;">
                    {{ $requiredParameters->map(fn ($parameter) => ($parameter['code'] ?? 'N/A') . ' - ' . ($parameter['name'] ?? ''))->implode('; ') }}
                </div>
            @endif
        </div>
    @endif

    @if($intakeData->filter()->isNotEmpty())
        <div class="section">
            <div class="section-title">Avaliação de Recepção e Condicionamento <span>Reception and conditioning assessment</span></div>
            <table class="details-table">
                <tr>
                    <td><span class="label">Decisão / Decision</span><span class="value">{{ $conditioningLabels[$intakeData->get('conditioning_status')] ?? 'Não avaliado / Not evaluated' }}</span></td>
                    <td><span class="label">Embalagem / Packaging condition</span><span class="value">{{ $intakeData->get('packaging_condition') ?: 'N/A' }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Condição térmica / Thermal condition</span><span class="value">{{ $intakeData->get('temperature_condition') ?: 'N/A' }}</span></td>
                    <td><span class="label">Cadeia de custódia / Chain of custody</span><span class="value">{{ $intakeData->get('chain_of_custody_notes') ?: 'N/A' }}</span></td>
                </tr>
            </table>
            @if($intakeData->get('integrity_observations'))
                <div class="note-box" style="margin-top: 10px;">{{ $intakeData->get('integrity_observations') }}</div>
            @endif
        </div>
    @endif

    @if($sample->obs)
        <div class="section">
            <div class="section-title">Observações <span>Observations</span></div>
            <div class="note-box">{{ $sample->obs }}</div>
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td><div class="signature-line">Recebido por / Received by: {{ $sample->received_by_label ?: ($sample->receivedBy->name ?? 'N/A') }}</div></td>
            <td><div class="signature-line">Validação técnica / Technical validation</div></td>
        </tr>
    </table>

    <div class="footer">
        Documento controlado gerado pelo sistema em {{ $date }} às {{ $time }}. Código de rastreabilidade: {{ $documentCode }}.<br>
        Controlled document generated by the system. The sample entry code must be referenced throughout collection, analysis, verification and reporting.
    </div>
</body>
</html>
