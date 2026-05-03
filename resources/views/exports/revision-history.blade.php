<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ISO 17025 Revision History</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 24px;
        }

        h1, h2, h3 {
            margin: 0 0 8px;
        }

        p {
            margin: 0 0 6px;
        }

        .section {
            margin-top: 20px;
        }

        .meta {
            display: table;
            width: 100%;
            margin-top: 12px;
        }

        .meta-row {
            display: table-row;
        }

        .meta-label,
        .meta-value {
            display: table-cell;
            padding: 4px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 180px;
            font-weight: bold;
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
            font-weight: bold;
        }

        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>ISO 17025 Revision History</h1>
    <p class="muted">Generated at {{ $exportDate->format('Y-m-d H:i:s') }}</p>

    <div class="section">
        <h2>Certificate Summary</h2>
        <div class="meta">
            <div class="meta-row">
                <div class="meta-label">Certificate Code</div>
                <div class="meta-value">{{ $certificate->code ?? 'N/A' }}</div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Customer</div>
                <div class="meta-value">{{ optional($certificate->customer)->name ?? 'N/A' }}</div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Warehouse</div>
                <div class="meta-value">{{ optional($certificate->warehouse)->name ?? 'N/A' }}</div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Current Version</div>
                <div class="meta-value">{{ $certificate->current_version ?? '1.0' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Revisions</h2>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Version</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Created By</th>
                    <th>Approved By</th>
                    <th>Effective Date</th>
                    <th>Current</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($revisions as $revision)
                    <tr>
                        <td>{{ $revision->revision_number }}</td>
                        <td>{{ $revision->version }}</td>
                        <td>{{ $revision->change_type }}</td>
                        <td>{{ $revision->change_reason }}</td>
                        <td>{{ optional($revision->createdBy)->name ?? 'N/A' }}</td>
                        <td>{{ optional($revision->approvedBy)->name ?? 'N/A' }}</td>
                        <td>{{ optional($revision->effective_date)?->format('Y-m-d H:i:s') ?? 'N/A' }}</td>
                        <td>{{ $revision->is_current ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No ISO revisions recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
