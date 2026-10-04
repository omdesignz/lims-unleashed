@php
    use App\Support\ControlledDocument;

    $documentTitle = 'Comparação de Revisões';
    $documentNumber = (string) ($certificate->code ?? $certificate->id);
    $issueDate = now()->format('d/m/Y H:i');
    $controlRows = [
        ['Relatório n.º', $documentNumber],
        ['Revisões', $revisionA->version.' e '.$revisionB->version],
        ['Emissão', $issueDate],
    ];
    $footerNotice = 'Registo das alterações ao relatório de ensaio (ISO/IEC 17025, 7.8.8).';
    $describe = fn ($revision): string => e((string) $revision->version).'<br>'.e((string) $revision->change_type).'<br>'.nl2br(e((string) $revision->change_reason), false);
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    <table class="doc-parties doc-plain"><tr>
        <td class="doc-party"><div class="doc-party-label">Revisão A</div><div class="doc-party-lines">{!! $describe($revisionA) !!}</div></td>
        <td class="doc-party"><div class="doc-party-label">Revisão B</div><div class="doc-party-lines">{!! $describe($revisionB) !!}</div></td>
    </tr></table>

    <div class="doc-section">
        <div class="doc-section-title">Diferenças</div>
        <table class="doc-results doc-plain">
            <thead>
                <tr>
                    <th style="width:26%;">Secção</th>
                    <th>Alteração</th>
                </tr>
            </thead>
            <tbody>
                @forelse($differences as $section => $changes)
                    <tr>
                        <td>{{ is_string($section) ? $section : 'Diferença' }}</td>
                        <td>
                            @if(is_array($changes))
                                <pre style="white-space: pre-wrap; margin: 0; font-size: 7.4pt;">{{ json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                {{ $changes }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2">Nenhuma diferença detectada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {!! ControlledDocument::endMark('documento') !!}
@endsection
