@component('mail::message')
# Upcoming Maintenance Tasks

You have **{{ $tasks->count() }}** maintenance tasks due within the next **{{ $daysThreshold }}** days.

@component('mail::table')
| Task Number | Equipment | Category | Due Date | Status |
|-------------|-----------|----------|----------|--------|
@foreach($tasks as $task)
| {{ $task->maintenance_task_no }} | {{ $task->equipment->name }} | {{ $task->category->name }} | {{ $task->due_date->format('d/m/Y') }} | {{ $task->due_date < now() ? 'Overdue' : 'Due' }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => url('/maintenance/dashboard'), 'color' => 'primary'])
View Maintenance Dashboard
@endcomponent

**Priority Tasks:**
@foreach($tasks->where('due_date', '<', now()->addDays(7)) as $task)
- {{ $task->equipment->name }} (Due: {{ $task->due_date->format('d/m/Y') }})
@endforeach

Thanks,<br>
{{ config('app.name') }} Maintenance System
@endcomponent