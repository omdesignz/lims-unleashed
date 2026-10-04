@php
    use App\Support\ControlledDocument;

    $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
    $sample = $discard->sample;
    $discardedAt = $discard->discarded_at ?: now();

    $documentTitle = 'Registo de Eliminação de Amostra';
    $documentNumber = 'DISC-'.str_pad((string) $discard->id, 6, '0', STR_PAD_LEFT);
    $issueDate = $date;
    $controlRows = [
        ['N.º', $documentNumber],
        ['Amostra', $sample?->code ?: ControlledDocument::NOT_RECORDED],
        ['Eliminação', $discardedAt->format('d/m/Y H:i')],
        ['Emissão', $date],
    ];
    $footerNotice = 'Este registo permanece associado ao registo original da amostra.';
    $verification = $documentNumber;
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::notice('Amostra eliminada.', 'A eliminação é definitiva. A amostra deixou de estar disponível para ensaio ou contra-análise.') !!}

    {!! ControlledDocument::sections([
        ['Amostra', ControlledDocument::keyValueGrid([
            ['label' => 'Código da entrada', 'value' => $sample?->code],
            ['label' => 'Designação', 'value' => $sample?->name],
            ['label' => 'Tipo de amostra', 'value' => $sample?->sample_type],
            ['label' => 'Cliente', 'value' => $sample?->customer?->name],
            ['label' => 'Recebida em', 'value' => $sample?->received_at?->format('d/m/Y H:i')],
            ['label' => 'Fim da retenção', 'value' => $sample?->retention_due_at?->format('d/m/Y')],
        ])],
        ['Eliminação', ControlledDocument::keyValueGrid([
            ['label' => 'Método', 'value' => $discard->discard_method],
            ['label' => 'Quantidade', 'value' => $discard->qty],
            ['label' => 'Data e hora', 'value' => $discardedAt->format('d/m/Y H:i')],
            ['label' => 'Executada por', 'value' => $discard->discardedBy?->name],
            ['label' => 'Laboratório', 'value' => $discard->lab?->name ?? $sample?->lab?->name],
            ['label' => 'Departamento', 'value' => $discard->department?->name ?? $sample?->department?->name],
        ])],
        ['Assinaturas', ControlledDocument::authorisation([
            ['name' => $discard->discardedBy?->name, 'caption' => 'Executado por'],
            ['name' => null, 'caption' => 'Verificado pela qualidade (nome, assinatura e data)'],
        ]), true],
    ]) !!}

    {!! ControlledDocument::endMark('registo') !!}
@endsection
