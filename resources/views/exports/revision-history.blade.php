@php
    use App\Support\ControlledDocument;

    $documentTitle = 'Histórico de Revisões';
    $documentNumber = (string) ($certificate->code ?? $certificate->id);
    $issueDate = $exportDate->format('d/m/Y H:i');
    $controlRows = [
        ['Relatório n.º', $documentNumber],
        ['Revisões', (string) $revisions->count()],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Registo das alterações ao relatório de ensaio (ISO/IEC 17025, 7.8.8).';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    {!! ControlledDocument::section(1, 'Relatório de ensaio', ControlledDocument::keyValueGrid([
        ['label' => 'N.º', 'value' => $certificate->code],
        ['label' => 'Versão actual', 'value' => $certificate->current_version],
        ['label' => 'Cliente', 'value' => $certificate->customer?->name],
        ['label' => 'Instalação', 'value' => $certificate->warehouse?->name],
    ])) !!}

    <div class="doc-section">
        <div class="doc-section-title"><span class="doc-section-number">2.</span> Revisões</div>
        <table class="doc-results doc-plain">
            <thead>
                <tr>
                    <th class="doc-num" style="width:5%;">#</th>
                    <th style="width:8%;">Versão</th>
                    <th style="width:12%;">Tipo</th>
                    <th>Motivo da alteração</th>
                    <th style="width:14%;">Criada por</th>
                    <th style="width:14%;">Aprovada por</th>
                    <th style="width:14%;">Em vigor desde</th>
                    <th style="width:7%;">Actual</th>
                </tr>
            </thead>
            <tbody>
                @forelse($revisions as $revision)
                    <tr>
                        <td class="doc-num">{{ $revision->revision_number }}</td>
                        <td>{{ $revision->version }}</td>
                        <td>{{ $revision->change_type }}</td>
                        <td>{{ $revision->change_reason }}</td>
                        <td>{{ $revision->createdBy?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $revision->approvedBy?->name ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $revision->effective_date?->format('d/m/Y H:i') ?: ControlledDocument::NOT_RECORDED }}</td>
                        <td>{{ $revision->is_current ? 'Sim' : 'Não' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">Sem revisões registadas: o relatório está na versão original.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {!! ControlledDocument::endMark('histórico') !!}
@endsection
