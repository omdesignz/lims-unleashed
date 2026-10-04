@php
    use App\Support\ControlledDocument;

    $statusLabels = ['opened' => 'Aberta', 'in_progress' => 'Em curso', 'resolved' => 'Resolvida', 'closed' => 'Encerrada'];
    $severityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'];
    $categoryLabels = ['quality' => 'Qualidade', 'safety' => 'Segurança', 'environmental' => 'Ambiental', 'regulatory' => 'Regulatório', 'other' => 'Outro'];
    $text = fn ($value): string => '<div class="doc-text">'.nl2br(e((string) $value), false).'</div>';

    $documentTitle = 'Registo de Não Conformidade';
    $documentNumber = (string) $nonConformity->nc_number;
    $issueDate = (string) $exportDate;
    $controlRows = [
        ['N.º', $documentNumber],
        ['Estado', $statusLabels[(string) $nonConformity->status] ?? (string) $nonConformity->status],
        ['Severidade', $severityLabels[(string) $nonConformity->severity] ?? (string) $nonConformity->severity],
        ['Emissão', $issueDate],
    ];
    $footerNotice = $nonConformity->trashed() ? 'Registo arquivado · Histórico preservado' : 'Registo activo';

    $actions = collect($nonConformity->actions ?? [])->map(function ($action) use ($text): string {
        $heading = 'Acção #'.$action->id.' · '.($action->trashed() ? 'Arquivada · Histórico preservado' : 'Activa');

        return '<p class="doc-subheading">'.e($heading).'</p>'.ControlledDocument::keyValueGrid([
            ['label' => 'Correcção', 'value' => $action->correction, 'wide' => true],
            ['label' => 'Acção correctiva', 'value' => $action->corrective_action, 'wide' => true],
            ['label' => 'Prazo', 'value' => $action->due_at?->format('d/m/Y')],
            ['label' => 'Aprovada em', 'value' => $action->approved_at?->format('d/m/Y H:i')],
            ['label' => 'Eficaz', 'value' => $action->was_effective === null ? 'Não avaliada' : ($action->was_effective ? 'Sim' : 'Não')],
            ['label' => 'Arquivada em', 'value' => $action->deleted_at?->format('d/m/Y H:i')],
            ['label' => 'Evidências', 'value' => $action->evidence, 'wide' => true],
        ]);
    })->implode('');

    $cycle = '<p class="doc-notes">As datas e evidências correspondem ao ciclo actual. A reabertura não apaga o histórico dos ciclos anteriores.</p>'
        .ControlledDocument::keyValueGrid(collect($lifecycleSummary)->map(fn (array $row): array => [
            'label' => $row[0],
            'value' => filled($row[1]) ? $row[1] : 'Não registada',
            'wide' => true,
        ])->all());

    $history = collect($lifecycleHistory)->map(fn (array $entry): string => '<tr class="lifecycle-entry">'
        .'<td>'.e('Revisão '.$entry['revision'].' - '.$entry['action']).'</td>'
        .'<td>'.e((string) $entry['at']).'</td>'
        .'<td>'.e((string) $entry['actor']).'</td>'
        .'<td>'.e($entry['from'].' → '.$entry['to']).'</td>'
        .'<td>'.nl2br(e(filled($entry['evidence']) ? (string) $entry['evidence'] : 'Sem observação adicional'), false).'</td>'
        .'</tr>')->implode('');
    $historyHtml = $history === ''
        ? '<p class="doc-text">Não existem etapas registadas neste fluxo. Não foram inferidas aprovações nem datas históricas.</p>'
        : '<table class="doc-results doc-plain"><thead><tr><th style="width:22%;">Etapa</th><th style="width:16%;">Data</th><th style="width:16%;">Responsável</th><th style="width:18%;">Transição</th><th>Evidência</th></tr></thead><tbody>'.$history.'</tbody></table>';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::sections([
        ['Identificação', ControlledDocument::keyValueGrid([
            ['label' => 'Título', 'value' => $nonConformity->title, 'wide' => true],
            ['label' => 'Categoria', 'value' => $categoryLabels[(string) $nonConformity->category] ?? $nonConformity->category],
            ['label' => 'Laboratório', 'value' => $nonConformity->lab?->name],
            ['label' => 'Departamento', 'value' => $nonConformity->department?->name],
            ['label' => 'Relatada por', 'value' => $nonConformity->reported_by],
            ['label' => 'Data do relato', 'value' => $nonConformity->reported_at?->format('d/m/Y H:i')],
            ['label' => 'Prazo', 'value' => $nonConformity->due_date?->format('d/m/Y')],
            ['label' => 'Atribuída a', 'value' => $nonConformity->assigned_to],
        ])],
        ['Descrição', filled($nonConformity->description) ? $text($nonConformity->description) : ''],
        ['Referências relacionadas', ControlledDocument::keyValueGrid([
            ['label' => 'Amostra', 'value' => $nonConformity->sample_id],
            ['label' => 'Método de ensaio', 'value' => $nonConformity->test_method],
            ['label' => 'Equipamento', 'value' => $nonConformity->equipment_id],
            ['label' => 'Lote', 'value' => $nonConformity->batch_number],
            ['label' => 'Área de ocorrência', 'value' => $nonConformity->occurrence_area],
        ])],
        ['Análise', ControlledDocument::keyValueGrid([
            ['label' => 'Causa raiz', 'value' => $nonConformity->root_cause, 'wide' => true],
            ['label' => 'Acções correctivas', 'value' => $nonConformity->corrective_actions, 'wide' => true],
            ['label' => 'Acções preventivas', 'value' => $nonConformity->preventive_actions, 'wide' => true],
            ['label' => 'Comentários', 'value' => $nonConformity->comments, 'wide' => true],
        ])],
        ['Acções correctivas registadas', $actions],
        ['Ciclo actual: resolução, verificação e encerramento', $cycle],
        ['Histórico do fluxo', $historyHtml],
    ]) !!}

    {!! ControlledDocument::endMark('registo') !!}
@endsection
