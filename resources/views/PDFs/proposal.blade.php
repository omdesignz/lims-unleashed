<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Proposta Comercial - {{ $model->proposal_no }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @page {
            margin: 16mm 14mm 18mm;
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
            background: #1d4ed8;
            border-radius: 16px 16px 0 0;
            padding: 16px 18px;
        }

        .hero-body {
            color: #dbeafe;
            padding: 13px 18px 16px;
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
            font-size: 22px;
            margin: 4px 0 2px;
        }

        .meta {
            color: #dbeafe;
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

        .section-title span {
            color: #64748b;
            display: block;
            font-size: 8px;
            font-weight: 600;
            letter-spacing: .8px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .details-table,
        .items-table,
        .totals-table {
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

        .items-table th {
            background: #0f172a;
            border: 1px solid #0f172a;
            color: #ffffff;
            font-size: 8px;
            letter-spacing: .4px;
            padding: 8px 6px;
            text-align: left;
            text-transform: uppercase;
        }

        .items-table td {
            border: 1px solid #e2e8f0;
            padding: 7px 6px;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .totals-table {
            margin-left: auto;
            margin-top: 10px;
            width: 46%;
        }

        .totals-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 6px;
        }

        .total-row td {
            background: #0f172a;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
        }

        .note-box {
            background: #f8fafc;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            color: #334155;
            line-height: 1.55;
            padding: 10px 12px;
        }

        .signature-table {
            border-collapse: collapse;
            margin-top: 20px;
            width: 100%;
        }

        .signature-table td {
            padding: 22px 12px 0;
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
        $labName = $settings->app_client_lab_name ?: ($settings->app_name ?: config('app.name'));
        $labDetails = collect([
            $settings->app_client_name,
            $settings->app_client_nif ? 'NIF '.$settings->app_client_nif : null,
            $settings->app_client_address,
            $settings->app_contact,
            $settings->app_email,
        ])->filter()->implode(' · ');
        $bankDetails = collect([
            $settings->app_bank_name,
            $settings->app_bank_account_name,
            $settings->app_bank_account_number ? 'Conta '.$settings->app_bank_account_number : null,
            $settings->app_bank_iban ? 'IBAN '.$settings->app_bank_iban : null,
            $settings->app_bank_swift ? 'SWIFT '.$settings->app_bank_swift : null,
            $settings->app_bank_details,
        ])->filter()->implode(' · ');
        $statusValue = $model->status instanceof \BackedEnum ? $model->status->value : (string) $model->status;
        $subtotal = (float) ($model->sub_total ?? $model->items->sum('total'));
        $taxTotal = (float) $model->items->sum(fn ($item) => (float) ($item->tax_amount ?? 0));
        $discountTotal = (float) ($model->global_discount_amount ?? 0) + (float) $model->items->sum(fn ($item) => (float) ($item->discount_amount ?? 0));
        $withholdingTotal = (float) ($model->withholding_tax_amount ?? 0);
        $total = (float) ($model->total ?? ($subtotal + $taxTotal - $discountTotal - $withholdingTotal));
        $details = $model->details;
        $detailsText = is_array($details)
            ? collect($details)->flatten()->filter()->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))->implode("\n")
            : (string) $details;
    @endphp

    <div class="hero">
        <div class="hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="eyebrow">{{ $labName }}</div>
                        <h1>Proposta Comercial</h1>
                        <div>Commercial proposal · Termos de ensaio, decisão e aceitação</div>
                    </td>
                    <td class="meta">
                        <strong>{{ $model->proposal_no }}</strong><br>
                        Emitida em {{ $model->created_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}<br>
                        Validade: {{ $model->expiry_date ? \Illuminate\Support\Carbon::parse($model->expiry_date)->format('d/m/Y') : 'N/A' }}
                    </td>
                </tr>
            </table>
        </div>
        <div class="hero-body">
            Esta proposta descreve o âmbito acordado para prestação de serviços laboratoriais, incluindo itens, decisão comercial, confidencialidade, imparcialidade e condições de aceitação.
        </div>
    </div>

    <div class="section">
        <div class="section-title">Cliente e Laboratório <span>Client and laboratory details</span></div>
        <table class="details-table">
            <tr>
                <td><span class="label">Cliente / Client</span><span class="value">{{ $model->customer->name ?? 'N/A' }}</span></td>
                <td><span class="label">Código / Code</span><span class="value">{{ $model->customer->code ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Local de serviço / Service location</span><span class="value">{{ $model->service_location ?: ($model->warehouse->address ?? 'N/A') }}</span></td>
                <td><span class="label">Departamento / Department</span><span class="value">{{ $model->department->name ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Laboratório / Laboratory</span><span class="value">{{ $labName }}</span></td>
                <td><span class="label">Preparada por / Prepared by</span><span class="value">{{ $model->user->name ?? 'N/A' }}</span></td>
            </tr>
        </table>
        @if($labDetails)
            <div class="note-box" style="margin-top: 10px;">{{ $labDetails }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Itens da Proposta <span>Commercial scope</span></div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Norma</th>
                    <th>Unid.</th>
                    <th style="text-align: right;">Qtd.</th>
                    <th style="text-align: right;">Preço</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($model->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->item_description ?: ('Item #'.$item->item_id) }}</strong>
                            @if($item->obs)
                                <br><span style="color: #64748b;">{{ $item->obs }}</span>
                            @endif
                        </td>
                        <td>{{ $item->standard->name ?? $item->standard->code ?? 'N/A' }}</td>
                        <td>{{ $item->unit->code ?? $item->unit->name ?? 'N/A' }}</td>
                        <td style="text-align: right;">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format((float) $item->total, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center;">Sem itens associados a esta proposta.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals-table">
            <tr><td>Subtotal</td><td style="text-align: right;">AOA {{ number_format($subtotal, 2, ',', '.') }}</td></tr>
            <tr><td>Descontos</td><td style="text-align: right;">AOA {{ number_format($discountTotal, 2, ',', '.') }}</td></tr>
            <tr><td>Impostos</td><td style="text-align: right;">AOA {{ number_format($taxTotal, 2, ',', '.') }}</td></tr>
            <tr><td>Retenção</td><td style="text-align: right;">AOA {{ number_format($withholdingTotal, 2, ',', '.') }}</td></tr>
            <tr class="total-row"><td>Total</td><td style="text-align: right;">AOA {{ number_format($total, 2, ',', '.') }}</td></tr>
        </table>
    </div>

    @if(trim(strip_tags($detailsText)) !== '')
        <div class="section">
            <div class="section-title">Condições e Observações <span>Terms and observations</span></div>
            <div class="note-box">{!! nl2br(e(strip_tags($detailsText))) !!}</div>
        </div>
    @endif

    <div class="section">
        <div class="section-title">Aceitação, Decisão e Conformidade <span>Acceptance, decision rule and compliance</span></div>
        <table class="details-table">
            <tr>
                <td><span class="label">Estado / Status</span><span class="value">{{ $statusValue ?: 'N/A' }}</span></td>
                <td><span class="label">Tolerância / Tolerance</span><span class="value">{{ $model->tolerance_days ?? 0 }} dia(s)</span></td>
            </tr>
            <tr>
                <td><span class="label">Confidencialidade / Confidentiality</span><span class="value">{{ $model->complianceAgreement?->confidentiality ? 'Aceite / Accepted' : 'Pendente / Pending' }}</span></td>
                <td><span class="label">Imparcialidade / Impartiality</span><span class="value">{{ $model->complianceAgreement?->impartiality ? 'Aceite / Accepted' : 'Pendente / Pending' }}</span></td>
            </tr>
        </table>
        <div class="note-box" style="margin-top: 10px;">
            A aceitação formal desta proposta confirma o âmbito contratado, condições comerciais, requisitos de confidencialidade/imparcialidade e base para execução dos ensaios. Quando aplicável, a regra de decisão e incerteza de medição devem constar do relatório analítico emitido.
        </div>
    </div>

    @if($bankDetails)
        <div class="section">
            <div class="section-title">Dados Bancários <span>Banking details</span></div>
            <div class="note-box">{{ $bankDetails }}</div>
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td><div class="signature-line">Pelo laboratório / For the laboratory</div></td>
            <td><div class="signature-line">Aceite pelo cliente / Accepted by client</div></td>
        </tr>
    </table>

    <div class="footer">
        Documento controlado gerado pelo sistema. Palavras-chave: {{ $settings->app_document_keywords ?: 'proposta; ISO 17025; confidencialidade; imparcialidade; rastreabilidade' }}.<br>
        Hash/Referência: {{ $model->unique_hash ?: $model->proposal_no }}.
    </div>
</body>
</html>
