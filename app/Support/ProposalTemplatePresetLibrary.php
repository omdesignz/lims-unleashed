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
            [
                'slug' => 'standard-commercial',
                'name' => 'Proposta comercial padrão',
                'category' => 'general',
                'description' => 'Modelo equilibrado para ensaios de rotina, serviços laboratoriais e propostas com decisão comercial clara.',
                'theme_preset' => 'corporate',
                'content' => <<<'HTML'
<h1>Proposta de prestação de serviços laboratoriais</h1>
<p>Apresentamos a proposta referente aos serviços solicitados por <strong>{customer_name}</strong>, considerando o âmbito analítico, a matriz declarada e os critérios de aceitação acordados.</p>
<h2>Âmbito e enquadramento</h2>
<p>Esta proposta cobre os ensaios requeridos, a rastreabilidade das amostras, os requisitos de validação dos resultados e a emissão do certificado ou relatório aplicável.</p>
<h2>Itens e serviços</h2>
<p>{items_table}</p>
<h2>Resumo financeiro</h2>
<p>{summary_table}</p>
<h2>Condições de execução</h2>
<ul>
  <li>Local de execução: {service_location}</li>
  <li>Validade da proposta: {expiry_date}</li>
  <li>Prazo de tolerância: {tolerance_days} dias</li>
  <li>Modo de precificação: {pricing_mode}</li>
</ul>
<h2>Decisão e aceitação</h2>
<p>A execução laboratorial inicia-se após a aceitação formal pelo cliente, nos termos aqui apresentados.</p>
<p>{observations}</p>
HTML,
                'layout_schema' => [
                    'first_page_header_html' => '<div style="border-bottom:1px solid #0f172a; padding-bottom:12px;"><h2 style="margin:0;">{{lab_name}}</h2><p style="margin-top:6px;">Proposta {{document_code}}</p></div>',
                    'default_header_html' => '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    'footer_html' => '<div style="font-size:9px; color:#334155;">Documento controlado · Página {PAGENO}/{nbpg}</div>',
                    'styles_css' => 'body{font-family:DejaVu Sans,sans-serif;color:#0f172a;} h1,h2{color:#0f172a;} table{width:100%;border-collapse:collapse;}',
                    'background_image_path' => '',
                ],
                'export_settings' => [
                    'margin_top' => 22,
                    'margin_right' => 14,
                    'margin_bottom' => 18,
                    'margin_left' => 14,
                    'first_page_margin_top' => 56,
                ],
            ],
            [
                'slug' => 'iso-decision-rule',
                'name' => 'Proposta com regra de decisão ISO',
                'category' => 'compliance',
                'description' => 'Modelo para trabalhos com forte enquadramento ISO 17025, incluindo decisão, incerteza e responsabilidade das partes.',
                'theme_preset' => 'compliance',
                'content' => <<<'HTML'
<h1>Proposta técnica com regra de decisão</h1>
<p>Esta proposta define a execução dos ensaios solicitados por <strong>{customer_name}</strong>, com referência explícita à incerteza aplicável e à regra de decisão adoptada.</p>
<h2>Âmbito técnico</h2>
<p>Os resultados serão emitidos no contexto do sistema de gestão do laboratório, com rastreabilidade metrológica, pessoal autorizado e equipamentos qualificados.</p>
<h2>Itens previstos</h2>
<p>{items_table}</p>
<h2>Regra de decisão e incerteza</h2>
<p>Quando aplicável, a declaração de conformidade seguirá a regra de decisão acordada, considerando a incerteza de medição e os limites de especificação relevantes.</p>
<h2>Resumo financeiro</h2>
<p>{summary_table}</p>
<h2>Condições de aceitação</h2>
<ul>
  <li>Cliente: {customer_name} ({customer_code})</li>
  <li>Validade da proposta: {expiry_date}</li>
  <li>Condições adicionais: {observations}</li>
</ul>
HTML,
                'layout_schema' => [
                    'first_page_header_html' => '<div style="border-bottom:2px solid #0f172a; padding-bottom:12px;"><div style="font-weight:bold;">{{lab_name}}</div><div style="font-size:11px;">Proposta técnica · {{document_code}}</div></div>',
                    'default_header_html' => '<div style="font-size:10px; color:#334155;">Proposta técnica · {{customer_name}}</div>',
                    'footer_html' => '<div style="font-size:9px; color:#475569;">Regra de decisão e rastreabilidade documental · Página {PAGENO}/{nbpg}</div>',
                    'styles_css' => 'body{font-family:DejaVu Sans,sans-serif;color:#0f172a;} h1{font-size:22px;} h2{font-size:15px;color:#1e293b;} ul{padding-left:18px;}',
                    'background_image_path' => '',
                ],
                'export_settings' => [
                    'margin_top' => 24,
                    'margin_right' => 14,
                    'margin_bottom' => 18,
                    'margin_left' => 14,
                    'first_page_margin_top' => 58,
                ],
            ],
            [
                'slug' => 'field-collection',
                'name' => 'Proposta com recolha e logística',
                'category' => 'field-services',
                'description' => 'Modelo orientado a recolhas, cadeia de custódia, logística e envio de equipa ao campo.',
                'theme_preset' => 'field',
                'content' => <<<'HTML'
<h1>Proposta para recolha e ensaio</h1>
<p>A presente proposta contempla actividades de recolha, recepção, manuseamento e análise das amostras identificadas para <strong>{customer_name}</strong>.</p>
<h2>Plano operacional</h2>
<ul>
  <li>Local de serviço: {service_location}</li>
  <li>Armazém ou ponto operacional: {warehouse}</li>
  <li>Departamento responsável: {department}</li>
</ul>
<h2>Serviços incluídos</h2>
<p>{items_table}</p>
<h2>Condições logísticas</h2>
<p>As amostras serão tratadas com rastreabilidade integral desde a recolha até à emissão do certificado aplicável.</p>
<h2>Resumo financeiro</h2>
<p>{summary_table}</p>
<p>{observations}</p>
HTML,
                'layout_schema' => [
                    'first_page_header_html' => '<div style="border-bottom:1px solid #14532d; padding-bottom:12px;"><h2 style="margin:0; color:#14532d;">{{lab_name}}</h2><p style="margin-top:6px;">Serviços de recolha e ensaio · {{document_code}}</p></div>',
                    'default_header_html' => '<div style="font-size:10px; color:#14532d;">{{customer_name}} · Recolha e logística</div>',
                    'footer_html' => '<div style="font-size:9px; color:#166534;">Cadeia de custódia e rastreabilidade · Página {PAGENO}/{nbpg}</div>',
                    'styles_css' => 'body{font-family:DejaVu Sans,sans-serif;color:#14532d;} h1,h2{color:#14532d;}',
                    'background_image_path' => '',
                ],
                'export_settings' => [
                    'margin_top' => 22,
                    'margin_right' => 14,
                    'margin_bottom' => 18,
                    'margin_left' => 14,
                    'first_page_margin_top' => 54,
                ],
            ],
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
}
