@php
    $kpis = collect($payload['kpis'] ?? [])->values();
@endphp

<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: separate; border-spacing: 0 6px; margin: 0 0 14px;">
    <tr>
        @foreach($kpis->take(4) as $kpi)
            <td style="width:25%; padding:0 4px; vertical-align:top;">
                <div class="metric-card" style="padding:12px 12px 11px;">
                    <div style="font-size:7.2pt; letter-spacing:0.12em; text-transform:uppercase; color:#6b7b74; font-weight:900;">
                        {{ $kpi['label'] ?? 'Indicador' }}
                    </div>
                    <div style="margin-top:7px; font-size:17pt; line-height:1; color:#143d37; font-weight:900;">
                        {{ $kpi['value'] ?? '—' }}
                    </div>
                    <div style="margin-top:7px; border-top:0.2mm solid #ded3bf; padding-top:6px; font-size:7.5pt; line-height:1.35; color:#475a53;">
                        {{ $kpi['hint'] ?? 'Contexto operacional' }}
                    </div>
                </div>
            </td>
        @endforeach
    </tr>
</table>
