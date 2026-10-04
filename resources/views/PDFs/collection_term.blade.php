@php
    use App\Support\ControlledDocument;
    use Illuminate\Support\Carbon;

    $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
    $collection = $model->collection;
    $site = $collection?->warehouse;
    $selectedReasons = collect($collection?->reasons ?? [])->pluck('id')->all();
    $formatDate = function ($value): ?string {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $collectionYear = $model->collection_date ? Carbon::parse($model->collection_date)->format('Y') : now()->format('Y');

    $documentTitle = 'Termo de Colheita de Amostras';
    $documentNumber = 'COL-'.$model->id.'-'.$collectionYear;
    $issueDate = now()->format('d/m/Y');
    $controlRows = [
        ['N.º', $documentNumber],
        ['Código laboratorial', $model->code?->code ?: ControlledDocument::NOT_RECORDED],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Registo de amostragem. Acompanha a amostra até à recepção no laboratório.';
    $verification = $documentNumber;

    $reasons = collect($reasons ?? [])
        ->map(fn ($reason): string => (in_array($reason->id, $selectedReasons, true) ? '[X] ' : '[  ] ').e($reason->name))
        ->implode('&nbsp;&nbsp;&nbsp;&nbsp;');
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::sections([
        ['Razões da amostragem', $reasons === '' ? '' : '<p class="doc-text">'.$reasons.'</p>'],
        ['Entidade requerente', ControlledDocument::keyValueGrid([
            ['label' => 'Requerente', 'value' => $collection?->customer?->name],
            ['label' => 'NIF', 'value' => $site?->nif],
            ['label' => 'Morada', 'value' => $site?->address],
            ['label' => 'Telefone', 'value' => implode(' / ', array_filter([$site?->primary_phone, $site?->alternative_phone]))],
            ['label' => 'Email', 'value' => $site?->email],
            ['label' => 'Email de cobrança', 'value' => $site?->invoicing_email],
        ])],
        ['Amostra colhida', ControlledDocument::keyValueGrid([
            ['label' => 'Designação', 'value' => $model->product?->name],
            ['label' => 'Marca comercial', 'value' => $model->comercial_brand],
            ['label' => 'Lote', 'value' => $model->lot],
            ['label' => 'Embalagem', 'value' => $model->packaging?->name],
            ['label' => 'Quantidade', 'value' => $model->qty],
            ['label' => 'Temperatura', 'value' => $model->temperature_value],
            ['label' => 'Data de produção', 'value' => $formatDate($model->production_date)],
            ['label' => 'Data de validade', 'value' => $formatDate($model->expiry_date)],
        ])],
        ['Amostragem', ControlledDocument::keyValueGrid([
            ['label' => 'Data de colheita', 'value' => $formatDate($model->collection_date)],
            ['label' => 'Amostragem', 'value' => $model->collected_by_lab === null ? null : ($model->collected_by_lab ? 'Realizada pelo laboratório' : 'Realizada pelo cliente')],
            ['label' => 'Local de colheita', 'value' => $model->location],
            ['label' => 'Plano de amostragem', 'value' => $model->sampling_plan_ref],
        ])],
        ['Observações', filled($model->obs) ? '<p class="doc-text">'.nl2br(e($model->obs), false).'</p>' : ''],
        ['Declaração', '<p class="doc-text">A amostragem foi efectuada em triplicado: duas (2) amostras seguem para o laboratório e uma (1) fica com a entidade requerente, como fiel depositário, para efeito de contra-análise.</p>'],
        ['Assinaturas', ControlledDocument::authorisation([
            ['name' => null, 'caption' => 'Requerente ou representante legal (nome, telefone e data)'],
            ['name' => null, 'caption' => 'Responsável pela colheita ou recepção (nome, telefone e data)'],
        ]), true],
    ]) !!}

    {!! ControlledDocument::endMark('termo') !!}
@endsection
