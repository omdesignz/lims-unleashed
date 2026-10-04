<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 20mm;
            margin-footer: 8mm;
            footer: html_dossier-pages;
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
        
        .info-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-card h3 {
            page-break-after: avoid;
            color: #374151;
            margin-top: 0;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .info-item {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #4b5563;
            display: block;
            margin-bottom: 2px;
        }
        
        .info-value {
            color: #111827;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-badge {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .severity-badge {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .actions-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        
        .action-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .action-header {
            page-break-after: avoid;
            background-color: #1e3a8a;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .lifecycle-entry {
            page-break-inside: avoid;
        }
        
        .long-text {
            white-space: pre-line;
            line-height: 1.6;
        }
        
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
        
        .mt-3 {
            margin-top: 12px;
        }
        
        .mb-3 {
            margin-bottom: 12px;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .text-box {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <htmlpagefooter name="dossier-pages">
        <div style="font-size: 9px; color: #6b7280; text-align: right;">{{ $nonConformity->nc_number }} | {PAGENO} / {nbpg}</div>
    </htmlpagefooter>
    <sethtmlpagefooter name="dossier-pages" value="on" page="ALL" />
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="subtitle">
            {{ $nonConformity->lab?->name }}<br>
            {{ $nonConformity->trashed() ? 'Arquivada · Histórico preservado' : 'Registo activo' }}<br>
            Gerado em: {{ $exportDate }}
        </div>
    </div>
    
    <div class="info-card">
        <h3>Informações Básicas</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Número NC:</span>
                <span class="info-value">{{ $nonConformity->nc_number }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Título:</span>
                <span class="info-value">{{ $nonConformity->title }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Estado:</span>
                <span class="badge status-badge">
                    @switch($nonConformity->status)
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
            </div>
            <div class="info-item">
                <span class="info-label">Severidade:</span>
                <span class="badge severity-badge">
                    @switch($nonConformity->severity)
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
            </div>
            <div class="info-item">
                <span class="info-label">Categoria:</span>
                <span class="info-value">
                    @switch($nonConformity->category)
                        @case('quality')
                            Qualidade
                            @break
                        @case('safety')
                            Segurança
                            @break
                        @case('environmental')
                            Ambiental
                            @break
                        @case('regulatory')
                            Regulatório
                            @break
                        @case('other')
                            Outro
                            @break
                    @endswitch
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Laboratório:</span>
                <span class="info-value">{{ $nonConformity->lab?->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Departamento:</span>
                <span class="info-value">{{ $nonConformity->department?->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Reportado por:</span>
                <span class="info-value">{{ $nonConformity->reported_by }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Data do Relato:</span>
                <span class="info-value">{{ $nonConformity->reported_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Data de Vencimento:</span>
                <span class="info-value">{{ $nonConformity->due_date ? $nonConformity->due_date->format('d/m/Y') : 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Atribuído para:</span>
                <span class="info-value">{{ $nonConformity->assigned_to ?? 'N/A' }}</span>
            </div>
        </div>
    </div>
    
    <div class="info-card">
        <h3>Descrição</h3>
        <div class="text-box">
            <div class="long-text">{{ $nonConformity->description }}</div>
        </div>
    </div>
    
    @if($nonConformity->sample_id || $nonConformity->test_method || $nonConformity->equipment_id || $nonConformity->batch_number)
    <div class="info-card">
        <h3>Informações Relacionadas</h3>
        <div class="info-grid">
            @if($nonConformity->sample_id)
            <div class="info-item">
                <span class="info-label">ID da Amostra:</span>
                <span class="info-value">{{ $nonConformity->sample_id }}</span>
            </div>
            @endif
            @if($nonConformity->test_method)
            <div class="info-item">
                <span class="info-label">Método de Teste:</span>
                <span class="info-value">{{ $nonConformity->test_method }}</span>
            </div>
            @endif
            @if($nonConformity->equipment_id)
            <div class="info-item">
                <span class="info-label">ID do Equipamento:</span>
                <span class="info-value">{{ $nonConformity->equipment_id }}</span>
            </div>
            @endif
            @if($nonConformity->batch_number)
            <div class="info-item">
                <span class="info-label">Número do Lote:</span>
                <span class="info-value">{{ $nonConformity->batch_number }}</span>
            </div>
            @endif
            @if($nonConformity->occurrence_area)
            <div class="info-item">
                <span class="info-label">Área de Ocorrência:</span>
                <span class="info-value">{{ $nonConformity->occurrence_area }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif
    
    @if(filled($nonConformity->root_cause) || filled($nonConformity->corrective_actions) || filled($nonConformity->preventive_actions) || filled($nonConformity->comments))
    <div class="info-card">
        <h3>Análise e Observações</h3>
        <div class="info-grid full-width">
            @if(filled($nonConformity->corrective_actions))
            <div class="info-item">
                <span class="info-label">Acções Correctivas:</span>
                <div class="text-box mt-3"><div class="long-text">{{ $nonConformity->corrective_actions }}</div></div>
            </div>
            @endif
            @if(filled($nonConformity->root_cause))
            <div class="info-item">
                <span class="info-label">Causa Raiz:</span>
                <div class="text-box mt-3">
                    <div class="long-text">{{ $nonConformity->root_cause }}</div>
                </div>
            </div>
            @endif
            @if(filled($nonConformity->preventive_actions))
            <div class="info-item">
                <span class="info-label">Acções Preventivas:</span>
                <div class="text-box mt-3">
                    <div class="long-text">{{ $nonConformity->preventive_actions }}</div>
                </div>
            </div>
            @endif
            @if(filled($nonConformity->comments))
            <div class="info-item">
                <span class="info-label">Comentários:</span>
                <div class="text-box mt-3">
                    <div class="long-text">{{ $nonConformity->comments }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
    
    @if($nonConformity->actions && $nonConformity->actions->count() > 0)
    <div class="actions-section">
        <div class="info-card">
            <h3>Acções Corretivas</h3>
            @foreach($nonConformity->actions as $index => $action)
            <div class="action-card">
                <div class="action-header">ACÇÃO #{{ $action->id }} · {{ $action->trashed() ? 'Arquivada · Histórico preservado' : 'Activa' }}</div>
                <div class="info-grid">
                    @if(filled($action->correction))
                    <div class="info-item full-width">
                        <span class="info-label">Correcção:</span>
                        <div class="text-box mt-3">
                            <div class="long-text">{{ $action->correction }}</div>
                        </div>
                    </div>
                    @endif
                    @if(filled($action->corrective_action))
                    <div class="info-item full-width">
                        <span class="info-label">Acção Corretiva:</span>
                        <div class="text-box mt-3">
                            <div class="long-text">{{ $action->corrective_action }}</div>
                        </div>
                    </div>
                    @endif
                    <div class="info-item">
                        <span class="info-label">Data de Vencimento:</span>
                        <span class="info-value">{{ $action->due_at ? $action->due_at->format('d/m/Y') : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Data de Aprovação:</span>
                        <span class="info-value">{{ $action->approved_at ? $action->approved_at->format('d/m/Y H:i') : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Efectiva:</span>
                        <span class="info-value">{{ $action->was_effective === null ? 'Não avaliada' : ($action->was_effective ? 'Sim' : 'Não') }}</span>
                    </div>
                    @if(filled($action->evidence))
                    <div class="info-item full-width">
                        <span class="info-label">Evidências:</span>
                        <div class="long-text">{{ $action->evidence }}</div>
                    </div>
                    @endif
                    @if($action->deleted_at)
                    <div class="info-item">
                        <span class="info-label">Arquivada em:</span>
                        <span class="info-value">{{ $action->deleted_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <div class="info-card">
        <h3>Ciclo actual: resolução, verificação e encerramento</h3>
        <p>Datas e evidências abaixo correspondem ao ciclo actual. A reabertura não apaga o histórico dos ciclos anteriores.</p>
        @foreach($lifecycleSummary as [$label, $value])
        <div class="info-item">
            <span class="info-label">{{ $label }}:</span>
            <div class="long-text">{{ filled($value) ? $value : 'Não registada' }}</div>
        </div>
        @endforeach
    </div>

    <h3 @class(['page-break' => count($lifecycleHistory) > 0]) style="page-break-after: avoid;">Histórico do fluxo</h3>
    @forelse($lifecycleHistory as $entry)
    <div class="action-card lifecycle-entry">
        <div class="action-header">Revisão {{ $entry['revision'] }} - {{ $entry['action'] }}</div>
        <p>{{ $entry['at'] }} | {{ $entry['actor'] }}</p>
        <p>{{ $entry['from'] }} → {{ $entry['to'] }}</p>
        <div class="long-text">{{ filled($entry['evidence']) ? $entry['evidence'] : 'Sem observação adicional' }}</div>
    </div>
    @empty
    <p>Não existem etapas registadas neste fluxo. Não foram inferidas aprovações nem datas históricas.</p>
    @endforelse

    <div class="footer">
        Sistema LIMS - Relatório gerado automaticamente
    </div>
</body>
</html>
