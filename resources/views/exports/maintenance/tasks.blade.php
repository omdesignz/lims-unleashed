<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance Tasks Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #1e3a8a; margin-bottom: 5px; }
        .header .subtitle { color: #6b7280; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 8px; border: 1px solid #e5e7eb; }
        .info-table .label { font-weight: bold; background-color: #f9fafb; }
        .main-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .main-table th { background-color: #1e3a8a; color: white; padding: 10px; text-align: left; }
        .main-table td { padding: 8px; border: 1px solid #e5e7eb; }
        .status-overdue { color: #dc2626; font-weight: bold; }
        .status-executed { color: #10b981; font-weight: bold; }
        .status-pending { color: #f59e0b; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; color: #6b7280; font-size: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Maintenance Tasks Report</h1>
        <div class="subtitle">
            Generated on: {{ $generated_at->format('d/m/Y H:i') }}<br>
            LIMS - Laboratory Information Management System
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label" width="20%">Report Period</td>
            <td>
                @if(isset($filters['date_from']) || isset($filters['date_to']))
                    {{ isset($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : 'Start' }}
                    to
                    {{ isset($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'End' }}
                @else
                    All dates
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Total Tasks</td>
            <td>{{ $tasks->count() }}</td>
        </tr>
        <tr>
            <td class="label">Total Cost</td>
            <td>AOA {{ number_format($tasks->sum('cost'), 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Status Summary</td>
            <td>
                Executed: {{ $tasks->where('is_executed', true)->count() }} |
                Overdue: {{ $tasks->where('is_executed', false)->where('due_date', '<', now())->count() }} |
                Pending: {{ $tasks->where('is_executed', false)->where('due_date', '>=', now())->count() }}
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th>Task No.</th>
                <th>Task Name</th>
                <th>Category</th>
                <th>Equipment</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Cost (AOA)</th>
                <th>Supplier</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
            <tr>
                <td>{{ $task->maintenance_task_no }}</td>
                <td>{{ $task->name }}</td>
                <td>{{ $task->category->name }}</td>
                <td>{{ $task->equipment->name }}</td>
                <td>{{ $task?->due_date?->format('d/m/Y') ?? 'N/A' }}</td>
                <td class="status-{{ $task->is_executed ? 'executed' : ($task->due_date < now() ? 'overdue' : 'pending') }}">
                    {{ $task->is_executed ? 'Executed' : ($task->due_date < now() ? 'Overdue' : 'Pending') }}
                </td>
                <td style="text-align: right;">{{ number_format($task->cost, 2, ',', '.') }}</td>
                <td>{{ $task->supplier ? $task->supplier->name : 'Internal' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f9fafb; font-weight: bold;">
                <td colspan="6" style="text-align: right;">Total:</td>
                <td style="text-align: right;">AOA {{ number_format($tasks->sum('cost'), 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Page 1 of 1 | This document was generated automatically by LIMS</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>