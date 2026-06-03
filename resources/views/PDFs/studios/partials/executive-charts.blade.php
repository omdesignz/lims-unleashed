@php
    $throughput = data_get($payload, 'charts.throughput', [
        'title' => 'Capacidade técnica por etapa',
        'labels' => ['Recepção', 'Preparação', 'Ensaio', 'Verificação', 'Emissão'],
        'series' => [24, 21, 18, 16, 12],
        'unit' => 'amostras',
    ]);
    $pressure = data_get($payload, 'charts.quality_pressure', [
        'title' => 'Pressão de qualidade',
        'labels' => ['No prazo', 'Atenção', 'Risco', 'Atrasado'],
        'series' => [62, 24, 9, 5],
        'unit' => '%',
    ]);
    $throughputLabels = collect($throughput['labels'] ?? [])->values();
    $throughputSeries = collect($throughput['series'] ?? [])->map(fn ($value) => (float) $value)->values();
    $pressureLabels = collect($pressure['labels'] ?? [])->values();
    $pressureSeries = collect($pressure['series'] ?? [])->map(fn ($value) => (float) $value)->values();
    $throughputMax = max(1, (float) $throughputSeries->max());
    $pressureMax = max(1, (float) $pressureSeries->max());
    $barWidth = 72;
    $barGap = 28;
    $chartHeight = 128;
@endphp

<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate; border-spacing:0 0; margin: 4px 0 18px;">
    <tr>
        <td style="width:50%; padding-right:8px; vertical-align:top;">
            <div class="pdf-card" style="border:0.2mm solid #ded3bf; border-radius:14px; background:#fffdf7; padding:13px;">
                <div style="font-size:8pt; letter-spacing:0.12em; text-transform:uppercase; color:#6b7b74; font-weight:900;">
                    {{ $throughput['title'] ?? 'Capacidade técnica' }}
                </div>
                <svg class="report-chart-svg" width="100%" viewBox="0 0 560 210" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $throughput['title'] ?? 'Gráfico executivo' }}">
                    <rect x="0" y="0" width="560" height="210" rx="22" fill="#f7f1e7"/>
                    <line x1="46" y1="34" x2="46" y2="164" stroke="#ded3bf" stroke-width="2"/>
                    <line x1="46" y1="164" x2="522" y2="164" stroke="#ded3bf" stroke-width="2"/>
                    @foreach($throughputSeries as $index => $value)
                        @php
                            $height = max(8, ($value / $throughputMax) * $chartHeight);
                            $x = 64 + ($index * ($barWidth + $barGap));
                            $y = 164 - $height;
                            $label = (string) ($throughputLabels[$index] ?? '');
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $height }}" rx="12" fill="#143d37"/>
                        <rect x="{{ $x + 10 }}" y="{{ $y + 8 }}" width="{{ $barWidth - 20 }}" height="{{ max(0, $height - 16) }}" rx="8" fill="#1f7a68" opacity="0.55"/>
                        <text x="{{ $x + ($barWidth / 2) }}" y="{{ $y - 8 }}" text-anchor="middle" font-size="17" fill="#143d37" font-weight="800">{{ $value }}</text>
                        <text x="{{ $x + ($barWidth / 2) }}" y="190" text-anchor="middle" font-size="14" fill="#475a53">{{ $label }}</text>
                    @endforeach
                </svg>
                <div style="font-size:7.5pt; color:#6b7b74;">Unidade: {{ $throughput['unit'] ?? 'amostras' }}</div>
            </div>
        </td>
        <td style="width:50%; padding-left:8px; vertical-align:top;">
            <div class="pdf-card" style="border:0.2mm solid #ded3bf; border-radius:14px; background:#fffdf7; padding:13px;">
                <div style="font-size:8pt; letter-spacing:0.12em; text-transform:uppercase; color:#6b7b74; font-weight:900;">
                    {{ $pressure['title'] ?? 'Pressão de qualidade' }}
                </div>
                <svg class="report-chart-svg" width="100%" viewBox="0 0 560 210" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $pressure['title'] ?? 'Gráfico de risco' }}">
                    <rect x="0" y="0" width="560" height="210" rx="22" fill="#f7f1e7"/>
                    @foreach($pressureSeries as $index => $value)
                        @php
                            $width = max(12, ($value / $pressureMax) * 380);
                            $y = 36 + ($index * 38);
                            $label = (string) ($pressureLabels[$index] ?? '');
                            $fill = ['#1f7a68', '#d9b05f', '#c79431', '#9f1d1d'][$index] ?? '#143d37';
                        @endphp
                        <text x="34" y="{{ $y + 17 }}" font-size="15" fill="#475a53">{{ $label }}</text>
                        <rect x="152" y="{{ $y }}" width="382" height="22" rx="11" fill="#eee5d5"/>
                        <rect x="152" y="{{ $y }}" width="{{ $width }}" height="22" rx="11" fill="{{ $fill }}"/>
                        <text x="{{ min(522, 162 + $width) }}" y="{{ $y + 16 }}" font-size="13" fill="#07110f" font-weight="800">{{ $value }}{{ $pressure['unit'] ?? '%' }}</text>
                    @endforeach
                </svg>
                <div style="font-size:7.5pt; color:#6b7b74;">Base de leitura: carteira operacional do período.</div>
            </div>
        </td>
    </tr>
</table>
