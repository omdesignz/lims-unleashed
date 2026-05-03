<div style="display:block;">
    <h1 style="font-size:20px; margin-bottom:12px;">Resumo Executivo</h1>

    <table width="100%" cellspacing="0" cellpadding="8" style="border-collapse: collapse; margin-bottom: 18px;">
        <thead>
            <tr>
                <th style="border-bottom:1px solid #cbd5e1; text-align:left; font-size:10px; color:#475569;">Indicador</th>
                <th style="border-bottom:1px solid #cbd5e1; text-align:right; font-size:10px; color:#475569;">Valor</th>
                <th style="border-bottom:1px solid #cbd5e1; text-align:left; font-size:10px; color:#475569;">Contexto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['kpis'] as $kpi)
                <tr>
                    <td style="border-bottom:1px solid #e2e8f0;">{{ $kpi['label'] }}</td>
                    <td style="border-bottom:1px solid #e2e8f0; text-align:right; font-weight:bold;">{{ $kpi['value'] }}</td>
                    <td style="border-bottom:1px solid #e2e8f0; color:#475569;">{{ $kpi['hint'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2 style="font-size:15px; margin:16px 0 8px;">Clientes com maior atividade recente</h2>
    <table width="100%" cellspacing="0" cellpadding="8" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th style="border-bottom:1px solid #cbd5e1; text-align:left; font-size:10px; color:#475569;">Cliente</th>
                <th style="border-bottom:1px solid #cbd5e1; text-align:left; font-size:10px; color:#475569;">Código</th>
                <th style="border-bottom:1px solid #cbd5e1; text-align:right; font-size:10px; color:#475569;">Locais registados</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['top_customers'] as $customer)
                <tr>
                    <td style="border-bottom:1px solid #e2e8f0;">{{ $customer['name'] }}</td>
                    <td style="border-bottom:1px solid #e2e8f0;">{{ $customer['code'] ?: '—' }}</td>
                    <td style="border-bottom:1px solid #e2e8f0; text-align:right;">{{ $customer['warehouses_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
