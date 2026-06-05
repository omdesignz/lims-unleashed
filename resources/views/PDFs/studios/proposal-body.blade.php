@php
    $labName = $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório';
    $labContact = collect([$settings->app_client_contact, $settings->app_client_email, $settings->app_contact])
        ->filter()
        ->implode(' · ');
    $bankLines = collect([
        $settings->app_bank_name,
        $settings->app_bank_account_name,
        $settings->app_bank_account_number ? 'Conta: '.$settings->app_bank_account_number : null,
        $settings->app_bank_iban ? 'IBAN: '.$settings->app_bank_iban : null,
        $settings->app_bank_swift ? 'SWIFT: '.$settings->app_bank_swift : null,
        $settings->app_bank_details,
    ])->filter();
@endphp

<div style="font-size: 11px; color: #20332f; line-height: 1.55;">
    <section style="margin-bottom: 18px;">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:62%; vertical-align: top; padding: 18px 20px; border: 1px solid #ded3bf; border-radius: 18px; background: #fffdf7;">
                    <div style="font-size: 9px; letter-spacing: 0.18em; text-transform: uppercase; color: #9a7a2f; font-weight: 800;">Proposta comercial</div>
                    <h1 style="margin: 8px 0 6px; font-size: 24px; color: #143d37;">{{ $proposal->proposal_number }}</h1>
                    <div style="color: #58665f;">
                        Emitida em {{ optional($proposal->created_at)->format('d/m/Y') ?: now()->format('d/m/Y') }}
                        · válida até {{ optional($proposal->expiry_date)->format('d/m/Y') ?: 'data por definir' }}
                    </div>
                </td>
                <td style="width:4%;"></td>
                <td style="width:34%; vertical-align: top; padding: 18px 20px; border: 1px solid #ded3bf; border-radius: 18px; background: #143d37; color: #fffdf7;">
                    <div style="font-size: 9px; letter-spacing: 0.18em; text-transform: uppercase; color: #d8b85f; font-weight: 800;">Laboratório</div>
                    <div style="margin-top: 8px; font-size: 14px; font-weight: 800;">{{ $labName }}</div>
                    @if ($settings->app_client_address)
                        <div style="margin-top: 8px; color: #e8efe8;">{{ $settings->app_client_address }}</div>
                    @endif
                    @if ($labContact)
                        <div style="margin-top: 8px; color: #e8efe8;">{{ $labContact }}</div>
                    @endif
                    @if ($settings->app_nif || $settings->app_client_nif)
                        <div style="margin-top: 8px; color: #e8efe8;">NIF: {{ $settings->app_nif ?: $settings->app_client_nif }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </section>

    <section style="margin-bottom: 18px;">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:50%; vertical-align: top; padding: 14px 16px; border: 1px solid #ded3bf; background: #ffffff;">
                    <div style="font-size: 9px; letter-spacing: 0.16em; text-transform: uppercase; color: #9a7a2f; font-weight: 800;">Cliente</div>
                    <div style="margin-top: 8px; font-size: 14px; font-weight: 800; color: #143d37;">{{ $proposal->customer?->name ?: 'Cliente não definido' }}</div>
                    @if ($proposal->customer?->code)
                        <div style="margin-top: 4px; color: #58665f;">Código: {{ $proposal->customer->code }}</div>
                    @endif
                    @if ($proposal->service_location)
                        <div style="margin-top: 8px; color: #58665f;">Local do serviço: {{ $proposal->service_location }}</div>
                    @endif
                </td>
                <td style="width:50%; vertical-align: top; padding: 14px 16px; border: 1px solid #ded3bf; background: #fbf7ee;">
                    <div style="font-size: 9px; letter-spacing: 0.16em; text-transform: uppercase; color: #9a7a2f; font-weight: 800;">Enquadramento</div>
                    <div style="margin-top: 8px;">Departamento: <strong>{{ $proposal->department?->name ?: 'Não definido' }}</strong></div>
                    <div style="margin-top: 4px;">Armazém / unidade: <strong>{{ $proposal->warehouse?->address ?: ($proposal->warehouse?->name ?: 'Não definido') }}</strong></div>
                    <div style="margin-top: 4px;">Modo de preço: <strong>{{ $proposal->use_matrix_price ? 'por matriz' : 'por parâmetro' }}</strong></div>
                    <div style="margin-top: 4px;">Retenção na fonte: <strong>{{ $proposal->withhold_tax ? 'sim' : 'não' }}</strong></div>
                </td>
            </tr>
        </table>
    </section>

    <section style="margin-bottom: 18px;">
        {!! $parsedContent !!}
    </section>

    <section style="margin: 18px 0;">
        <table style="width:100%; border-collapse: collapse; font-size: 10px;">
            <tr style="background: #143d37; color: #fffdf7;">
                <th style="padding: 8px; text-align: left; border: 1px solid #143d37;">Resumo financeiro</th>
                <th style="padding: 8px; text-align: right; border: 1px solid #143d37;">Valor</th>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ded3bf;">Subtotal</td>
                <td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">AOA {{ number_format((float) $proposal->sub_total, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ded3bf;">Desconto</td>
                <td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">AOA {{ number_format((float) $proposal->discount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ded3bf;">Impostos</td>
                <td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">AOA {{ number_format((float) $proposal->tax, 2, ',', '.') }}</td>
            </tr>
            <tr style="background: #fbf7ee;">
                <td style="padding: 10px; border: 1px solid #ded3bf; font-weight: 800; color: #143d37;">Total a pagar</td>
                <td style="padding: 10px; border: 1px solid #ded3bf; text-align: right; font-weight: 800; color: #143d37;">AOA {{ number_format((float) $proposal->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </section>

    <section style="margin: 18px 0;">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:50%; vertical-align: top; padding: 14px 16px; border: 1px solid #ded3bf; background: #fffdf7;">
                    <div style="font-size: 9px; letter-spacing: 0.16em; text-transform: uppercase; color: #9a7a2f; font-weight: 800;">Condições e decisão</div>
                    <div style="margin-top: 8px;">A proposta assume os métodos, critérios de aceitação, regra de decisão e condições acordadas com o cliente antes da execução dos ensaios.</div>
                    <div style="margin-top: 8px;">Prazo de validade: {{ $proposal->tolerance_days }} dias.</div>
                </td>
                <td style="width:50%; vertical-align: top; padding: 14px 16px; border: 1px solid #ded3bf; background: #ffffff;">
                    <div style="font-size: 9px; letter-spacing: 0.16em; text-transform: uppercase; color: #9a7a2f; font-weight: 800;">Dados bancários</div>
                    @forelse ($bankLines as $line)
                        <div style="margin-top: 5px;">{{ $line }}</div>
                    @empty
                        <div style="margin-top: 8px; color: #58665f;">Dados bancários por configurar nas definições da aplicação.</div>
                    @endforelse
                </td>
            </tr>
        </table>
    </section>

    @if ($proposal->obs)
        <section style="margin: 18px 0; padding: 14px 16px; border-left: 4px solid #d8b85f; background: #fbf7ee;">
            <strong>Observações</strong>
            <div style="margin-top: 6px;">{{ $proposal->obs }}</div>
        </section>
    @endif

    @if ($settings->app_document_keywords)
        <section style="margin: 18px 0; color: #58665f; font-size: 9px;">
            <strong>Palavras-chave:</strong> {{ $settings->app_document_keywords }}
        </section>
    @endif

    <section style="margin: 20px 0;">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:50%; vertical-align: top; padding-right: 8px;">
                    {!! $proposalAcceptanceEvidence ?? '' !!}
                </td>
                <td style="width:50%; vertical-align: top; padding-left: 8px;">
                    {!! $proposalAuthenticity ?? '' !!}
                </td>
            </tr>
        </table>
    </section>

    <section style="margin-top: 26px;">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:48%; padding-top: 28px; border-top: 1px solid #143d37; color: #20332f;">
                    <strong>Laboratório</strong><br>
                    Validação técnica / comercial
                </td>
                <td style="width:4%;"></td>
                <td style="width:48%; padding-top: 28px; border-top: 1px solid #143d37; color: #20332f;">
                    <strong>Cliente</strong><br>
                    Aceitação da proposta
                </td>
            </tr>
        </table>
    </section>
</div>
