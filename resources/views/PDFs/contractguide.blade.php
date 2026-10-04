@php
    use App\Support\ControlledDocument;
    use Illuminate\Support\Carbon;

    $documentDate = filled($model->date) ? (function ($value) {
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    })($model->date) : null;

    $documentTitle = 'Guia de Contratação';
    $documentNumber = (string) $model->guide_no;
    $issueDate = now()->format('d/m/Y');
    $controlRows = [
        ['N.º', $documentNumber],
        ['Data', $documentDate ?: ControlledDocument::NOT_RECORDED],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Este documento não substitui o Boletim de Análises nem certifica a qualidade dos produtos.';
    $verification = $documentNumber;
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::sections([
        ['Requerente', ControlledDocument::keyValueGrid([
            ['label' => 'Empresa', 'value' => $model->customer?->name],
            ['label' => 'NIF', 'value' => $model->nif],
            ['label' => 'Estabelecimento', 'value' => $model->warehouse?->name],
            ['label' => 'Local de recolha', 'value' => $model->collection_point],
            ['label' => 'Telefone', 'value' => $model->contact],
            ['label' => 'Email', 'value' => $model->email],
        ])],
        ['Documentação de suporte', ControlledDocument::keyValueGrid([
            ['label' => 'Ponto de entrada', 'value' => $model->entry_point],
            ['label' => 'B/L ou carta de porte', 'value' => $model->bl],
            ['label' => 'Referência', 'value' => $model->ref_no],
            ['label' => 'Documento único (DU)', 'value' => $model->du_no],
        ])],
    ]) !!}

    <div class="doc-section">
        <div class="doc-section-title"><span class="doc-section-number">3.</span> Produtos para análise</div>
        <table class="doc-results doc-plain">
            <thead>
                <tr>
                    <th style="width:30%;">Produto</th>
                    <th>País de origem</th>
                    <th>Fabricante ou produtor</th>
                    <th>Marca</th>
                    <th>Lote</th>
                </tr>
            </thead>
            <tbody>
                @forelse($model->items as $item)
                    <tr>
                        <td>{{ $item->product?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $item->country?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $item->manufacturer ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $item->brand ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $item->lot ?: ControlledDocument::NOT_RECORDED }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Sem produtos registados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(filled($model->obs))
        {!! ControlledDocument::section(4, 'Observações', '<p class="doc-text">'.nl2br(e($model->obs), false).'</p>') !!}
    @endif

    {!! ControlledDocument::section(filled($model->obs) ? 5 : 4, 'Termos e condições', ControlledDocument::statements([
        'Nos termos do Decreto Presidencial n.º 179/18, de 2 de Agosto, a VAP Soluções compromete-se a realizar as análises dos produtos descritos nesta Guia de Contratação.',
        'A VAP Soluções obriga-se a efectuar as referidas análises e a apresentar o correspondente Boletim de Análises no prazo máximo de 15 dias.',
        'O presente documento não substitui o Boletim de Análises e não confere a certificação da qualidade do(s) produto(s).',
    ]), keepTogether: true) !!}

    {!! ControlledDocument::section(filled($model->obs) ? 6 : 5, 'Assinatura', ControlledDocument::authorisation([
        ['name' => 'O Director Geral', 'caption' => 'Nome, assinatura e carimbo'],
    ]), keepTogether: true) !!}

    {!! ControlledDocument::endMark('documento') !!}
@endsection
