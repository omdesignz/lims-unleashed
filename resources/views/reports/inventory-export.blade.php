<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 18mm 15mm; }
        body { color: #17212f; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .masthead { border-bottom: 2px solid #216e81; margin-bottom: 18px; padding-bottom: 14px; }
        .eyebrow { color: #216e81; font-size: 8px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 20px; line-height: 1.25; margin: 5px 0 7px; }
        .meta { color: #586779; font-size: 8px; line-height: 1.6; }
        .summary { background: #eef5f6; border: 1px solid #d7e7e9; border-radius: 7px; margin-bottom: 14px; padding: 8px 11px; }
        table { border-collapse: collapse; width: 100%; }
        thead { display: table-header-group; }
        th { background: #183844; border: 1px solid #183844; color: #ffffff; font-size: 7px; padding: 7px 5px; text-align: left; }
        td { border-bottom: 1px solid #dbe4e8; font-size: 8px; padding: 6px 5px; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f6f9fa; }
        tr { page-break-inside: avoid; }
        .empty { color: #586779; padding: 18px 8px; text-align: center; }
        .footer { border-top: 1px solid #dbe4e8; color: #586779; font-size: 7px; margin-top: 20px; padding-top: 8px; }
    </style>
</head>
<body>
    <header class="masthead">
        <div class="eyebrow">{{ $lab_name ?: config('app.name') }} · Inventário</div>
        <h1>{{ $title }}</h1>
        <div class="meta">Emitido em {{ $generated_at }} · Por {{ $generated_by }}</div>
    </header>

    <div class="summary">{{ count($rows) }} {{ count($rows) === 1 ? 'registo' : 'registos' }} neste relatório. Quantidades apresentadas na unidade de cada artigo.</div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value ?? '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($columns) }}">Sem registos para os filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Relatório operacional de inventário · {{ $lab_name ?: config('app.name') }}</div>
</body>
</html>
