@php
    use App\Support\ControlledDocument;

    $humanise = fn ($key): string => ucfirst(str_replace('_', ' ', (string) $key));
    $cellText = fn ($cell): string => is_scalar($cell) || is_null($cell)
        ? (string) ($cell ?? ControlledDocument::NOT_RECORDED)
        : (string) json_encode($cell, JSON_UNESCAPED_UNICODE);
    $period = array_filter([
        ($dateRange['start'] ?? null)?->format('d/m/Y'),
        ($dateRange['end'] ?? null)?->format('d/m/Y'),
    ]);

    $documentTitle = 'Relatório de '.$humanise($reportType ?? 'inventário');
    $documentNumber = 'INV-'.now()->format('Ymd-Hi');
    $issueDate = now()->format('d/m/Y H:i');
    $controlRows = array_values(array_filter([
        ['N.º', $documentNumber],
        $period !== [] ? ['Período', implode(' a ', $period)] : null,
        ['Emissão', $issueDate],
    ]));
    $footerNotice = 'Relatório de inventário do laboratório.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    @foreach(($data ?? []) as $section => $value)
        @php
            $normalized = $value instanceof \Illuminate\Support\Collection ? $value : collect(is_array($value) ? $value : []);
            $first = $normalized->first();
            $toArray = fn ($row): array => is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : get_object_vars($row));
        @endphp
        <div class="doc-section">
            <div class="doc-section-title">{{ $humanise($section) }}</div>
            @if($normalized->isNotEmpty() && (is_array($first) || is_object($first)))
                @php $headings = array_slice(array_keys($toArray($first)), 0, 8); @endphp
                <table class="doc-results doc-plain">
                    <thead>
                        <tr>
                            @foreach($headings as $heading)
                                <th>{{ $humanise($heading) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($normalized as $row)
                            @php $rowArray = $toArray($row); @endphp
                            <tr>
                                @foreach($headings as $heading)
                                    <td>{{ $cellText($rowArray[$heading] ?? null) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif(is_array($value) && count($value) > 0)
                {!! ControlledDocument::keyValueGrid(collect($value)->map(fn ($itemValue, $itemKey): array => ['label' => $humanise($itemKey), 'value' => $cellText($itemValue)])->values()->all()) !!}
            @else
                <p class="doc-text">{{ is_scalar($value) ? $value : 'Sem registos para esta secção.' }}</p>
            @endif
        </div>
    @endforeach

    {!! ControlledDocument::endMark('relatório') !!}
@endsection
