<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }
        
        .header h1 {
            color: #1e3a8a;
            margin: 0;
            font-size: 20px;
        }
        
        .header .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .filters {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .filters h3 {
            color: #374151;
            margin-top: 0;
            font-size: 14px;
        }
        
        .filters ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th {
            background-color: #1e3a8a;
            color: white;
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        
        .table td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-opened { background-color: #dbeafe; color: #1e40af; }
        .status-in_progress { background-color: #fef3c7; color: #92400e; }
        .status-resolved { background-color: #d1fae5; color: #065f46; }
        .status-closed { background-color: #f3f4f6; color: #374151; }
        
        .severity-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .severity-low { background-color: #d1fae5; color: #065f46; }
        .severity-medium { background-color: #fef3c7; color: #92400e; }
        .severity-high { background-color: #fed7aa; color: #9a3412; }
        .severity-critical { background-color: #fee2e2; color: #991b1b; }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 11px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mb-3 {
            margin-bottom: 12px;
        }
        
        .mt-3 {
            margin-top: 12px;
        }
        
        .summary {
            background-color: #f0f4ff;
            border: 1px solid #c7d2fe;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .summary h3 {
            color: #1e3a8a;
            margin-top: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="subtitle">
            Gerado em: {{ $exportDate }}
        </div>
    </div>
    
    @if(!empty($filters))
    <div class="filters">
        <h3>Filtros Aplicados:</h3>
        <ul>
            @if(!empty($filters['status']))
                <li>Status: {{ $filters['status'] }}</li>
            @endif
            @if(!empty($filters['severity']))
                <li>Severidade: {{ $filters['severity'] }}</li>
            @endif
            @if(!empty($filters['category']))
                <li>Categoria: {{ $filters['category'] }}</li>
            @endif
            @if(!empty($filters['start_date']))
                <li>Data inicial: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }}</li>
            @endif
            @if(!empty($filters['end_date']))
                <li>Data final: {{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}</li>
            @endif
        </ul>
    </div>
    @endif
    
    <div class="summary">
        <h3>Resumo:</h3>
        <p>Total de registros: {{ $nonConformities->count() }}</p>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Número NC</th>
                <th>Título</th>
                <th>Status</th>
                <th>Severidade</th>
                <th>Reportado por</th>
                <th>Data do Relato</th>
                <th>Data de Vencimento</th>
                <th>Laboratório</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nonConformities as $nc)
            <tr>
                <td>{{ $nc->nc_number }}</td>
                <td>{{ $nc->title }}</td>
                <td>
                    <span class="status-badge status-{{ $nc->status }}">
                        @switch($nc->status)
                            @case('opened')
                                Aberto
                                @break
                            @case('in_progress')
                                Em Andamento
                                @break
                            @case('resolved')
                                Resolvido
                                @break
                            @case('closed')
                                Fechado
                                @break
                        @endswitch
                    </span>
                </td>
                <td>
                    <span class="severity-badge severity-{{ $nc->severity }}">
                        @switch($nc->severity)
                            @case('low')
                                Baixa
                                @break
                            @case('medium')
                                Média
                                @break
                            @case('high')
                                Alta
                                @break
                            @case('critical')
                                Crítica
                                @break
                        @endswitch
                    </span>
                </td>
                <td>{{ $nc->reported_by }}</td>
                <td>{{ $nc->reported_at->format('d/m/Y H:i') }}</td>
                <td>{{ $nc->due_date ? $nc->due_date->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $nc->lab?->name ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        Sistema LIMS - Relatório gerado automaticamente
    </div>
</body>
</html>