@php
    use App\Support\ControlledDocument;

    $documentTitle = 'Genealogia do Lote';
    $documentNumber = (string) $batch->batch_number;
    $issueDate = now()->format('d/m/Y H:i');
    $controlRows = [
        ['Lote', $documentNumber],
        ['Movimentos', (string) $batch->transactions->count()],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Rastreabilidade dos movimentos do lote.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    <table class="doc-results doc-plain" style="margin-top:3mm;">
        <thead>
            <tr>
                <th style="width:16%;">Data</th>
                <th style="width:20%;">Utilizador</th>
                <th style="width:16%;">Movimento</th>
                <th class="doc-num" style="width:12%;">Variação</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($batch->transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $transaction->user?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                    <td>{{ $transaction->type?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                    <td class="doc-num">{{ $transaction->qty }}</td>
                    <td>{{ $transaction->reason }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sem movimentos registados.</td></tr>
            @endforelse
        </tbody>
    </table>

    {!! ControlledDocument::endMark('documento') !!}
@endsection
