@php
    use App\Support\ControlledDocument;

    $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
    $generated_at = $generated_at ?? now();
    $metrics = collect($data['metrics'] ?? []);
    $number = fn ($value): string => number_format((float) $value, 2, ',', '.');
    $chartTitles = [
        'consumption' => 'Tendência de consumo',
        'stock' => 'Distribuição de existências',
        'monthly' => 'Comparativo mensal',
        'topReagents' => 'Reagentes mais consumidos',
    ];
    $rows = match ($chartType) {
        'consumption' => collect($data['consumptionTrend'] ?? [])->map(fn ($row) => [
            'Data' => $row['date'] ?? ControlledDocument::NOT_RECORDED,
            'Quantidade' => $number($row['quantity'] ?? 0),
        ]),
        'stock' => collect($data['stockDistribution'] ?? [])->map(fn ($row) => [
            'Categoria' => $row['category'] ?? ControlledDocument::NOT_RECORDED,
            'Quantidade' => $number($row['quantity'] ?? 0),
        ]),
        'monthly' => collect($data['monthlyComparison'] ?? [])->map(fn ($row) => [
            'Mês' => $row['month'] ?? ControlledDocument::NOT_RECORDED,
            'Ano actual' => $number($row['current'] ?? 0),
            'Ano anterior' => $number($row['previous'] ?? 0),
        ]),
        'topReagents' => collect($data['topReagents'] ?? [])->map(fn ($row) => [
            'Reagente' => $row['name'] ?? ControlledDocument::NOT_RECORDED,
            'Consumo' => $number($row['consumption'] ?? 0),
        ]),
        default => collect(),
    };
    $headers = $rows->first() ? array_keys($rows->first()) : [];
    $appliedFilters = collect($filters ?? [])
        ->except(['format', 'chartType', '_token'])
        ->filter(fn ($value) => filled($value))
        ->map(fn ($value, $key) => $key.': '.(is_array($value) ? implode(', ', $value) : $value))
        ->implode(' · ');

    $documentTitle = $chartTitles[$chartType] ?? 'Indicadores de inventário';
    $documentNumber = 'INV-'.$generated_at->format('Ymd-Hi');
    $issueDate = $generated_at->format('d/m/Y H:i');
    $controlRows = [
        ['N.º', $documentNumber],
        ['Registos', (string) $rows->count()],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Indicadores de inventário do laboratório.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::section('', 'Indicadores do período', ControlledDocument::keyValueGrid([
        ['label' => 'Consumo total', 'value' => $number($metrics->get('totalConsumption', 0))],
        ['label' => 'Média diária', 'value' => $number($metrics->get('dailyAverage', 0))],
        ['label' => 'Alertas de reposição', 'value' => (string) (int) $metrics->get('reorderAlerts', 0)],
        ['label' => 'Alertas críticos', 'value' => (string) (int) $metrics->get('criticalAlerts', 0)],
    ])) !!}

    <div class="doc-section">
        <div class="doc-section-title">Dados</div>
        @if($rows->isNotEmpty())
            <table class="doc-results doc-plain">
                <thead>
                    <tr>
                        @foreach($headers as $index => $header)
                            <th @class(['doc-num' => $index > 0])>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($headers as $index => $header)
                                <td @class(['doc-num' => $index > 0])>{{ $row[$header] ?? ControlledDocument::NOT_RECORDED }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="doc-text">Sem dados para os filtros seleccionados.</p>
        @endif
        @if($appliedFilters !== '')
            <p class="doc-notes">Filtros aplicados: {{ $appliedFilters }}</p>
        @endif
    </div>

    {!! ControlledDocument::endMark('relatório') !!}
@endsection
