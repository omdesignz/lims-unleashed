@php
    use App\Support\ControlledDocument;

    $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
    $days = collect($calendar)->filter(fn ($day): bool => collect($day['tasks'] ?? [])->isNotEmpty())->values();
    $totalTasks = $days->sum(fn ($day): int => collect($day['tasks'])->count());

    $documentTitle = 'Calendário de Manutenção';
    $documentNumber = 'MNT-CAL-'.$generated_at->format('Ymd-Hi');
    $issueDate = $generated_at->format('d/m/Y H:i');
    $controlRows = [
        ['Laboratório', (string) ($labName ?? ControlledDocument::laboratoryName($settings))],
        ['Dias com tarefas', (string) $days->count()],
        ['Tarefas', (string) $totalTasks],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Planeamento de manutenção e calibração de equipamentos.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    <table class="doc-results doc-plain" style="margin-top:3mm;">
        <thead>
            <tr>
                <th style="width:16%;">Data</th>
                <th style="width:18%;">Tarefa</th>
                <th>Descrição</th>
                <th style="width:22%;">Equipamento</th>
                <th style="width:16%;">Categoria</th>
            </tr>
        </thead>
        <tbody>
            @forelse($days as $day)
                @foreach(collect($day['tasks']) as $index => $task)
                    <tr>
                        <td>@if($index === 0)<strong>{{ $day['date'] ?? '' }}</strong><br>{{ $day['day'] ?? '' }}@endif</td>
                        <td>{{ $task->maintenance_task_no ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $task->name }}</td>
                        <td>{{ $task->equipment?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $task->category?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="5">Nenhuma tarefa planeada no período seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    {!! ControlledDocument::endMark('calendário') !!}
@endsection
