@php
    use App\Support\ControlledDocument;

    $intakeData = collect($sample->client_submitted_info ?? []);
    $resolvedProfiles = collect($intakeData->get('resolved_profiles', []));
    $requiredParameters = collect($intakeData->get('required_parameters', []));
    $conditioningLabels = [
        'accepted' => 'Aceite',
        'restricted' => 'Aceite com restrições',
        'rejected' => 'Rejeitada ou em quarentena',
    ];
    $requestedServices = collect($sample->requested_services ?? [])
        ->map(fn ($service) => is_array($service) ? ($service['name'] ?? $service['label'] ?? implode(' - ', array_filter($service))) : $service)
        ->filter()
        ->implode('; ');
    $receivedBy = $sample->received_by_label ?: $sample->receivedBy?->name;

    $documentTitle = 'Registo de Recepção de Amostra';
    $documentNumber = $sample->code ?: str_pad((string) $sample->id, 6, '0', STR_PAD_LEFT);
    $issueDate = $date;
    $controlRows = [
        ['N.º', $documentNumber],
        ['Recepção', $sample->received_at?->format('d/m/Y H:i') ?: ControlledDocument::NOT_RECORDED],
        ['Emissão', $date],
    ];
    $footerNotice = 'O código da entrada identifica a amostra na colheita, no ensaio, na verificação e no relatório.';
    $verification = $documentNumber;
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::sections([
        ['Amostra', ControlledDocument::keyValueGrid([
            ['label' => 'Código da entrada', 'value' => $sample->code],
            ['label' => 'Designação', 'value' => $sample->name],
            ['label' => 'Tipo de amostra', 'value' => $sample->sample_type],
            ['label' => 'Embalagem', 'value' => $sample->packaging?->name],
            ['label' => 'Produto colhido', 'value' => $sample->collectionProduct?->code?->code],
            ['label' => 'Proposta', 'value' => $sample->proposal?->code ?? ($sample->proposal_id ? '#'.$sample->proposal_id : null)],
        ])],
        ['Cliente', ControlledDocument::keyValueGrid([
            ['label' => 'Cliente', 'value' => $sample->customer?->name],
            ['label' => 'Código do cliente', 'value' => $sample->customer?->code],
            ['label' => 'Instalação', 'value' => $sample->warehouse?->name],
            ['label' => 'Laboratório', 'value' => $sample->lab?->name],
            ['label' => 'Departamento', 'value' => $sample->department?->name],
        ])],
        ['Recepção e estado da amostra', ControlledDocument::keyValueGrid([
            ['label' => 'Recebida em', 'value' => $sample->received_at?->format('d/m/Y H:i')],
            ['label' => 'Recebida por', 'value' => $receivedBy],
            ['label' => 'Amostragem', 'value' => $sample->collected_by_lab === null ? null : ($sample->collected_by_lab ? 'Realizada pelo laboratório' : 'Realizada pelo cliente')],
            ['label' => 'Data de colheita', 'value' => $sample->collected_at?->format('d/m/Y H:i')],
            ['label' => 'Decisão de aceitação', 'value' => $conditioningLabels[(string) $intakeData->get('conditioning_status')] ?? null],
            ['label' => 'Estado da embalagem', 'value' => $intakeData->get('packaging_condition')],
            ['label' => 'Condição térmica', 'value' => $intakeData->get('temperature_condition')],
            ['label' => 'Cadeia de custódia', 'value' => $intakeData->get('chain_of_custody_notes'), 'wide' => true],
            ['label' => 'Observações de integridade', 'value' => $intakeData->get('integrity_observations'), 'wide' => true],
        ])],
        ['Âmbito analítico pedido', ControlledDocument::keyValueGrid([
            ['label' => 'Serviços pedidos', 'value' => $requestedServices, 'wide' => true],
            ['label' => 'Perfis', 'value' => $resolvedProfiles->pluck('name')->filter()->implode(', '), 'wide' => true],
            ['label' => 'Parâmetros ('.$requiredParameters->count().')', 'value' => $requiredParameters->map(fn ($parameter) => trim(($parameter['code'] ?? '').' '.($parameter['name'] ?? '')))->filter()->implode('; '), 'wide' => true],
            ['label' => 'Início dos ensaios', 'value' => $sample->analysis_start_date?->format('d/m/Y H:i')],
            ['label' => 'Fim dos ensaios', 'value' => $sample->analysis_end_date?->format('d/m/Y H:i')],
        ])],
        ['Observações', filled($sample->obs) ? '<p class="doc-text">'.nl2br(e($sample->obs), false).'</p>' : ''],
        ['Assinaturas', ControlledDocument::authorisation([
            ['name' => $receivedBy, 'caption' => 'Recebido pelo laboratório'],
            ['name' => null, 'caption' => 'Entregue por (nome e assinatura)'],
        ]), true],
    ]) !!}

    {!! ControlledDocument::endMark('registo') !!}
@endsection
