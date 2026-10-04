@php
    use App\Support\ControlledDocument;

    $metrics = collect($data['metrics'] ?? []);
    $topItems = collect($data['topItems'] ?? $data['topReagents'] ?? []);
    $totalConsumption = (float) $metrics->get('totalConsumption', 0);
    $number = fn ($value, int $decimals = 2): string => number_format((float) $value, $decimals, ',', '.');

    $documentTitle = 'Relatório de Consumo de Inventário';
    $documentNumber = 'INV-'.now()->format('Ymd-Hi');
    $issueDate = now()->format('d/m/Y H:i');
    $controlRows = [
        ['N.º', $documentNumber],
        ['Período', $dateRange['start']->format('d/m/Y').' a '.$dateRange['end']->format('d/m/Y')],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Consumo de reagentes e materiais do laboratório.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::section('', 'Indicadores do período', ControlledDocument::keyValueGrid([
        ['label' => 'Consumo total', 'value' => $number($totalConsumption)],
        ['label' => 'Média diária', 'value' => $number($metrics->get('dailyAverage', 0))],
        ['label' => 'Valor do inventário', 'value' => 'AOA '.$number($metrics->get('inventoryValue', 0))],
    ])) !!}

    <div class="doc-section">
        <div class="doc-section-title">Reagentes com maior consumo</div>
        <table class="doc-results doc-plain">
            <thead>
                <tr>
                    <th>Reagente</th>
                    <th class="doc-num">Quantidade consumida</th>
                    <th class="doc-num">% do total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topItems as $item)
                    @php $consumption = (float) ($item['consumption'] ?? 0); @endphp
                    <tr>
                        <td>{{ $item['name'] ?? ControlledDocument::NOT_RECORDED }}</td>
                        <td class="doc-num">{{ $number($consumption) }}</td>
                        <td class="doc-num">{{ $totalConsumption > 0 ? $number(($consumption / $totalConsumption) * 100, 1) : '0,0' }} %</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Sem dados de consumo para o período seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {!! ControlledDocument::endMark('relatório') !!}
@endsection
