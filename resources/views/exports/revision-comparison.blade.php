<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ISO 17025 Revision Comparison</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 24px;
        }

        h1, h2 {
            margin: 0 0 10px;
        }

        .card {
            border: 1px solid #d1d5db;
            padding: 12px;
            margin-bottom: 16px;
            background: #f9fafb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <h1>ISO 17025 Revision Comparison</h1>
    <p>Certificate: {{ $certificate->code ?? 'N/A' }}</p>

    <div class="card">
        <h2>Revision A</h2>
        <p>Version: {{ $revisionA->version }}</p>
        <p>Type: {{ $revisionA->change_type }}</p>
        <p>Reason: {{ $revisionA->change_reason }}</p>
    </div>

    <div class="card">
        <h2>Revision B</h2>
        <p>Version: {{ $revisionB->version }}</p>
        <p>Type: {{ $revisionB->change_type }}</p>
        <p>Reason: {{ $revisionB->change_reason }}</p>
    </div>

    <h2>Differences</h2>
    <table>
        <thead>
            <tr>
                <th>Section</th>
                <th>Change</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($differences as $section => $changes)
                <tr>
                    <td>{{ is_string($section) ? $section : 'Difference' }}</td>
                    <td>
                        @if (is_array($changes))
                            <pre style="white-space: pre-wrap; margin: 0;">{{ json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            {{ $changes }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No differences detected.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
