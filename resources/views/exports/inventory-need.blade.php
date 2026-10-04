@php
    use App\Support\ControlledDocument;

    $documentTitle = 'Requisição Interna de Material';
    $documentNumber = (string) $need->reference;
    $issueDate = (string) $printedDate;
    $controlRows = [
        ['N.º', $documentNumber],
        ['Estado', (string) $statusLabel],
        ['Necessário até', $need->needed_by_date?->format('d/m/Y') ?: ControlledDocument::NOT_RECORDED],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Emitido por '.$printedBy;
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::sections([
        ['Pedido', ControlledDocument::keyValueGrid([
            ['label' => 'Laboratório', 'value' => $need->lab?->name],
            ['label' => 'Departamento', 'value' => $need->department?->name],
            ['label' => 'Requisitante', 'value' => $need->requestedBy?->name],
            ['label' => 'Submetida em', 'value' => $need->submitted_at?->format('d/m/Y H:i')],
            ['label' => 'Aprovador', 'value' => $need->approvedBy?->name],
            ['label' => 'Pedido de compra', 'value' => $need->inventoryOrder?->reference],
            ['label' => 'Justificação', 'value' => $need->justification, 'wide' => true],
            ['label' => 'Notas de aprovação', 'value' => $need->approval_notes, 'wide' => true],
        ])],
    ]) !!}

    <div class="doc-section">
        <div class="doc-section-title"><span class="doc-section-number">2.</span> Itens</div>
        <table class="doc-results doc-plain">
            <thead>
                <tr>
                    <th style="width:30%;">Item</th>
                    <th style="width:14%;">Armazém</th>
                    <th class="doc-num">Qtd. pedida</th>
                    <th class="doc-num">Qtd. aprovada</th>
                    <th class="doc-num">Preço estimado</th>
                    <th style="width:22%;">Notas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($need->items as $item)
                    @php $unit = $item->inventoryItem?->unit?->code; @endphp
                    <tr>
                        <td>{{ $item->inventoryItem?->name ?: ControlledDocument::NOT_RECORDED }}@if($item->inventoryItem?->code)<br><span class="doc-auth-role">{{ $item->inventoryItem->code }}</span>@endif</td>
                        <td>{{ $item->warehouse?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td class="doc-num">{{ $item->quantity_requested }} {{ $unit }}</td>
                        <td class="doc-num">{{ $item->quantity_approved ? $item->quantity_approved.' '.$unit : ControlledDocument::NOT_RECORDED }}</td>
                        <td class="doc-num">{{ $item->estimated_unit_price ? number_format((float) $item->estimated_unit_price, 2, ',', '.') : ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $item->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="doc-notes">Linhas: {{ $need->items->count() }} · pedidas: {{ $requestedLineCount }} · aprovadas: {{ $approvedLineCount ?: ControlledDocument::NOT_RECORDED }}</p>
    </div>

    <table class="doc-split doc-plain"><tr>
        <td class="doc-split-notes"></td>
        <td class="doc-split-totals">
            <table class="doc-totals doc-plain">
                <tr class="doc-totals-grand"><td>Montante estimado</td><td style="text-align:right;">{{ number_format((float) $estimatedTotalAmount, 2, ',', '.') }}</td></tr>
            </table>
        </td>
    </tr></table>

    {!! ControlledDocument::section(3, 'Assinaturas', ControlledDocument::authorisation([
        ['name' => $need->requestedBy?->name, 'caption' => 'Requisitante'],
        ['name' => $need->approvedBy?->name, 'caption' => 'Aprovação'],
    ]), keepTogether: true) !!}
@endsection
