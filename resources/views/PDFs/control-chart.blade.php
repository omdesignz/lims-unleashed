@php
    use App\Models\ControlChart;
    use App\Support\ControlChartDocument;
    use App\Support\ControlChartEvaluation;
    use App\Support\ControlledDocument;

    $limits = $chart->limits();
    $points = $document->points();
    $statistics = $document->statistics();
    $number = fn (?float $value): string => ControlChartDocument::number($value);

    $documentTitle = 'Carta de Controlo';
    $documentNumber = 'CC-'.str_pad((string) $chart->id, 4, '0', STR_PAD_LEFT);
    $issueDate = now()->format('d/m/Y');
    $controlRows = [
        ['N.º', $documentNumber],
        ['Pontos', (string) $statistics['count']],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Registo do controlo interno da qualidade · '.$chart->name;
    $verification = $documentNumber.' · '.$chart->name.' · '.$issueDate;

    $identification = ControlledDocument::keyValueGrid([
        ['label' => 'Carta', 'value' => $chart->name, 'wide' => true],
        ['label' => 'Tipo', 'value' => ControlChart::TYPES[$chart->chart_type] ?? $chart->chart_type],
        ['label' => 'Estado', 'value' => ControlChart::STATUSES[$chart->status] ?? $chart->status],
        ['label' => 'Parâmetro', 'value' => $chart->parameter ? trim(($chart->parameter->code ? $chart->parameter->code.' · ' : '').$chart->parameter->name) : null],
        ['label' => 'Método', 'value' => $chart->method],
        ['label' => 'Matriz', 'value' => $chart->matrix],
        ['label' => 'Unidade', 'value' => $chart->unit],
        ['label' => 'Material de controlo', 'value' => $chart->control_material ?: $chart->controlProduct?->name],
        ['label' => 'Origem dos pontos', 'value' => \App\Services\ControlChartFeed::feedsFromResults($chart)
            ? 'Resultados aprovados das amostras de '.($chart->controlProduct?->name ?? 'controlo').' e valores registados à mão'
            : 'Valores registados à mão', 'wide' => true],
        ['label' => 'Lote do material', 'value' => $chart->material_lot],
    ]);

    $limitsHtml = $limits === null
        ? '<p class="doc-text">Limites por definir: os pontos estão registados, mas não foram avaliados.</p>'
        : ControlledDocument::keyValueGrid([
            ['label' => $chart->isRange() ? 'Amplitude média (R̄)' : 'Linha central (LC)', 'value' => $number($limits['centre'])],
            ['label' => 'Desvio-padrão (s)', 'value' => $chart->isRange() ? null : $number($chart->standard_deviation)],
            ['label' => 'Limite superior de aviso (LSV)', 'value' => $number($limits['upper_warning'])],
            ['label' => 'Limite superior de acção (LSA)', 'value' => $number($limits['upper_action'])],
            ['label' => 'Limite inferior de aviso (LIV)', 'value' => $limits['lower_warning'] === null ? null : $number($limits['lower_warning'])],
            ['label' => 'Limite inferior de acção (LIA)', 'value' => $limits['lower_action'] === null ? null : $number($limits['lower_action'])],
            ['label' => 'Origem', 'value' => ControlChart::LIMIT_SOURCES[$chart->limits_source] ?? null],
            ['label' => 'Definidos em', 'value' => trim(($chart->limits_set_at?->format('d/m/Y H:i') ?? '').($chart->limitsSetBy ? ' · '.$chart->limitsSetBy->name : ''), ' ·')],
            ['label' => 'Fundamento', 'value' => $chart->limits_basis, 'wide' => true],
        ]).'<p class="doc-notes">'.e($chart->isRange()
            ? 'Carta de amplitudes de duplicados: LSV = 2,512 × R̄; LSA = 3,267 × R̄. A amplitude não tem limites inferiores.'
            : 'Carta de médias: limites de aviso LC ± 2s; limites de acção LC ± 3s.').'</p>';

    $chartHtml = '<div style="text-align:center;"><img src="data:image/svg+xml;base64,'.base64_encode($document->svg()).'" alt="" style="width:180mm; height:71mm;"></div>'
        .'<p class="doc-notes">Pontos incluídos, por ordem de medição (n.º do ponto no eixo horizontal). Vermelho: fora de controlo; âmbar: entre os limites de aviso e de acção.</p>';

    $statisticsHtml = ControlledDocument::keyValueGrid([
        ['label' => 'Pontos incluídos', 'value' => (string) $statistics['count']],
        ['label' => 'Pontos excluídos', 'value' => $statistics['excluded'] > 0 ? (string) $statistics['excluded'] : null],
        ['label' => 'Média observada', 'value' => $statistics['mean'] === null ? null : $number($statistics['mean'])],
        ['label' => 'Desvio-padrão observado', 'value' => $statistics['standard_deviation'] === null ? null : $number($statistics['standard_deviation'])],
        ['label' => 'Mínimo', 'value' => $statistics['minimum'] === null ? null : $number($statistics['minimum'])],
        ['label' => 'Máximo', 'value' => $statistics['maximum'] === null ? null : $number($statistics['maximum'])],
        ['label' => 'Dentro dos limites de aviso', 'value' => $statistics['within_warning'] === null ? null : str_replace('.', ',', (string) $statistics['within_warning']).' %'],
        ['label' => 'Pontos fora de controlo', 'value' => $limits === null ? null : (string) $statistics['out_of_control']],
        ['label' => 'Fora de controlo sem acção registada', 'value' => $limits === null ? null : (string) $statistics['open_actions']],
    ]);

    $rows = collect($points)->map(fn (array $point): string => '<tr>'
        .'<td class="doc-num">'.$point['sequence'].'</td>'
        .'<td>'.e((string) $point['measured_at']).'</td>'
        .'<td>'.e((string) ($point['run_reference'] ?: ControlledDocument::NOT_RECORDED)).'</td>'
        .($chart->isRange() ? '<td class="doc-num">'.e($number($point['replicate_a'])).' / '.e($number($point['replicate_b'])).'</td>' : '')
        .'<td class="doc-num doc-result-value">'.e($point['value_label']).'</td>'
        .'<td'.($point['state'] === ControlChartEvaluation::OUT_OF_CONTROL ? ' class="doc-nonconforming"' : '').'>'.e($point['state_label'])
            .($point['rule_labels'] !== [] ? '<br><span class="doc-flag">'.e(implode(' ', $point['rule_labels'])).'</span>' : '')
            .($point['excluded'] && filled($point['exclusion_reason']) ? '<br><span class="doc-flag">'.e($point['exclusion_reason']).'</span>' : '').'</td>'
        .'<td>'.e((string) ($point['recorded_by'] ?: ControlledDocument::NOT_RECORDED)).'</td>'
        .'</tr>')->implode('');
    $pointsHtml = $rows === ''
        ? '<p class="doc-text">Sem pontos registados.</p>'
        : '<table class="doc-results doc-plain"><thead><tr><th class="doc-num" style="width:6%;">N.º</th><th style="width:12%;">Data</th><th style="width:18%;">Corrida / lote</th>'
            .($chart->isRange() ? '<th class="doc-num">Réplicas</th>' : '')
            .'<th class="doc-num">'.($chart->isRange() ? 'Amplitude' : 'Valor').($chart->unit ? ' ('.e($chart->unit).')' : '').'</th><th style="width:30%;">Avaliação</th><th>Registado por</th></tr></thead><tbody>'.$rows.'</tbody></table>';

    $actionRows = collect($points)->filter(fn (array $point): bool => $point['state'] === ControlChartEvaluation::OUT_OF_CONTROL)
        ->map(fn (array $point): array => [
            'label' => 'Ponto '.$point['sequence'].' · '.$point['measured_at'],
            'value' => filled($point['corrective_action'])
                ? $point['corrective_action'].' ('.trim($point['corrective_action_by'].' · '.$point['corrective_action_at'], ' ·').')'
                : 'Sem acção registada.',
            'wide' => true,
        ])->values()->all();

    $rulesHtml = ControlledDocument::statements(array_values(ControlChartEvaluation::RULES));
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::sections([
        ['Identificação', $identification],
        ['Limites de controlo', $limitsHtml],
        ['Carta', $chartHtml, true],
        ['Resumo', $statisticsHtml],
        ['Pontos', $pointsHtml],
        ['Acções sobre pontos fora de controlo', ControlledDocument::keyValueGrid($actionRows)],
        ['Regras de interpretação', '<p class="doc-text">Um ponto está fora de controlo quando:</p>'.$rulesHtml],
        ['Notas', filled($chart->notes) ? '<p class="doc-text">'.nl2br(e($chart->notes), false).'</p>' : ''],
    ]) !!}
    {!! ControlledDocument::endMark('registo') !!}
@endsection
