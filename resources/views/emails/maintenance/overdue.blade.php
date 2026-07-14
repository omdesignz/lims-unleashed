@component('mail::message')
# ⚠️ Tarefas de manutenção em atraso - atenção imediata necessária

Existem **{{ $tasks->count() }}** tarefas de manutenção **EM ATRASO**.

@component('mail::table')
| Número da tarefa | Equipamento | Categoria | Em atraso desde | Dias de atraso |
|-------------|-----------|----------|---------------|--------------|
@foreach($tasks as $task)
@php
    $daysOverdue = now()->diffInDays($task->due_date);
@endphp
| {{ $task->maintenance_task_no }} | {{ $task->equipment->name }} | {{ $task->category->name }} | {{ $task->due_date->format('d/m/Y') }} | {{ $daysOverdue }} dias |
@endforeach
@endcomponent

## 🚨 Impacto crítico
- O equipamento pode estar fora de calibração
- Os resultados dos ensaios podem ser inválidos
- A conformidade regulamentar pode estar em risco
- Podem existir riscos para a segurança

@component('mail::button', ['url' => url('/maintenance/dashboard?status=overdue'), 'color' => 'red'])
Rever tarefas em atraso
@endcomponent

**Acções necessárias:**
1. Agendar imediatamente estas tarefas de manutenção
2. Notificar os responsáveis dos departamentos envolvidos
3. Suspender a utilização do equipamento, se necessário
4. Actualizar o plano de manutenção

Esta situação exige **atenção imediata** para garantir a conformidade e a segurança.

Obrigado,<br>
Sistema de manutenção {{ config('app.name') }}
@endcomponent
