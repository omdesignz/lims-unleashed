<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Relatório de Manutenção</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @page {
            margin: 16mm 14mm;
        }

        body {
            color: #111827;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
        }

        .hero {
            background: #0f172a;
            border-radius: 16px 16px 10px 10px;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .hero-top {
            background: #2563eb;
            border-radius: 16px 16px 0 0;
            padding: 14px 16px;
        }

        .hero-body {
            color: #dbeafe;
            padding: 12px 16px 14px;
        }

        .eyebrow {
            color: #bfdbfe;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }

        h1 {
            color: #ffffff;
            font-size: 21px;
            margin: 4px 0 2px;
        }

        .meta {
            color: #dbeafe;
            font-size: 9px;
            text-align: right;
            white-space: nowrap;
        }

        .summary-table,
        .main-table {
            border-collapse: collapse;
            width: 100%;
        }

        .summary-table td {
            background: #f8fafc;
            border: 1px solid #dbe4f0;
            padding: 9px;
        }

        .summary-label {
            color: #64748b;
            display: block;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-top: 3px;
        }

        .main-table {
            margin-top: 16px;
        }

        .main-table th {
            background: #0f172a;
            border: 1px solid #0f172a;
            color: #ffffff;
            font-size: 8px;
            letter-spacing: .5px;
            padding: 8px 7px;
            text-align: left;
            text-transform: uppercase;
        }

        .main-table td {
            border: 1px solid #e2e8f0;
            padding: 7px;
            vertical-align: top;
        }

        .main-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .status-overdue {
            color: #b91c1c;
            font-weight: 800;
        }

        .status-executed {
            color: #047857;
            font-weight: 800;
        }

        .status-pending {
            color: #b45309;
            font-weight: 800;
        }

        .footer {
            border-top: 1px solid #dbe4f0;
            color: #64748b;
            font-size: 8px;
            line-height: 1.5;
            margin-top: 18px;
            padding-top: 9px;
            text-align: center;
        }
    </style>
</head>
<body class="pdf-document">
    @php
        $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
        $labName = $settings->app_client_lab_name ?: ($settings->app_name ?: config('app.name'));
        $overdueCount = $tasks->where('is_executed', false)->filter(fn ($task) => $task->due_date && $task->due_date->lt(now()))->count();
        $pendingCount = $tasks->where('is_executed', false)->filter(fn ($task) => ! $task->due_date || $task->due_date->gte(now()))->count();
        $periodStart = isset($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : 'Início';
        $periodEnd = isset($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'Fim';
    @endphp

    <div class="hero">
        <div class="hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="eyebrow">{{ $labName }}</div>
                        <h1>Relatório de Manutenção</h1>
                        <div>Maintenance tasks report · Equipamentos, calibração e rastreabilidade</div>
                    </td>
                    <td class="meta">
                        Emitido em {{ $generated_at->format('d/m/Y H:i') }}<br>
                        Período: {{ $periodStart }} - {{ $periodEnd }}<br>
                        {{ $tasks->count() }} tarefa(s)
                    </td>
                </tr>
            </table>
        </div>
        <div class="hero-body">
            Relatório operacional para acompanhamento de manutenção preventiva/corretiva, evidência de execução e controlo de custos.
        </div>
    </div>

    <table class="summary-table">
        <tr>
            <td><span class="summary-label">Total de tarefas</span><span class="summary-value">{{ $tasks->count() }}</span></td>
            <td><span class="summary-label">Executadas</span><span class="summary-value">{{ $tasks->where('is_executed', true)->count() }}</span></td>
            <td><span class="summary-label">Vencidas</span><span class="summary-value">{{ $overdueCount }}</span></td>
            <td><span class="summary-label">Pendentes</span><span class="summary-value">{{ $pendingCount }}</span></td>
            <td><span class="summary-label">Custo total</span><span class="summary-value">AOA {{ number_format((float) $tasks->sum('cost'), 2, ',', '.') }}</span></td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th>Tarefa</th>
                <th>Categoria</th>
                <th>Equipamento</th>
                <th>Data limite</th>
                <th>Estado</th>
                <th>Custo</th>
                <th>Fornecedor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $task)
                @php
                    $isOverdue = ! $task->is_executed && $task->due_date && $task->due_date->lt(now());
                    $statusLabel = $task->is_executed ? 'Executada / Executed' : ($isOverdue ? 'Vencida / Overdue' : 'Pendente / Pending');
                    $statusClass = $task->is_executed ? 'executed' : ($isOverdue ? 'overdue' : 'pending');
                @endphp
                <tr>
                    <td>
                        <strong>{{ $task->maintenance_task_no ?: 'N/A' }}</strong><br>
                        {{ $task->name ?: 'N/A' }}
                    </td>
                    <td>{{ $task->category->name ?? 'N/A' }}</td>
                    <td>{{ $task->equipment->name ?? 'N/A' }}</td>
                    <td>{{ $task->due_date?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td class="status-{{ $statusClass }}">{{ $statusLabel }}</td>
                    <td style="text-align: right;">AOA {{ number_format((float) $task->cost, 2, ',', '.') }}</td>
                    <td>{{ $task->supplier->name ?? 'Interno / Internal' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Sem tarefas para os filtros selecionados.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="font-weight: 800; text-align: right;">Total</td>
                <td style="font-weight: 800; text-align: right;">AOA {{ number_format((float) $tasks->sum('cost'), 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Documento controlado gerado pelo sistema. Palavras-chave: {{ $settings->app_document_keywords ?: 'manutenção; calibração; ISO 17025; rastreabilidade' }}.<br>
        Controlled maintenance evidence generated by {{ $labName }}.
    </div>
</body>
</html>
