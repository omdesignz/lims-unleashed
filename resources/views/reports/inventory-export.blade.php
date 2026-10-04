@php
    use App\Support\ControlledDocument;

    $documentTitle = (string) $title;
    $documentNumber = 'INV-'.now()->format('Ymd-Hi');
    $issueDate = (string) $generated_at;
    $controlRows = [
        ['Laboratório', (string) ($lab_name ?: ControlledDocument::NOT_RECORDED)],
        ['Registos', (string) count($rows)],
        ['Emitido por', (string) $generated_by],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Quantidades na unidade de medida de cada item.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    <table class="doc-results doc-plain" style="margin-top:3mm;">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value ?? ControlledDocument::NOT_RECORDED }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}">Sem registos para os filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>

    {!! ControlledDocument::endMark('relatório') !!}
@endsection
