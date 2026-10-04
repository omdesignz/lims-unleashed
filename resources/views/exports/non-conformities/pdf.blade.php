@php
    use App\Support\ControlledDocument;
    use Illuminate\Support\Carbon;

    $statusLabels = ['opened' => 'Aberta', 'in_progress' => 'Em curso', 'resolved' => 'Resolvida', 'closed' => 'Encerrada'];
    $severityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'];
    $categoryLabels = ['quality' => 'Qualidade', 'safety' => 'Segurança', 'environmental' => 'Ambiental', 'regulatory' => 'Regulatório', 'other' => 'Outro'];
    $filters = $filters ?? [];
    $day = fn ($value): string => Carbon::parse($value)->format('d/m/Y');
    $appliedFilters = array_filter([
        ! empty($filters['search']) ? 'Pesquisa: '.$filters['search'] : null,
        ! empty($filters['status']) ? 'Estado: '.($statusLabels[$filters['status']] ?? $filters['status']) : null,
        ! empty($filters['severity']) ? 'Severidade: '.($severityLabels[$filters['severity']] ?? $filters['severity']) : null,
        ! empty($filters['category']) ? 'Categoria: '.($categoryLabels[$filters['category']] ?? $filters['category']) : null,
        ! empty($filters['start_date']) ? 'Desde '.$day($filters['start_date']) : null,
        ! empty($filters['end_date']) ? 'Até '.$day($filters['end_date']) : null,
    ]);

    $documentTitle = 'Relatório de Não Conformidades';
    $documentNumber = 'NC-'.now()->format('Ymd-Hi');
    $issueDate = (string) $exportDate;
    $controlRows = [
        ['Laboratório', (string) $labName],
        ['Âmbito', ! empty($filters['archived']) ? 'Arquivo' : 'Registos activos'],
        ['Registos', (string) $nonConformities->count()],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Relatório interno do sistema de gestão da qualidade.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    @if($appliedFilters !== [])
        <p class="doc-notes">Filtros aplicados: {{ implode(' · ', $appliedFilters) }}</p>
    @endif

    <table class="doc-results doc-plain" style="margin-top:2mm;">
        <thead>
            <tr>
                <th style="width:11%;">N.º</th>
                <th>Título</th>
                <th style="width:9%;">Estado</th>
                <th style="width:9%;">Severidade</th>
                <th style="width:14%;">Relatada por</th>
                <th style="width:12%;">Data do relato</th>
                <th style="width:10%;">Prazo</th>
                <th style="width:14%;">Laboratório</th>
            </tr>
        </thead>
        <tbody>
            @forelse($nonConformities as $nc)
                <tr>
                    <td>{{ $nc->nc_number }}</td>
                    <td>{{ $nc->title }}</td>
                    <td>{{ $statusLabels[(string) $nc->status] ?? $nc->status }}</td>
                    <td @class(['doc-nonconforming' => in_array($nc->severity, ['high', 'critical'], true)])>{{ $severityLabels[(string) $nc->severity] ?? $nc->severity }}</td>
                    <td>{{ $nc->reported_by }}</td>
                    <td>{{ $nc->reported_at?->format('d/m/Y H:i') ?? ControlledDocument::NOT_RECORDED }}</td>
                    <td>{{ $nc->due_date?->format('d/m/Y') ?? ControlledDocument::NOT_RECORDED }}</td>
                    <td>{{ $nc->lab?->name ?? ControlledDocument::NOT_RECORDED }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Sem não conformidades para os filtros aplicados.</td></tr>
            @endforelse
        </tbody>
    </table>

    {!! ControlledDocument::endMark('relatório') !!}
@endsection
