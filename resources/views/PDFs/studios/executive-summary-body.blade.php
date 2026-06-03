<section style="margin-bottom:18px;">
    <div class="document-hero studio-avoid-break" style="padding:22px 24px;">
        <div class="document-kicker">
            Resumo executivo · {{ $payload['period_label'] ?? now()->translatedFormat('F Y') }}
        </div>
        <h1 style="margin:8px 0 8px;">Direcção técnica, capacidade e risco</h1>
        <p class="studio-lead">
            {{ $payload['executive_summary'] ?? 'Painel executivo preparado para decisão: capacidade operacional, trabalhos autorizados, amostras em curso, recebíveis e pressão de risco ficam consolidados numa leitura auditável.' }}
        </p>
    </div>
</section>

@include('PDFs.studios.partials.executive-kpis', ['payload' => $payload])

<section style="margin-top:4px;">
    @include('PDFs.studios.partials.executive-charts', ['payload' => $payload])
</section>

<section class="studio-avoid-break" style="margin-top:18px;">
    <h2 style="font-size:13pt; margin:0 0 8px; color:#143d37;">Clientes com maior actividade recente</h2>
    @include('PDFs.studios.partials.executive-customers', ['payload' => $payload])
</section>

<section class="document-callout studio-avoid-break" style="margin-top:18px;">
    A leitura executiva deve ser revista em conjunto com ocorrências, não conformidades, competência técnica e estado dos certificados antes de decisões críticas.
</section>
