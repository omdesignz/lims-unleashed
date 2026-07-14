@component('mail::message')
# Próximas tarefas de manutenção

Existem **{{ $tasks->count() }}** tarefas de manutenção com vencimento nos próximos **{{ $daysThreshold }}** dias.

@component('mail::table')
| Número da tarefa | Equipamento | Categoria | Data de vencimento | Estado |
|-------------|-----------|----------|----------|--------|
@foreach($tasks as $task)
| {{ $task->maintenance_task_no }} | {{ $task->equipment->name }} | {{ $task->category->name }} | {{ $task->due_date->format('d/m/Y') }} | {{ $task->due_date < now() ? 'Em atraso' : 'A vencer' }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => url('/maintenance/dashboard'), 'color' => 'primary'])
Abrir painel de manutenção
@endcomponent

**Tarefas prioritárias:**
@foreach($tasks->where('due_date', '<', now()->addDays(7)) as $task)
- {{ $task->equipment->name }} (vencimento: {{ $task->due_date->format('d/m/Y') }})
@endforeach

Obrigado,<br>
Sistema de manutenção {{ config('app.name') }}
@endcomponent
