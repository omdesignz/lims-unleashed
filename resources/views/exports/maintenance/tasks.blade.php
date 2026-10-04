@php
    use App\Support\ControlledDocument;
    use Illuminate\Support\Carbon;

    $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
    $filters = $filters ?? [];
    $isOverdue = fn ($task): bool => ! $task->is_executed && $task->due_date && $task->due_date->lt(today());
    $executedCount = $tasks->where('is_executed', true)->count();
    $overdueCount = $tasks->filter($isOverdue)->count();
    $pendingCount = $tasks->count() - $executedCount - $overdueCount;
    $period = (isset($filters['date_from']) ? Carbon::parse($filters['date_from'])->format('d/m/Y') : 'início')
        .' a '.(isset($filters['date_to']) ? Carbon::parse($filters['date_to'])->format('d/m/Y') : 'hoje');
    $money = fn ($value): string => 'AOA '.number_format((float) $value, 2, ',', '.');

    $documentTitle = 'Relatório de Manutenção';
    $documentNumber = 'MNT-'.$generated_at->format('Ymd-Hi');
    $issueDate = $generated_at->format('d/m/Y H:i');
    $controlRows = [
        ['Laboratório', (string) ($labName ?? ControlledDocument::laboratoryName($settings))],
        ['Período', $period],
        ['Tarefas', (string) $tasks->count()],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Registo de manutenção e calibração de equipamentos.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::section('', 'Resumo', ControlledDocument::keyValueGrid([
        ['label' => 'Executadas', 'value' => (string) $executedCount],
        ['label' => 'Vencidas', 'value' => (string) $overdueCount],
        ['label' => 'Pendentes', 'value' => (string) $pendingCount],
        ['label' => 'Custo total', 'value' => $money($tasks->sum('cost'))],
    ])) !!}

    <div class="doc-section">
        <div class="doc-section-title">Tarefas</div>
        <table class="doc-results doc-plain">
            <thead>
                <tr>
                    <th style="width:26%;">Tarefa</th>
                    <th>Categoria</th>
                    <th>Equipamento</th>
                    <th>Data limite</th>
                    <th>Estado</th>
                    <th class="doc-num">Custo</th>
                    <th>Fornecedor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td><strong>{{ $task->maintenance_task_no ?: ControlledDocument::NOT_RECORDED }}</strong><br>{{ $task->name }}</td>
                        <td>{{ $task->category?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $task->equipment?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $task->due_date?->format('d/m/Y') ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td @class(['doc-nonconforming' => $isOverdue($task)])>{{ $task->is_executed ? 'Executada' : ($isOverdue($task) ? 'Vencida' : 'Pendente') }}</td>
                        <td class="doc-num">{{ $money($task->cost) }}</td>
                        <td>{{ $task->supplier?->name ?: 'Interno' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Sem tarefas para os filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {!! ControlledDocument::endMark('relatório') !!}
@endsection
