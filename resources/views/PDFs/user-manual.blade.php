@php
    $appName = $settings->app_name ?: 'GestLab';
    $labName = $settings->app_client_lab_name ?: $appName;
    $generatedBy = auth()->user()?->name ?: 'Sistema';
    $generatedAtText = $generatedAt->format('d/m/Y H:i');
    $homeDirectory = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: ''), DIRECTORY_SEPARATOR);
    $firstExistingFont = static function (array $paths): ?string {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return str_replace('\\', '/', $path);
            }
        }

        return null;
    };
    $centuryGothicRegular = $firstExistingFont([
        resource_path('fonts/Century Gothic.ttf'),
        storage_path('app/fonts/Century Gothic.ttf'),
        $homeDirectory ? $homeDirectory.'/Library/Fonts/Century Gothic.ttf' : null,
        '/Library/Fonts/Century Gothic.ttf',
        '/System/Library/Fonts/Supplemental/Century Gothic.ttf',
        '/usr/share/fonts/truetype/msttcorefonts/Century_Gothic.ttf',
    ]);
    $centuryGothicBold = $firstExistingFont([
        resource_path('fonts/Century Gothic Bold.ttf'),
        storage_path('app/fonts/Century Gothic Bold.ttf'),
        $homeDirectory ? $homeDirectory.'/Library/Fonts/Century Gothic Bold.ttf' : null,
        $homeDirectory ? $homeDirectory.'/Library/Fonts/ufonts.com_century-gothic-bold.ttf' : null,
        '/Library/Fonts/Century Gothic Bold.ttf',
        '/System/Library/Fonts/Supplemental/Century Gothic Bold.ttf',
        '/usr/share/fonts/truetype/msttcorefonts/Century_Gothic_Bold.ttf',
    ]);

    $operationalIndex = [
        [
            'number' => '01',
            'title' => 'Governação do SGQ',
            'description' => 'Princípios para operar com rastreabilidade, imparcialidade, confidencialidade e evidência auditável.',
        ],
        [
            'number' => '02',
            'title' => 'Ciclo da amostra',
            'description' => 'Entrada de amostra, colheita, validação, análise, verificação, aprovação e emissão documental.',
        ],
        [
            'number' => '03',
            'title' => 'Resultados e relatórios',
            'description' => 'Inserção de resultados, incerteza, contra-análise, certificado, QR, assinatura e templates controlados.',
        ],
        [
            'number' => '04',
            'title' => 'Portal e propostas',
            'description' => 'Propostas, aceite do cliente, pedidos de análise, documentos comerciais e comunicação controlada.',
        ],
        [
            'number' => '05',
            'title' => 'Inventário e compras',
            'description' => 'Stock multi-localização, necessidades laboratoriais, aprovações, compras e recepção parcial.',
        ],
        [
            'number' => '06',
            'title' => 'Melhoria contínua',
            'description' => 'Não conformidades, proficiência, fornecedores, competência técnica, avaliações e acções correctivas.',
        ],
    ];

    $principles = [
        ['title' => 'Rastreabilidade', 'description' => 'Cada decisão crítica deve apontar para uma amostra, utilizador, data, versão, anexo ou evidência operacional.'],
        ['title' => 'Imparcialidade', 'description' => 'Permissões, responsabilidades e aprovação técnica devem reduzir influência indevida sobre resultados e certificados.'],
        ['title' => 'Confidencialidade', 'description' => 'Dados de clientes, resultados, documentos comerciais e informação interna devem circular apenas por perfis autorizados.'],
        ['title' => 'Competência', 'description' => 'Tarefas críticas devem considerar formação, certificação, autorização por método e matriz de responsabilidade.'],
        ['title' => 'Validação', 'description' => 'Resultados só devem avançar quando método, unidade, cálculo, incerteza, verificação e aprovação forem coerentes.'],
        ['title' => 'Comunicação', 'description' => 'Clientes e equipas internas devem receber notificações claras para evitar amostras paradas ou decisões sem dono.'],
    ];

    $sampleFlow = [
        [
            'phase' => 'Entrada',
            'importance' => 'Cria o código laboratorial e estabelece cliente, origem, produto, matriz, prioridade e cadeia de custódia.',
            'action' => 'Abrir Entrada de Amostra, preencher dados do cliente ou origem interna, seleccionar produto, matriz e perfis.',
            'evidence' => 'Código da entrada, amostras criadas, dados de recepção, anexos e responsável registado.',
        ],
        [
            'phase' => 'Validação operacional',
            'importance' => 'Confirma que a amostra pode seguir para análise sem lacunas de escopo, método, equipamento ou reagente.',
            'action' => 'Validar campos obrigatórios, parâmetros, laboratório, disciplina, embalagem, temperatura e integridade.',
            'evidence' => 'Estado validado, observações técnicas e trilho de auditoria da decisão.',
        ],
        [
            'phase' => 'Execução analítica',
            'importance' => 'Regista resultados primários, cálculos, unidades, incerteza e observações que suportam a conclusão.',
            'action' => 'Inserir resultados por parâmetro, usar fórmulas quando aplicável e anexar evidência técnica.',
            'evidence' => 'Resultado, unidade, método, fonte de incerteza, cálculo, anexos e utilizador responsável.',
        ],
        [
            'phase' => 'Verificação e contra-análise',
            'importance' => 'Protege a validade técnica antes da aprovação e documenta divergências quando houver repetição ou revisão.',
            'action' => 'Comparar resultados, rever limites, regras de decisão, incerteza e abrir contra-análise quando aplicável.',
            'evidence' => 'Decisão de verificação, motivo técnico, ligação ao resultado original e conclusão documentada.',
        ],
        [
            'phase' => 'Aprovação e emissão',
            'importance' => 'Liberta certificado ou relatório apenas quando existe autorização técnica e template controlado.',
            'action' => 'Aprovar, gerar relatório/certificado, confirmar assinatura, cabeçalhos, rodapés, paginação e QR.',
            'evidence' => 'PDF emitido, versão, assinatura, QR de autenticidade e histórico de entrega.',
        ],
    ];

    $profileJourneys = [
        [
            'profile' => 'Recepção',
            'entry' => 'Entrada de Amostra',
            'responsibility' => 'Registar dados completos, confirmar integridade, prioridade, temperatura, embalagem e origem.',
            'done' => 'Amostra validada ou devolvida com motivo documentado.',
        ],
        [
            'profile' => 'Técnico',
            'entry' => 'Fila de análise',
            'responsibility' => 'Executar métodos autorizados, inserir resultados, cálculos, incerteza, anexos e observações.',
            'done' => 'Resultados submetidos para verificação com evidência suficiente.',
        ],
        [
            'profile' => 'Verificador / Aprovador',
            'entry' => 'Gestão de resultados',
            'responsibility' => 'Rever coerência técnica, regras de decisão, contra-análise, assinaturas e emissão documental.',
            'done' => 'Certificado ou relatório aprovado, emitido e rastreável.',
        ],
        [
            'profile' => 'Qualidade',
            'entry' => 'SGQ',
            'responsibility' => 'Acompanhar não conformidades, proficiência, competência, fornecedores e acções correctivas.',
            'done' => 'Evidência do SGQ actualizada e pronta para auditoria.',
        ],
        [
            'profile' => 'Cliente',
            'entry' => 'Portal',
            'responsibility' => 'Acompanhar propostas, aceitar/rejeitar condições, pedir análises e consultar documentos emitidos.',
            'done' => 'Pedido ou aceite registado sem canais paralelos.',
        ],
    ];

    $routeMap = [
        ['area' => 'Dashboard', 'path' => '/dashboard', 'purpose' => 'Prioridades, pendências, atalhos operacionais e indicadores executivos.'],
        ['area' => 'Entrada de Amostra', 'path' => '/sample-entry', 'purpose' => 'Ponto principal para iniciar trabalho laboratorial interno ou de cliente.'],
        ['area' => 'Amostras', 'path' => '/vap-samples', 'purpose' => 'Fila técnica de amostras já ligadas ao processo normal.'],
        ['area' => 'Análises', 'path' => '/analysis', 'purpose' => 'Planeamento e execução de análises e parâmetros.'],
        ['area' => 'Contra-análise', 'path' => '/counteranalysis', 'purpose' => 'Registo técnico de revisão, repetição ou decisão sobre resultado contestado.'],
        ['area' => 'Report Studio', 'path' => '/report-studios', 'purpose' => 'Templates de relatórios, certificados e documentos com paginação controlada.'],
        ['area' => 'Propostas', 'path' => '/vap-proposals', 'purpose' => 'Escopo, regra de decisão, aceite/rejeição do cliente e base comercial.'],
        ['area' => 'Inventário', 'path' => '/vap-inventory', 'purpose' => 'Itens, reagentes, armazéns, transferências, compras e necessidades laboratoriais.'],
        ['area' => 'SGQ', 'path' => '/vap-non-conformities', 'purpose' => 'Não conformidades, evidências, responsáveis, acções e melhoria contínua.'],
        ['area' => 'Ficheiros', 'path' => '/file-manager', 'purpose' => 'Documentos controlados, anexos e evidência organizada por processo.'],
    ];

    $moduleGuide = [
        ['module' => 'Sample Entry', 'why' => 'É a origem do rasto de análise.', 'evidence' => 'Código, cliente/origem, produto, matriz, perfis, lote, anexos, estado.'],
        ['module' => 'Resultados', 'why' => 'É onde o valor técnico é produzido.', 'evidence' => 'Resultado, método, unidade, incerteza, fórmula, verificação, aprovação.'],
        ['module' => 'Report Studio', 'why' => 'Controla a aparência e validade dos PDFs.', 'evidence' => 'Template, versão, cabeçalho, rodapé, assinatura, QR, paginação.'],
        ['module' => 'Inventário', 'why' => 'Evita ruptura operacional e perdas.', 'evidence' => 'Localização, lote, validade, consumo, recepção, transferência, necessidade.'],
        ['module' => 'Equipamentos', 'why' => 'Demonstra aptidão técnica.', 'evidence' => 'Manutenção, calibração, estado operacional, certificados e ligação à amostra.'],
        ['module' => 'SGQ', 'why' => 'Sustenta ISO/IEC 17025.', 'evidence' => 'Não conformidades, acções, competência, proficiência, fornecedores, avaliações.'],
    ];

    $readinessChecks = [
        ['area' => 'Dados mestres', 'ready' => 'Clientes, produtos, matrizes, parâmetros, unidades, métodos e perfis estão actualizados.', 'owner' => 'Administração técnica'],
        ['area' => 'Pessoas', 'ready' => 'Utilizadores, papéis, permissões, assinaturas, passkeys/segurança e competências estão configurados.', 'owner' => 'Administração / Qualidade'],
        ['area' => 'Laboratórios', 'ready' => 'Departamentos, laboratórios, responsáveis, equipamentos e condições ambientais estão ligados.', 'owner' => 'Direcção técnica'],
        ['area' => 'Documentos', 'ready' => 'Templates de propostas, certificados, relatórios e documentos comerciais estão aprovados.', 'owner' => 'Qualidade / Direcção'],
        ['area' => 'Comunicação', 'ready' => 'Notificações, emails, lembretes e portal de cliente estão activos e testados.', 'owner' => 'Administração'],
        ['area' => 'Auditoria', 'ready' => 'Backups, logs, actividade, evidências e exportações estão disponíveis.', 'owner' => 'Administração / TI'],
    ];
@endphp

<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Manual do Utilizador</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @if ($centuryGothicRegular)
            @font-face {
                font-family: 'centurygothic';
                font-style: normal;
                font-weight: 400;
                src: url("{{ $centuryGothicRegular }}") format('truetype');
            }
        @endif

        @if ($centuryGothicBold)
            @font-face {
                font-family: 'centurygothic';
                font-style: normal;
                font-weight: 700;
                src: url("{{ $centuryGothicBold }}") format('truetype');
            }

            @font-face {
                font-family: 'centurygothic';
                font-style: normal;
                font-weight: 900;
                src: url("{{ $centuryGothicBold }}") format('truetype');
            }
        @endif

        @page {
            background-color: #ffffff;
            footer: manualFooter;
            margin: 11mm 10mm 17mm 10mm;
            margin-footer: 6mm;
        }

        html,
        body.pdf-document {
            background: #ffffff;
            font-family: centurygothic, 'Century Gothic', DejaVu Sans, Arial, sans-serif;
            font-size: 8.9pt;
            line-height: 1.48;
        }

        .pdf-document,
        .pdf-document h1,
        .pdf-document h2,
        .pdf-document h3,
        .pdf-document h4,
        .pdf-document h5,
        .pdf-document h6,
        .pdf-document p,
        .pdf-document td,
        .pdf-document th,
        .pdf-document div,
        .pdf-document span {
            font-family: centurygothic, 'Century Gothic', DejaVu Sans, Arial, sans-serif;
        }

        .cover-panel {
            background: #143d37;
            border-radius: 9mm;
            color: #fffdf7;
            min-height: 126mm;
            padding: 11mm;
        }

        .cover-top-table,
        .meta-card-table,
        .index-table,
        .principles-table,
        .profile-table,
        .route-table,
        .readiness-table {
            width: 100%;
            border-collapse: separate;
        }

        .cover-top-table {
            margin-bottom: 12mm;
        }

        .manual-logo {
            background: #fffdf7;
            border-radius: 4mm;
            color: #143d37;
            font-size: 12pt;
            font-weight: 900;
            height: 15mm;
            text-align: center;
            vertical-align: middle;
            width: 15mm;
        }

        .cover-kicker {
            color: #f1d78b;
            font-size: 7.2pt;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .cover-title {
            color: #fffdf7;
            font-size: 27pt;
            font-weight: 900;
            letter-spacing: -0.06em;
            line-height: 1.02;
            margin: 0 0 5mm;
            width: 91%;
        }

        .cover-lead {
            color: #dbe8df;
            font-size: 10pt;
            line-height: 1.62;
            width: 86%;
        }

        .cover-ring {
            border: 0.35mm solid rgba(255, 255, 255, 0.18);
            border-radius: 50%;
            height: 58mm;
            position: absolute;
            right: -19mm;
            top: -18mm;
            width: 58mm;
        }

        .meta-card-table {
            border-spacing: 2.4mm;
            margin: 9mm -2.4mm 0;
        }

        .meta-card {
            background: rgba(255, 255, 255, 0.10);
            border: 0.22mm solid rgba(255, 255, 255, 0.24);
            border-radius: 4.5mm;
            color: #fffdf7;
            padding: 4mm;
            vertical-align: top;
        }

        .meta-card .label {
            color: #c8d8d0;
            display: block;
            font-size: 6.7pt;
            font-weight: 900;
            letter-spacing: 0.11em;
            text-transform: uppercase;
        }

        .meta-card .value {
            color: #ffffff;
            display: block;
            font-size: 8.5pt;
            font-weight: 900;
            line-height: 1.35;
            margin-top: 1.5mm;
        }

        .control-panel {
            background: #ffffff;
            border: 0.25mm solid #ded3bf;
            border-radius: 7mm;
            margin-top: 7mm;
            padding: 7mm;
        }

        .section-kicker {
            background: #c8f3e5;
            border-radius: 9mm;
            color: #13705c;
            display: inline-block;
            font-size: 6.8pt;
            font-weight: 900;
            letter-spacing: 0.16em;
            margin-bottom: 4mm;
            padding: 1.4mm 3mm;
            text-transform: uppercase;
        }

        .section-heading {
            color: #111827;
            font-size: 16pt;
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1.08;
            margin-bottom: 2mm;
        }

        .section-lead {
            color: #475a53;
            font-size: 9.2pt;
            line-height: 1.56;
            margin-bottom: 5mm;
            width: 91%;
        }

        .control-table {
            border-collapse: collapse;
            width: 100%;
        }

        .control-table td {
            border-bottom: 0.18mm solid #d8cbb8;
            color: #1f3430;
            padding: 3mm 2mm;
            vertical-align: top;
        }

        .control-table .label {
            color: #6b7b74;
            font-size: 6.8pt;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            width: 27%;
        }

        .page-title-block {
            margin-bottom: 6mm;
            page-break-inside: avoid;
        }

        .index-table,
        .principles-table {
            border-spacing: 4mm;
            margin: 0 -4mm;
        }

        .index-card,
        .principle-card,
        .workflow-card {
            background: #fffdf7;
            border: 0.25mm solid #ded3bf;
            border-radius: 5mm;
            padding: 5mm;
            vertical-align: top;
        }

        .index-number {
            color: #c79431;
            font-size: 17pt;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 4mm;
        }

        .card-title {
            color: #143d37;
            font-size: 10pt;
            font-weight: 900;
            line-height: 1.2;
            margin-bottom: 2mm;
        }

        .card-text {
            color: #475a53;
            font-size: 8.3pt;
            line-height: 1.48;
        }

        .callout-strong {
            background: #fff7df;
            border-left: 1.4mm solid #d9b05f;
            border-radius: 4mm;
            color: #31413b;
            font-size: 8.8pt;
            line-height: 1.52;
            margin: 5mm 0 0;
            padding: 4mm 5mm;
        }

        .phase-block {
            border: 0.25mm solid #ded3bf;
            border-radius: 5mm;
            margin-bottom: 4mm;
            page-break-inside: avoid;
        }

        .phase-title {
            background: #143d37;
            border-radius: 5mm 5mm 0 0;
            color: #fffdf7;
            font-size: 10.2pt;
            font-weight: 900;
            padding: 3mm 4mm;
        }

        .phase-table {
            border-collapse: collapse;
            width: 100%;
        }

        .phase-table td {
            border-top: 0.18mm solid #ded3bf;
            padding: 3mm 4mm;
            vertical-align: top;
        }

        .phase-table .label {
            color: #c79431;
            font-size: 6.8pt;
            font-weight: 900;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            width: 25%;
        }

        .profile-table,
        .route-table,
        .readiness-table {
            border-collapse: collapse;
            margin-top: 4mm;
        }

        .profile-table th,
        .route-table th,
        .readiness-table th {
            background: #143d37;
            color: #fffdf7;
            font-size: 7pt;
            font-weight: 900;
            letter-spacing: 0.08em;
            padding: 2.6mm 2.4mm;
            text-align: left;
            text-transform: uppercase;
        }

        .profile-table td,
        .route-table td,
        .readiness-table td {
            border-bottom: 0.2mm solid #ded3bf;
            color: #31413b;
            font-size: 8pt;
            line-height: 1.42;
            padding: 3mm 2.4mm;
            vertical-align: top;
        }

        .profile-table tbody tr:nth-child(even) td,
        .route-table tbody tr:nth-child(even) td,
        .readiness-table tbody tr:nth-child(even) td {
            background: #fbf7ef;
        }

        .route-path {
            color: #13705c;
            font-family: centurygothic, 'Century Gothic', DejaVu Sans, Arial, sans-serif;
            font-size: 7.2pt;
            font-weight: 800;
        }

        .module-table {
            border-spacing: 3mm;
            margin: 0 -3mm;
            width: 100%;
        }

        .module-card {
            background: #fffdf7;
            border: 0.25mm solid #ded3bf;
            border-radius: 5mm;
            padding: 4.5mm;
            vertical-align: top;
        }

        .module-card .module-name {
            color: #143d37;
            font-size: 9.3pt;
            font-weight: 900;
            margin-bottom: 2mm;
        }

        .module-card .tag {
            background: #eaf8f2;
            border-radius: 7mm;
            color: #13705c;
            display: inline-block;
            font-size: 6.5pt;
            font-weight: 900;
            letter-spacing: 0.08em;
            margin-bottom: 2mm;
            padding: 1mm 2.2mm;
            text-transform: uppercase;
        }

        .footer-line {
            border-top: 0.2mm solid #ded3bf;
            color: #6b7b74;
            font-size: 7pt;
            padding-top: 2mm;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body class="pdf-document">
<htmlpagefooter name="manualFooter">
    <div class="footer-line">
        {{ $labName }} | Manual operacional do GestLab | Documento controlado | Página {PAGENO}/{nbpg}
    </div>
</htmlpagefooter>

<section class="cover-panel">
    <table class="cover-top-table">
        <tr>
            <td class="manual-logo">GL</td>
            <td style="padding-left:4mm;">
                <div class="cover-kicker">Manual operacional institucional</div>
                <div style="color:#c8d8d0; font-size:7.2pt; margin-top:1mm;">Sistema de gestão laboratorial, qualidade e rastreabilidade</div>
            </td>
            <td style="color:#c8d8d0; font-size:7pt; text-align:right; vertical-align:top;">{{ $generatedAtText }}</td>
        </tr>
    </table>

    <h1 class="cover-title">{{ $appName }}: guia para operar processos laboratoriais com rigor ISO/IEC 17025.</h1>
    <p class="cover-lead">
        Este manual orienta recepção, técnicos, verificadores, qualidade, direcção, administração e clientes sobre onde actuar, que decisão tomar, que evidência preservar e como concluir cada processo sem perder rastreabilidade.
    </p>

    <table class="meta-card-table">
        <tr>
            <td class="meta-card">
                <span class="label">Instituição</span>
                <span class="value">{{ $labName }}</span>
            </td>
            <td class="meta-card">
                <span class="label">Plataforma</span>
                <span class="value">{{ $appName }} — LIMS, SGQ e portal laboratorial</span>
            </td>
            <td class="meta-card">
                <span class="label">Emissão</span>
                <span class="value">{{ $generatedAtText }} por {{ $generatedBy }}</span>
            </td>
        </tr>
    </table>
</section>

<section class="control-panel">
    <span class="section-kicker">Controlo documental</span>
    <h2 class="section-heading">Informação de gestão deste manual.</h2>
    <p class="section-lead">
        Este guia deve ser tratado como documento vivo. Sempre que o laboratório alterar fluxos, permissões, templates, regras de decisão, notificações ou módulos críticos, a versão publicada deve ser revista.
    </p>

    <table class="control-table">
        <tr>
            <td class="label">Responsável</td>
            <td>Direcção técnica, qualidade e administração da plataforma.</td>
        </tr>
        <tr>
            <td class="label">Público-alvo</td>
            <td>Recepção, técnicos, verificadores, aprovadores, qualidade, direcção, administração, armazém e clientes autorizados.</td>
        </tr>
        <tr>
            <td class="label">Versão</td>
            <td>1.0 — modelo operacional para implantação e formação inicial.</td>
        </tr>
        <tr>
            <td class="label">Revisão</td>
            <td>Rever antes de produção, antes de auditorias ISO/IEC 17025 e sempre que o fluxo de amostra ou emissão documental mudar.</td>
        </tr>
        <tr>
            <td class="label">Emissão</td>
            <td>{{ $generatedAtText }} por {{ $generatedBy }}.</td>
        </tr>
    </table>
</section>

<div class="page-break"></div>

<section>
    <div class="page-title-block">
        <span class="section-kicker">Índice operacional</span>
        <h2 class="section-heading">Como navegar este manual conforme a necessidade da equipa.</h2>
        <p class="section-lead">
            Use este índice durante preparação de produção, formação de utilizadores, suporte operacional, auditoria interna ou revisão de processos laboratoriais.
        </p>
    </div>

    <table class="index-table">
        @foreach (array_chunk($operationalIndex, 2) as $row)
            <tr>
                @foreach ($row as $item)
                    <td class="index-card" style="width:50%;">
                        <div class="index-number">{{ $item['number'] }}</div>
                        <div class="card-title">{{ $item['title'] }}</div>
                        <div class="card-text">{{ $item['description'] }}</div>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <div class="callout-strong">
        Recomendação operacional: mantenha este manual disponível no menu de ajuda e use-o como roteiro de formação. Cada processo crítico deve terminar com um estado claro, responsável identificado e evidência associada.
    </div>
</section>

<div class="page-break"></div>

<section>
    <div class="page-title-block">
        <span class="section-kicker">Princípios de governação</span>
        <h2 class="section-heading">O GestLab deve reforçar confiança, não apenas registar dados.</h2>
        <p class="section-lead">
            O valor do sistema depende da forma como as decisões são preparadas, documentadas, verificadas, aprovadas e comunicadas. Estes princípios devem orientar todos os perfis.
        </p>
    </div>

    <table class="principles-table">
        @foreach (array_chunk($principles, 2) as $row)
            <tr>
                @foreach ($row as $principle)
                    <td class="principle-card" style="width:50%;">
                        <div class="card-title">{{ $principle['title'] }}</div>
                        <div class="card-text">{{ $principle['description'] }}</div>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</section>

<div class="page-break"></div>

<section>
    <div class="page-title-block">
        <span class="section-kicker">Ciclo da amostra</span>
        <h2 class="section-heading">Importância, acção e evidência esperada em cada etapa.</h2>
        <p class="section-lead">
            A Entrada de Amostra deve ser o ponto principal para iniciar trabalho laboratorial. Colheitas directas, colheitas programadas, análises e relatórios devem ficar ligados ao mesmo rasto.
        </p>
    </div>

    @foreach ($sampleFlow as $phase)
        <div class="phase-block">
            <div class="phase-title">{{ $phase['phase'] }}</div>
            <table class="phase-table">
                <tr>
                    <td class="label">Importância</td>
                    <td>{{ $phase['importance'] }}</td>
                </tr>
                <tr>
                    <td class="label">Onde actuar</td>
                    <td>{{ $phase['action'] }}</td>
                </tr>
                <tr>
                    <td class="label">Resultado esperado</td>
                    <td>{{ $phase['evidence'] }}</td>
                </tr>
            </table>
        </div>
    @endforeach
</section>

<div class="page-break"></div>

<section>
    <div class="page-title-block">
        <span class="section-kicker">Percursos por perfil</span>
        <h2 class="section-heading">O que cada utilizador deve fazer no sistema.</h2>
        <p class="section-lead">
            Estes percursos ajudam a reduzir improviso, clarificar responsabilidade e preparar a equipa para operar com evidência suficiente.
        </p>
    </div>

    <table class="profile-table">
        <thead>
        <tr>
            <th style="width:16%;">Perfil</th>
            <th style="width:19%;">Ponto de entrada</th>
            <th style="width:40%;">Responsabilidade</th>
            <th style="width:25%;">Considerar concluído quando</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($profileJourneys as $journey)
            <tr>
                <td><strong>{{ $journey['profile'] }}</strong></td>
                <td>{{ $journey['entry'] }}</td>
                <td>{{ $journey['responsibility'] }}</td>
                <td>{{ $journey['done'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>

<section style="margin-top:8mm;">
    <div class="page-title-block">
        <span class="section-kicker">Mapa de navegação</span>
        <h2 class="section-heading">Onde encontrar cada área durante a operação.</h2>
    </div>

    <table class="route-table">
        <thead>
        <tr>
            <th style="width:22%;">Área</th>
            <th style="width:22%;">Caminho</th>
            <th>Para que serve</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($routeMap as $route)
            <tr>
                <td><strong>{{ $route['area'] }}</strong></td>
                <td><span class="route-path">{{ $route['path'] }}</span></td>
                <td>{{ $route['purpose'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>

<div class="page-break"></div>

<section>
    <div class="page-title-block">
        <span class="section-kicker">Módulos críticos</span>
        <h2 class="section-heading">Áreas que exigem evidência clara para produção e auditoria.</h2>
        <p class="section-lead">
            Cada módulo abaixo deve ser tratado como parte do sistema de qualidade. Não é suficiente criar registos: é necessário garantir evidência, responsável, estado e ligação ao processo.
        </p>
    </div>

    <table class="module-table">
        @foreach (array_chunk($moduleGuide, 2) as $row)
            <tr>
                @foreach ($row as $module)
                    <td class="module-card" style="width:50%;">
                        <div class="tag">Módulo</div>
                        <div class="module-name">{{ $module['module'] }}</div>
                        <div class="card-text"><strong>Por que precisa de guia:</strong> {{ $module['why'] }}</div>
                        <div class="card-text" style="margin-top:2mm;"><strong>Evidência recomendada:</strong> {{ $module['evidence'] }}</div>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</section>

<div class="page-break"></div>

<section>
    <div class="page-title-block">
        <span class="section-kicker">Relatórios e documentos</span>
        <h2 class="section-heading">Como preservar credibilidade nos PDFs emitidos pelo laboratório.</h2>
        <p class="section-lead">
            Relatórios, certificados, propostas e documentos comerciais devem comunicar seriedade. O conteúdo técnico, a estrutura visual e a rastreabilidade precisam funcionar em conjunto.
        </p>
    </div>

    <table class="principles-table">
        <tr>
            <td class="principle-card">
                <div class="card-title">Cabeçalhos e rodapés</div>
                <div class="card-text">Devem identificar laboratório, documento, versão, paginação, código e primeira página quando necessário.</div>
            </td>
            <td class="principle-card">
                <div class="card-title">Amostra e cliente</div>
                <div class="card-text">Relatórios de análise devem apresentar cliente, produto, matriz, lote, origem, data de recepção, método e estado.</div>
            </td>
        </tr>
        <tr>
            <td class="principle-card">
                <div class="card-title">Resultados</div>
                <div class="card-text">Tabelas devem incluir parâmetro, resultado, unidade, método, limite, incerteza e observações técnicas quando aplicável.</div>
            </td>
            <td class="principle-card">
                <div class="card-title">Autenticidade</div>
                <div class="card-text">Assinatura, carimbo, QR code e histórico de emissão devem confirmar que o documento não é uma cópia informal.</div>
            </td>
        </tr>
    </table>

    <div class="callout-strong">
        Sempre que o template usar gráficos, fundos, posicionamento livre ou composição visual avançada, validar a saída final no renderizador Chrome. Para documentos clássicos e compatíveis com CSS 2.1, o mPDF continua a ser fallback operacional.
    </div>
</section>

<div class="page-break"></div>

<section>
    <div class="page-title-block">
        <span class="section-kicker">Prontidão por área</span>
        <h2 class="section-heading">O que deve estar pronto antes de colocar o sistema em produção.</h2>
        <p class="section-lead">
            Use esta checklist como fecho de implantação, revisão pré-auditoria ou validação mensal de operação.
        </p>
    </div>

    <table class="readiness-table">
        <thead>
        <tr>
            <th style="width:24%;">Área</th>
            <th>Pronta quando</th>
            <th style="width:24%;">Dono</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($readinessChecks as $check)
            <tr>
                <td><strong>{{ $check['area'] }}</strong></td>
                <td>{{ $check['ready'] }}</td>
                <td>{{ $check['owner'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>

<section style="margin-top:8mm;">
    <span class="section-kicker">Resolução de bloqueios</span>
    <h2 class="section-heading">Como actuar quando o processo não avança.</h2>
    <p class="section-lead">
        Antes de tratar um bloqueio como falha técnica, confirme permissões, dados mestres, estado do processo, responsável, prazos, anexos e notificações. A correcção também deve gerar evidência.
    </p>

    <table class="readiness-table">
        <thead>
        <tr>
            <th style="width:31%;">Situação</th>
            <th>Acção recomendada</th>
            <th style="width:22%;">Dono</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Amostra sem avanço</td>
            <td>Confirmar validação, parâmetros, laboratório, técnico responsável, disponibilidade de equipamento/reagente e notificações.</td>
            <td>Recepção / Técnico</td>
        </tr>
        <tr>
            <td>Resultado inconsistente</td>
            <td>Rever método, unidade, fórmula, incerteza, limite, anexos e necessidade de contra-análise.</td>
            <td>Verificador</td>
        </tr>
        <tr>
            <td>Documento incompleto</td>
            <td>Validar template, variáveis, sample details, assinaturas, QR, cabeçalho, rodapé e paginação.</td>
            <td>Qualidade / Direcção</td>
        </tr>
        <tr>
            <td>Cliente sem visibilidade</td>
            <td>Confirmar portal, permissões, aceite de proposta, documento publicado e notificação enviada.</td>
            <td>Administração</td>
        </tr>
        </tbody>
    </table>
</section>
</body>
</html>
