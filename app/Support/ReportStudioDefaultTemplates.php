<?php

namespace App\Support;

use App\Models\ReportStudioTemplate;
use HeadlessChromium\BrowserFactory;

class ReportStudioDefaultTemplates
{
    /** The text colour of every controlled document. */
    private const INK = '#111827';

    /** A document of free form: its title, text and fields are the template's own. */
    public const CUSTOM = 'custom';

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
            self::CUSTOM,
        ];
    }

    /**
     * The fields a free-form document starts with. Each is a token of the
     * template, filled in by whoever issues the document.
     *
     * @return array<int, array{key: string, label: string, type: string, sample: string, required: bool}>
     */
    public static function customFields(): array
    {
        return [
            ['key' => 'recipient', 'label' => 'Destinatário', 'type' => 'text', 'sample' => 'Cliente laboratorial de referência', 'required' => false],
            ['key' => 'document_subject', 'label' => 'Assunto', 'type' => 'text', 'sample' => 'Declaração de prestação de serviços de ensaio', 'required' => true],
            ['key' => 'document_body', 'label' => 'Texto', 'type' => 'long_text', 'sample' => "Para os devidos efeitos, declara-se que o laboratório realizou os ensaios solicitados, de acordo com os métodos acordados com o cliente.\n\nA presente declaração é emitida a pedido do interessado.", 'required' => true],
            ['key' => 'signatory_name', 'label' => 'Assinado por', 'type' => 'text', 'sample' => 'Direcção técnica', 'required' => false],
            ['key' => 'signatory_role', 'label' => 'Função', 'type' => 'text', 'sample' => 'Responsável pelo laboratório', 'required' => false],
        ];
    }

    /**
     * Tokens every free-form document has without declaring them, so a field
     * of the same name could never be told apart from them.
     *
     * @return list<string>
     */
    public static function reservedCustomTokens(): array
    {
        return [
            'document_title', 'document_code', 'document_revision', 'issue_date', 'signature_block', 'end_of_document',
            'lab_name', 'lab_logo', 'lab_identity', 'lab_details', 'verification_qr', 'customer_name',
            'brand_primary_color', 'brand_secondary_color', 'brand_accent_color', 'app_primary_color', 'app_secondary_color', 'app_accent_color',
            'PAGENO', 'nbpg',
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
            self::CUSTOM => 'Documento livre',
            default => 'Relatório analítico padrão',
        };
    }

    private static function descriptionFor(string $studioType): string
    {
        return match ($studioType) {
            'executive' => 'Pacote executivo com indicadores, gráficos, leitura de risco e capacidade operacional.',
            'proposal' => 'Proposta técnica-comercial com âmbito, condições, dados bancários, assinatura e aceite do cliente.',
            'export_certificate' => 'Certificado de exportação com produto, origem, destino, expedição e validação técnica.',
            'import_certificate' => 'Certificado de importação com importador, portos, lotes, validade e assinatura técnica.',
            'quote' => 'Proforma com itens, condições comerciais, resumo financeiro e validação formal.',
            'invoice' => 'Factura fiscal com cliente, vencimento, itens, impostos, dados bancários e paginação.',
            'receipt' => 'Recibo de tesouraria com liquidação, forma de pagamento, confirmação e assinatura.',
            'credit_note' => 'Nota de crédito com motivo de rectificação, impacto financeiro e validação.',
            self::CUSTOM => 'Documento controlado de formato livre: cartas, declarações, actas, procedimentos e impressos, com os campos que definir.',
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
    /**
     * The system default layout of a document type.
     *
     * @return array<string, mixed>
     */
    public static function layout(string $studioType): array
    {
        return self::layoutFor(in_array($studioType, self::supportedTypes(), true) ? $studioType : 'analysis');
    }

    private static function layoutFor(string $studioType): array
    {
        $isCommercial = in_array($studioType, ['proposal', 'quote', 'invoice', 'receipt', 'credit_note'], true);
        $title = self::titleFor($studioType);
        $number = self::numberTokenFor($studioType);

        return [
            'first_page_header_html' => ControlledDocument::letterhead($title, self::controlRowsFor($studioType)),
            'default_header_html' => ControlledDocument::runningHeader($title, $number),
            'footer_html' => ControlledDocument::footer($number, self::footerNoticeFor($studioType)),
            'body_html' => self::bodyHtmlFor($studioType),
            'styles_css' => self::stylesCss(),
            'sections' => [
                ['key' => 'identification', 'label' => 'Identificação', 'visible' => true],
                ['key' => $isCommercial ? 'commercial_terms' : 'technical_scope', 'label' => $isCommercial ? 'Condições comerciais' : 'Âmbito técnico', 'visible' => true],
                ['key' => 'validation', 'label' => 'Validação', 'visible' => true],
            ],
            'variable_catalog' => self::variableCatalogFor($studioType),
            'custom_fields' => $studioType === self::CUSTOM ? self::customFields() : [],
            'canvas_blocks' => $studioType === self::CUSTOM ? [] : self::canvasBlocksFor($studioType, self::INK),
            'document_font_family' => 'DejaVu Sans, sans-serif',
            'page_background_color' => '#ffffff',
            'background_image_path' => '',
            'background_size' => 'cover',
            'background_position' => 'center center',
            'background_repeat' => 'no-repeat',
            'table_header_background' => '#f3f4f6',
            'table_header_text_color' => self::INK,
            'table_border_color' => '#d1d5db',
            'table_font_size' => 10,
            'table_cell_padding' => 5,
            'table_summary_background' => '#ffffff',
            'table_summary_text_color' => self::INK,
            'table_summary_muted_color' => '#4b5563',
            'show_canvas_grid' => true,
            'show_canvas_rulers' => true,
            'snap_to_grid' => true,
            'snap_grid_size' => 4,
            'page_safe_area' => true,
        ];
    }

    /**
     * The lines of the document control box, under the title.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private static function controlRowsFor(string $studioType): array
    {
        $number = ['N.º', self::numberTokenFor($studioType)];

        return match ($studioType) {
            'analysis', 'export_certificate', 'import_certificate', self::CUSTOM => [$number, ['Revisão', '{{document_revision}}'], ['Emissão', '{{issue_date}}']],
            'invoice' => [$number, ['Emissão', '{{issue_date}}'], ['Vencimento', '{{due_date}}']],
            'quote' => [$number, ['Emissão', '{{issue_date}}'], ['Validade', '{{expiry_date}}']],
            'proposal' => [$number, ['Emissão', '{{issue_date}}'], ['Validade', '{{expiry_date}}']],
            default => [$number, ['Emissão', '{{issue_date}}']],
        };
    }

    /** The notice printed at the foot of every page. */
    private static function footerNoticeFor(string $studioType): string
    {
        return match ($studioType) {
            'analysis' => 'Este relatório só pode ser reproduzido na íntegra, salvo autorização escrita do laboratório.',
            'export_certificate', 'import_certificate' => 'Este certificado só pode ser reproduzido na íntegra, salvo autorização escrita do laboratório.',
            'invoice', 'receipt', 'credit_note' => '{{hash_excerpt}}{if:agt_validation_number} · Processado por programa validado n.º {{agt_validation_number}}{endif:agt_validation_number}',
            default => '{{lab_name}}',
        };
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
            '{lab_logo}' => 'Logótipo do laboratório',
            '{lab_identity}' => 'Morada, contactos e NIF do laboratório',
            '{document_revision}' => 'Revisão do documento',
            '{verification_qr}' => 'Código QR de verificação',
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
                '{report_notices}' => 'Avisos: relatório não autorizado, alteração',
                '{customer_block}' => 'Secção: cliente',
                '{item_block}' => 'Secção: item ensaiado',
                '{sampling_block}' => 'Secção: amostragem',
                '{dates_block}' => 'Secção: datas',
                '{results_block}' => 'Secção: resultados e notas',
                '{conformity_block}' => 'Secção: declaração de conformidade',
                '{observations_block}' => 'Secção: observações',
                '{statements_block}' => 'Secção: declarações',
                '{authorisation_block}' => 'Secção: autorização',
                '{end_of_report}' => 'Marca de fim do relatório',
                '{certificate_code}' => 'Código do certificado',
                '{sample_entry_code}' => 'Código de entrada da amostra',
                '{lab_code}' => 'Código laboratorial',
                '{warehouse_name}' => 'Local de recepção',
                '{sample_code}' => 'Código da amostra',
                '{sample_name}' => 'Nome da amostra',
                '{sample_type}' => 'Tipo de amostra',
                '{sample_product}' => 'Produto',
                '{sample_matrix}' => 'Matriz',
                '{sample_lot}' => 'Lote',
                '{sample_origin}' => 'Origem',
                '{sampling_plan_ref}' => 'Plano de amostragem',
                '{collection_date}' => 'Data de recolha',
                '{received_at}' => 'Data de recepção',
                '{sample_details}' => 'Tabela de detalhes da amostra',
                '{collection_details}' => 'Recepção e cadeia de custódia',
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
                '{payment_status}' => 'Estado do pagamento',
                '{payment_status_badge}' => 'Painel do estado do pagamento',
                '{paid_date}' => 'Data de pagamento',
                '{payment_method}' => 'Método de pagamento',
                '{amount_due}' => 'Valor pendente',
                '{is_paid}' => 'Factura paga',
                '{is_unpaid}' => 'Factura por pagar',
            ]),
            'receipt' => array_merge($common, self::commercialVariableCatalog(), [
                '{payment_type}' => 'Forma de pagamento',
            ]),
            'credit_note' => array_merge($common, self::commercialVariableCatalog(), [
                '{reason_label}' => 'Motivo da rectificação',
            ]),
            self::CUSTOM => array_merge($common, [
                '{document_title}' => 'Título do documento',
                '{signature_block}' => 'Assinatura (campos «Assinado por» e «Função»)',
                '{end_of_document}' => 'Marca de fim do documento',
            ], collect(self::customFields())->mapWithKeys(fn (array $field): array => ['{'.$field['key'].'}' => $field['label']])->all()),
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

    /**
     * Document-specific additions to the shared controlled-document stylesheet
     * (`PDFs.partials.premium-document-style`), which already carries the layout.
     */
    private static function stylesCss(): string
    {
        return <<<'CSS'
.pdf-document .studio-avoid-break { page-break-inside: avoid; break-inside: avoid; }
CSS;
    }

    private static function bodyHtmlFor(string $studioType): string
    {
        return match ($studioType) {
            'executive' => self::executiveBodyHtml(),
            'proposal' => self::proposalBodyHtml(),
            'export_certificate' => self::exportCertificateBodyHtml(),
            'import_certificate' => self::importCertificateBodyHtml(),
            'quote' => self::commercialBodyHtml('Condições', 'Emissão: {issue_date}<br>Validade: {expiry_date}{if:service_location}<br>Local do serviço: {service_location}{endif:service_location}'),
            'invoice' => '{payment_status_badge}'.self::commercialBodyHtml('Condições', 'Emissão: {issue_date}<br>Vencimento: {due_date}{if:service_location}<br>Local do serviço: {service_location}{endif:service_location}'),
            'receipt' => self::commercialBodyHtml('Recebimento', 'Data: {issue_date}<br>Forma de pagamento: {payment_type}{if:service_location}<br>Local do serviço: {service_location}{endif:service_location}'),
            self::CUSTOM => self::customBodyHtml(),
            'credit_note' => self::commercialBodyHtml('Motivo', '{reason_label}<br>Data: {issue_date}{if:service_location}<br>Local do serviço: {service_location}{endif:service_location}'),
            default => self::analysisBodyHtml(),
        };
    }

    /**
     * A free-form document: who it is addressed to, its subject, its text, who
     * signs it and a marked end. Every part is a field the template may rename,
     * remove or add to.
     */
    private static function customBodyHtml(): string
    {
        return <<<'HTML'
{if:recipient}<p class="doc-text"><strong>Destinatário:</strong> {recipient}</p>{endif:recipient}
{if:document_subject}<p class="doc-text"><strong>Assunto:</strong> {document_subject}</p>{endif:document_subject}
<div class="doc-text">{document_body}</div>
{signature_block}
{end_of_document}
HTML;
    }

    /**
     * The test report, in the order a reader of ISO/IEC 17025 reports expects:
     * who it is for, what was tested, how it was sampled, when, the results and
     * their conformity, the statements, the authorisation and a marked end.
     */
    private static function analysisBodyHtml(): string
    {
        return <<<'HTML'
{report_notices}
{customer_block}
{item_block}
{sampling_block}
{dates_block}
{results_block}
{conformity_block}
{observations_block}
{statements_block}
{authorisation_block}
{end_of_report}
HTML;
    }

    private static function executiveBodyHtml(): string
    {
        return <<<'HTML'
<div class="doc-section">
    <div class="doc-section-title">Síntese do período</div>
    <p class="doc-text">{executive_summary}</p>
</div>
<div class="doc-section">
    <div class="doc-section-title">Indicadores</div>
    {executive_kpis}
</div>
<div class="doc-section">{executive_charts}</div>
<div class="doc-section">
    <div class="doc-section-title">Clientes com maior actividade recente</div>
    {top_customers_table}
</div>
HTML;
    }

    private static function proposalBodyHtml(): string
    {
        return <<<'HTML'
<table class="doc-parties doc-plain"><tr>
    <td class="doc-party"><div class="doc-party-label">Cliente</div><div class="doc-party-lines">{customer_details}</div></td>
    <td class="doc-party"><div class="doc-party-label">Condições da proposta</div><div class="doc-party-lines">Válida até: {expiry_date}{if:service_location}<br>Local do serviço: {service_location}{endif:service_location}</div></td>
</tr></table>
<div class="doc-section">{proposal_content}</div>
<div class="doc-section">
    <div class="doc-section-title">Serviços propostos</div>
    {items_table}
</div>
<table class="doc-split doc-plain"><tr>
    <td class="doc-split-notes">
        <div class="doc-party-label">Dados bancários</div><div class="doc-party-lines">{banking_details}</div>
        {if:observations}<br><div class="doc-party-label">Observações</div><div class="doc-party-lines">{observations}</div>{endif:observations}
    </td>
    <td class="doc-split-totals">{summary_table}</td>
</tr></table>
<table class="doc-parties doc-plain doc-keep"><tr>
    <td class="doc-party">{proposal_acceptance_evidence}</td>
    <td class="doc-party">{proposal_authenticity}</td>
</tr></table>
<div class="doc-section doc-keep">{signature_block}</div>
HTML;
    }

    private static function exportCertificateBodyHtml(): string
    {
        return <<<'HTML'
<table class="doc-parties doc-plain"><tr>
    <td class="doc-party"><div class="doc-party-label">Exportador</div><div class="doc-party-lines">{customer_details}</div></td>
    <td class="doc-party"><div class="doc-party-label">Expedição</div><div class="doc-party-lines">Origem: {origin_city}, {origin_country}<br>Destino: {destination_city}, {destination_country}<br>Transporte: {transport_type}<br>Local e data: {expedition_location} · {expedition_date}</div></td>
</tr></table>
<div class="doc-section">
    <div class="doc-section-title">Produtos certificados</div>
    {products_table}
</div>
{if:remarks}<div class="doc-section"><div class="doc-section-title">Observações</div><div class="doc-text">{remarks}</div></div>{endif:remarks}
<div class="doc-section doc-keep">
    <div class="doc-section-title">Autorização</div>
    <p class="doc-text">Pessoal autorizado: {authorized_personnel}</p>
    <div class="doc-signature">{signature_block}</div>
</div>
<div class="doc-end">*** Fim do certificado ***</div>
HTML;
    }

    private static function importCertificateBodyHtml(): string
    {
        return <<<'HTML'
<table class="doc-parties doc-plain"><tr>
    <td class="doc-party"><div class="doc-party-label">Importador</div><div class="doc-party-lines">{customer_details}</div></td>
    <td class="doc-party"><div class="doc-party-label">Importação</div><div class="doc-party-lines">Exportador: {exporter_name}<br>País de destino: {destination_country}<br>Transporte: {transport_type}<br>Porto de saída: {port_exit}<br>Porto de entrada: {port_entry}</div></td>
</tr></table>
<div class="doc-section">
    <div class="doc-section-title">Produtos certificados</div>
    {items_table}
</div>
{if:remarks}<div class="doc-section"><div class="doc-section-title">Observações</div><div class="doc-text">{remarks}</div></div>{endif:remarks}
<div class="doc-section doc-keep">
    <div class="doc-section-title">Autorização</div>
    <p class="doc-text">Pessoal autorizado: {authorized_personnel}</p>
    <div class="doc-signature">{signature_block}</div>
</div>
<div class="doc-end">*** Fim do certificado ***</div>
HTML;
    }

    /**
     * Quote, invoice, receipt and credit note: who it is for and on what terms,
     * the lines, the totals beside the payment details, and who issued it.
     * The title and number are in the letterhead.
     */
    private static function commercialBodyHtml(string $termsTitle, string $termsBody): string
    {
        return <<<HTML
<table class="doc-parties doc-plain"><tr>
    <td class="doc-party"><div class="doc-party-label">Cliente</div><div class="doc-party-lines">{customer_details}</div></td>
    <td class="doc-party"><div class="doc-party-label">{$termsTitle}</div><div class="doc-party-lines">{$termsBody}</div></td>
</tr></table>
<div class="doc-section">
    <div class="doc-section-title">Descrição</div>
    {items_table}
</div>
<table class="doc-split doc-plain"><tr>
    <td class="doc-split-notes">
        <div class="doc-party-label">Dados bancários</div><div class="doc-party-lines">{banking_details}</div>
        {if:observations}<br><div class="doc-party-label">Observações</div><div class="doc-party-lines">{observations}</div>{endif:observations}
    </td>
    <td class="doc-split-totals">{summary_table}</td>
</tr></table>
<div class="doc-signature doc-keep">{signature_block}</div>
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
            // The letterhead prints the verification code itself; this block stays for layouts that place it elsewhere.
            'is_hidden' => true,
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
            self::CUSTOM => '{{document_title}}',
            default => TestReportContent::TITLE,
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
            'margin_top' => 16,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
            // Room for the letterhead where it is drawn as the first page's header.
            'first_page_margin_top' => 40,
        ];
    }
}
