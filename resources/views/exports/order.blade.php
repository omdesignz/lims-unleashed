@php
    use App\Support\ControlledDocument;

    $lineStatusLabels = [
        'PENDING' => 'Pendente',
        'ORDERED' => 'Encomendado',
        'PARTIALLY_RECEIVED' => 'Parcial',
        'RECEIVED' => 'Recebido',
        'CANCELLED' => 'Cancelado',
    ];
    $money = fn ($value, ?string $currency): string => trim(number_format((float) $value, 2, ',', ' ').' '.$currency);

    $documentTitle = 'Pedido de Compra';
    $documentNumber = (string) ($order->reference ?: ($order->seq ?? $order->id));
    $issueDate = (string) $printedDate;
    $controlRows = [
        ['N.º', $documentNumber],
        ['Data do pedido', (string) $orderDate],
        ['Estado', (string) $orderStatus],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Emitido por '.$printedBy.' · Criado em '.$createdDate;
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    <table class="doc-parties doc-plain"><tr>
        <td class="doc-party">
            <div class="doc-party-label">Fornecedor</div>
            <div class="doc-party-lines">
                {{ $order->supplier?->name ?: ControlledDocument::NOT_RECORDED }}
                @if($order->supplier?->address)<br>{{ $order->supplier->address }}@endif
            </div>
        </td>
        <td class="doc-party">
            <div class="doc-party-label">Pedido</div>
            <div class="doc-party-lines">
                Referência: {{ $documentNumber }}<br>
                Criado por: {{ $order->user?->name ?: ControlledDocument::NOT_RECORDED }}<br>
                Linhas: {{ $totalItems }} · com entrada: {{ $receivedLineCount }} · pendentes: {{ $pendingLineCount }}
            </div>
        </td>
    </tr></table>

    <div class="doc-section">
        <div class="doc-section-title">Itens do pedido</div>
        <table class="doc-results doc-plain">
            <thead>
                <tr>
                    <th style="width:4%;">#</th>
                    <th style="width:24%;">Item</th>
                    <th class="doc-num" style="width:10%;">Pedido</th>
                    <th class="doc-num" style="width:10%;">Recebido</th>
                    <th class="doc-num" style="width:10%;">Pendente</th>
                    <th style="width:14%;">Armazém</th>
                    <th class="doc-num" style="width:10%;">Preço unit.</th>
                    <th class="doc-num" style="width:9%;">Total</th>
                    <th style="width:9%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $index => $item)
                    @php
                        $itemStatus = $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status;
                        $unit = $item->item?->unit?->code;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->item?->name ?: ControlledDocument::NOT_RECORDED }}@if($item->item?->code)<br><span class="doc-auth-role">{{ $item->item->code }}</span>@endif</td>
                        <td class="doc-num">{{ $item->qty }} {{ $unit }}</td>
                        <td class="doc-num">{{ $item->received_qty ?? 0 }} {{ $unit }}</td>
                        <td class="doc-num">{{ $item->pending_qty ?? $item->qty }} {{ $unit }}</td>
                        <td>{{ $item->warehouse?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td class="doc-num">{{ $money($item->unit_price, $item->currency) }}</td>
                        <td class="doc-num">{{ $money($item->total_price, $item->currency) }}</td>
                        <td>{{ $lineStatusLabels[$itemStatus] ?? ($itemStatus ?: ControlledDocument::NOT_RECORDED) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="doc-split doc-plain"><tr>
        <td class="doc-split-notes">
            @if($order->obs)
                <div class="doc-party-label">Observações</div>
                <div class="doc-party-lines">{!! nl2br(e($order->obs), false) !!}</div>
            @endif
        </td>
        <td class="doc-split-totals">
            <table class="doc-totals doc-plain">
                <tr class="doc-totals-grand"><td>Valor total do pedido</td><td style="text-align:right;">{{ $money($totalAmount, $order->currency) }}</td></tr>
            </table>
        </td>
    </tr></table>

    {!! ControlledDocument::section('', 'Aprovação', ControlledDocument::authorisation([
        ['name' => $order->user?->name, 'caption' => 'Elaborado por'],
        ['name' => null, 'caption' => 'Aprovado por (nome, assinatura e data)'],
    ]), keepTogether: true) !!}
@endsection
