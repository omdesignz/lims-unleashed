<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Calendário de Manutenção</title>
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
            background: #0f766e;
            border-radius: 16px 16px 0 0;
            padding: 14px 16px;
        }

        .hero-body {
            color: #ccfbf1;
            padding: 12px 16px 14px;
        }

        .eyebrow {
            color: #99f6e4;
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
            color: #ccfbf1;
            font-size: 9px;
            text-align: right;
            white-space: nowrap;
        }

        .calendar-table {
            border-collapse: collapse;
            width: 100%;
        }

        .calendar-table th {
            background: #0f172a;
            border: 1px solid #0f172a;
            color: #ffffff;
            font-size: 8px;
            letter-spacing: .5px;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        .calendar-table td {
            border: 1px solid #dbe4f0;
            padding: 8px;
            vertical-align: top;
        }

        .calendar-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .date-cell {
            color: #0f172a;
            font-weight: 800;
            white-space: nowrap;
            width: 18%;
        }

        .task-pill {
            background: #ecfeff;
            border: 1px solid #99f6e4;
            border-radius: 10px;
            color: #134e4a;
            display: block;
            line-height: 1.45;
            margin-bottom: 5px;
            padding: 6px 8px;
        }

        .empty {
            color: #94a3b8;
            font-style: italic;
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
        $totalTasks = collect($calendar)->sum(fn ($day) => collect($day['tasks'] ?? [])->count());
    @endphp

    <div class="hero">
        <div class="hero-top">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="eyebrow">{{ $labName }}</div>
                        <h1>Calendário de Manutenção</h1>
                        <div>Maintenance calendar · Planeamento e lembretes operacionais</div>
                    </td>
                    <td class="meta">
                        Emitido em {{ $generated_at->format('d/m/Y H:i') }}<br>
                        {{ count($calendar) }} dia(s) · {{ $totalTasks }} tarefa(s)
                    </td>
                </tr>
            </table>
        </div>
        <div class="hero-body">
            Visão calendarizada das tarefas de manutenção para apoiar planeamento, execução, notificações e evidência ISO 17025.
        </div>
    </div>

    <table class="calendar-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Tarefas planeadas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($calendar as $day)
                @php $tasks = collect($day['tasks'] ?? []); @endphp
                @if($tasks->isNotEmpty())
                    <tr>
                        <td class="date-cell">{{ $day['date'] ?? 'N/A' }}<br>{{ $day['day'] ?? '' }}</td>
                        <td>
                            @foreach($tasks as $task)
                                <span class="task-pill">
                                    <strong>{{ $task->maintenance_task_no ?: $task->name }}</strong><br>
                                    {{ $task->name ?: 'N/A' }} · {{ $task->equipment->name ?? 'Equipamento não informado' }} · {{ $task->category->name ?? 'Categoria não informada' }}
                                </span>
                            @endforeach
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="2" class="empty">Sem calendário para os filtros selecionados.</td>
                </tr>
            @endforelse

            @if($totalTasks === 0)
                <tr>
                    <td colspan="2" class="empty">Nenhuma tarefa planeada no período selecionado.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Documento controlado gerado pelo sistema. Palavras-chave: {{ $settings->app_document_keywords ?: 'manutenção; calendário; notificações; ISO 17025' }}.<br>
        Controlled maintenance planning evidence generated by {{ $labName }}.
    </div>
</body>
</html>
