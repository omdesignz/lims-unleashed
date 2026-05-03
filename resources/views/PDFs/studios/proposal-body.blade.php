<div style="font-size: 11px; color: #0f172a;">
    <section style="margin-bottom: 18px;">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:50%; vertical-align: top; padding: 8px; border: 1px solid #cbd5e1;">
                    <strong>Cliente</strong><br>
                    {{ $proposal->customer?->name }}<br>
                    {{ $proposal->service_location }}
                </td>
                <td style="width:50%; vertical-align: top; padding: 8px; border: 1px solid #cbd5e1;">
                    <strong>Condições</strong><br>
                    Validade: {{ optional($proposal->expiry_date)->format('d/m/Y') ?: 'Não definida' }}<br>
                    Tolerância: {{ $proposal->tolerance_days }} dias<br>
                    Regra de decisão: conforme proposta aprovada
                </td>
            </tr>
        </table>
    </section>

    <section>
        {!! $parsedContent !!}
    </section>
</div>
