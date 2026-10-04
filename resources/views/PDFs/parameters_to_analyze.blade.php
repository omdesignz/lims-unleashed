@php
    use App\Support\ControlledDocument;
    use Illuminate\Support\Carbon;

    $settings = $settings ?? app(\App\Settings\GeneralSettings::class);
    $samples = collect($model->code?->samples ?? []);
    $uniqueParameters = collect($parameters)->unique('id')->values();
    $collectionDate = $model->collection_date ? Carbon::parse($model->collection_date) : null;

    // The dilution columns each parameter needs beside its result column.
    $dilutionsOf = function (array $parameter): array {
        $ratios = [];

        foreach ((array) ($parameter['new_dilutions'] ?? []) as $group) {
            foreach ((array) $group as $item) {
                if (is_array($item)) {
                    $ratios[] = $item['ratio'] ?? null;
                }
            }
        }

        return $ratios;
    };
    $appliesTo = fn ($sample, array $parameter): bool => (bool) $sample->analysis?->profile?->parameters?->contains('id', $parameter['id']);
    $nameOf = fn (array $parameter): string => (string) (filled($parameter['code'] ?? null) ? $parameter['code'] : ($parameter['description'] ?? ''));

    $documentTitle = 'Folha de Trabalho';
    $documentNumber = 'WS-'.$model->id.'-'.($collectionDate?->format('Y') ?? now()->format('Y'));
    $issueDate = now()->format('d/m/Y');
    $controlRows = [
        ['N.º', $documentNumber],
        ['Código laboratorial', $model->code?->code ?: ControlledDocument::NOT_RECORDED],
        ['Colheita', $collectionDate?->format('d/m/Y') ?: ControlledDocument::NOT_RECORDED],
        ['Emissão', now()->format('d/m/Y H:i')],
    ];
    $footerNotice = 'Registo técnico de uso exclusivo do laboratório.';
@endphp

@extends('PDFs.partials.controlled-layout')

@section('content')
    @if($samples->isEmpty() || $uniqueParameters->isEmpty())
        {!! ControlledDocument::notice('Sem ensaios atribuídos.', 'A amostra ainda não foi colocada em análise ou não tem parâmetros associados a este código laboratorial.') !!}
    @else
        <div class="doc-section">
            <div class="doc-section-title"><span class="doc-section-number">1.</span> Ensaios por departamento</div>
            <table class="doc-results doc-plain">
                <thead>
                    <tr>
                        <th style="width:24%;">Departamento</th>
                        <th class="doc-num" style="width:10%;">Amostras</th>
                        <th>Parâmetros a ensaiar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($samples->groupBy(fn ($sample) => $sample->analysis?->department?->name ?: 'Sem departamento') as $departmentName => $departmentSamples)
                        @php
                            $departmentParameters = $uniqueParameters->filter(fn (array $parameter): bool => $departmentSamples->contains(fn ($sample): bool => $appliesTo($sample, $parameter)));
                        @endphp
                        <tr>
                            <td>{{ $departmentName }}</td>
                            <td class="doc-num">{{ $departmentSamples->count() }}</td>
                            <td>{{ $departmentParameters->map($nameOf)->implode('; ') ?: ControlledDocument::NOT_RECORDED }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="doc-section">
            <div class="doc-section-title"><span class="doc-section-number">2.</span> Registo de resultados</div>
            <table class="doc-results doc-grid doc-plain">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:14%;">Amostra</th>
                        <th rowspan="2" style="width:11%;">Departamento</th>
                        @foreach($uniqueParameters as $parameter)
                            <th colspan="{{ count($dilutionsOf($parameter)) + 1 }}" class="doc-center">{{ $nameOf($parameter) }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($uniqueParameters as $parameter)
                            <th class="doc-center">Resultado</th>
                            @foreach($dilutionsOf($parameter) as $ratio)
                                <th class="doc-center">Dil. {{ $ratio }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($samples as $sample)
                        <tr>
                            <td>
                                <strong>{{ $model->code->code }}</strong><br>
                                <barcode code="{{ str_replace('/', '', (string) $model->code->code) }}" type="C128B" text="0" size="0.6" height="0.8" />
                            </td>
                            <td>{{ $sample->analysis?->department?->name }}</td>
                            @foreach($uniqueParameters as $parameter)
                                @if($appliesTo($sample, $parameter))
                                    <td style="height:15mm;">&nbsp;</td>
                                    @foreach($dilutionsOf($parameter) as $ratio)
                                        <td style="height:15mm;">&nbsp;</td>
                                    @endforeach
                                @else
                                    <td colspan="{{ count($dilutionsOf($parameter)) + 1 }}" class="doc-center doc-not-applicable">{{ ControlledDocument::NOT_RECORDED }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="doc-notes">{{ ControlledDocument::NOT_RECORDED }} parâmetro não aplicável à amostra. Dil.: diluição.</p>
        </div>
    @endif

    {!! ControlledDocument::section($samples->isEmpty() || $uniqueParameters->isEmpty() ? 1 : 3, 'Assinaturas', ControlledDocument::authorisation([
        ['name' => null, 'caption' => 'Técnico de ensaio (nome, assinatura e data)'],
        ['name' => null, 'caption' => 'Técnico de codificação (nome, assinatura e data)'],
    ]), keepTogether: true) !!}
@endsection
