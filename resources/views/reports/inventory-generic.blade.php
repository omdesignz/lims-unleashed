<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin: 24px 0 8px; }
        p { margin: 0 0 4px; color: #475569; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; }
        .meta { margin-bottom: 16px; }
        .section { margin-top: 16px; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ ucfirst(str_replace('_', ' ', $reportType ?? 'inventory')) }} Report</h1>
    <div class="meta">
        <p>Generated at: {{ now()->format('Y-m-d H:i') }}</p>
        @if(!empty($dateRange['start']) || !empty($dateRange['end']))
            <p>Date range: {{ optional($dateRange['start'] ?? null)->format('Y-m-d') }} to {{ optional($dateRange['end'] ?? null)->format('Y-m-d') }}</p>
        @endif
    </div>

    @foreach(($data ?? []) as $section => $value)
        <div class="section">
            <h2>{{ ucfirst(str_replace('_', ' ', $section)) }}</h2>

            @if(is_array($value) && count($value) > 0)
                @php
                    $first = $value[array_key_first($value)];
                @endphp

                @if(is_array($first))
                    <table>
                        <thead>
                            <tr>
                                @foreach(array_keys($first) as $heading)
                                    <th>{{ ucfirst(str_replace('_', ' ', $heading)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($value as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td>{{ is_scalar($cell) ? $cell : json_encode($cell) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($value as $itemKey => $itemValue)
                                <tr>
                                    <td>{{ $itemKey }}</td>
                                    <td>{{ is_scalar($itemValue) ? $itemValue : json_encode($itemValue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @elseif($value instanceof \Illuminate\Support\Collection && $value->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            @foreach(array_keys($value->first()->toArray()) as $heading)
                                <th>{{ ucfirst(str_replace('_', ' ', $heading)) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($value as $row)
                            <tr>
                                @foreach($row->toArray() as $cell)
                                    <td>{{ is_scalar($cell) ? $cell : json_encode($cell) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">{{ is_scalar($value) ? $value : 'No records for this section.' }}</p>
            @endif
        </div>
    @endforeach
</body>
</html>
