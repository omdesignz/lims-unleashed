@component('mail::message')
# ⚠️ Overdue Maintenance Tasks - Immediate Attention Required!

You have **{{ $tasks->count() }}** maintenance tasks that are **OVERDUE**.

@component('mail::table')
| Task Number | Equipment | Category | Overdue Since | Days Overdue |
|-------------|-----------|----------|---------------|--------------|
@foreach($tasks as $task)
@php
    $daysOverdue = now()->diffInDays($task->due_date);
@endphp
| {{ $task->maintenance_task_no }} | {{ $task->equipment->name }} | {{ $task->category->name }} | {{ $task->due_date->format('d/m/Y') }} | {{ $daysOverdue }} days |
@endforeach
@endcomponent

## 🚨 Critical Impact:
- Equipment may be out of calibration
- Test results may be invalid
- Regulatory compliance at risk
- Potential safety hazards

@component('mail::button', ['url' => url('/maintenance/dashboard?status=overdue'), 'color' => 'red'])
Review Overdue Tasks Now
@endcomponent

**Required Actions:**
1. Immediately schedule these maintenance tasks
2. Notify relevant department heads
3. Place equipment on hold if necessary
4. Update maintenance schedule

This requires **immediate attention** to ensure compliance and safety.

Thanks,<br>
{{ config('app.name') }} Maintenance System
@endcomponent