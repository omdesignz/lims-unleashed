<?php

namespace App\Support;

use App\Models\ReportStudioTemplate;
use HeadlessChromium\BrowserFactory;

class ReportStudioDefaultTemplates
{
    /**
     * @return array<int, string>
     */
    public static function supportedTypes(): array
    {
        return [
            'analysis',
            'executive',
            'proposal',
            'export_certificate',
            'import_certificate',
            'quote',
            'invoice',
            'receipt',
            'credit_note',
        ];
    }

    public static function make(string $studioType): ReportStudioTemplate
    {
        $studioType = in_array($studioType, self::supportedTypes(), true) ? $studioType : 'analysis';

        return new ReportStudioTemplate([
            'name' => self::nameFor($studioType),
            'studio_type' => $studioType,
            'renderer' => self::preferredRenderer(),
            'status' => 'active',
            'is_default' => true,
            'theme_preset' => in_array($studioType, ['analysis', 'export_certificate', 'import_certificate'], true) ? 'compliance' : 'corporate',
            'description' => self::descriptionFor($studioType),
            'layout_schema' => self::layoutFor($studioType),
            'export_settings' => self::exportSettingsFor($studioType),
        ]);
    }

    /**
     * @return array<int, array{
     *     slug: string,
     *     name: string,
     *     name_key: string,
     *     category: string,
     *     renderer: string,
     *     theme_preset: string,
     *     description: string,
     *     description_key: string,
     *     source: string,
     *     layout_schema: array<string, mixed>,
     *     export_settings: array<string, mixed>
     * }>
     */
    public static function presets(): array
    {
        return array_map(
            fn (string $studioType): array => self::presetFor($studioType),
            self::supportedTypes()
        );
    }

    /**
     * @return array{
     *     slug: string,
     *     name: string,
     *     name_key: string,
     *     category: string,
     *     renderer: string,
     *     theme_preset: string,
     *     description: string,
     *     description_key: string,
     *     source: string,
     *     layout_schema: array<string, mixed>,
     *     export_settings: array<string, mixed>
     * }
     */
    public static function presetFor(string $studioType): array
    {
        $template = self::make($studioType);

        return [
            'slug' => 'system-'.$template->studio_type,
            'name' => $template->name,
            'name_key' => self::nameTranslationKeyFor($template->studio_type),
            'category' => $template->studio_type,
            'renderer' => $template->renderer,
            'theme_preset' => $template->theme_preset,
            'description' => $template->description,
            'description_key' => self::descriptionTranslationKeyFor($template->studio_type),
            'source' => 'system',
            'layout_schema' => $template->layout_schema ?? [],
            'export_settings' => $template->export_settings ?? [],
        ];
    }

    private static function preferredRenderer(): string
    {
        $chromeBinary = config('laravel-pdf.chrome.chrome_binary');

        if (
            class_exists(BrowserFactory::class)
            && (! is_string($chromeBinary) || $chromeBinary === '' || is_executable($chromeBinary))
        ) {
            return 'chrome';
        }

        return 'internal';
    }

    private static function nameFor(string $studioType): string
    {
        return match ($studioType) {
            'executive' => 'Resumo executivo padrão',
            'proposal' => 'Proposta técnica-comercial padrão',
            'export_certificate' => 'Certificado de exportação padrão',
            'import_certificate' => 'Certificado de importação padrão',
            'quote' => 'Proforma comercial padrão',
            'invoice' => 'Factura fiscal padrão',
            'receipt' => 'Recibo de tesouraria padrão',
            'credit_note' => 'Nota de crédito padrão',
            default => 'Relatório analítico padrão',
        };
    }

    private static function descriptionFor(string $studioType): string
    {
        return match ($studioType) {
            'executive' => 'Pacote executivo com indicadores, gráficos, leitura de risco e capacidade operacional.',
            'proposal' => 'Proposta técnica-comercial com escopo, condições, dados bancários, assinatura e aceite do cliente.',
            'export_certificate' => 'Certificado de exportação com produto, origem, destino, expedição e validação técnica.',
            'import_certificate' => 'Certificado de importação com importador, portos, lotes, validade e assinatura técnica.',
            'quote' => 'Proforma com itens, condições comerciais, resumo financeiro e validação formal.',
            'invoice' => 'Factura fiscal com cliente, vencimento, itens, impostos, dados bancários e paginação.',
            'receipt' => 'Recibo de tesouraria com liquidação, forma de pagamento, confirmação e assinatura.',
            'credit_note' => 'Nota de crédito com motivo de rectificação, impacto financeiro e validação.',
            default => 'Relatório analítico com amostra, cadeia de custódia, resultados, incerteza, decisão e assinatura.',
        };
    }

    private static function nameTranslationKeyFor(string $studioType): string
    {
        return 'gestlab.general.labels.vap_report_studios.presets.names.'.$studioType;
    }

    private static function descriptionTranslationKeyFor(string $studioType): string
    {
        return 'gestlab.general.labels.vap_report_studios.presets.descriptions.'.$studioType;
    }

    /**
     * @return array<string, mixed>
     */
    private static function layoutFor(string $studioType): array
    {
        $isCommercial = in_array($studioType, ['proposal', 'quote', 'invoice', 'receipt', 'credit_note'], true);
        $accent = in_array($studioType, ['export_certificate', 'import_certificate'], true) ? '#3f6f58' : '#143d37';
        $title = self::titleFor($studioType);
        $subject = self::subjectTokenFor($studioType);
        $number = self::numberTokenFor($studioType);

        return [
            'first_page_header_html' => self::firstPageHeaderHtml($title, $number, $subject, $accent),
            'default_header_html' => '<div style="font-size:10px; color:#475a53; border-bottom:1px solid #ded3bf; padding-bottom:6px;">'.$title.' · {{document_code}} · '.$subject.'</div>',
            'footer_html' => '<div style="font-size:9px; color:#475a53; border-top:1px solid #ded3bf; padding-top:6px;">Documento controlado · {{document_code}} · Página {PAGENO}/{nbpg}</div>',
            'body_html' => self::bodyHtmlFor($studioType),
            'styles_css' => self::stylesCss($accent),
            'sections' => [
                ['key' => 'identification', 'label' => 'Identificação', 'visible' => true],
                ['key' => $isCommercial ? 'commercial_terms' : 'technical_scope', 'label' => $isCommercial ? 'Condições comerciais' : 'Âmbito técnico', 'visible' => true],
                ['key' => 'validation', 'label' => 'Validação', 'visible' => true],
            ],
            'variable_catalog' => self::variableCatalogFor($studioType),
            'canvas_blocks' => self::canvasBlocksFor($studioType, $accent),
            'document_font_family' => 'Manrope, DejaVu Sans, sans-serif',
            'page_background_color' => '#fffdf7',
            'background_image_path' => '',
            'background_size' => 'cover',
            'background_position' => 'center center',
            'background_repeat' => 'no-repeat',
            'table_header_background' => $accent,
            'table_header_text_color' => '#ffffff',
            'table_border_color' => '#ded3bf',
            'table_font_size' => 10,
            'table_cell_padding' => 8,
            'table_summary_background' => '#fffdf7',
            'table_summary_text_color' => '#15231f',
            'table_summary_muted_color' => '#64748b',
            'show_canvas_grid' => true,
            'show_canvas_rulers' => true,
            'snap_to_grid' => true,
            'snap_grid_size' => 4,
            'page_safe_area' => true,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private static function variableCatalogFor(string $studioType): array
    {
        $common = [
            '{document_code}' => 'Código do documento',
            '{issue_date}' => 'Data de emissão',
            '{lab_name}' => 'Laboratório',
            '{customer_name}' => 'Cliente',
            '{lab_details}' => 'Dados do laboratório',
            '{customer_details}' => 'Dados do cliente',
            '{document_keywords}' => 'Palavras-chave do documento',
            '{brand_primary_color}' => 'Cor primária da marca',
            '{brand_secondary_color}' => 'Cor secundária da marca',
            '{brand_accent_color}' => 'Cor de destaque da marca',
            '{app_primary_color}' => 'Cor primária configurada',
            '{app_secondary_color}' => 'Cor secundária configurada',
            '{app_accent_color}' => 'Cor de destaque configurada',
        ];

        $catalog = match ($studioType) {
            'analysis' => array_merge($common, [
                '{report_title}' => 'Título do relatório',
                '{certificate_code}' => 'Código do certificado',
                '{sample_entry_code}' => 'Código de entrada da amostra',
                '{lab_code}' => 'Código laboratorial',
                '{warehouse_name}' => 'Local de receção',
                '{sample_code}' => 'Código da amostra',
                '{sample_name}' => 'Nome da amostra',
                '{sample_type}' => 'Tipo de amostra',
                '{sample_product}' => 'Produto',
                '{sample_matrix}' => 'Matriz',
                '{sample_lot}' => 'Lote',
                '{sample_origin}' => 'Origem',
                '{sampling_plan_ref}' => 'Plano de amostragem',
                '{collection_date}' => 'Data de recolha',
                '{received_at}' => 'Data de receção',
                '{sample_details}' => 'Tabela de detalhes da amostra',
                '{collection_details}' => 'Receção e cadeia de custódia',
                '{analytical_scope}' => 'Âmbito analítico',
                '{results_table}' => 'Tabela de resultados',
                '{analysis_chart_title}' => 'Título do gráfico de resultados',
                '{analysis_chart_labels}' => 'Etiquetas do gráfico de resultados',
                '{analysis_chart_values}' => 'Valores do gráfico de resultados',
                '{analysis_chart_caption}' => 'Legenda do gráfico de resultados',
                '{analysis_chart_card}' => 'Cartão visual de resultados',
                '{uncertainty_statement}' => 'Declaração de incerteza',
                '{decision_rule}' => 'Regra de decisão',
                '{conclusion}' => 'Conclusão técnica',
                '{validated_by}' => 'Responsável pela validação',
                '{signature_block}' => 'Bloco de assinatura',
            ]),
            'executive' => array_merge($common, [
                '{period_label}' => 'Período analisado',
                '{executive_summary}' => 'Resumo executivo',
                '{executive_kpis}' => 'Indicadores executivos',
                '{executive_charts}' => 'Gráficos executivos',
                '{executive_chart_title}' => 'Título do gráfico do canvas',
                '{executive_chart_labels}' => 'Etiquetas do gráfico do canvas',
                '{executive_chart_values}' => 'Valores do gráfico do canvas',
                '{executive_chart_caption}' => 'Legenda do gráfico do canvas',
                '{top_customers_table}' => 'Clientes com maior actividade',
            ]),
            'proposal' => array_merge($common, [
                '{proposal_number}' => 'Número da proposta',
                '{service_location}' => 'Local do serviço',
                '{expiry_date}' => 'Data de validade',
                '{proposal_content}' => 'Conteúdo editorial da proposta',
                '{parsed_content}' => 'Conteúdo processado da proposta',
                '{items_table}' => 'Tabela de serviços',
                '{summary_table}' => 'Resumo financeiro',
                '{banking_details}' => 'Dados bancários',
                '{bank_name}' => 'Banco',
                '{bank_account_name}' => 'Titular da conta',
                '{bank_account_number}' => 'Número da conta',
                '{bank_iban}' => 'IBAN',
                '{bank_swift}' => 'SWIFT/BIC',
                '{bank_details}' => 'Observações bancárias',
                '{verification_url}' => 'Ligação pública de verificação',
                '{proposal_authenticity}' => 'QR e autenticidade da proposta',
                '{proposal_acceptance_evidence}' => 'Evidência de aceite do cliente',
                '{decision_rule}' => 'Regra de decisão',
                '{observations}' => 'Observações comerciais',
                '{signature_block}' => 'Assinaturas e aceite',
            ]),
            'export_certificate' => array_merge($common, [
                '{certificate_number}' => 'Número do certificado',
                '{exporter_name}' => 'Exportador',
                '{origin_country}' => 'País de origem',
                '{destination_country}' => 'País de destino',
                '{origin_city}' => 'Cidade de origem',
                '{destination_city}' => 'Cidade de destino',
                '{transport_type}' => 'Tipo de transporte',
                '{authorized_personnel}' => 'Responsável autorizado',
                '{expedition_date}' => 'Data de expedição',
                '{expedition_location}' => 'Local de expedição',
                '{products_table}' => 'Tabela de produtos',
                '{remarks}' => 'Observações do certificado',
                '{signature_block}' => 'Bloco de assinatura',
            ]),
            'import_certificate' => array_merge($common, [
                '{certificate_number}' => 'Número do certificado',
                '{importer_name}' => 'Importador',
                '{exporter_name}' => 'Exportador',
                '{destination_country}' => 'País de destino',
                '{port_entry}' => 'Porto de entrada',
                '{port_exit}' => 'Porto de saída',
                '{transport_type}' => 'Tipo de transporte',
                '{authorized_personnel}' => 'Responsável autorizado',
                '{items_table}' => 'Tabela de lotes',
                '{remarks}' => 'Observações do certificado',
                '{signature_block}' => 'Bloco de assinatura',
            ]),
            'quote' => array_merge($common, self::commercialVariableCatalog(), [
                '{quote_number}' => 'Número da proforma',
                '{expiry_date}' => 'Data de validade',
            ]),
            'invoice' => array_merge($common, self::commercialVariableCatalog(), [
                '{due_date}' => 'Data de vencimento',
            ]),
            'receipt' => array_merge($common, self::commercialVariableCatalog(), [
                '{payment_type}' => 'Forma de pagamento',
            ]),
            'credit_note' => array_merge($common, self::commercialVariableCatalog(), [
                '{reason_label}' => 'Motivo da rectificação',
            ]),
            default => $common,
        };

        return collect($catalog)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function commercialVariableCatalog(): array
    {
        return [
            '{document_number}' => 'Número do documento',
            '{unique_hash}' => 'Assinatura completa do documento',
            '{hash_excerpt}' => 'Extracto da assinatura fiscal',
            '{record_verification_payload}' => 'Payload de verificação do registo',
            '{record_verification_evidence}' => 'Evidência de verificação do registo',
            '{agt_validation_number}' => 'Número de validação AGT',
            '{service_location}' => 'Local do serviço',
            '{items_table}' => 'Tabela de itens',
            '{summary_table}' => 'Resumo financeiro',
            '{banking_details}' => 'Dados bancários',
            '{bank_name}' => 'Banco',
            '{bank_account_name}' => 'Titular da conta',
            '{bank_account_number}' => 'Número da conta',
            '{bank_iban}' => 'IBAN',
            '{bank_swift}' => 'SWIFT/BIC',
            '{bank_details}' => 'Observações bancárias',
            '{observations}' => 'Observações comerciais',
            '{signature_block}' => 'Bloco de assinatura',
        ];
    }

    private static function firstPageHeaderHtml(string $title, string $number, string $subject, string $accent): string
    {
        return <<<HTML
<div style="min-height:92px; border:1px solid #ded3bf; border-radius:22px; padding:16px 116px 16px 18px; background:#fffdf7;">
    <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#d9b05f; font-weight:700;">{$title}</div>
    <div style="margin-top:7px; font-size:18px; color:{$accent}; font-weight:800;">{$number}</div>
    <div style="margin-top:5px; font-size:11px; color:#475a53;">{$subject} · {{issue_date}}</div>
</div>
HTML;
    }

    private static function stylesCss(string $accent): string
    {
        return <<<CSS
body { color:#15231f; font-family: Manrope, DejaVu Sans, sans-serif; }
h1, h2, h3 { color: {$accent}; letter-spacing: -0.01em; }
.report-table { border-collapse: collapse; width: 100%; font-size: 10px; }
.report-table th { background: {$accent}; color: #ffffff; border: 1px solid {$accent}; padding: 7px; text-align: left; }
.report-table td { border: 1px solid #ded3bf; padding: 7px; vertical-align: top; }
.document-hero { background: {$accent}; border-radius: 24px; color: #ffffff; }
.document-kicker { color: #d9b05f; font-size: 10px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; }
.studio-lead { color: #e7efe8; font-size: 12px; line-height: 1.65; }
.document-callout { background: #fffaf0; border: 1px solid #eadfca; border-left: 4px solid #d9b05f; border-radius: 18px; padding: 14px; }
.document-summary-table { border-collapse: separate; border-spacing: 0; width: 100%; }
.document-summary-cell { background: #fffdf7; border: 1px solid #ded3bf; border-radius: 18px; padding: 14px; vertical-align: top; }
.label { color: #64748b; display: block; font-size: 9px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; }
.value { color: #15231f; display: block; font-size: 12px; font-weight: 800; margin-top: 4px; }
.muted { color: #64748b; font-size: 10px; line-height: 1.55; }
.bilingual-label { color:#64748b; display:block; font-size:8px; letter-spacing:0.08em; margin-top:2px; text-transform:uppercase; }
.studio-avoid-break { page-break-inside: avoid; break-inside: avoid; }
CSS;
    }

    private static function bodyHtmlFor(string $studioType): string
    {
        return match ($studioType) {
            'executive' => self::executiveBodyHtml(),
            'proposal' => self::proposalBodyHtml(),
            'export_certificate' => self::exportCertificateBodyHtml(),
            'import_certificate' => self::importCertificateBodyHtml(),
            'quote' => self::commercialBodyHtml('Proforma {quote_number}', 'Condições / Terms', 'Emissão: {issue_date}<br>Validade: {expiry_date}<br>Local: {service_location}'),
            'invoice' => self::commercialBodyHtml('Factura {document_number}', 'Condições / Terms', 'Emissão: {issue_date}<br>Vencimento: {due_date}<br>Local: {service_location}'),
            'receipt' => self::commercialBodyHtml('Recibo {document_number}', 'Recebimento / Payment', 'Data: {issue_date}<br>Forma de pagamento: {payment_type}<br>Local: {service_location}'),
            'credit_note' => self::commercialBodyHtml('Nota de crédito {document_number}', 'Motivo / Reason', '{reason_label}<br>Data: {issue_date}<br>Local: {service_location}'),
            default => self::analysisBodyHtml(),
        };
    }

    private static function analysisBodyHtml(): string
    {
        return <<<'HTML'
<section style="padding:28px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;">
    <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#d9b05f; font-weight:800;">Relatório analítico / Analysis report</div>
    <h1 style="margin:10px 0 0; font-size:28px; line-height:1.08;">{report_title}</h1>
    <p style="margin:12px 0 0; font-size:13px; color:#e7efe8;">{certificate_code} · {customer_name} · Entrada {sample_entry_code} · Código laboratorial {lab_code}</p>
</section>
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Cliente / Customer</div><div class="value">{customer_name}</div><div class="muted">{customer_details}</div></td>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Laboratório / Laboratory</div><div class="value">{lab_name}</div><div class="muted">{lab_details}</div></td>
        </tr>
        <tr>
            <td class="document-summary-cell"><div class="label">Amostra / Sample</div><div class="value">{sample_product}</div><div class="muted">Matriz: {sample_matrix}<br>Lote: {sample_lot}<br>Origem: {sample_origin}</div></td>
            <td class="document-summary-cell"><div class="label">Receção / Reception</div><div class="value">{warehouse_name}</div><div class="muted">Recebida em: {received_at}<br>Recolha: {collection_date}<br>Plano: {sampling_plan_ref}</div></td>
        </tr>
    </table>
</section>
<section style="margin:18px 0;">{sample_details}</section>
<section style="margin:18px 0;">{collection_details}</section>
<section style="margin:18px 0;">{analytical_scope}</section>
<section style="margin:20px 0;">{results_table}</section>
<section style="margin:20px 0;">{analysis_chart_card}</section>
<section class="document-callout studio-avoid-break" style="margin-top:20px;">{uncertainty_statement}<br>{decision_rule}</section>
<section style="margin-top:18px;">{conclusion}</section>
<section style="margin-top:18px;">{document_keywords}</section>
<section style="margin-top:24px;">{signature_block}</section>
HTML;
    }

    private static function executiveBodyHtml(): string
    {
        return <<<'HTML'
<section style="padding:30px; border-radius:26px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;">
    <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#d9b05f; font-weight:800;">{{lab_name}} · {{issue_date}}</div>
    <h1 style="margin:12px 0 0; font-size:30px; line-height:1.08;">Resumo executivo</h1>
    <p style="margin:12px 0 0; font-size:13px; color:#e7efe8;">{executive_summary}</p>
</section>
{executive_kpis}
<section style="margin:20px 0;">{executive_charts}</section>
<section style="margin-top:18px;"><h2 style="font-size:16px; color:#143d37;">Clientes com maior actividade recente</h2>{top_customers_table}</section>
HTML;
    }

    private static function proposalBodyHtml(): string
    {
        return <<<'HTML'
<section style="padding:30px; border-radius:26px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;">
    <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#d9b05f; font-weight:800;">Proposta técnica-comercial</div>
    <h1 style="margin:12px 0 0; font-size:30px; line-height:1.08;">Proposta {proposal_number}</h1>
    <p style="margin:12px 0 0; font-size:13px; color:#e7efe8;">{customer_name} · {service_location} · Válida até {expiry_date}</p>
</section>
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Cliente / Customer</div><div class="value">{customer_name}</div><div class="muted">{customer_details}</div></td>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Laboratório / Laboratory</div><div class="value">{lab_name}</div><div class="muted">{lab_details}</div></td>
        </tr>
        <tr>
            <td class="document-summary-cell"><div class="label">Âmbito / Scope</div><div class="value">Serviços laboratoriais propostos</div><div class="muted">O âmbito técnico deve ser aceite antes da execução.</div></td>
            <td class="document-summary-cell"><div class="label">Condições / Terms</div><div class="value">Validade: {expiry_date}</div><div class="muted">Local: {service_location}<br>Regra de decisão: {decision_rule}</div></td>
        </tr>
    </table>
</section>
<section style="margin:20px 0;">{proposal_content}</section>
<section style="margin:20px 0;">{items_table}</section>
<section style="margin-top:20px; page-break-inside:avoid;">{summary_table}</section>
<section class="document-callout studio-avoid-break" style="margin-top:20px;">{observations}</section>
<section style="margin-top:20px;">{banking_details}</section>
<section style="margin-top:20px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%; vertical-align:top;">{proposal_acceptance_evidence}</td>
            <td class="document-summary-cell" style="width:50%; vertical-align:top;">{proposal_authenticity}</td>
        </tr>
    </table>
</section>
<section style="margin-top:18px;">{document_keywords}</section>
<section style="margin-top:24px;">{signature_block}</section>
<pagebreak />
<section style="padding:26px; border-radius:24px; border:1px solid #ded3bf; background:#fffdf7;">
    <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#d9b05f; font-weight:800;">Aceitação do cliente</div>
    <h2 style="margin:10px 0 8px; color:#143d37;">Confirmação de âmbito e condições</h2>
    <p style="margin:0; color:#475a53;">O cliente confirma que o âmbito, a regra de decisão, os prazos e as condições comerciais foram revistos antes da execução dos serviços.</p>
</section>
HTML;
    }

    private static function exportCertificateBodyHtml(): string
    {
        return <<<'HTML'
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Exportador / Exporter</div><div class="value">{exporter_name}</div><div class="muted">{customer_details}</div></td>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Laboratório / Laboratory</div><div class="value">{lab_name}</div><div class="muted">{lab_details}</div></td>
        </tr>
        <tr>
            <td class="document-summary-cell"><div class="label">Origem / Origin</div><div class="value">{origin_city}, {origin_country}</div><div class="muted">Exportador: {exporter_name}</div></td>
            <td class="document-summary-cell"><div class="label">Destino / Destination</div><div class="value">{destination_city}, {destination_country}</div><div class="muted">Transporte: {transport_type}</div></td>
        </tr>
    </table>
</section>
<section style="margin:20px 0;">{products_table}</section>
<section class="document-callout studio-avoid-break" style="margin-top:18px;">Pessoal autorizado: {authorized_personnel}<br>Expedição: {expedition_location} · {expedition_date}</section>
<section style="margin-top:20px;">{remarks}</section>
<section style="margin-top:18px;">{document_keywords}</section>
<section style="margin-top:24px;">{signature_block}</section>
HTML;
    }

    private static function importCertificateBodyHtml(): string
    {
        return <<<'HTML'
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Importador / Importer</div><div class="value">{importer_name}</div><div class="muted">{customer_details}</div></td>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Laboratório / Laboratory</div><div class="value">{lab_name}</div><div class="muted">{lab_details}</div></td>
        </tr>
        <tr>
            <td class="document-summary-cell"><div class="label">Importação / Import</div><div class="value">{destination_country}</div><div class="muted">Importador: {importer_name}<br>Exportador: {exporter_name}</div></td>
            <td class="document-summary-cell"><div class="label">Logística / Logistics</div><div class="value">{transport_type}</div><div class="muted">Porto de saída: {port_exit}<br>Porto de entrada: {port_entry}</div></td>
        </tr>
    </table>
</section>
<section style="margin:20px 0;">{items_table}</section>
<section class="document-callout studio-avoid-break" style="margin-top:18px;">Pessoal autorizado: {authorized_personnel}<br>Emissão: {issue_date}</section>
<section style="margin-top:20px;">{remarks}</section>
<section style="margin-top:18px;">{document_keywords}</section>
<section style="margin-top:24px;">{signature_block}</section>
HTML;
    }

    private static function commercialBodyHtml(string $title, string $termsTitle, string $termsBody): string
    {
        return <<<HTML
<section style="padding:28px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;">
    <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#d9b05f; font-weight:800;">Documento comercial controlado</div>
    <h1 style="margin:12px 0 0; font-size:28px; line-height:1.08;">{$title}</h1>
    <p style="margin:12px 0 0; font-size:13px; color:#e7efe8;">{customer_name} · {service_location} · {issue_date}</p>
</section>
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Cliente / Customer</div><div class="value">{customer_name}</div><div class="muted">{customer_details}</div></td>
            <td class="document-summary-cell" style="width:50%;"><div class="label">Laboratório / Laboratory</div><div class="value">{lab_name}</div><div class="muted">{lab_details}</div></td>
        </tr>
        <tr>
            <td class="document-summary-cell"><div class="label">{$termsTitle}</div><div class="value">Condições do documento</div><div class="muted">{$termsBody}</div></td>
            <td class="document-summary-cell"><div class="label">Dados bancários / Banking</div><div class="value">Pagamento</div><div class="muted">{banking_details}</div></td>
        </tr>
    </table>
</section>
<section style="margin:20px 0;">{items_table}</section>
<section style="margin-top:20px; page-break-inside:avoid;">{summary_table}</section>
<section class="document-callout studio-avoid-break" style="margin-top:20px;">{observations}</section>
<section style="margin-top:18px;">{document_keywords}</section>
<section style="margin-top:24px;">{signature_block}</section>
HTML;
    }

    /**
     * @return array<string, mixed>
     */
    private static function headerQrBlock(string $studioType, string $accent): array
    {
        $qrContent = self::isCommercial($studioType)
            ? '{{record_verification_payload}}'
            : '{{document_code}} · {{customer_name}} · {{issue_date}}';

        return [
            'id' => $studioType.'-default-auth-qr',
            'title' => 'QR de autenticidade',
            'block_kind' => 'qr_code',
            'surface' => 'first_page_header_html',
            'page_scope' => 'all',
            'x' => 83,
            'y' => 7,
            'width' => 12,
            'min_height' => 82,
            'z_index' => 30,
            'padding' => 6,
            'background_color' => 'rgba(255,255,255,0.95)',
            'border_width' => 1,
            'border_color' => 'rgba(222,211,191,0.95)',
            'border_radius' => 16,
            'shadow_preset' => 'none',
            'qr_content' => $qrContent,
            'qr_label' => 'Verificação',
            'qr_foreground_color' => $accent,
            'qr_background_color' => '#ffffff',
            'qr_error_correction' => 'medium',
            'qr_margin' => 6,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function canvasBlocksFor(string $studioType, string $accent): array
    {
        $blocks = [
            self::headerQrBlock($studioType, $accent),
            self::signatureBlock($studioType),
        ];

        if ($studioType === 'executive') {
            $blocks[] = self::executiveChartBlock($accent);
        }

        if ($studioType === 'analysis') {
            $blocks[] = self::analysisDecisionRuleBlock();
            $blocks[] = self::analysisChartBlock($accent);
        }

        if (self::isCommercial($studioType)) {
            $blocks[] = self::bankingDetailsBlock($studioType);
        }

        if ($studioType === 'proposal') {
            $blocks[] = self::proposalAcceptanceBlock();
        }

        return $blocks;
    }

    private static function isCommercial(string $studioType): bool
    {
        return in_array($studioType, ['proposal', 'quote', 'invoice', 'receipt', 'credit_note'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private static function signatureBlock(string $studioType): array
    {
        $isCommercial = self::isCommercial($studioType);

        return [
            'id' => $studioType.'-signature-block',
            'title' => $isCommercial ? 'Assinatura e validação' : 'Assinatura técnica',
            'block_kind' => 'signature',
            'surface' => 'content',
            'page_scope' => 'first',
            'x' => $isCommercial ? 56 : 58,
            'y' => 76,
            'width' => $isCommercial ? 36 : 34,
            'min_height' => 118,
            'z_index' => 20,
            'padding' => 16,
            'background_color' => 'rgba(255,255,255,0.92)',
            'border_width' => 1,
            'border_color' => 'rgba(222,211,191,0.9)',
            'border_radius' => 24,
            'shadow_preset' => 'soft',
            'signature_label' => $isCommercial ? 'Validação do documento' : 'Validação técnica',
            'signature_name' => '{{lab_name}}',
            'signature_title' => $isCommercial ? 'Direcção comercial / financeira' : 'Direcção técnica',
            'signature_image_fit' => 'contain',
            'signature_image_position' => 'center center',
            'signature_image_width' => 180,
            'signature_image_height' => 72,
            'signature_line_style' => 'solid',
            'signature_align' => 'left',
            'signature_show_date' => true,
            'signature_date_label' => 'Data: {issue_date}',
            'is_hidden' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function executiveChartBlock(string $accent): array
    {
        return [
            'id' => 'executive-studio-chart',
            'title' => 'Gráfico executivo',
            'block_kind' => 'chart_snapshot',
            'surface' => 'content',
            'page_scope' => 'first',
            'x' => 7,
            'y' => 52,
            'width' => 45,
            'min_height' => 210,
            'z_index' => 18,
            'padding' => 16,
            'background_color' => 'rgba(255,255,255,0.96)',
            'border_width' => 1,
            'border_color' => 'rgba(222,211,191,0.92)',
            'border_radius' => 26,
            'shadow_preset' => 'elevated',
            'chart_title' => '{executive_chart_title}',
            'chart_caption' => '{executive_chart_caption}',
            'chart_type' => 'line',
            'chart_labels' => '{executive_chart_labels}',
            'chart_values' => '{executive_chart_values}',
            'chart_colors' => $accent.', #d9b05f, #3f6f58',
            'chart_primary_color' => $accent,
            'chart_background_color' => '#f8f4ea',
            'chart_show_values' => true,
            'is_hidden' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function analysisDecisionRuleBlock(): array
    {
        return [
            'id' => 'analysis-decision-rule-note',
            'title' => 'Regra de decisão',
            'block_kind' => 'rich_text',
            'surface' => 'content',
            'page_scope' => 'first',
            'x' => 6,
            'y' => 72,
            'width' => 42,
            'min_height' => 120,
            'z_index' => 16,
            'padding' => 18,
            'background_color' => '#fffaf0',
            'border_width' => 1,
            'border_color' => 'rgba(217,176,95,0.68)',
            'border_radius' => 24,
            'shadow_preset' => 'soft',
            'content_html' => '<p style="margin:0 0 6px; font-weight:700; color:#143d37;">Decisão e incerteza</p><p style="margin:0; color:#475a53;">{decision_rule}</p>',
            'is_hidden' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function analysisChartBlock(string $accent): array
    {
        return [
            'id' => 'analysis-results-chart',
            'title' => 'Gráfico de resultados',
            'block_kind' => 'chart_snapshot',
            'surface' => 'content',
            'page_scope' => 'first',
            'x' => 6,
            'y' => 52,
            'width' => 42,
            'min_height' => 168,
            'z_index' => 15,
            'padding' => 14,
            'background_color' => 'rgba(255,255,255,0.96)',
            'border_width' => 1,
            'border_color' => 'rgba(222,211,191,0.92)',
            'border_radius' => 24,
            'shadow_preset' => 'soft',
            'chart_title' => '{analysis_chart_title}',
            'chart_caption' => '{analysis_chart_caption}',
            'chart_type' => 'bar',
            'chart_labels' => '{analysis_chart_labels}',
            'chart_values' => '{analysis_chart_values}',
            'chart_colors' => $accent.', #d9b05f, #0f766e',
            'chart_primary_color' => $accent,
            'chart_background_color' => '#f8f4ea',
            'chart_show_values' => true,
            'is_hidden' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function bankingDetailsBlock(string $studioType): array
    {
        return [
            'id' => $studioType.'-banking-details',
            'title' => 'Dados bancários',
            'block_kind' => 'rich_text',
            'surface' => 'content',
            'page_scope' => 'first',
            'x' => 6,
            'y' => 76,
            'width' => 44,
            'min_height' => 118,
            'z_index' => 16,
            'padding' => 18,
            'background_color' => '#fffaf0',
            'border_width' => 1,
            'border_color' => 'rgba(217,176,95,0.68)',
            'border_radius' => 24,
            'shadow_preset' => 'soft',
            'content_html' => '<p style="margin:0 0 8px; font-weight:700; color:#143d37;">Dados bancários</p>{banking_details}',
            'is_hidden' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function proposalAcceptanceBlock(): array
    {
        return [
            'id' => 'proposal-client-acceptance',
            'title' => 'Aceitação do cliente',
            'block_kind' => 'signature',
            'surface' => 'content',
            'page_scope' => 'following',
            'x' => 52,
            'y' => 64,
            'width' => 40,
            'min_height' => 130,
            'z_index' => 22,
            'padding' => 16,
            'background_color' => 'rgba(255,255,255,0.94)',
            'border_width' => 1,
            'border_color' => 'rgba(222,211,191,0.9)',
            'border_radius' => 24,
            'shadow_preset' => 'soft',
            'signature_label' => 'Aceitação do cliente',
            'signature_name' => '{customer_name}',
            'signature_title' => 'Representante autorizado',
            'signature_image_fit' => 'contain',
            'signature_image_position' => 'center center',
            'signature_image_width' => 180,
            'signature_image_height' => 72,
            'signature_line_style' => 'solid',
            'signature_align' => 'right',
            'signature_show_date' => true,
            'signature_date_label' => 'Data de aceite: ____ / ____ / ______',
            'is_hidden' => true,
        ];
    }

    private static function titleFor(string $studioType): string
    {
        return match ($studioType) {
            'executive' => 'Resumo executivo',
            'proposal' => 'Proposta técnica-comercial',
            'export_certificate' => 'Certificado de exportação',
            'import_certificate' => 'Certificado de importação',
            'quote' => 'Proforma comercial',
            'invoice' => 'Factura fiscal',
            'receipt' => 'Recibo de tesouraria',
            'credit_note' => 'Nota de crédito',
            default => 'Relatório analítico',
        };
    }

    private static function numberTokenFor(string $studioType): string
    {
        return match ($studioType) {
            'proposal' => '{{proposal_number}}',
            default => '{{document_code}}',
        };
    }

    private static function subjectTokenFor(string $studioType): string
    {
        return match ($studioType) {
            'export_certificate' => '{{exporter_name}}',
            'import_certificate' => '{{importer_name}}',
            default => '{{customer_name}}',
        };
    }

    /**
     * @return array<string, int|string|null>
     */
    private static function exportSettingsFor(string $studioType): array
    {
        return [
            'paper_size' => 'A4',
            'custom_page_width' => null,
            'custom_page_height' => null,
            'orientation' => 'P',
            'margin_top' => 20,
            'margin_bottom' => in_array($studioType, ['analysis'], true) ? 24 : 22,
            'margin_left' => 14,
            'margin_right' => 14,
            'first_page_margin_top' => match ($studioType) {
                'analysis' => 58,
                'executive' => 42,
                'export_certificate', 'import_certificate' => 52,
                default => 56,
            },
        ];
    }
}
