<?php

namespace App\Support;

class ProposalTemplatePresetLibrary
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::standardCommercial(),
            self::isoDecisionRule(),
            self::fieldCollection(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function summaries(): array
    {
        return collect(self::all())
            ->map(fn (array $preset) => [
                'slug' => $preset['slug'],
                'name' => $preset['name'],
                'category' => $preset['category'],
                'description' => $preset['description'],
                'theme_preset' => $preset['theme_preset'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(?string $slug): ?array
    {
        if (! $slug) {
            return null;
        }

        return collect(self::all())->firstWhere('slug', $slug);
    }

    /**
     * @return array<string, mixed>
     */
    private static function standardCommercial(): array
    {
        return [
            'slug' => 'standard-commercial',
            'name' => 'Proposta comercial executiva',
            'category' => 'general',
            'description' => 'Modelo premium para apresentar âmbito técnico, investimento, condições comerciais, dados bancários, QR de verificação e assinatura de aceitação.',
            'theme_preset' => 'corporate',
            'content' => <<<'HTML'
<h1>Proposta de prestação de serviços laboratoriais</h1>
<p>Apresentamos a proposta referente aos serviços solicitados por <strong>{customer_name}</strong>, consolidando o âmbito analítico, a rastreabilidade das amostras, os critérios técnicos e as condições comerciais aplicáveis.</p>

<h2>Âmbito e enquadramento</h2>
<p>A execução laboratorial será conduzida de acordo com o sistema de gestão definido pelo laboratório, incluindo recepção, validação, análise, revisão e emissão do documento final aplicável.</p>
<p>{lab_details}</p>
<p>{customer_details}</p>

<h2>Serviços e investimento</h2>
<p>{items_table}</p>
<p>{summary_table}</p>

<h2>Condições comerciais</h2>
<ul>
  <li>Local de execução: {service_location}</li>
  <li>Validade da proposta: {expiry_date}</li>
  <li>Prazo de tolerância: {tolerance_days} dias</li>
  <li>Modo de precificação: {pricing_mode}</li>
</ul>
<p>A proposta formaliza o âmbito acordado antes da recepção ou processamento das amostras, reduzindo ambiguidades sobre métodos, prazos, responsabilidades e critérios de aceitação.</p>

<h2>Dados bancários</h2>
<p>{banking_details}</p>

<h2>Aceitação formal</h2>
<p>A execução inicia-se após a aceitação formal pelo cliente e validação interna do laboratório.</p>
<p>{signature_block}</p>
<p>{document_keywords}</p>
HTML,
            'layout_schema' => self::layout(
                firstPageHeaderHtml: '<div style="display:flex; justify-content:space-between; gap:18px; align-items:flex-start; border:1px solid #d8cbb4; border-radius:22px; padding:18px 20px; background:#fbf7ed;"><div><div style="font-size:9px; letter-spacing:0.2em; text-transform:uppercase; color:#9a7a2f;">Proposta laboratorial</div><h2 style="margin:7px 0 0; color:#143d37; font-size:20px;">{{lab_name}}</h2><p style="margin:6px 0 0; color:#59665f;">{{document_code}} · {{customer_name}}</p></div><div style="text-align:right; font-size:10px; color:#59665f;"><strong>Validade</strong><br>{{expiry_date}}<br><span style="color:#9a7a2f;">{{service_location}}</span></div></div>',
                defaultHeaderHtml: '<div style="display:flex; justify-content:space-between; font-size:10px; color:#143d37;"><span>{{document_code}}</span><span>{{customer_name}}</span></div>',
                footerHtml: '<div style="display:flex; justify-content:space-between; border-top:1px solid #d8cbb4; padding-top:7px; font-size:9px; color:#59665f;"><span>Proposta controlada · {{lab_name}}</span><span>Página {PAGENO}/{nbpg}</span></div>',
                stylesCss: self::styles('#143d37', '#d8b85f', '#f7f1e6'),
                canvasBlocks: [
                    self::canvasBlock([
                        'id' => 'commercial-cover-band',
                        'title' => 'Capa executiva',
                        'surface' => 'content',
                        'content_html' => '<div style="font-size:10px; letter-spacing:0.22em; text-transform:uppercase; color:#d8b85f;">{{document_code}}</div><div style="margin-top:12px; font-size:26px; line-height:1.15; font-weight:700;">Proposta para {customer_name}</div><div style="margin-top:12px; font-size:12px;">Escopo técnico, investimento e condições de aceitação num documento único.</div>',
                        'x' => 0,
                        'y' => 0,
                        'width' => 100,
                        'min_height' => 155,
                        'z_index' => 8,
                        'padding' => 24,
                        'background_color' => '#143d37',
                        'text_color' => '#fffdf7',
                        'border_radius' => 28,
                        'font_size' => 12,
                        'line_height' => 1.45,
                        'page_scope' => 'first',
                    ]),
                    self::canvasBlock([
                        'id' => 'commercial-qr',
                        'title' => 'QR de verificação',
                        'surface' => 'content',
                        'block_kind' => 'qr_code',
                        'x' => 76,
                        'y' => 4,
                        'width' => 18,
                        'min_height' => 128,
                        'z_index' => 20,
                        'padding' => 10,
                        'background_color' => 'rgba(255,255,255,0.96)',
                        'border_width' => 1,
                        'border_color' => '#d8cbb4',
                        'border_radius' => 22,
                        'qr_content' => 'Proposta {proposal_number} · {customer_name} · {expiry_date}',
                        'qr_label' => 'Verificar proposta {proposal_number}',
                        'page_scope' => 'first',
                    ]),
                    self::signatureBlock('commercial-client-signature', 'Aceitação do cliente', 52, 73, 'following'),
                ],
                tableHeaderBackground: '#143d37',
                tableHeaderTextColor: '#fffdf7',
                tableBorderColor: '#d8cbb4'
            ),
            'export_settings' => self::exportSettings(firstPageMarginTop: 62),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function isoDecisionRule(): array
    {
        return [
            'slug' => 'iso-decision-rule',
            'name' => 'Proposta técnica ISO 17025',
            'category' => 'compliance',
            'description' => 'Modelo orientado a ISO/IEC 17025, regra de decisão, incerteza, imparcialidade, confidencialidade e evidência de aceitação.',
            'theme_preset' => 'compliance',
            'content' => <<<'HTML'
<h1>Proposta técnica com regra de decisão</h1>
<p>Esta proposta define a execução dos ensaios solicitados por <strong>{customer_name}</strong>, com referência à competência técnica, à rastreabilidade metrológica e à regra de decisão aplicável quando existir declaração de conformidade.</p>

<h2>Âmbito técnico</h2>
<p>Os resultados serão emitidos no contexto do sistema de gestão do laboratório, com pessoal autorizado, equipamentos qualificados, métodos controlados e registos auditáveis.</p>
<p>{lab_details}</p>
<p>{customer_details}</p>

<h2>Itens previstos</h2>
<p>{items_table}</p>

<h2>Regra de decisão e incerteza</h2>
<p>Quando aplicável, a declaração de conformidade seguirá a regra de decisão acordada, considerando a incerteza de medição, os limites de especificação e a responsabilidade técnica definida.</p>
<p>O cliente reconhece que a regra de decisão deve estar aceite antes da execução quando a análise puder resultar numa declaração de conformidade.</p>

<h2>Confidencialidade e imparcialidade</h2>
<p>O laboratório preserva a confidencialidade da informação do cliente e executa os ensaios sem influência indevida sobre a decisão técnica.</p>

<h2>Resumo financeiro</h2>
<p>{summary_table}</p>

<h2>Aceitação</h2>
<ul>
  <li>Cliente: {customer_name} ({customer_code})</li>
  <li>Validade da proposta: {expiry_date}</li>
  <li>Condições adicionais: {observations}</li>
</ul>
<p>{signature_block}</p>
<p>{document_keywords}</p>
HTML,
            'layout_schema' => self::layout(
                firstPageHeaderHtml: '<div style="border-left:5px solid #0f766e; border-radius:20px; padding:16px 18px; background:#ecfdf5;"><div style="font-size:9px; letter-spacing:0.2em; text-transform:uppercase; color:#0f766e;">ISO/IEC 17025 · decisão técnica</div><h2 style="margin:8px 0 0; color:#14532d; font-size:19px;">{{lab_name}}</h2><p style="margin:6px 0 0; color:#166534;">{{document_code}} · {{customer_name}}</p></div>',
                defaultHeaderHtml: '<div style="display:flex; justify-content:space-between; font-size:10px; color:#14532d;"><span>{{document_code}}</span><span>Regra de decisão · {{customer_name}}</span></div>',
                footerHtml: '<div style="display:flex; justify-content:space-between; border-top:1px solid #86efac; padding-top:7px; font-size:9px; color:#166534;"><span>Rastreabilidade, imparcialidade e confidencialidade</span><span>Página {PAGENO}/{nbpg}</span></div>',
                stylesCss: self::styles('#14532d', '#0f766e', '#ecfdf5'),
                canvasBlocks: [
                    self::canvasBlock([
                        'id' => 'iso-compliance-band',
                        'title' => 'Faixa de conformidade',
                        'surface' => 'content',
                        'content_html' => '<div style="font-size:10px; letter-spacing:0.22em; text-transform:uppercase;">Cláusulas críticas</div><div style="margin-top:10px; font-size:20px; line-height:1.2; font-weight:700;">Regra de decisão, incerteza e evidência de aceitação.</div><div style="margin-top:10px; font-size:11px;">Documento preparado para reduzir ambiguidade antes da execução analítica.</div>',
                        'x' => 0,
                        'y' => 0,
                        'width' => 100,
                        'min_height' => 140,
                        'z_index' => 8,
                        'padding' => 22,
                        'background_color' => '#14532d',
                        'text_color' => '#ffffff',
                        'border_radius' => 28,
                        'font_size' => 12,
                        'line_height' => 1.45,
                        'page_scope' => 'first',
                    ]),
                    self::canvasBlock([
                        'id' => 'iso-duty-card',
                        'title' => 'Imparcialidade e confidencialidade',
                        'surface' => 'content',
                        'content_html' => '<strong>Compromisso técnico</strong><br>Confidencialidade, imparcialidade e competência documentadas desde a aceitação da proposta.',
                        'x' => 5,
                        'y' => 76,
                        'width' => 38,
                        'min_height' => 92,
                        'z_index' => 18,
                        'padding' => 14,
                        'background_color' => '#f0fdf4',
                        'text_color' => '#14532d',
                        'border_width' => 1,
                        'border_color' => '#86efac',
                        'border_radius' => 22,
                        'font_size' => 11,
                        'page_scope' => 'following',
                    ]),
                    self::canvasBlock([
                        'id' => 'iso-qr',
                        'title' => 'QR de rastreabilidade',
                        'surface' => 'content',
                        'block_kind' => 'qr_code',
                        'x' => 76,
                        'y' => 4,
                        'width' => 18,
                        'min_height' => 128,
                        'z_index' => 20,
                        'padding' => 10,
                        'background_color' => '#ffffff',
                        'border_width' => 1,
                        'border_color' => '#86efac',
                        'border_radius' => 22,
                        'qr_content' => 'ISO 17025 · Proposta {proposal_number} · {customer_name}',
                        'qr_label' => 'Validar proposta {proposal_number}',
                        'page_scope' => 'first',
                    ]),
                    self::signatureBlock('iso-client-signature', 'Aceitação da regra de decisão', 54, 74, 'following'),
                ],
                tableHeaderBackground: '#14532d',
                tableHeaderTextColor: '#ffffff',
                tableBorderColor: '#bbf7d0'
            ),
            'export_settings' => self::exportSettings(firstPageMarginTop: 64),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fieldCollection(): array
    {
        return [
            'slug' => 'field-collection',
            'name' => 'Proposta de recolha e cadeia de custódia',
            'category' => 'field-services',
            'description' => 'Modelo para recolha programada ou directa, logística, cadeia de custódia, prioridade operacional e condições de recepção.',
            'theme_preset' => 'field',
            'content' => <<<'HTML'
<h1>Proposta para recolha, recepção e ensaio</h1>
<p>A presente proposta contempla actividades de recolha, transporte, recepção, manuseamento e análise das amostras identificadas para <strong>{customer_name}</strong>.</p>

<h2>Plano operacional</h2>
<ul>
  <li>Local de serviço: {service_location}</li>
  <li>Armazém ou ponto operacional: {warehouse}</li>
  <li>Departamento responsável: {department}</li>
</ul>
<p>{lab_details}</p>
<p>{customer_details}</p>

<h2>Serviços incluídos</h2>
<p>{items_table}</p>

<h2>Cadeia de custódia</h2>
<p>As amostras serão acompanhadas por registos de origem, recolha, recepção, estado e encaminhamento analítico, preservando rastreabilidade integral até ao documento final.</p>

<h2>Resumo financeiro</h2>
<p>{summary_table}</p>

<h2>Dados bancários e aceitação</h2>
<p>{banking_details}</p>
<p>{signature_block}</p>
<p>{document_keywords}</p>
HTML,
            'layout_schema' => self::layout(
                firstPageHeaderHtml: '<div style="display:flex; justify-content:space-between; align-items:flex-start; border:1px solid #fdba74; border-radius:22px; padding:16px 18px; background:#fff7ed;"><div><div style="font-size:9px; letter-spacing:0.2em; text-transform:uppercase; color:#c2410c;">Recolha e cadeia de custódia</div><h2 style="margin:8px 0 0; color:#9a3412; font-size:19px;">{{lab_name}}</h2><p style="margin:6px 0 0; color:#9a3412;">{{document_code}} · {{customer_name}}</p></div><div style="text-align:right; font-size:10px; color:#c2410c;">{{service_location}}<br>{{warehouse}}</div></div>',
                defaultHeaderHtml: '<div style="display:flex; justify-content:space-between; font-size:10px; color:#9a3412;"><span>{{document_code}}</span><span>Recolha e logística · {{customer_name}}</span></div>',
                footerHtml: '<div style="display:flex; justify-content:space-between; border-top:1px solid #fdba74; padding-top:7px; font-size:9px; color:#9a3412;"><span>Cadeia de custódia e rastreabilidade operacional</span><span>Página {PAGENO}/{nbpg}</span></div>',
                stylesCss: self::styles('#9a3412', '#c2410c', '#fff7ed'),
                canvasBlocks: [
                    self::canvasBlock([
                        'id' => 'field-route-card',
                        'title' => 'Cartão operacional',
                        'surface' => 'content',
                        'content_html' => '<div style="font-size:10px; letter-spacing:0.2em; text-transform:uppercase;">Plano operacional</div><div style="margin-top:10px; font-size:21px; line-height:1.2; font-weight:700;">Recolha, recepção e ensaio com rastreabilidade desde o primeiro contacto.</div><div style="margin-top:10px; font-size:11px;">{service_location} · {warehouse}</div>',
                        'x' => 0,
                        'y' => 0,
                        'width' => 100,
                        'min_height' => 145,
                        'z_index' => 8,
                        'padding' => 22,
                        'background_color' => '#9a3412',
                        'text_color' => '#fff7ed',
                        'border_radius' => 28,
                        'font_size' => 12,
                        'line_height' => 1.45,
                        'page_scope' => 'first',
                    ]),
                    self::canvasBlock([
                        'id' => 'field-custody-note',
                        'title' => 'Nota de custódia',
                        'surface' => 'content',
                        'content_html' => '<strong>Cadeia de custódia</strong><br>Origem, estado, recolha, transporte e recepção devem permanecer ligados ao processo analítico.',
                        'x' => 5,
                        'y' => 75,
                        'width' => 40,
                        'min_height' => 94,
                        'z_index' => 18,
                        'padding' => 14,
                        'background_color' => '#fff7ed',
                        'text_color' => '#9a3412',
                        'border_width' => 1,
                        'border_color' => '#fdba74',
                        'border_radius' => 22,
                        'font_size' => 11,
                        'page_scope' => 'following',
                    ]),
                    self::canvasBlock([
                        'id' => 'field-qr',
                        'title' => 'QR do plano operacional',
                        'surface' => 'content',
                        'block_kind' => 'qr_code',
                        'x' => 76,
                        'y' => 4,
                        'width' => 18,
                        'min_height' => 128,
                        'z_index' => 20,
                        'padding' => 10,
                        'background_color' => '#ffffff',
                        'border_width' => 1,
                        'border_color' => '#fdba74',
                        'border_radius' => 22,
                        'qr_content' => 'Recolha · Proposta {proposal_number} · {customer_name} · {service_location}',
                        'qr_label' => 'Validar recolha {proposal_number}',
                        'page_scope' => 'first',
                    ]),
                    self::signatureBlock('field-client-signature', 'Aceitação da recolha', 54, 74, 'following'),
                ],
                tableHeaderBackground: '#9a3412',
                tableHeaderTextColor: '#ffffff',
                tableBorderColor: '#fdba74'
            ),
            'export_settings' => self::exportSettings(firstPageMarginTop: 60),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $canvasBlocks
     * @return array<string, mixed>
     */
    private static function layout(
        string $firstPageHeaderHtml,
        string $defaultHeaderHtml,
        string $footerHtml,
        string $stylesCss,
        array $canvasBlocks,
        string $tableHeaderBackground,
        string $tableHeaderTextColor,
        string $tableBorderColor
    ): array {
        return [
            'first_page_header_html' => $firstPageHeaderHtml,
            'default_header_html' => $defaultHeaderHtml,
            'footer_html' => $footerHtml,
            'styles_css' => $stylesCss,
            'document_font_family' => '"Century Gothic", DejaVu Sans, sans-serif',
            'variable_catalog' => [
                ['value' => '{customer_name}', 'label' => 'Cliente'],
                ['value' => '{proposal_number}', 'label' => 'Número da proposta'],
                ['value' => '{items_table}', 'label' => 'Tabela de serviços'],
                ['value' => '{items_list}', 'label' => 'Lista de serviços'],
                ['value' => '{summary_table}', 'label' => 'Resumo financeiro'],
                ['value' => '{lab_details}', 'label' => 'Dados do laboratório'],
                ['value' => '{customer_details}', 'label' => 'Dados do cliente'],
                ['value' => '{banking_details}', 'label' => 'Dados bancários'],
                ['value' => '{bank_name}', 'label' => 'Banco'],
                ['value' => '{bank_iban}', 'label' => 'IBAN'],
                ['value' => '{expiry_date}', 'label' => 'Validade'],
                ['value' => '{service_location}', 'label' => 'Local do serviço'],
                ['value' => '{department}', 'label' => 'Departamento'],
                ['value' => '{warehouse}', 'label' => 'Unidade operacional'],
                ['value' => '{signature_block}', 'label' => 'Bloco de assinatura'],
                ['value' => '{lab_signature}', 'label' => 'Assinatura do laboratório'],
                ['value' => '{client_signature}', 'label' => 'Assinatura do cliente'],
                ['value' => '{document_keywords}', 'label' => 'Palavras-chave'],
            ],
            'canvas_blocks' => $canvasBlocks,
            'background_image_path' => '',
            'background_size' => 'cover',
            'background_position' => 'center center',
            'background_repeat' => 'no-repeat',
            'table_header_background' => $tableHeaderBackground,
            'table_header_text_color' => $tableHeaderTextColor,
            'table_border_color' => $tableBorderColor,
            'table_font_size' => 10,
            'table_cell_padding' => 8,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function canvasBlock(array $overrides): array
    {
        return array_replace([
            'id' => 'canvas-block',
            'title' => 'Bloco editorial',
            'surface' => 'content',
            'block_kind' => 'rich_text',
            'content_html' => '',
            'image_url' => '',
            'image_alt' => '',
            'image_fit' => 'contain',
            'image_position' => 'center center',
            'qr_content' => '',
            'qr_label' => '',
            'x' => 0,
            'y' => 0,
            'width' => 100,
            'min_height' => 0,
            'z_index' => 10,
            'padding' => 0,
            'background_color' => 'transparent',
            'background_image' => '',
            'background_image_fit' => 'cover',
            'background_image_position' => 'center center',
            'overlay_color' => '',
            'overlay_opacity' => 0.35,
            'text_color' => '#143d37',
            'border_width' => 0,
            'border_color' => 'rgba(148,163,184,0.35)',
            'border_radius' => 0,
            'opacity' => 1,
            'text_align' => 'left',
            'font_size' => 12,
            'line_height' => 1.5,
            'is_locked' => false,
            'page_scope' => 'all',
            'page_number' => 1,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private static function signatureBlock(string $id, string $label, float $x, float $y, string $pageScope): array
    {
        return self::canvasBlock([
            'id' => $id,
            'title' => $label,
            'surface' => 'content',
            'block_kind' => 'signature',
            'x' => $x,
            'y' => $y,
            'width' => 36,
            'min_height' => 124,
            'z_index' => 36,
            'padding' => 12,
            'background_color' => 'rgba(255,255,255,0.92)',
            'text_color' => '#143d37',
            'border_width' => 1,
            'border_color' => '#d8cbb4',
            'border_radius' => 22,
            'signature_label' => $label,
            'signature_name' => '{customer_name}',
            'signature_title' => 'Cliente / representante autorizado',
            'signature_image' => '',
            'signature_line_style' => 'dashed',
            'signature_align' => 'right',
            'signature_show_date' => true,
            'signature_date_label' => 'Data da aceitação: ____ / ____ / ______',
            'page_scope' => $pageScope,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function exportSettings(int $firstPageMarginTop): array
    {
        return [
            'paper_size' => 'A4',
            'orientation' => 'P',
            'margin_top' => 22,
            'margin_right' => 14,
            'margin_bottom' => 18,
            'margin_left' => 14,
            'first_page_margin_top' => $firstPageMarginTop,
        ];
    }

    private static function styles(string $primary, string $accent, string $softBackground): string
    {
        return <<<CSS
body{font-family:"Century Gothic",DejaVu Sans,sans-serif;color:#263c36;font-size:11px;line-height:1.62;}
h1{font-size:24px;line-height:1.16;color:{$primary};margin:0 0 18px;font-weight:700;}
h2{font-size:15px;color:{$primary};margin:22px 0 10px;font-weight:700;}
p{margin:0 0 10px;}
ul{padding-left:18px;margin:0 0 12px;}
li{margin-bottom:5px;}
table{width:100%;border-collapse:collapse;margin:10px 0 16px;}
th{background:{$primary};color:#fffdf7;font-size:9px;letter-spacing:0.05em;text-transform:uppercase;}
th,td{border:1px solid #d8cbb4;padding:8px;vertical-align:top;}
tbody tr:nth-child(even){background:{$softBackground};}
.document-callout{border-left:4px solid {$accent};background:{$softBackground};padding:14px 16px;border-radius:16px;}
CSS;
    }
}
