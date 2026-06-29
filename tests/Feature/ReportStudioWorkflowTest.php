<?php

namespace Tests\Feature;

use App\Models\GestlabMedia;
use App\Models\Invoice;
use App\Models\LabCode;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\ReportStudioTemplate;
use App\Models\Result;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioAssetLibrary;
use App\Support\ReportStudioCssEscaper;
use App\Support\ReportStudioDefaultTemplates;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use HeadlessChromium\BrowserFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionMethod;
use Tests\TestCase;

class ReportStudioWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function captureResponseOutput(callable $callback): array
    {
        ob_start();

        try {
            $response = $callback();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return [$response, (string) $output];
    }

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    public function test_admin_can_view_report_studios_index(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->get(route('report-studios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ReportStudios/Index')
                ->has('systemPresets', count(ReportStudioDefaultTemplates::supportedTypes()))
                ->where('systemPresets.0.source', 'system')
                ->where('systemPresets.0.category', 'analysis')
                ->where('systemPresets.0.name_key', 'gestlab.general.labels.vap_report_studios.presets.names.analysis')
                ->where('systemPresets.0.description_key', 'gestlab.general.labels.vap_report_studios.presets.descriptions.analysis')
                ->where('systemPresets.0.layout_schema.variable_catalog.0.value', '{document_code}')
                ->where('systemPresets.0.layout_schema.canvas_blocks.0.block_kind', 'qr_code')
                ->where('systemPresets.0.layout_schema.canvas_blocks.1.block_kind', 'signature')
                ->where('systemPresets.0.layout_schema.canvas_blocks.2.id', 'analysis-decision-rule-note')
                ->where('systemPresets.0.layout_schema.page_background_color', '#fffdf7')
                ->where('systemPresets.0.layout_schema.body_html', fn (string $bodyHtml): bool => str_contains($bodyHtml, '{results_table}'))
                ->where('systemPresets.0.export_settings.paper_size', 'A4')
            );
    }

    public function test_system_presets_include_typed_variable_catalogs(): void
    {
        $presets = collect(ReportStudioDefaultTemplates::presets())->keyBy('category');

        $analysisVariables = collect(data_get($presets->get('analysis'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $executiveVariables = collect(data_get($presets->get('executive'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $proposalVariables = collect(data_get($presets->get('proposal'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $exportVariables = collect(data_get($presets->get('export_certificate'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $importVariables = collect(data_get($presets->get('import_certificate'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $invoiceVariables = collect(data_get($presets->get('invoice'), 'layout_schema.variable_catalog'))->pluck('value')->all();

        $this->assertContains('{results_table}', $analysisVariables);
        $this->assertContains('{uncertainty_statement}', $analysisVariables);
        $this->assertContains('{sample_entry_code}', $analysisVariables);
        $this->assertContains('{analysis_chart_card}', $analysisVariables);
        $this->assertContains('{analysis_chart_values}', $analysisVariables);
        $this->assertContains('{brand_primary_color}', $analysisVariables);
        $this->assertContains('{brand_secondary_color}', $analysisVariables);
        $this->assertContains('{brand_accent_color}', $analysisVariables);
        $this->assertContains('{executive_chart_values}', $executiveVariables);
        $this->assertContains('{brand_primary_color}', $executiveVariables);
        $this->assertContains('{banking_details}', $proposalVariables);
        $this->assertContains('{brand_primary_color}', $proposalVariables);
        $this->assertContains('{bank_account_name}', $proposalVariables);
        $this->assertContains('{bank_account_number}', $proposalVariables);
        $this->assertContains('{bank_swift}', $proposalVariables);
        $this->assertContains('{proposal_content}', $proposalVariables);
        $this->assertContains('{products_table}', $exportVariables);
        $this->assertContains('{items_table}', $importVariables);
        $this->assertContains('{due_date}', $invoiceVariables);
        $this->assertContains('{unique_hash}', $invoiceVariables);
        $this->assertContains('{hash_excerpt}', $invoiceVariables);
        $this->assertContains('{record_verification_payload}', $invoiceVariables);
        $this->assertContains('{record_verification_evidence}', $invoiceVariables);
        $this->assertContains('{agt_validation_number}', $invoiceVariables);
        $this->assertContains('{bank_iban}', $invoiceVariables);
        $this->assertContains('{bank_details}', $invoiceVariables);
    }

    public function test_system_presets_include_production_canvas_blocks(): void
    {
        $presets = collect(ReportStudioDefaultTemplates::presets())->keyBy('category');

        $analysisBlocks = collect(data_get($presets->get('analysis'), 'layout_schema.canvas_blocks'))->pluck('id')->all();
        $executiveBlocks = collect(data_get($presets->get('executive'), 'layout_schema.canvas_blocks'))->pluck('id')->all();
        $proposalBlocks = collect(data_get($presets->get('proposal'), 'layout_schema.canvas_blocks'))->pluck('id')->all();
        $invoiceBlocks = collect(data_get($presets->get('invoice'), 'layout_schema.canvas_blocks'))->pluck('id')->all();

        $this->assertContains('analysis-signature-block', $analysisBlocks);
        $this->assertContains('analysis-decision-rule-note', $analysisBlocks);
        $this->assertContains('analysis-results-chart', $analysisBlocks);
        $this->assertContains('executive-studio-chart', $executiveBlocks);
        $this->assertContains('proposal-banking-details', $proposalBlocks);
        $this->assertContains('proposal-client-acceptance', $proposalBlocks);
        $this->assertContains('invoice-banking-details', $invoiceBlocks);
        $this->assertSame('{{record_verification_payload}}', data_get($presets->get('invoice'), 'layout_schema.canvas_blocks.0.qr_content'));
        $this->assertFalse((bool) data_get($presets->get('invoice'), 'layout_schema.canvas_blocks.0.is_hidden'));
        $this->assertSame('chart_snapshot', data_get($presets->get('executive'), 'layout_schema.canvas_blocks.2.block_kind'));
        $this->assertSame('{executive_chart_values}', data_get($presets->get('executive'), 'layout_schema.canvas_blocks.2.chart_values'));
        $this->assertSame('chart_snapshot', data_get($presets->get('analysis'), 'layout_schema.canvas_blocks.3.block_kind'));
        $this->assertSame('{analysis_chart_values}', data_get($presets->get('analysis'), 'layout_schema.canvas_blocks.3.chart_values'));
        $this->assertSame('signature', data_get($presets->get('proposal'), 'layout_schema.canvas_blocks.3.block_kind'));
        $this->assertTrue((bool) collect(data_get($presets->get('analysis'), 'layout_schema.canvas_blocks'))->firstWhere('id', 'analysis-results-chart')['is_hidden']);
        $this->assertTrue((bool) collect(data_get($presets->get('executive'), 'layout_schema.canvas_blocks'))->firstWhere('id', 'executive-studio-chart')['is_hidden']);
        $this->assertTrue((bool) collect(data_get($presets->get('invoice'), 'layout_schema.canvas_blocks'))->firstWhere('id', 'invoice-banking-details')['is_hidden']);
        $this->assertSame('#fffdf7', data_get($presets->get('analysis'), 'layout_schema.page_background_color'));
        $this->assertSame('#fffdf7', data_get($presets->get('invoice'), 'layout_schema.page_background_color'));
    }

    public function test_system_presets_include_backend_body_templates_for_generated_documents(): void
    {
        $presets = collect(ReportStudioDefaultTemplates::presets())->keyBy('category');

        $this->assertStringContainsString('{results_table}', data_get($presets->get('analysis'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{analysis_chart_card}', data_get($presets->get('analysis'), 'layout_schema.body_html'));
        $this->assertStringContainsString('document-summary-table', data_get($presets->get('analysis'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{executive_charts}', data_get($presets->get('executive'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{banking_details}', data_get($presets->get('proposal'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{proposal_content}', data_get($presets->get('proposal'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{items_table}', data_get($presets->get('proposal'), 'layout_schema.body_html'));
        $this->assertStringContainsString('document-summary-cell', data_get($presets->get('proposal'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{products_table}', data_get($presets->get('export_certificate'), 'layout_schema.body_html'));
        $this->assertStringContainsString('document-summary-table', data_get($presets->get('export_certificate'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{items_table}', data_get($presets->get('import_certificate'), 'layout_schema.body_html'));
        $this->assertStringContainsString('document-summary-table', data_get($presets->get('import_certificate'), 'layout_schema.body_html'));
        $this->assertStringContainsString('Proforma {quote_number}', data_get($presets->get('quote'), 'layout_schema.body_html'));
        $this->assertStringContainsString('document-summary-table', data_get($presets->get('quote'), 'layout_schema.body_html'));
        $this->assertStringContainsString('Factura {document_number}', data_get($presets->get('invoice'), 'layout_schema.body_html'));
        $this->assertStringContainsString('Recibo {document_number}', data_get($presets->get('receipt'), 'layout_schema.body_html'));
        $this->assertStringContainsString('Nota de crédito {document_number}', data_get($presets->get('credit_note'), 'layout_schema.body_html'));
        $this->assertStringNotContainsString('border:1px solid #cbd5e1', json_encode($presets->values()->all(), JSON_THROW_ON_ERROR));
    }

    public function test_studio_preview_payloads_use_production_language_instead_of_demo_names(): void
    {
        $settings = app(GeneralSettings::class);
        $builder = app(ReportStudioPdfBuilder::class);
        $studioFor = fn (string $type): ReportStudioTemplate => new ReportStudioTemplate([
            'name' => 'Preview '.$type,
            'studio_type' => $type,
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payloads = [
            $builder->buildAnalysisStudioPreviewPayload($studioFor('analysis'), $settings),
            $builder->buildProposalStudioPreviewPayload($studioFor('proposal'), $settings),
            $builder->buildExportCertificatePreviewPayload($studioFor('export_certificate'), $settings),
            $builder->buildImportCertificatePreviewPayload($studioFor('import_certificate'), $settings),
            $builder->buildQuotePreviewPayload($studioFor('quote'), $settings),
            $builder->buildInvoicePreviewPayload($studioFor('invoice'), $settings),
            $builder->buildReceiptPreviewPayload($studioFor('receipt'), $settings),
            $builder->buildCreditNotePreviewPayload($studioFor('credit_note'), $settings),
        ];

        $serializedPayloads = json_encode($payloads, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('Cliente Exemplo', $serializedPayloads);
        $this->assertStringNotContainsString('Exportadora Exemplo', $serializedPayloads);
        $this->assertStringNotContainsString('Importadora Exemplo', $serializedPayloads);
        $this->assertStringContainsString('Cliente industrial de referência', $serializedPayloads);
        $this->assertStringContainsString('Exportador alimentar certificado', $serializedPayloads);
        $this->assertStringContainsString('Importador alimentar certificado', $serializedPayloads);
    }

    public function test_report_studio_templates_resolve_conditional_blocks_in_body_and_canvas_layers(): void
    {
        $studio = new ReportStudioTemplate([
            'name' => 'Conditional Analysis Studio',
            'studio_type' => 'analysis',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => <<<'HTML'
<section>
    {if:uncertainty_statement}<strong class="uncertainty-visible">Incerteza disponível</strong>{endif:uncertainty_statement}
    {if:missing_value}<span class="missing-body">Não deve aparecer</span>{endif:missing_value}
    {{ifnot:missing_value}}<span class="unless-visible">Secção alternativa</span>{{endif:missing_value}}
</section>
HTML,
                'canvas_blocks' => [
                    [
                        'id' => 'conditional-canvas-note',
                        'surface' => 'content',
                        'block_kind' => 'rich_text',
                        'content_html' => '{if:customer_name}<span class="canvas-visible">{{customer_name}}</span>{endif:customer_name}{if:missing_value}<span class="missing-canvas">Oculto</span>{endif:missing_value}',
                        'x' => 5,
                        'y' => 5,
                        'width' => 40,
                        'min_height' => 24,
                        'z_index' => 20,
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildAnalysisStudioPreviewPayload($studio, app(GeneralSettings::class));
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('uncertainty-visible', $bodyHtml);
        $this->assertStringContainsString('unless-visible', $bodyHtml);
        $this->assertStringContainsString('canvas-visible', $bodyHtml);
        $this->assertStringNotContainsString('{{customer_name}}', $bodyHtml);
        $this->assertStringNotContainsString('missing-body', $bodyHtml);
        $this->assertStringNotContainsString('missing-canvas', $bodyHtml);
        $this->assertStringNotContainsString('{if:', $bodyHtml);
        $this->assertStringNotContainsString('{{ifnot:', $bodyHtml);
    }

    public function test_admin_can_create_and_update_report_studio_templates(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('report-studios.store'), [
                'name' => 'Executive Board Pack',
                'studio_type' => 'executive',
                'renderer' => 'canva',
                'status' => 'active',
                'is_default' => true,
                'theme_preset' => 'executive',
                'canva_design_url' => 'https://www.canva.com/design/test-report-studio',
                'description' => 'Premium board reporting template.',
                'layout_schema' => [
                    'first_page_header_html' => '<div>Board Report Cover</div>',
                    'default_header_html' => '<div>Board Report Header</div>',
                    'footer_html' => '<div>Page {PAGENO}/{nbpg}</div>',
                    'body_html' => '<h1>Resumo Executivo</h1><p>KPIs e contexto.</p>',
                    'styles_css' => 'body{color:#0f172a;}',
                    'document_font_family' => 'Manrope, DejaVu Sans, sans-serif',
                    'page_background_color' => '#fff8e7',
                    'show_canvas_grid' => false,
                    'show_canvas_rulers' => false,
                    'snap_to_grid' => false,
                    'snap_grid_size' => 8,
                    'page_safe_area' => false,
                    'sections' => [
                        ['key' => 'kpis', 'visible' => true],
                        ['key' => 'customers', 'visible' => true],
                    ],
                ],
                'export_settings' => ['format' => 'pdf'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $template = ReportStudioTemplate::query()->where('name', 'Executive Board Pack')->first();

        $this->assertNotNull($template);
        $this->assertTrue($template->is_default);
        $this->assertSame('executive', $template->studio_type);
        $this->assertSame('Manrope, DejaVu Sans, sans-serif', data_get($template->layout_schema, 'document_font_family'));
        $this->assertSame('#fff8e7', data_get($template->layout_schema, 'page_background_color'));
        $this->assertFalse((bool) data_get($template->layout_schema, 'show_canvas_grid'));
        $this->assertFalse((bool) data_get($template->layout_schema, 'show_canvas_rulers'));
        $this->assertFalse((bool) data_get($template->layout_schema, 'snap_to_grid'));
        $this->assertSame(8, data_get($template->layout_schema, 'snap_grid_size'));
        $this->assertFalse((bool) data_get($template->layout_schema, 'page_safe_area'));

        $this->actingAs($user)
            ->put(route('report-studios.update', $template), [
                'name' => 'Executive Board Pack v2',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'active',
                'is_default' => true,
                'theme_preset' => 'corporate',
                'canva_design_url' => null,
                'description' => 'Updated executive reporting template.',
                'layout_schema' => [
                    'first_page_header_html' => '<div>Board Report v2</div>',
                    'default_header_html' => '<div>Board Header v2</div>',
                    'footer_html' => '<div>Page {PAGENO}/{nbpg}</div>',
                    'body_html' => '<h1>Resumo Executivo</h1><p>Recebíveis e fornecedores.</p>',
                    'styles_css' => 'body{color:#111827;}',
                    'page_background_color' => 'rgb(255 253 247)',
                    'show_canvas_grid' => true,
                    'show_canvas_rulers' => true,
                    'snap_to_grid' => true,
                    'snap_grid_size' => 12,
                    'page_safe_area' => true,
                    'sections' => [
                        ['key' => 'kpis', 'visible' => true],
                        ['key' => 'receivables', 'visible' => true],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4', 'orientation' => 'L'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('report_studio_templates', [
            'id' => $template->id,
            'name' => 'Executive Board Pack v2',
            'renderer' => 'internal',
            'theme_preset' => 'corporate',
        ]);

        $template->refresh();

        $this->assertSame('rgb(255 253 247)', data_get($template->layout_schema, 'page_background_color'));
        $this->assertTrue((bool) data_get($template->layout_schema, 'show_canvas_grid'));
        $this->assertTrue((bool) data_get($template->layout_schema, 'show_canvas_rulers'));
        $this->assertTrue((bool) data_get($template->layout_schema, 'snap_to_grid'));
        $this->assertSame(12, data_get($template->layout_schema, 'snap_grid_size'));
        $this->assertTrue((bool) data_get($template->layout_schema, 'page_safe_area'));
    }

    public function test_report_studio_rejects_unsupported_export_page_settings(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Invalid Export Settings Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'draft',
                'is_default' => false,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                ],
                'export_settings' => [
                    'paper_size' => 'Tabloid',
                    'orientation' => 'Landscape',
                ],
            ]);

        $response->assertSessionHasErrors([
            'export_settings.paper_size',
            'export_settings.orientation',
        ]);
    }

    public function test_report_studio_requires_dimensions_for_custom_export_page_size(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Incomplete Custom Export Size Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'draft',
                'is_default' => false,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                ],
                'export_settings' => [
                    'paper_size' => 'custom',
                    'orientation' => 'P',
                    'custom_page_width' => 210,
                ],
            ]);

        $response->assertSessionHasErrors([
            'export_settings.custom_page_height',
        ]);
    }

    public function test_pdf_payload_generates_document_and_table_control_styles_from_layout_settings(): void
    {
        $studio = new ReportStudioTemplate([
            'name' => 'Controlled Style Studio',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<h1>Resumo Executivo</h1><table><thead><tr><th>Indicador</th></tr></thead><tbody><tr><td>Valor</td></tr></tbody></table>',
                'document_font_family' => 'Manrope, DejaVu Sans, sans-serif',
                'table_header_background' => '#123456',
                'table_header_text_color' => '#f8fafc',
                'table_border_color' => '#94a3b8',
                'table_font_size' => 12,
                'table_cell_padding' => 10,
                'table_summary_background' => '#f8fafc',
                'table_summary_text_color' => '#0f172a',
                'table_summary_muted_color' => '#475569',
                'styles_css' => <<<'CSS'
/* studio-table-controls:start */
th{background:#ff0000;}
/* studio-table-controls:end */
.custom-document-note{color:#334155;}
CSS,
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload(
            ['kpis' => []],
            app(GeneralSettings::class),
            $studio
        );

        $styles = (string) data_get($payload, 'data.styles');

        $this->assertStringContainsString('font-family:Manrope, DejaVu Sans, sans-serif !important', $styles);
        $this->assertStringContainsString('background:#123456 !important', $styles);
        $this->assertStringContainsString('color:#f8fafc !important', $styles);
        $this->assertStringContainsString('border:1px solid #94a3b8 !important', $styles);
        $this->assertStringContainsString('font-size:12px !important', $styles);
        $this->assertStringContainsString('padding:10px !important', $styles);
        $this->assertStringContainsString('.pdf-document table:not(.document-summary-table){border-collapse:collapse;}', $styles);
        $this->assertStringContainsString('.pdf-document .document-summary-table{border-collapse:separate !important;border-spacing:0 8px !important;}', $styles);
        $this->assertStringContainsString('.pdf-document .document-summary-cell{background:#f8fafc !important;border:1px solid #94a3b8 !important;border-radius:18px !important;padding:14px !important;vertical-align:top;}', $styles);
        $this->assertStringContainsString('.pdf-document .document-summary-cell .value{display:block !important;color:#0f172a !important;', $styles);
        $this->assertStringContainsString('.pdf-document .document-summary-cell .muted{display:block !important;color:#475569 !important;', $styles);
        $this->assertStringContainsString('.pdf-document .document-financial-summary td{color:#0f172a;}', $styles);
        $this->assertStringContainsString('.custom-document-note{color:#334155;}', $styles);
        $this->assertStringNotContainsString('#ff0000', $styles);
    }

    public function test_executive_report_default_payload_renders_pdf_charts(): void
    {
        $studio = new ReportStudioTemplate([
            'name' => 'Executive Chart Studio',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload(
            [
                'executive_summary' => 'Leitura executiva para decisão técnica.',
                'kpis' => [
                    ['label' => 'Amostras activas', 'value' => 14, 'hint' => 'Fluxo em curso'],
                ],
                'charts' => [
                    'throughput' => [
                        'title' => 'Capacidade técnica por etapa',
                        'labels' => ['Recepção', 'Ensaio', 'Emissão'],
                        'series' => [12, 9, 7],
                    ],
                    'quality_pressure' => [
                        'title' => 'Pressão de qualidade',
                        'labels' => ['No prazo', 'Atenção'],
                        'series' => [80, 20],
                    ],
                ],
                'top_customers' => [],
            ],
            app(GeneralSettings::class),
            $studio
        );

        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('report-chart-svg', $bodyHtml);
        $this->assertStringContainsString('<svg', $bodyHtml);
        $this->assertStringContainsString('Capacidade técnica por etapa', $bodyHtml);
        $this->assertStringContainsString('Pressão de qualidade', $bodyHtml);
    }

    public function test_chart_canvas_blocks_can_bind_to_executive_payload_series(): void
    {
        $studio = new ReportStudioTemplate([
            'name' => 'Payload Bound Chart Studio',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<section>Resumo executivo operacional</section>',
                'canvas_blocks' => [
                    [
                        'id' => 'payload-bound-chart',
                        'surface' => 'content',
                        'page_scope' => 'first',
                        'block_kind' => 'chart_snapshot',
                        'chart_title' => '{executive_chart_title}',
                        'chart_caption' => '{executive_chart_caption}',
                        'chart_type' => 'bar',
                        'chart_labels' => '{executive_chart_labels}',
                        'chart_values' => '{executive_chart_values}',
                        'chart_colors' => '#143d37, #d9b05f',
                        'width' => 60,
                        'min_height' => 220,
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload(
            [
                'charts' => [
                    'throughput' => [
                        'title' => 'Ciclo técnico validado',
                        'labels' => ['Recepção premium', 'Emissão final'],
                        'series' => [31, 17],
                    ],
                ],
                'kpis' => [],
                'top_customers' => [],
            ],
            app(GeneralSettings::class),
            $studio
        );

        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('data-chart-type="bar"', $bodyHtml);
        $this->assertStringContainsString('Ciclo técnico validado', $bodyHtml);
        $this->assertStringContainsString('Recepção premium', $bodyHtml);
        $this->assertStringContainsString('Emissão final', $bodyHtml);
        $this->assertStringContainsString('>31</text>', $bodyHtml);
        $this->assertStringContainsString('>17</text>', $bodyHtml);
        $this->assertStringContainsString('Dados provenientes da série executiva do período seleccionado.', $bodyHtml);
    }

    public function test_chart_canvas_blocks_use_brand_palette_when_colors_are_not_configured(): void
    {
        $studio = new ReportStudioTemplate([
            'name' => 'Brand Palette Chart Studio',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<section>Resumo executivo operacional</section>',
                'canvas_blocks' => [
                    [
                        'id' => 'brand-palette-chart',
                        'surface' => 'content',
                        'page_scope' => 'first',
                        'block_kind' => 'chart_snapshot',
                        'chart_title' => 'Distribuição operacional',
                        'chart_type' => 'bar',
                        'chart_labels' => 'Recepção, Triagem, Ensaios, Verificação, Aprovação, Emissão',
                        'chart_values' => '10, 9, 8, 7, 6, 5',
                        'width' => 60,
                        'min_height' => 220,
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload(
            ['kpis' => [], 'top_customers' => []],
            app(GeneralSettings::class),
            $studio
        );

        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('data-chart-type="bar"', $bodyHtml);
        $this->assertStringContainsString('#3f6f58', $bodyHtml);
        $this->assertStringNotContainsString('#2563eb', $bodyHtml);
    }

    public function test_builder_uses_backend_default_studio_when_no_database_template_exists(): void
    {
        ReportStudioTemplate::query()->delete();

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload(
            [
                'executive_summary' => 'Leitura executiva para decisão técnica.',
                'kpis' => [],
                'charts' => [],
                'top_customers' => [],
            ],
            app(GeneralSettings::class)
        );

        $this->assertSame('Resumo executivo padrão', data_get($payload, 'data.documentTitle'));
        $this->assertStringContainsString('Resumo executivo', (string) data_get($payload, 'data.firstPageHeader'));
        $this->assertStringContainsString('QR code', (string) data_get($payload, 'data.firstPageHeader'));
        $this->assertStringContainsString('.document-hero', (string) data_get($payload, 'data.styles'));
        $this->assertNull(ReportStudioTemplate::resolveDefaultFor('executive'));
    }

    public function test_generated_documents_ignore_active_non_default_report_studio_templates(): void
    {
        ReportStudioTemplate::query()
            ->where('studio_type', 'invoice')
            ->update([
                'status' => 'draft',
                'is_default' => false,
            ]);

        ReportStudioTemplate::query()->create([
            'name' => 'Active Non Default Invoice Template',
            'studio_type' => 'invoice',
            'renderer' => 'chrome',
            'status' => 'active',
            'is_default' => false,
            'layout_schema' => [
                'body_html' => '<section class="wrong-template">{report_title}{results_table}</section>',
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $invoice = Invoice::query()->firstOrFail();
        $payload = app(ReportStudioPdfBuilder::class)->buildInvoicePayload(
            $invoice,
            app(GeneralSettings::class)
        );

        $this->assertNull(ReportStudioTemplate::resolveDefaultFor('invoice'));
        $this->assertSame('Factura fiscal padrão', data_get($payload, 'data.documentTitle'));
        $this->assertStringContainsString('Factura '.(string) $invoice->inv_no, (string) data_get($payload, 'data.bodyHtml'));
        $this->assertStringNotContainsString('wrong-template', (string) data_get($payload, 'data.bodyHtml'));
        $this->assertStringNotContainsString('{report_title}', (string) data_get($payload, 'data.bodyHtml'));
        $this->assertStringNotContainsString('{results_table}', (string) data_get($payload, 'data.bodyHtml'));
    }

    public function test_commercial_canvas_blocks_resolve_banking_details_in_generated_payloads(): void
    {
        $quote = Quote::query()->firstOrFail();
        $settings = app(GeneralSettings::class);
        $original = [
            'app_bank_name' => $settings->app_bank_name,
            'app_bank_account_name' => $settings->app_bank_account_name,
            'app_bank_account_number' => $settings->app_bank_account_number,
            'app_bank_iban' => $settings->app_bank_iban,
            'app_bank_swift' => $settings->app_bank_swift,
            'app_bank_details' => $settings->app_bank_details,
        ];
        $studio = new ReportStudioTemplate([
            'name' => 'Commercial Banking Canvas',
            'studio_type' => 'quote',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<p>{document_number}</p>',
                'canvas_blocks' => [
                    [
                        'id' => 'banking-canvas-block',
                        'title' => 'Dados bancários',
                        'block_kind' => 'rich_text',
                        'surface' => 'content',
                        'page_scope' => 'first',
                        'x' => 0,
                        'y' => 0,
                        'width' => 60,
                        'min_height' => 120,
                        'z_index' => 10,
                        'padding' => 12,
                        'content_html' => '<div class="banking-marker">{banking_details}<p>{{bank_name}}</p><p>{bank_account_name}</p><p>{bank_account_number}</p><p>{bank_iban}</p><p>{bank_swift}</p><p>{bank_details}</p></div>',
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        try {
            $settings->fill([
                'app_bank_name' => 'Banco Comercial de Teste',
                'app_bank_account_name' => 'Laboratório Comercial de Teste',
                'app_bank_account_number' => '0099887766',
                'app_bank_iban' => 'AO06000000000000998877660',
                'app_bank_swift' => 'TESTAOLU',
                'app_bank_details' => 'Transferência com referência da proforma.',
            ]);
            $settings->save();

            $payload = app(ReportStudioPdfBuilder::class)->buildQuotePayload(
                $quote,
                $settings,
                $studio
            );
            $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

            $this->assertStringContainsString('banking-marker', $bodyHtml);
            $this->assertStringContainsString('Banco:', $bodyHtml);
            $this->assertStringContainsString('Banco Comercial de Teste', $bodyHtml);
            $this->assertStringContainsString('Laboratório Comercial de Teste', $bodyHtml);
            $this->assertStringContainsString('0099887766', $bodyHtml);
            $this->assertStringContainsString('AO06000000000000998877660', $bodyHtml);
            $this->assertStringContainsString('TESTAOLU', $bodyHtml);
            $this->assertStringContainsString('Transferência com referência da proforma.', $bodyHtml);
            $this->assertStringNotContainsString('{banking_details}', $bodyHtml);
            $this->assertStringNotContainsString('{{bank_name}}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_account_name}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_account_number}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_iban}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_swift}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_details}', $bodyHtml);
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_quote_preview_canvas_blocks_resolve_full_commercial_context(): void
    {
        $settings = app(GeneralSettings::class);
        $original = [
            'app_bank_name' => $settings->app_bank_name,
            'app_bank_account_name' => $settings->app_bank_account_name,
            'app_bank_account_number' => $settings->app_bank_account_number,
            'app_bank_iban' => $settings->app_bank_iban,
            'app_bank_swift' => $settings->app_bank_swift,
            'app_bank_details' => $settings->app_bank_details,
        ];
        $studio = new ReportStudioTemplate([
            'name' => 'Quote Preview Context',
            'studio_type' => 'quote',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'first_page_header_html' => '<div class="quote-preview-header">{{document_code}} · {customer_name}</div>',
                'footer_html' => '<div class="quote-preview-footer">{document_keywords}</div>',
                'body_html' => '<section>{quote_number}</section>',
                'canvas_blocks' => [
                    [
                        'id' => 'quote-preview-header-bank',
                        'title' => 'Banco no cabeçalho',
                        'block_kind' => 'rich_text',
                        'surface' => 'first_page_header_html',
                        'x' => 0,
                        'y' => 0,
                        'width' => 60,
                        'min_height' => 40,
                        'z_index' => 10,
                        'padding' => 8,
                        'content_html' => '<div class="header-bank">{{bank_name}} · {bank_iban}</div>',
                    ],
                    [
                        'id' => 'quote-preview-body-bank',
                        'title' => 'Banco no corpo',
                        'block_kind' => 'rich_text',
                        'surface' => 'content',
                        'page_scope' => 'first',
                        'x' => 0,
                        'y' => 0,
                        'width' => 60,
                        'min_height' => 80,
                        'z_index' => 10,
                        'padding' => 8,
                        'content_html' => '<div class="body-bank">{banking_details}<p>{bank_account_name}</p><p>{bank_account_number}</p><p>{bank_swift}</p><p>{bank_details}</p></div>',
                    ],
                    [
                        'id' => 'quote-preview-footer-bank',
                        'title' => 'Banco no rodapé',
                        'block_kind' => 'rich_text',
                        'surface' => 'footer_html',
                        'x' => 0,
                        'y' => 0,
                        'width' => 60,
                        'min_height' => 40,
                        'z_index' => 10,
                        'padding' => 8,
                        'content_html' => '<div class="footer-bank">{document_number} · {{bank_account_number}}</div>',
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        try {
            $settings->fill([
                'app_bank_name' => 'Banco de Pré-visualização',
                'app_bank_account_name' => 'Laboratório Pré-visualização',
                'app_bank_account_number' => '0011223344',
                'app_bank_iban' => 'AO06000000000000112233440',
                'app_bank_swift' => 'PREVAOLU',
                'app_bank_details' => 'Referenciar sempre o número da proforma.',
            ]);
            $settings->save();

            $payload = app(ReportStudioPdfBuilder::class)->buildQuotePreviewPayload($studio, $settings);
            $combinedHtml = implode("\n", [
                (string) data_get($payload, 'data.firstPageHeader'),
                (string) data_get($payload, 'data.bodyHtml'),
                (string) data_get($payload, 'data.footerHtml'),
            ]);

            $this->assertStringContainsString('quote-preview-header', $combinedHtml);
            $this->assertStringContainsString('header-bank', $combinedHtml);
            $this->assertStringContainsString('body-bank', $combinedHtml);
            $this->assertStringContainsString('footer-bank', $combinedHtml);
            $this->assertStringContainsString('Banco de Pré-visualização', $combinedHtml);
            $this->assertStringContainsString('Laboratório Pré-visualização', $combinedHtml);
            $this->assertStringContainsString('0011223344', $combinedHtml);
            $this->assertStringContainsString('AO06000000000000112233440', $combinedHtml);
            $this->assertStringContainsString('PREVAOLU', $combinedHtml);
            $this->assertStringContainsString('Referenciar sempre o número da proforma.', $combinedHtml);
            $this->assertStringContainsString('PP 05/2026/0048', $combinedHtml);
            $this->assertStringContainsString('Palavras-chave / Keywords:', $combinedHtml);
            $this->assertStringNotContainsString('{banking_details}', $combinedHtml);
            $this->assertStringNotContainsString('{{bank_name}}', $combinedHtml);
            $this->assertStringNotContainsString('{bank_account_name}', $combinedHtml);
            $this->assertStringNotContainsString('{{bank_account_number}}', $combinedHtml);
            $this->assertStringNotContainsString('{document_number}', $combinedHtml);
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_commercial_fallback_payload_uses_premium_document_table_system(): void
    {
        $quote = Quote::query()->firstOrFail();
        $studio = new ReportStudioTemplate([
            'name' => 'Commercial Fallback Polish',
            'studio_type' => 'quote',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildQuotePayload(
            $quote,
            app(GeneralSettings::class),
            $studio
        );

        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('document-summary-table', $bodyHtml);
        $this->assertStringContainsString('document-summary-cell', $bodyHtml);
        $this->assertStringContainsString('class="report-table studio-avoid-break"', $bodyHtml);
        $this->assertStringContainsString('document-financial-summary', $bodyHtml);
        $this->assertStringContainsString('Pagamento / Banking', $bodyHtml);
        $this->assertStringNotContainsString('border:1px solid #cbd5e1', $bodyHtml);
        $this->assertStringNotContainsString('border-bottom:1px solid #cbd5e1', $bodyHtml);
        $this->assertStringNotContainsString('{items_table}', $bodyHtml);
        $this->assertStringNotContainsString('{summary_table}', $bodyHtml);
    }

    public function test_commercial_payloads_include_signed_record_verification_evidence(): void
    {
        $settings = app(GeneralSettings::class);
        $originalValidationNumber = $settings->app_agt_validation_number;
        $signature = '0123456789ABCDEFGHIJKLMNOPQRSTUV';
        $customer = (object) [
            'name' => 'Cliente fiscal de teste',
            'address' => 'Rua da Evidência, Luanda',
            'nif' => '5000000000',
        ];
        $warehouse = (object) [
            'name' => 'Unidade Financeira',
            'address' => 'Luanda',
        ];
        $issuer = (object) [
            'name' => 'Direcção Financeira',
        ];

        $studioFor = fn (string $type): ReportStudioTemplate => new ReportStudioTemplate([
            'name' => 'Signed '.$type.' studio',
            'studio_type' => $type,
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'first_page_header_html' => '<div class="verification-header">{record_verification_payload}</div>',
                'body_html' => '<section class="'.$type.'-verification">{document_number}{record_verification_evidence}{unique_hash}{hash_excerpt}{agt_validation_number}</section>',
                'footer_html' => '<div class="'.$type.'-footer">{document_number}</div>',
                'canvas_blocks' => [],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $quote = Quote::query()->firstOrFail();
        $quote->forceFill([
            'quote_no' => 'PP 06/2026/0048',
            'date' => now(),
            'due_date' => now()->addDays(15),
            'unique_hash' => $signature,
        ]);
        $quote->setRelation('items', collect());
        $quote->setRelation('customer', $customer);
        $quote->setRelation('warehouse', $warehouse);
        $quote->setRelation('user', $issuer);

        $invoice = new Invoice([
            'inv_no' => 'FT 06/2026/0091',
            'date' => now(),
            'due_date' => now()->addDays(30),
            'sub_total' => 1000,
            'tax' => 140,
            'total' => 1140,
            'unique_hash' => $signature,
        ]);
        $invoice->setRelation('items', collect());
        $invoice->setRelation('customer', $customer);
        $invoice->setRelation('warehouse', $warehouse);
        $invoice->setRelation('user', $issuer);

        $receipt = new Receipt([
            'rec_no' => 'RG 06/2026/0021',
            'date' => now(),
            'unique_hash' => $signature,
        ]);
        $receipt->setRelation('items', collect());
        $receipt->setRelation('customer', $customer);
        $receipt->setRelation('warehouse', $warehouse);
        $receipt->setRelation('user', $issuer);
        $receipt->setRelation('type', null);

        try {
            $settings->fill([
                'app_agt_validation_number' => 'AGT-2026-TEST',
            ]);
            $settings->save();

            $builder = app(ReportStudioPdfBuilder::class);
            $payloads = [
                'quote' => $builder->buildQuotePayload($quote, $settings, $studioFor('quote')),
                'invoice' => $builder->buildInvoicePayload($invoice, $settings, $studioFor('invoice')),
                'receipt' => $builder->buildReceiptPayload($receipt, $settings, $studioFor('receipt')),
            ];

            foreach ($payloads as $type => $payload) {
                $bodyHtml = (string) data_get($payload, 'data.bodyHtml');
                $footerHtml = (string) data_get($payload, 'data.footerHtml');
                $combinedHtml = implode("\n", [
                    (string) data_get($payload, 'data.firstPageHeader'),
                    $bodyHtml,
                    $footerHtml,
                ]);

                $this->assertStringContainsString($type.'-verification', $combinedHtml);
                $this->assertStringContainsString('commercial-record-evidence', $bodyHtml);
                $this->assertStringNotContainsString('commercial-record-evidence', $footerHtml);
                $this->assertStringContainsString('commercial-record-evidence', $combinedHtml);
                $this->assertStringContainsString('Documento:', $combinedHtml);
                $this->assertStringContainsString('Extracto 0AKU', $combinedHtml);
                $this->assertStringContainsString($signature, $combinedHtml);
                $this->assertStringContainsString('Processado por programa validado N. AGT-2026-TEST', $combinedHtml);
                $this->assertStringNotContainsString('{record_verification_payload}', $combinedHtml);
                $this->assertStringNotContainsString('{record_verification_evidence}', $combinedHtml);
                $this->assertStringNotContainsString('{unique_hash}', $combinedHtml);
                $this->assertStringNotContainsString('{hash_excerpt}', $combinedHtml);
            }
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill([
                'app_agt_validation_number' => $originalValidationNumber,
            ]);
            $settings->save();
        }
    }

    public function test_mpdf_studio_document_preserves_data_uri_backgrounds(): void
    {
        $backgroundDataUri = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"></svg>');

        $html = view('PDFs.studios.document', [
            'documentTitle' => 'Preview',
            'firstPageHeader' => '<div>Header</div>',
            'defaultHeader' => '<div>Header</div>',
            'footerHtml' => '<div>Footer</div>',
            'bodyHtml' => '<p>Body</p>',
            'styles' => '',
            'backgroundImage' => $backgroundDataUri,
            'backgroundSize' => 'contain',
            'backgroundPosition' => 'center center',
            'backgroundRepeat' => 'no-repeat',
            'margins' => [
                'top' => 20,
                'right' => 14,
                'bottom' => 20,
                'left' => 14,
                'first_top' => 42,
            ],
        ])->render();

        $this->assertStringContainsString('background-image: url("'.$backgroundDataUri.'")', $html);
    }

    public function test_mpdf_studio_document_resolves_same_host_public_backgrounds_to_local_paths(): void
    {
        $originalAppUrl = config('app.url');
        Config::set('app.url', 'https://lims-unleashed.test');

        try {
            $html = view('PDFs.studios.document', [
                'documentTitle' => 'Preview',
                'firstPageHeader' => '<div>Header</div>',
                'defaultHeader' => '<div>Header</div>',
                'footerHtml' => '<div>Footer</div>',
                'bodyHtml' => '<p>Body</p>',
                'styles' => '',
                'backgroundImage' => 'https://lims-unleashed.test/storage/media/2026/01/01/7/fundo.svg',
                'backgroundSize' => 'cover',
                'backgroundPosition' => '72% 28%',
                'backgroundRepeat' => 'no-repeat',
                'margins' => [
                    'top' => 20,
                    'right' => 14,
                    'bottom' => 20,
                    'left' => 14,
                    'first_top' => 42,
                ],
            ])->render();

            $this->assertStringContainsString(
                'background-image: url("'.public_path('storage/media/2026/01/01/7/fundo.svg').'")',
                $html
            );
            $this->assertStringContainsString('background-position: 72% 28%', $html);
        } finally {
            Config::set('app.url', $originalAppUrl);
        }
    }

    public function test_mpdf_studio_document_preserves_external_background_urls(): void
    {
        $html = view('PDFs.studios.document', [
            'documentTitle' => 'Preview',
            'firstPageHeader' => '<div>Header</div>',
            'defaultHeader' => '<div>Header</div>',
            'footerHtml' => '<div>Footer</div>',
            'bodyHtml' => '<p>Body</p>',
            'styles' => '',
            'backgroundImage' => 'https://cdn.example.test/storage/media/fundo.svg',
            'backgroundSize' => 'contain',
            'backgroundPosition' => 'top center',
            'backgroundRepeat' => 'repeat-y',
            'margins' => [
                'top' => 20,
                'right' => 14,
                'bottom' => 20,
                'left' => 14,
                'first_top' => 42,
            ],
        ])->render();

        $this->assertStringContainsString('background-image: url("https://cdn.example.test/storage/media/fundo.svg")', $html);
        $this->assertStringContainsString('background-repeat: repeat-y', $html);
    }

    public function test_studio_document_shells_escape_background_urls_for_css_contexts(): void
    {
        $syntaxBreakingUrl = 'https://cdn.example.test/background.svg"); color:red; /*';
        $escapedUrl = ReportStudioCssEscaper::quotedString($syntaxBreakingUrl);

        $mpdfHtml = view('PDFs.studios.document', [
            'documentTitle' => 'Preview',
            'firstPageHeader' => '<div>Header</div>',
            'defaultHeader' => '<div>Header</div>',
            'footerHtml' => '<div>Footer</div>',
            'bodyHtml' => '<p>Body</p>',
            'styles' => '',
            'resolvedBackgroundImage' => $syntaxBreakingUrl,
            'backgroundSize' => 'cover',
            'backgroundPosition' => 'center center',
            'backgroundRepeat' => 'no-repeat',
            'margins' => [
                'top' => 20,
                'right' => 14,
                'bottom' => 20,
                'left' => 14,
                'first_top' => 42,
            ],
        ])->render();
        $chromeHtml = view('PDFs.studios.chrome-document', [
            'documentTitle' => 'Chrome Preview',
            'firstPageHeader' => '',
            'bodyHtml' => '<section>Conteúdo</section>',
            'styles' => '',
            'fontFamily' => 'Century Gothic, DejaVu Sans, sans-serif',
            'resolvedBackgroundImage' => $syntaxBreakingUrl,
            'backgroundSize' => 'cover',
            'backgroundPosition' => 'center center',
            'backgroundRepeat' => 'no-repeat',
            'margins' => [
                'top' => 18,
                'right' => 12,
                'bottom' => 20,
                'left' => 16,
                'first_top' => 54,
            ],
        ])->render();

        foreach ([$mpdfHtml, $chromeHtml] as $html) {
            $this->assertStringContainsString('background-image: url("'.$escapedUrl.'")', $html);
            $this->assertStringNotContainsString('background-image: url("'.$syntaxBreakingUrl.'")', $html);
        }
    }

    public function test_mpdf_studio_document_drops_unsafe_direct_background_schemes(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html;base64,PHNjcmlwdD4='] as $backgroundImage) {
            $html = view('PDFs.studios.document', [
                'documentTitle' => 'Preview',
                'firstPageHeader' => '<div>Header</div>',
                'defaultHeader' => '<div>Header</div>',
                'footerHtml' => '<div>Footer</div>',
                'bodyHtml' => '<p>Body</p>',
                'styles' => '',
                'backgroundImage' => $backgroundImage,
                'backgroundSize' => 'cover',
                'backgroundPosition' => 'center center',
                'backgroundRepeat' => 'no-repeat',
                'margins' => [
                    'top' => 20,
                    'right' => 14,
                    'bottom' => 20,
                    'left' => 14,
                    'first_top' => 42,
                ],
            ])->render();

            $this->assertStringNotContainsString($backgroundImage, $html);
            $this->assertStringNotContainsString('background-image: url(', $html);
        }
    }

    public function test_mpdf_driver_data_normalizes_local_html_and_css_asset_references(): void
    {
        $originalAppUrl = config('app.url');
        Config::set('app.url', 'https://lims-unleashed.test');

        try {
            $method = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForMpdf');
            $method->setAccessible(true);

            $data = $method->invoke(app(ReportStudioPdfRenderer::class), [
                'view' => 'PDFs.studios.document',
                'data' => [
                    'firstPageHeader' => '<img src="/images/logo.svg" alt="logótipo"><a href="/portal">Portal</a>',
                    'defaultHeader' => '<img src="https://cdn.example.test/header.png" alt="externo">',
                    'footerHtml' => '<span style="background-image:url(\'images/footer-mark.svg\')">Rodapé</span>',
                    'bodyHtml' => '<img src="storage/signatures/user.svg" alt="assinatura">',
                    'styles' => '.studio-cover{background-image:url("/storage/report-studios/pattern.svg");}.external-mark{background:url("https://cdn.example.test/pattern.svg");}',
                    'backgroundImage' => 'https://lims-unleashed.test/storage/media/2026/01/01/7/fundo.svg',
                    'backgroundSize' => 'cover; position:fixed',
                    'backgroundPosition' => 'center; background:red',
                    'backgroundRepeat' => 'repeat no-repeat',
                ],
            ]);

            $this->assertStringContainsString('src="'.public_path('images/logo.svg').'"', $data['firstPageHeader']);
            $this->assertStringContainsString('href="/portal"', $data['firstPageHeader']);
            $this->assertStringContainsString('src="https://cdn.example.test/header.png"', $data['defaultHeader']);
            $this->assertStringContainsString("url('".public_path('images/footer-mark.svg')."')", $data['footerHtml']);
            $this->assertStringContainsString('src="'.public_path('storage/signatures/user.svg').'"', $data['bodyHtml']);
            $this->assertStringContainsString('url("'.public_path('storage/report-studios/pattern.svg').'")', $data['styles']);
            $this->assertStringContainsString('url("https://cdn.example.test/pattern.svg")', $data['styles']);
            $this->assertStringNotContainsString('file://', $data['styles']);
            $this->assertSame(public_path('storage/media/2026/01/01/7/fundo.svg'), $data['resolvedBackgroundImage']);
            $this->assertSame('cover', $data['backgroundSize']);
            $this->assertSame('center center', $data['backgroundPosition']);
            $this->assertSame('no-repeat', $data['backgroundRepeat']);

            $preservedData = $method->invoke(app(ReportStudioPdfRenderer::class), [
                'view' => 'PDFs.studios.document',
                'data' => [
                    'resolvedBackgroundImage' => public_path('images/pre-resolved-background.svg'),
                    'bodyHtml' => '<p>Conteúdo</p>',
                ],
            ]);

            $this->assertSame(public_path('images/pre-resolved-background.svg'), $preservedData['resolvedBackgroundImage']);
        } finally {
            Config::set('app.url', $originalAppUrl);
        }
    }

    public function test_chrome_header_footer_font_resolution_ignores_css_priority_suffix(): void
    {
        $method = new ReflectionMethod(ReportStudioPdfRenderer::class, 'documentFontFamilyFromPayload');
        $method->setAccessible(true);

        $fontFamily = $method->invoke(app(ReportStudioPdfRenderer::class), [
            'view' => 'PDFs.studios.document',
            'data' => [
                'styles' => 'body.pdf-document,.pdf-document{font-family:Manrope, DejaVu Sans, sans-serif !important;}',
            ],
        ]);

        $this->assertSame('Manrope, DejaVu Sans, sans-serif', $fontFamily);
    }

    public function test_mpdf_driver_data_and_document_view_use_resolved_studio_font_family(): void
    {
        $method = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForMpdf');
        $method->setAccessible(true);

        $data = $method->invoke(app(ReportStudioPdfRenderer::class), [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => 'Documento com fonte de marca',
                'firstPageHeader' => '<div>Capa</div>',
                'defaultHeader' => '<div>Cabeçalho</div>',
                'footerHtml' => '<div>Rodapé {PAGENO}/{nbpg}</div>',
                'bodyHtml' => '<section>Conteúdo</section>',
                'styles' => 'body.pdf-document,.pdf-document{font-family:Century Gothic, DejaVu Sans, sans-serif !important;}',
                'margins' => [
                    'top' => 20,
                    'right' => 14,
                    'bottom' => 24,
                    'left' => 14,
                    'first_top' => 56,
                ],
            ],
        ]);

        $this->assertSame('Century Gothic, DejaVu Sans, sans-serif', $data['fontFamily']);

        $html = view('PDFs.studios.document', $data)->render();

        $this->assertStringContainsString('font-family: Century Gothic, DejaVu Sans, sans-serif;', $html);
        $this->assertStringNotContainsString('font-family: DejaVu Sans, sans-serif;', $html);
    }

    public function test_chrome_driver_data_preserves_first_page_margin_offset(): void
    {
        $method = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForDriver');
        $method->setAccessible(true);

        $data = $method->invoke(app(ReportStudioPdfRenderer::class), [
            'view' => 'PDFs.studios.document',
            'data' => [
                'backgroundImage' => 'images/report-background.svg',
                'backgroundSize' => 'contain; position:fixed',
                'backgroundPosition' => 'top; left:0',
                'backgroundRepeat' => 'repeat repeat',
                'styles' => 'body.pdf-document,.pdf-document{font-family:Century Gothic, DejaVu Sans, sans-serif !important;}',
                'margins' => [
                    'top' => 18,
                    'right' => 12,
                    'bottom' => 20,
                    'left' => 16,
                    'first_top' => 54,
                ],
            ],
        ], 'chrome');

        $this->assertSame(36.0, $data['browserFirstPageTopOffset']);
        $this->assertSame('file://'.public_path('images/report-background.svg'), $data['resolvedBackgroundImage']);
        $this->assertSame('cover', $data['backgroundSize']);
        $this->assertSame('center center', $data['backgroundPosition']);
        $this->assertSame('no-repeat', $data['backgroundRepeat']);
        $this->assertSame('Century Gothic, DejaVu Sans, sans-serif', $data['fontFamily']);
    }

    public function test_pdf_renderer_normalizes_legacy_export_margins_before_driver_output(): void
    {
        $driverMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForDriver');
        $driverMethod->setAccessible(true);
        $geometryMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'withPrintableCanvasGeometry');
        $geometryMethod->setAccessible(true);

        $payload = [
            'view' => 'PDFs.studios.document',
            'data' => [
                'format' => 'A4',
                'orientation' => 'P',
                'margins' => [
                    'top' => -10,
                    'right' => 260,
                    'bottom' => 'not-a-number',
                    'left' => 999,
                    'first_top' => 500,
                ],
            ],
        ];

        $data = $driverMethod->invoke(app(ReportStudioPdfRenderer::class), $payload, 'chrome');

        $this->assertSame([
            'top' => 0.0,
            'right' => 200.0,
            'bottom' => 24.0,
            'left' => 200.0,
            'first_top' => 250.0,
        ], $data['margins']);
        $this->assertSame(250.0, $data['browserFirstPageTopOffset']);

        $geometryPayload = $geometryMethod->invoke(app(ReportStudioPdfRenderer::class), $payload);

        $this->assertSame(273.0, data_get($geometryPayload, 'data.canvasPageMinHeight'));
        $this->assertSame(40.0, data_get($geometryPayload, 'data.firstCanvasPageMinHeight'));
        $this->assertSame(200.0, data_get($geometryPayload, 'data.margins.left'));
    }

    public function test_pdf_renderer_normalizes_legacy_page_settings_before_driver_output(): void
    {
        $driverMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForDriver');
        $driverMethod->setAccessible(true);
        $mpdfMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForMpdf');
        $mpdfMethod->setAccessible(true);
        $geometryMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'withPrintableCanvasGeometry');
        $geometryMethod->setAccessible(true);
        $renderer = app(ReportStudioPdfRenderer::class);

        $legacyPayload = [
            'view' => 'PDFs.studios.document',
            'data' => [
                'format' => 'Tabloid',
                'orientation' => 'Landscape',
                'customPageWidth' => 30,
                'customPageHeight' => 5000,
                'margins' => [
                    'top' => 20,
                    'right' => 14,
                    'bottom' => 24,
                    'left' => 14,
                ],
            ],
        ];

        $driverData = $driverMethod->invoke($renderer, $legacyPayload, 'chrome');
        $mpdfData = $mpdfMethod->invoke($renderer, $legacyPayload);
        $geometryPayload = $geometryMethod->invoke($renderer, $legacyPayload);

        $this->assertSame('A4', $driverData['format']);
        $this->assertSame('P', $driverData['orientation']);
        $this->assertSame('A4', $mpdfData['format']);
        $this->assertSame('P', $mpdfData['orientation']);
        $this->assertArrayNotHasKey('customPageWidth', $driverData);
        $this->assertArrayNotHasKey('customPageHeight', $driverData);
        $this->assertArrayNotHasKey('customPageWidth', $mpdfData);
        $this->assertArrayNotHasKey('customPageHeight', $mpdfData);
        $this->assertSame(253.0, data_get($geometryPayload, 'data.canvasPageMinHeight'));

        $letterLandscapePayload = [
            'view' => 'PDFs.studios.document',
            'data' => [
                'format' => 'letter',
                'orientation' => 'L',
                'customPageWidth' => 320,
                'customPageHeight' => 180,
                'margins' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
            ],
        ];

        $driverData = $driverMethod->invoke($renderer, $letterLandscapePayload, 'chrome');
        $geometryPayload = $geometryMethod->invoke($renderer, $letterLandscapePayload);

        $this->assertSame('Letter', $driverData['format']);
        $this->assertSame('L', $driverData['orientation']);
        $this->assertArrayNotHasKey('customPageWidth', $driverData);
        $this->assertArrayNotHasKey('customPageHeight', $driverData);
        $this->assertEqualsWithDelta(215.9, data_get($geometryPayload, 'data.canvasPageMinHeight'), 0.01);

        $customPayload = [
            'view' => 'PDFs.studios.document',
            'data' => [
                'format' => 'custom',
                'orientation' => 'P',
                'customPageWidth' => 320,
                'customPageHeight' => 180,
                'margins' => [
                    'top' => 10,
                    'right' => 8,
                    'bottom' => 12,
                    'left' => 8,
                ],
            ],
        ];

        $driverData = $driverMethod->invoke($renderer, $customPayload, 'chrome');
        $mpdfData = $mpdfMethod->invoke($renderer, $customPayload);

        $this->assertSame('custom', $driverData['format']);
        $this->assertSame(320.0, $driverData['customPageWidth']);
        $this->assertSame(180.0, $driverData['customPageHeight']);
        $this->assertSame('custom', $mpdfData['format']);
        $this->assertSame(320.0, $mpdfData['customPageWidth']);
        $this->assertSame(180.0, $mpdfData['customPageHeight']);
    }

    public function test_canvas_geometry_uses_custom_landscape_page_and_print_margins(): void
    {
        $method = new ReflectionMethod(ReportStudioPdfRenderer::class, 'withPrintableCanvasGeometry');
        $method->setAccessible(true);

        $payload = $method->invoke(app(ReportStudioPdfRenderer::class), [
            'view' => 'PDFs.studios.document',
            'data' => [
                'format' => 'custom',
                'customPageWidth' => 320,
                'customPageHeight' => 180,
                'orientation' => 'L',
                'margins' => [
                    'top' => 18,
                    'right' => 12,
                    'bottom' => 22,
                    'left' => 16,
                    'first_top' => 46,
                ],
            ],
        ]);

        $this->assertSame(140.0, data_get($payload, 'data.canvasPageMinHeight'));
        $this->assertSame(112.0, data_get($payload, 'data.firstCanvasPageMinHeight'));
    }

    public function test_chrome_driver_data_normalizes_html_asset_references_for_browser_rendering(): void
    {
        $method = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForDriver');
        $method->setAccessible(true);

        $data = $method->invoke(app(ReportStudioPdfRenderer::class), [
            'view' => 'PDFs.studios.document',
            'data' => [
                'firstPageHeader' => '<img src="'.__FILE__.'" alt="assinatura"><a href="mailto:qualidade@example.test">Contacto</a>',
                'defaultHeader' => '<img src="/images/logo.svg" alt="logótipo">',
                'footerHtml' => '<span style="background-image:url(\'images/footer-mark.svg\')">Rodapé</span>',
                'bodyHtml' => '<a href="/documents/verify/REL-2026-001">Verificar autenticidade</a><img src="storage/signatures/user.svg" alt="assinatura">',
                'styles' => '.studio-cover{background-image:url("/storage/report-studios/pattern.svg");}.external-mark{background:url("https://cdn.example.test/pattern.svg");}',
                'backgroundImage' => 'data:image/svg+xml;base64,'.base64_encode('<svg></svg>'),
                'margins' => [
                    'top' => 20,
                    'right' => 14,
                    'bottom' => 20,
                    'left' => 14,
                    'first_top' => 20,
                ],
            ],
        ], 'chrome');

        $this->assertStringContainsString('src="file://'.__FILE__.'"', $data['firstPageHeader']);
        $this->assertStringContainsString('href="mailto:qualidade@example.test"', $data['firstPageHeader']);
        $this->assertStringContainsString('src="file://'.public_path('images/logo.svg').'"', $data['defaultHeader']);
        $this->assertStringContainsString("url('file://".public_path('images/footer-mark.svg')."')", $data['footerHtml']);
        $this->assertStringContainsString('href="/documents/verify/REL-2026-001"', $data['bodyHtml']);
        $this->assertStringContainsString('src="file://'.public_path('storage/signatures/user.svg').'"', $data['bodyHtml']);
        $this->assertStringContainsString('url("file://'.public_path('storage/report-studios/pattern.svg').'")', $data['styles']);
        $this->assertStringContainsString('url("https://cdn.example.test/pattern.svg")', $data['styles']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $data['resolvedBackgroundImage']);
    }

    public function test_chrome_driver_data_resolves_same_host_public_assets_to_file_urls(): void
    {
        $originalAppUrl = config('app.url');
        Config::set('app.url', 'https://lims-unleashed.test');

        try {
            $method = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForDriver');
            $method->setAccessible(true);

            $data = $method->invoke(app(ReportStudioPdfRenderer::class), [
                'view' => 'PDFs.studios.document',
                'data' => [
                    'firstPageHeader' => '<img src="https://lims-unleashed.test/storage/report-studios/header-logo.svg" alt="logótipo">',
                    'defaultHeader' => '<img src="https://cdn.example.test/header-logo.svg" alt="externo">',
                    'bodyHtml' => '<img src="https://lims-unleashed.test/images/document-seal.svg" alt="selo">',
                    'styles' => '.studio-page{background-image:url("https://lims-unleashed.test/storage/report-studios/page-pattern.svg");}.external{background:url("https://cdn.example.test/pattern.svg");}',
                    'backgroundImage' => 'https://lims-unleashed.test/storage/report-studios/background.svg',
                ],
            ], 'chrome');

            $this->assertStringContainsString('src="file://'.public_path('storage/report-studios/header-logo.svg').'"', $data['firstPageHeader']);
            $this->assertStringContainsString('src="https://cdn.example.test/header-logo.svg"', $data['defaultHeader']);
            $this->assertStringContainsString('src="file://'.public_path('images/document-seal.svg').'"', $data['bodyHtml']);
            $this->assertStringContainsString('url("file://'.public_path('storage/report-studios/page-pattern.svg').'")', $data['styles']);
            $this->assertStringContainsString('url("https://cdn.example.test/pattern.svg")', $data['styles']);
            $this->assertSame('file://'.public_path('storage/report-studios/background.svg'), $data['resolvedBackgroundImage']);

            $preservedData = $method->invoke(app(ReportStudioPdfRenderer::class), [
                'view' => 'PDFs.studios.document',
                'data' => [
                    'resolvedBackgroundImage' => 'file://'.public_path('images/pre-resolved-background.svg'),
                    'bodyHtml' => '<p>Conteúdo</p>',
                ],
            ], 'chrome');

            $this->assertSame('file://'.public_path('images/pre-resolved-background.svg'), $preservedData['resolvedBackgroundImage']);
        } finally {
            Config::set('app.url', $originalAppUrl);
        }
    }

    public function test_pdf_renderer_rejects_unsafe_asset_schemes_during_driver_normalization(): void
    {
        $driverMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForDriver');
        $driverMethod->setAccessible(true);
        $mpdfMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'dataForMpdf');
        $mpdfMethod->setAccessible(true);

        $payload = [
            'view' => 'PDFs.studios.document',
            'data' => [
                'firstPageHeader' => '<a href="mailto:qualidade@example.test">Contacto</a><img src="javascript:alert(1)" alt="x">',
                'defaultHeader' => '<img src="data:text/html;base64,PHNjcmlwdD4=" alt="x">',
                'footerHtml' => '<span style="background-image:url(vbscript:msgbox(1))">Rodapé</span>',
                'bodyHtml' => '<img src="data:image/png;base64,aGVsbG8=" alt="ok">',
                'styles' => '.unsafe{background-image:url("javascript:alert(2)");}.safe{background-image:url("https://cdn.example.test/pattern.svg");}',
                'backgroundImage' => 'javascript:alert(3)',
            ],
        ];

        $chromeData = $driverMethod->invoke(app(ReportStudioPdfRenderer::class), $payload, 'chrome');
        $mpdfData = $mpdfMethod->invoke(app(ReportStudioPdfRenderer::class), $payload);

        foreach ([$chromeData, $mpdfData] as $data) {
            $serialized = implode("\n", [
                (string) $data['firstPageHeader'],
                (string) $data['defaultHeader'],
                (string) $data['footerHtml'],
                (string) $data['bodyHtml'],
                (string) $data['styles'],
                (string) ($data['resolvedBackgroundImage'] ?? ''),
            ]);

            $this->assertStringContainsString('href="mailto:qualidade@example.test"', (string) $data['firstPageHeader']);
            $this->assertStringContainsString('src="data:image/png;base64,aGVsbG8="', (string) $data['bodyHtml']);
            $this->assertStringContainsString('url("https://cdn.example.test/pattern.svg")', (string) $data['styles']);
            $this->assertStringNotContainsString('javascript:', $serialized);
            $this->assertStringNotContainsString('vbscript:', $serialized);
            $this->assertStringNotContainsString('data:text/html', $serialized);
        }

        $this->assertSame('', $chromeData['resolvedBackgroundImage']);
        $this->assertSame('', $mpdfData['resolvedBackgroundImage']);
    }

    public function test_chrome_header_footer_templates_follow_configured_pdf_margins(): void
    {
        $headerHtml = view('PDFs.studios.chrome-header', [
            'headerHtml' => '<div>Header</div>',
            'fontFamily' => 'Manrope, DejaVu Sans, sans-serif',
            'styles' => '.studio-brand-header{background:#143d37;color:#fffdf7;}',
            'marginLeft' => 18,
            'marginRight' => 10,
        ])->render();
        $footerHtml = view('PDFs.studios.chrome-footer', [
            'footerHtml' => '<div>Footer</div>',
            'fontFamily' => 'Manrope, DejaVu Sans, sans-serif',
            'styles' => '.studio-brand-footer{border-top:1px solid #d9b05f;}',
            'marginLeft' => 18,
            'marginRight' => 10,
        ])->render();

        $this->assertStringContainsString('padding: 0 10mm 0 18mm', $headerHtml);
        $this->assertStringContainsString('font-family: Manrope, DejaVu Sans, sans-serif', $headerHtml);
        $this->assertStringContainsString('.studio-brand-header{background:#143d37;color:#fffdf7;}', $headerHtml);
        $this->assertStringContainsString('padding: 0 10mm 0 18mm', $footerHtml);
        $this->assertStringContainsString('.studio-brand-footer{border-top:1px solid #d9b05f;}', $footerHtml);
    }

    public function test_chrome_header_footer_renderer_includes_studio_styles_and_pagination_tokens(): void
    {
        $headerMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'chromeHeaderHtml');
        $headerMethod->setAccessible(true);
        $footerMethod = new ReflectionMethod(ReportStudioPdfRenderer::class, 'chromeFooterHtml');
        $footerMethod->setAccessible(true);
        $payload = [
            'view' => 'PDFs.studios.document',
            'data' => [
                'defaultHeader' => '<div class="studio-brand-header">Relatório {PAGENO}/{nbpg}</div>',
                'footerHtml' => '<div class="studio-brand-footer">Rodapé {{page_number}}/{{total_pages}}</div>',
                'styles' => 'body.pdf-document,.pdf-document{font-family:Century Gothic, DejaVu Sans, sans-serif !important;}.studio-brand-header{background:#143d37;color:#fffdf7;}.studio-brand-footer{border-top:1px solid #d9b05f;}',
                'margins' => [
                    'left' => 16,
                    'right' => 12,
                ],
            ],
        ];

        $headerHtml = $headerMethod->invoke(app(ReportStudioPdfRenderer::class), $payload);
        $footerHtml = $footerMethod->invoke(app(ReportStudioPdfRenderer::class), $payload);

        $this->assertStringContainsString('font-family: Century Gothic, DejaVu Sans, sans-serif', $headerHtml);
        $this->assertStringContainsString('.studio-brand-header{background:#143d37;color:#fffdf7;}', $headerHtml);
        $this->assertStringContainsString('<span class="pageNumber"></span>/<span class="totalPages"></span>', $headerHtml);
        $this->assertStringContainsString('padding: 0 12mm 0 16mm', $headerHtml);
        $this->assertStringContainsString('.studio-brand-footer{border-top:1px solid #d9b05f;}', $footerHtml);
        $this->assertStringContainsString('<span class="pageNumber"></span>/<span class="totalPages"></span>', $footerHtml);
    }

    public function test_chrome_document_converts_studio_pagebreak_tags_to_browser_print_breaks(): void
    {
        $html = view('PDFs.studios.chrome-document', [
            'documentTitle' => 'Chrome Preview',
            'firstPageHeader' => '<div>Primeira página</div>',
            'bodyHtml' => '<section>Primeira secção</section><pagebreak orientation="L" resetpagenum="1" /><section>Segunda secção</section>',
            'styles' => '',
            'fontFamily' => 'Century Gothic, DejaVu Sans, sans-serif',
            'resolvedBackgroundImage' => null,
            'browserFirstPageTopOffset' => 24,
        ])->render();

        $this->assertStringContainsString('font-family: Century Gothic, DejaVu Sans, sans-serif', $html);
        $this->assertStringContainsString('break-before: page', $html);
        $this->assertStringContainsString('<section class="studio-first-page-shell">', $html);
        $this->assertStringContainsString('<div class="studio-first-page-content">', $html);
        $this->assertStringContainsString('<div class="studio-page-break"></div>', $html);
        $this->assertStringNotContainsString('<pagebreak', $html);
        $this->assertStringNotContainsString('orientation="L"', $html);
    }

    public function test_chrome_document_embeds_studio_page_geometry_css(): void
    {
        $letterHtml = view('PDFs.studios.chrome-document', [
            'documentTitle' => 'Chrome Preview',
            'firstPageHeader' => '',
            'bodyHtml' => '<section>Conteúdo</section>',
            'styles' => '',
            'fontFamily' => 'Century Gothic, DejaVu Sans, sans-serif',
            'resolvedBackgroundImage' => null,
            'format' => 'Letter',
            'orientation' => 'L',
            'customPageWidth' => 320,
            'customPageHeight' => 180,
            'margins' => [
                'top' => 18,
                'right' => 12,
                'bottom' => 20,
                'left' => 16,
                'first_top' => 54,
            ],
        ])->render();

        $customHtml = view('PDFs.studios.chrome-document', [
            'documentTitle' => 'Chrome Custom Preview',
            'firstPageHeader' => '',
            'bodyHtml' => '<section>Conteúdo</section>',
            'styles' => '',
            'fontFamily' => 'Century Gothic, DejaVu Sans, sans-serif',
            'resolvedBackgroundImage' => null,
            'format' => 'custom',
            'orientation' => 'P',
            'customPageWidth' => 320,
            'customPageHeight' => 180,
            'margins' => [
                'top' => 10,
                'right' => 8,
                'bottom' => 12,
                'left' => 8,
            ],
        ])->render();

        $this->assertStringContainsString('size: Letter landscape;', $letterHtml);
        $this->assertStringContainsString('margin: 18mm 12mm 20mm 16mm;', $letterHtml);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $letterHtml);
        $this->assertStringContainsString('.report-chart-svg', $letterHtml);
        $this->assertStringContainsString('.commercial-record-evidence', $letterHtml);
        $this->assertStringContainsString('break-inside: avoid;', $letterHtml);
        $this->assertStringNotContainsString('size: 320mm 180mm;', $letterHtml);
        $this->assertStringContainsString('size: 320mm 180mm;', $customHtml);
        $this->assertStringContainsString('margin: 10mm 8mm 12mm 8mm;', $customHtml);
    }

    public function test_pdf_builder_segments_at_attributed_pagebreaks_for_page_scoped_canvas_blocks(): void
    {
        $studio = new ReportStudioTemplate([
            'name' => 'Page Scoped Studio',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<section>Primeira página</section><pagebreak orientation="L" /><section>Segunda página</section>',
                'canvas_blocks' => [
                    [
                        'surface' => 'content',
                        'block_kind' => 'rich_text',
                        'content_html' => '<strong>Bloco da página dois {{document_code}}</strong>',
                        'page_scope' => 'specific',
                        'page_number' => 2,
                        'x' => 8,
                        'y' => 12,
                        'width' => 40,
                        'min_height' => 36,
                        'z_index' => 20,
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload(
            ['kpis' => [], 'top_customers' => []],
            app(GeneralSettings::class),
            $studio
        );
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('Primeira página', $bodyHtml);
        $this->assertStringContainsString('<pagebreak />', $bodyHtml);
        $this->assertStringContainsString('Segunda página', $bodyHtml);
        $this->assertStringContainsString('class="studio-canvas-page studio-canvas-page-2"', $bodyHtml);
        $this->assertStringContainsString('Bloco da página dois EXEC-', $bodyHtml);
        $this->assertStringNotContainsString('orientation="L"', $bodyHtml);
    }

    public function test_canvas_blocks_support_overlay_assets_and_qr_codes(): void
    {
        $user = $this->verifiedAdmin();
        $signatureDataUri = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="50"></svg>');
        $blockBackgroundDataUri = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="90"></svg>');

        $response = $this->actingAs($user)
            ->post(route('report-studios.store'), [
                'name' => 'Overlay Stamp Signature Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'active',
                'is_default' => false,
                'theme_preset' => 'corporate',
                'description' => 'Template with layered signature, stamp, image and QR assets.',
                'layout_schema' => [
                    'body_html' => '<h1>Resumo Executivo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'signature-layer',
                            'title' => 'Assinatura',
                            'surface' => 'content',
                            'block_kind' => 'signature',
                            'signature_name' => '{{lab_name}}',
                            'signature_image' => $signatureDataUri,
                            'signature_image_fit' => 'cover',
                            'signature_image_position' => '37% 68%',
                            'signature_image_width' => 220,
                            'signature_image_height' => 92,
                            'z_index' => 10,
                        ],
                        [
                            'id' => 'stamp-layer',
                            'title' => 'Carimbo',
                            'surface' => 'content',
                            'block_kind' => 'stamp',
                            'image_url' => 'https://example.test/stamp.png',
                            'image_fit' => 'contain',
                            'rotation_deg' => -12,
                            'shadow_preset' => 'stamp',
                            'z_index' => 20,
                        ],
                        [
                            'id' => 'qr-layer',
                            'title' => 'QR',
                            'surface' => 'content',
                            'block_kind' => 'qr_code',
                            'qr_content' => '{{document_code}}',
                            'qr_label' => 'Verificação digital',
                            'qr_foreground_color' => '#143d37',
                            'qr_background_color' => '#f7f1e7',
                            'qr_error_correction' => 'quartile',
                            'qr_margin' => 12,
                            'z_index' => 30,
                        ],
                        [
                            'id' => 'chart-layer',
                            'title' => 'Tendência',
                            'surface' => 'content',
                            'block_kind' => 'chart_snapshot',
                            'chart_title' => 'Tendência de ensaios',
                            'chart_caption' => 'Snapshot ApexCharts para relatório premium.',
                            'chart_svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="60"><rect width="120" height="60" fill="#ecfeff"/></svg>',
                            'background_image' => $blockBackgroundDataUri,
                            'z_index' => 40,
                        ],
                        [
                            'id' => 'hidden-layer',
                            'title' => 'Objecto oculto',
                            'surface' => 'content',
                            'block_kind' => 'rich_text',
                            'content_html' => '<p>SHOULD_NOT_RENDER</p>',
                            'is_hidden' => true,
                            'z_index' => 50,
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $template = ReportStudioTemplate::query()->where('name', 'Overlay Stamp Signature Template')->firstOrFail();
        $this->assertTrue((bool) data_get($template->layout_schema, 'canvas_blocks.4.is_hidden'));
        $this->assertSame('cover', data_get($template->layout_schema, 'canvas_blocks.0.signature_image_fit'));
        $this->assertSame('37% 68%', data_get($template->layout_schema, 'canvas_blocks.0.signature_image_position'));
        $this->assertSame(220, data_get($template->layout_schema, 'canvas_blocks.0.signature_image_width'));
        $this->assertSame(92, data_get($template->layout_schema, 'canvas_blocks.0.signature_image_height'));
        $this->assertSame('#143d37', data_get($template->layout_schema, 'canvas_blocks.2.qr_foreground_color'));
        $this->assertSame('#f7f1e7', data_get($template->layout_schema, 'canvas_blocks.2.qr_background_color'));
        $this->assertSame('quartile', data_get($template->layout_schema, 'canvas_blocks.2.qr_error_correction'));
        $this->assertSame(12, data_get($template->layout_schema, 'canvas_blocks.2.qr_margin'));
        $this->assertSame(-12, data_get($template->layout_schema, 'canvas_blocks.1.rotation_deg'));
        $this->assertSame('stamp', data_get($template->layout_schema, 'canvas_blocks.1.shadow_preset'));

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
            'kpis' => [],
            'top_customers' => [],
        ], app(GeneralSettings::class), $template);
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString($signatureDataUri, $bodyHtml);
        $this->assertStringContainsString('width:220px; max-width:100%; height:92px; object-fit:cover; object-position:37% 68%;', $bodyHtml);
        $this->assertStringContainsString($blockBackgroundDataUri, $bodyHtml);
        $this->assertStringContainsString('stamp.png', $bodyHtml);
        $this->assertStringContainsString('data:image/svg+xml;base64', $bodyHtml);
        $this->assertStringContainsString('report-chart', $bodyHtml);
        $this->assertStringContainsString('Tendência de ensaios', $bodyHtml);
        $this->assertStringContainsString('Snapshot ApexCharts', $bodyHtml);
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $bodyHtml);
        $this->assertStringContainsString('transform: rotate(-12deg); transform-origin: center center;', $bodyHtml);
        $this->assertStringContainsString('box-shadow: 0 10px 22px rgba(20, 61, 55, 0.22);', $bodyHtml);
        $this->assertStringNotContainsString('SHOULD_NOT_RENDER', $bodyHtml);
        preg_match_all('/data:image\/svg\+xml;base64,([^"\']+)/', $bodyHtml, $svgDataUris);
        $this->assertTrue(collect($svgDataUris[1])->contains(function (string $encodedSvg): bool {
            $svg = base64_decode($encodedSvg, true);

            return is_string($svg)
                && str_contains($svg, 'fill="#143d37"')
                && str_contains($svg, 'fill="#f7f1e7"');
        }));
        $this->assertLessThan(
            strpos($bodyHtml, 'stamp.png'),
            strpos($bodyHtml, $signatureDataUri)
        );
    }

    public function test_report_studio_rejects_non_renderable_canvas_surfaces(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Invalid Surface Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'draft',
                'is_default' => false,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'non-renderable-surface-layer',
                            'surface' => 'styles_css',
                            'block_kind' => 'rich_text',
                            'content_html' => '<p>Não deve ser persistido como canvas.</p>',
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertSessionHasErrors([
            'layout_schema.canvas_blocks.0.surface',
        ]);
    }

    public function test_report_studio_requires_page_number_for_specific_content_canvas_blocks(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Missing Specific Page Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'draft',
                'is_default' => false,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'missing-specific-page-layer',
                            'surface' => 'content',
                            'block_kind' => 'rich_text',
                            'content_html' => '<p>Camada posicionada numa página específica.</p>',
                            'page_scope' => 'specific',
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertSessionHasErrors([
            'layout_schema.canvas_blocks.0.page_number',
        ]);
    }

    public function test_report_studio_rejects_invalid_qr_customisation(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Invalid QR Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'draft',
                'is_default' => false,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-qr-layer',
                            'surface' => 'content',
                            'block_kind' => 'qr_code',
                            'qr_content' => '{{document_code}}',
                            'qr_foreground_color' => 'blue',
                            'qr_background_color' => '#ffffff',
                            'qr_error_correction' => 'extreme',
                            'qr_margin' => 80,
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response
            ->assertSessionHasErrors([
                'layout_schema.canvas_blocks.0.qr_foreground_color',
                'layout_schema.canvas_blocks.0.qr_error_correction',
                'layout_schema.canvas_blocks.0.qr_margin',
            ]);
    }

    public function test_report_studio_rejects_invalid_canvas_visual_effects(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Invalid Visual Effects Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'draft',
                'is_default' => false,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-effects-layer',
                            'title' => 'Efeito inválido',
                            'surface' => 'content',
                            'block_kind' => 'stamp',
                            'rotation_deg' => 90,
                            'shadow_preset' => 'dramatic',
                            'signature_image_fit' => 'stretch',
                            'signature_image_position' => 'center; transform:rotate(45deg)',
                            'signature_image_width' => 800,
                            'signature_image_height' => 4,
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertSessionHasErrors([
            'layout_schema.canvas_blocks.0.rotation_deg',
            'layout_schema.canvas_blocks.0.shadow_preset',
            'layout_schema.canvas_blocks.0.signature_image_fit',
            'layout_schema.canvas_blocks.0.signature_image_position',
            'layout_schema.canvas_blocks.0.signature_image_width',
            'layout_schema.canvas_blocks.0.signature_image_height',
        ]);
    }

    public function test_report_studio_rejects_unsafe_canvas_css_values(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Unsafe Canvas CSS Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'draft',
                'is_default' => false,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'page_background_color' => '#fffdf7; background:red',
                    'table_header_background' => '#f8f4ea; background:red',
                    'table_header_text_color' => 'rgba(20,61,55,0.95); font-size:80px',
                    'table_border_color' => 'url(javascript:alert(1))',
                    'table_summary_background' => '#fffdf7; color:red',
                    'table_summary_text_color' => 'expression(alert(1))',
                    'table_summary_muted_color' => 'rgba(20,61,55,0.75); position:fixed',
                    'background_size' => 'cover; position:fixed',
                    'background_position' => 'center; position:fixed',
                    'background_repeat' => 'repeat no-repeat',
                    'canvas_blocks' => [
                        [
                            'id' => 'unsafe-css-layer',
                            'surface' => 'content',
                            'block_kind' => 'image',
                            'image_url' => 'https://cdn.example.test/selo.png',
                            'image_position' => '37%; transform:rotate(45deg)',
                            'background_color' => '#f8f4ea; background:url(https://bad.example.test/x)',
                            'background_image_position' => 'center; background:red',
                            'overlay_color' => 'rgba(20,61,55,0.2); color:red',
                            'text_color' => '#143d37; position:fixed',
                            'border_color' => 'url(javascript:alert(1))',
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertSessionHasErrors([
            'layout_schema.table_header_background',
            'layout_schema.table_header_text_color',
            'layout_schema.table_border_color',
            'layout_schema.table_summary_background',
            'layout_schema.table_summary_text_color',
            'layout_schema.table_summary_muted_color',
            'layout_schema.page_background_color',
            'layout_schema.background_size',
            'layout_schema.background_position',
            'layout_schema.background_repeat',
            'layout_schema.canvas_blocks.0.image_position',
            'layout_schema.canvas_blocks.0.background_color',
            'layout_schema.canvas_blocks.0.background_image_position',
            'layout_schema.canvas_blocks.0.overlay_color',
            'layout_schema.canvas_blocks.0.text_color',
            'layout_schema.canvas_blocks.0.border_color',
        ]);
    }

    public function test_report_studio_rejects_unsafe_canvas_media_references(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Unsafe Canvas Media Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'draft',
                'is_default' => false,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'background_image_path' => 'javascript:alert(1)',
                    'canvas_blocks' => [
                        [
                            'id' => 'unsafe-media-layer',
                            'surface' => 'content',
                            'block_kind' => 'image',
                            'image_url' => '/storage/media/logo.png" onerror="alert(1)',
                            'background_image' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
                            'signature_image' => 'ftp://example.test/signature.png',
                            'chart_image_url' => '/private/tmp/chart.png',
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertSessionHasErrors([
            'layout_schema.background_image_path',
            'layout_schema.canvas_blocks.0.image_url',
            'layout_schema.canvas_blocks.0.background_image',
            'layout_schema.canvas_blocks.0.signature_image',
            'layout_schema.canvas_blocks.0.chart_image_url',
        ]);
    }

    public function test_chart_blocks_generate_pdf_svg_from_configured_data(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Configured Chart Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'active',
                'layout_schema' => [
                    'body_html' => '<h1>Resumo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'configured-chart',
                            'title' => 'Indicadores por etapa',
                            'surface' => 'content',
                            'block_kind' => 'chart_snapshot',
                            'chart_title' => 'Fluxo técnico',
                            'chart_caption' => 'Recepção, validação e emissão no período.',
                            'chart_type' => 'line',
                            'chart_labels' => ['Recepção', 'Validação', 'Emissão'],
                            'chart_values' => [18, 12, 9],
                            'chart_colors' => ['#143d37', '#d9b05f', '#0f766e'],
                            'chart_primary_color' => '#143d37',
                            'chart_background_color' => '#f8f4ea',
                            'chart_show_values' => true,
                            'width' => 70,
                            'min_height' => 240,
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertRedirect()->assertSessionHas('success');

        $template = ReportStudioTemplate::query()->where('name', 'Configured Chart Template')->firstOrFail();
        $this->assertSame('line', data_get($template->layout_schema, 'canvas_blocks.0.chart_type'));
        $this->assertSame(['Recepção', 'Validação', 'Emissão'], data_get($template->layout_schema, 'canvas_blocks.0.chart_labels'));
        $this->assertSame([18, 12, 9], data_get($template->layout_schema, 'canvas_blocks.0.chart_values'));
        $this->assertSame('#f8f4ea', data_get($template->layout_schema, 'canvas_blocks.0.chart_background_color'));

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
            'kpis' => [],
            'top_customers' => [],
        ], app(GeneralSettings::class), $template);
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('data-chart-type="line"', $bodyHtml);
        $this->assertStringContainsString('report-chart-svg', $bodyHtml);
        $this->assertStringContainsString('aria-label="Fluxo técnico" style="font-family:inherit;"', $bodyHtml);
        $this->assertStringContainsString('#143d37', $bodyHtml);
        $this->assertStringContainsString('#f8f4ea', $bodyHtml);
        $this->assertStringContainsString('Fluxo técnico', $bodyHtml);
        $this->assertStringContainsString('Recepção', $bodyHtml);
        $this->assertStringContainsString('Validação', $bodyHtml);
        $this->assertStringContainsString('Emissão', $bodyHtml);
        $this->assertStringContainsString('Recepção, validação e emissão no período.', $bodyHtml);
    }

    public function test_chart_snapshot_svg_is_sanitized_before_pdf_rendering(): void
    {
        $template = new ReportStudioTemplate([
            'name' => 'Unsafe SVG Chart Template',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<h1>Resumo executivo</h1>',
                'canvas_blocks' => [
                    [
                        'id' => 'unsafe-svg-chart',
                        'surface' => 'content',
                        'block_kind' => 'chart_snapshot',
                        'chart_title' => 'Gráfico SVG importado',
                        'chart_svg' => '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)" viewBox="0 0 120 60"><script>alert(1)</script><foreignObject><body>unsafe</body></foreignObject><a href="javascript:alert(1)"><text onclick="alert(2)">Clique</text></a><rect width="120" height="60" fill="#143d37" style="background-image:url(javascript:alert(3))"/></svg><p>fora do svg</p>',
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
            'kpis' => [],
            'top_customers' => [],
        ], app(GeneralSettings::class), $template);
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $bodyHtml);
        $this->assertStringContainsString('<rect width="120" height="60" fill="#143d37"', $bodyHtml);
        $this->assertStringNotContainsString('<script', $bodyHtml);
        $this->assertStringNotContainsString('foreignObject', $bodyHtml);
        $this->assertStringNotContainsString('onload=', $bodyHtml);
        $this->assertStringNotContainsString('onclick=', $bodyHtml);
        $this->assertStringNotContainsString('javascript:', $bodyHtml);
        $this->assertStringNotContainsString('fora do svg', $bodyHtml);
    }

    public function test_chart_blocks_accept_studio_text_lists_and_placeholder_series(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Placeholder Chart Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'active',
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'placeholder-chart',
                            'title' => 'Ciclo de ensaio',
                            'surface' => 'content',
                            'block_kind' => 'chart_snapshot',
                            'chart_title' => '{executive_chart_title}',
                            'chart_caption' => '{executive_chart_caption}',
                            'chart_type' => 'bar',
                            'chart_labels' => '{executive_chart_labels}',
                            'chart_values' => '{executive_chart_values}',
                            'chart_colors' => "#143d37, #d9b05f\n#0f766e",
                            'chart_show_values' => true,
                            'width' => 76,
                            'min_height' => 240,
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $template = ReportStudioTemplate::query()->where('name', 'Placeholder Chart Template')->firstOrFail();
        $this->assertSame('{executive_chart_values}', data_get($template->layout_schema, 'canvas_blocks.0.chart_values'));
        $this->assertSame("#143d37, #d9b05f\n#0f766e", data_get($template->layout_schema, 'canvas_blocks.0.chart_colors'));

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
            'kpis' => [],
            'top_customers' => [],
            'charts' => [
                'throughput' => [
                    'title' => 'Ciclo técnico por etapa',
                    'caption' => 'Indicadores preenchidos a partir do modelo executivo.',
                    'labels' => ['Recepção', 'Validação', 'Emissão'],
                    'series' => [18, 12, 9],
                ],
            ],
        ], app(GeneralSettings::class), $template);
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('data-chart-type="bar"', $bodyHtml);
        $this->assertStringContainsString('Ciclo técnico por etapa', $bodyHtml);
        $this->assertStringContainsString('Indicadores preenchidos a partir do modelo executivo.', $bodyHtml);
        $this->assertStringContainsString('Recepção', $bodyHtml);
        $this->assertStringContainsString('>18</text>', $bodyHtml);
        $this->assertStringContainsString('#d9b05f', $bodyHtml);

        $this->actingAs($this->verifiedAdmin())
            ->put(route('report-studios.update', $template), [
                'name' => $template->name,
                'studio_type' => $template->studio_type,
                'renderer' => $template->renderer,
                'status' => $template->status,
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-placeholder-chart',
                            'surface' => 'content',
                            'block_kind' => 'chart_snapshot',
                            'chart_values' => '18, inválido, 9',
                            'chart_colors' => '#143d37, gold',
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors([
                'layout_schema.canvas_blocks.0.chart_values',
                'layout_schema.canvas_blocks.0.chart_colors',
            ]);
    }

    public function test_canvas_chart_and_qr_blocks_resolve_brand_color_placeholders_in_pdf_output(): void
    {
        $settings = app(GeneralSettings::class);
        $originalSettings = [
            'app_primary_color' => $settings->app_primary_color,
            'app_secondary_color' => $settings->app_secondary_color,
            'app_accent_color' => $settings->app_accent_color,
        ];

        try {
            $settings->fill([
                'app_primary_color' => '#245f4a',
                'app_secondary_color' => '#fff4d6',
                'app_accent_color' => '#d9b05f',
            ]);
            $settings->save();

            $response = $this->actingAs($this->verifiedAdmin())
                ->post(route('report-studios.store'), [
                    'name' => 'Brand Placeholder Canvas Template',
                    'studio_type' => 'executive',
                    'renderer' => 'internal',
                    'status' => 'active',
                    'layout_schema' => [
                        'body_html' => '<h1>Resumo executivo</h1>',
                        'canvas_blocks' => [
                            [
                                'id' => 'brand-chart',
                                'title' => 'Gráfico com cores da marca',
                                'surface' => 'content',
                                'block_kind' => 'chart_snapshot',
                                'chart_title' => 'Ciclo técnico',
                                'chart_type' => 'line',
                                'chart_labels' => 'Recepção, Validação, Emissão',
                                'chart_values' => '18, 12, 9',
                                'chart_colors' => '{brand_primary_color}, {brand_accent_color}, #0f766e',
                                'chart_primary_color' => '{brand_primary_color}',
                                'chart_background_color' => '{{ brand_secondary_color }}',
                                'chart_show_values' => true,
                                'width' => 72,
                                'min_height' => 240,
                            ],
                            [
                                'id' => 'brand-qr',
                                'title' => 'QR com cores da marca',
                                'surface' => 'content',
                                'block_kind' => 'qr_code',
                                'qr_content' => '{{document_code}}',
                                'qr_label' => 'Verificação digital',
                                'qr_foreground_color' => '{{ brand_primary_color }}',
                                'qr_background_color' => '{brand_secondary_color}',
                                'qr_error_correction' => 'quartile',
                                'qr_margin' => 12,
                                'width' => 20,
                                'min_height' => 140,
                            ],
                        ],
                    ],
                    'export_settings' => ['paper_size' => 'A4'],
                ]);

            $response->assertRedirect()
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success');

            $template = ReportStudioTemplate::query()
                ->where('name', 'Brand Placeholder Canvas Template')
                ->firstOrFail();

            $this->assertSame('{brand_primary_color}', data_get($template->layout_schema, 'canvas_blocks.0.chart_primary_color'));
            $this->assertSame('{{ brand_secondary_color }}', data_get($template->layout_schema, 'canvas_blocks.0.chart_background_color'));
            $this->assertSame('{{ brand_primary_color }}', data_get($template->layout_schema, 'canvas_blocks.1.qr_foreground_color'));
            $this->assertSame('{brand_secondary_color}', data_get($template->layout_schema, 'canvas_blocks.1.qr_background_color'));

            $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
                'kpis' => [],
                'top_customers' => [],
            ], $settings, $template);
            $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

            $this->assertStringContainsString('data-chart-type="line"', $bodyHtml);
            $this->assertStringContainsString('stroke="#245f4a"', $bodyHtml);
            $this->assertStringContainsString('fill="#fff4d6"', $bodyHtml);
            $this->assertStringContainsString('fill="#d9b05f"', $bodyHtml);
            $this->assertStringNotContainsString('{brand_primary_color}', $bodyHtml);
            $this->assertStringNotContainsString('brand_secondary_color', $bodyHtml);

            preg_match_all('/data:image\/svg\+xml;base64,([^"\']+)/', $bodyHtml, $svgDataUris);
            $this->assertTrue(collect($svgDataUris[1])->contains(function (string $encodedSvg): bool {
                $svg = base64_decode($encodedSvg, true);

                return is_string($svg)
                    && str_contains($svg, 'fill="#245f4a"')
                    && str_contains($svg, 'fill="#fff4d6"');
            }));
        } finally {
            $settings->fill($originalSettings);
            $settings->save();
        }
    }

    public function test_generated_chart_blocks_use_readable_ink_on_dark_backgrounds(): void
    {
        $template = new ReportStudioTemplate([
            'name' => 'Dark Chart Contrast Template',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<h1>Resumo executivo</h1>',
                'canvas_blocks' => [
                    [
                        'id' => 'dark-chart',
                        'surface' => 'content',
                        'block_kind' => 'chart_snapshot',
                        'chart_title' => 'Contraste técnico',
                        'chart_type' => 'bar',
                        'chart_labels' => 'Recepção, Validação, Emissão',
                        'chart_values' => "1,5; 2,75\n3,25",
                        'chart_colors' => '#d9b05f, #0f766e, #7dd3fc',
                        'chart_background_color' => '#07110f',
                        'chart_show_values' => true,
                        'width' => 76,
                        'min_height' => 240,
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
            'kpis' => [],
            'top_customers' => [],
        ], app(GeneralSettings::class), $template);
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('data-chart-type="bar"', $bodyHtml);
        $this->assertStringContainsString('<text x="28" y="38" font-size="16" font-weight="700" fill="#fffdf7">Contraste técnico</text>', $bodyHtml);
        $this->assertStringContainsString('stroke="#48615a"', $bodyHtml);
        $this->assertStringContainsString('font-size="11" font-weight="700" fill="#fffdf7">1.5</text>', $bodyHtml);
        $this->assertStringContainsString('font-size="11" font-weight="700" fill="#fffdf7">2.75</text>', $bodyHtml);
        $this->assertStringContainsString('font-size="11" font-weight="700" fill="#fffdf7">3.25</text>', $bodyHtml);
        $this->assertStringContainsString('font-size="10" fill="#d7e3dc">Recepção</text>', $bodyHtml);
        $this->assertStringNotContainsString('>75</text>', $bodyHtml);
        $this->assertStringNotContainsString('font-weight="700" fill="#0f172a">Contraste técnico</text>', $bodyHtml);
    }

    public function test_report_studio_rejects_invalid_chart_configuration(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Invalid Chart Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'active',
                'layout_schema' => [
                    'body_html' => '<h1>Resumo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'invalid-chart',
                            'surface' => 'content',
                            'block_kind' => 'chart_snapshot',
                            'chart_type' => 'radar',
                            'chart_values' => [18, 'not-a-number'],
                            'chart_colors' => ['#143d37', 'gold'],
                            'chart_primary_color' => 'navy',
                            'chart_background_color' => 'transparent',
                        ],
                    ],
                ],
            ]);

        $response->assertSessionHasErrors([
            'layout_schema.canvas_blocks.0.chart_type',
            'layout_schema.canvas_blocks.0.chart_values.1',
            'layout_schema.canvas_blocks.0.chart_colors.1',
            'layout_schema.canvas_blocks.0.chart_primary_color',
            'layout_schema.canvas_blocks.0.chart_background_color',
        ]);
    }

    public function test_media_store_returns_report_studio_asset_payload(): void
    {
        Storage::fake('public');

        $user = $this->verifiedAdmin();

        $response = $this->actingAs($user)->postJson(route('media.store'), [
            'file' => UploadedFile::fake()->create('selo-tecnico.png', 32, 'image/png'),
        ]);

        $media = GestlabMedia::query()->latest('id')->firstOrFail();

        $response
            ->assertOk()
            ->assertJsonPath('id', $media->id)
            ->assertJsonPath('preview_url', $media->preview_url)
            ->assertJsonPath('media.id', $media->id)
            ->assertJsonPath('media.name', 'selo-tecnico.png')
            ->assertJsonPath('media.mime_type', 'image/png')
            ->assertJsonPath('media.file_type', 'image')
            ->assertJsonPath('media.path', $media->path)
            ->assertJsonPath('asset.id', 'gallery-'.$media->id)
            ->assertJsonPath('asset.label', 'selo-tecnico.png')
            ->assertJsonPath('asset.kind', 'gallery_image')
            ->assertJsonPath('asset.source', 'Upload do studio')
            ->assertJsonPath('asset.mime_type', 'image/png')
            ->assertJsonPath('asset.file_type', 'image')
            ->assertJsonPath('asset.url', $media->preview_url)
            ->assertJsonPath('asset.pdf_url', $media->preview_url)
            ->assertJsonPath('asset.author', $user->name);

        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_store_persists_report_studio_asset_role_metadata(): void
    {
        Storage::fake('public');

        $user = $this->verifiedAdmin();

        $response = $this->actingAs($user)->postJson(route('media.store'), [
            'studio_asset_context' => 'report_studio',
            'studio_asset_kind' => 'uploaded_stamp',
            'file' => UploadedFile::fake()->create('carimbo-validacao.svg', 12, 'image/svg+xml'),
        ]);

        $media = GestlabMedia::query()->latest('id')->firstOrFail();

        $response
            ->assertOk()
            ->assertJsonPath('media.studio_asset_kind', 'uploaded_stamp')
            ->assertJsonPath('media.studio_asset_source', 'Carimbos carregados')
            ->assertJsonPath('asset.id', 'gallery-'.$media->id)
            ->assertJsonPath('asset.kind', 'uploaded_stamp')
            ->assertJsonPath('asset.source', 'Carimbos carregados')
            ->assertJsonPath('asset.file_type', 'image');

        $this->assertSame('uploaded_stamp', $media->studio_asset_kind);
        $this->assertSame('Carimbos carregados', $media->studio_asset_source);

        $asset = collect(app(ReportStudioAssetLibrary::class)->assets())
            ->firstWhere('id', 'gallery-'.$media->id);

        $this->assertIsArray($asset);
        $this->assertSame('uploaded_stamp', $asset['kind']);
        $this->assertSame('Carimbos carregados', $asset['source']);

        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_store_returns_json_validation_errors_for_studio_uploads(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->postJson(route('media.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file'])
            ->assertJsonPath('errors.file.0', 'Seleccione um ficheiro para carregar.');

        $this->actingAs($this->verifiedAdmin())
            ->postJson(route('media.store'), [
                'studio_asset_context' => 'report_studio',
                'studio_asset_kind' => 'uploaded_chart',
                'file' => UploadedFile::fake()->create('anexo.pdf', 20, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file'])
            ->assertJsonPath('errors.file.0', 'Use SVG, PNG, JPEG, WebP, GIF ou AVIF para media de estúdio.');
    }

    public function test_report_studio_profile_signature_assets_are_pdf_ready(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Direcção Técnica',
            'email_verified_at' => now(),
        ]);

        $user
            ->addMedia(UploadedFile::fake()->image('assinatura-tecnica.png', 320, 120))
            ->toMediaCollection('signature');

        $asset = collect(app(ReportStudioAssetLibrary::class)->assets())
            ->firstWhere('id', 'signature-'.$user->id);

        $this->assertIsArray($asset);
        $this->assertSame('Direcção Técnica', $asset['label']);
        $this->assertSame('profile_signature', $asset['kind']);
        $this->assertSame('Assinaturas', $asset['source']);
        $this->assertSame($asset['url'], $asset['pdf_url']);
        $this->assertStringContainsString('/storage/', $asset['pdf_url']);

        $method = new ReflectionMethod(ReportStudioPdfBuilder::class, 'resolvePdfImageSource');
        $method->setAccessible(true);

        $this->assertSame(
            public_path(ltrim((string) parse_url($asset['pdf_url'], PHP_URL_PATH), '/')),
            $method->invoke(app(ReportStudioPdfBuilder::class), $asset['pdf_url'])
        );
    }

    public function test_canvas_media_asset_metadata_is_persisted_and_rendered(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Canvas Media Asset Template',
                'studio_type' => 'executive',
                'renderer' => 'internal',
                'status' => 'active',
                'layout_schema' => [
                    'body_html' => '<h1>Resumo executivo</h1>',
                    'canvas_blocks' => [
                        [
                            'id' => 'canvas-image-asset',
                            'title' => 'Selo de aprovação',
                            'surface' => 'content',
                            'block_kind' => 'image',
                            'asset_id' => 'gallery-987',
                            'asset_label' => 'selo-aprovacao.png',
                            'asset_source' => 'Upload do studio',
                            'asset_mime_type' => 'image/png',
                            'asset_size' => 32768,
                            'image_url' => 'https://cdn.example.test/selo-aprovacao.png',
                            'image_alt' => 'Selo de aprovação',
                            'image_fit' => 'contain',
                            'image_position' => '37% 68%',
                            'x' => 8,
                            'y' => 14,
                            'width' => 42,
                            'min_height' => 200,
                            'z_index' => 22,
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertRedirect()->assertSessionHas('success');

        $template = ReportStudioTemplate::query()->where('name', 'Canvas Media Asset Template')->firstOrFail();

        $this->assertSame('gallery-987', data_get($template->layout_schema, 'canvas_blocks.0.asset_id'));
        $this->assertSame('selo-aprovacao.png', data_get($template->layout_schema, 'canvas_blocks.0.asset_label'));
        $this->assertSame('Upload do studio', data_get($template->layout_schema, 'canvas_blocks.0.asset_source'));
        $this->assertSame('image/png', data_get($template->layout_schema, 'canvas_blocks.0.asset_mime_type'));
        $this->assertSame(32768, data_get($template->layout_schema, 'canvas_blocks.0.asset_size'));
        $this->assertSame('37% 68%', data_get($template->layout_schema, 'canvas_blocks.0.image_position'));

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
            'kpis' => [],
            'top_customers' => [],
        ], app(GeneralSettings::class), $template);
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('https://cdn.example.test/selo-aprovacao.png', $bodyHtml);
        $this->assertStringContainsString('Selo de aprovação', $bodyHtml);
        $this->assertStringContainsString('overflow: hidden;', $bodyHtml);
        $this->assertStringContainsString('min-height:inherit;', $bodyHtml);
        $this->assertStringContainsString('object-position:37% 68%', $bodyHtml);
    }

    public function test_pdf_builder_sanitizes_canvas_block_visual_css_values(): void
    {
        $template = new ReportStudioTemplate([
            'name' => 'Unsafe Canvas Import',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<h1>Resumo executivo</h1>',
                'canvas_blocks' => [
                    [
                        'id' => 'unsafe-rich-text',
                        'title' => 'Bloco importado',
                        'surface' => 'content',
                        'block_kind' => 'rich_text',
                        'content_html' => '<p>Conteúdo controlado</p>',
                        'background_color' => '#f8f4ea; background:url(https://bad.example.test/x)',
                        'background_image' => '/storage/report-studios/pattern.svg',
                        'background_image_fit' => 'cover;position:fixed',
                        'background_image_position' => 'center; background:red',
                        'overlay_color' => 'rgba(20,61,55,0.2); color:red',
                        'text_color' => '#143d37; position:fixed',
                        'border_width' => 1,
                        'border_color' => 'url(javascript:alert(1))',
                        'x' => 4,
                        'y' => 8,
                        'width' => 70,
                        'min_height' => 120,
                    ],
                    [
                        'id' => 'unsafe-image',
                        'title' => 'Imagem importada',
                        'surface' => 'content',
                        'block_kind' => 'image',
                        'image_url' => 'https://cdn.example.test/selo.png',
                        'image_position' => '37%; transform:rotate(45deg)',
                        'x' => 12,
                        'y' => 24,
                        'width' => 30,
                        'min_height' => 80,
                    ],
                    [
                        'id' => 'unsafe-signature',
                        'title' => 'Assinatura importada',
                        'surface' => 'content',
                        'block_kind' => 'signature',
                        'signature_image' => 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="50"></svg>'),
                        'signature_image_fit' => 'stretch',
                        'signature_image_position' => '37%; transform:rotate(45deg)',
                        'signature_image_width' => 900,
                        'signature_image_height' => 4,
                        'x' => 48,
                        'y' => 24,
                        'width' => 30,
                        'min_height' => 100,
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
            'kpis' => [],
            'top_customers' => [],
        ], app(GeneralSettings::class), $template);
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('background-size: cover;', $bodyHtml);
        $this->assertStringContainsString('background-position: center center;', $bodyHtml);
        $this->assertStringContainsString('border: 1px solid rgba(148,163,184,0.35);', $bodyHtml);
        $this->assertStringContainsString('object-position:center center;', $bodyHtml);
        $this->assertStringContainsString('width:360px; max-width:100%; height:16px; object-fit:contain; object-position:center center;', $bodyHtml);
        $this->assertStringNotContainsString('background:url(https://bad.example.test/x)', $bodyHtml);
        $this->assertStringNotContainsString('cover;position:fixed', $bodyHtml);
        $this->assertStringNotContainsString('center; background:red', $bodyHtml);
        $this->assertStringNotContainsString('rgba(20,61,55,0.2); color:red', $bodyHtml);
        $this->assertStringNotContainsString('#143d37; position:fixed', $bodyHtml);
        $this->assertStringNotContainsString('url(javascript:alert(1))', $bodyHtml);
        $this->assertStringNotContainsString('37%; transform:rotate(45deg)', $bodyHtml);
        $this->assertStringNotContainsString('object-fit:stretch', $bodyHtml);
    }

    public function test_pdf_builder_escapes_canvas_media_urls_in_html_and_css_contexts(): void
    {
        $template = new ReportStudioTemplate([
            'name' => 'Escaped Canvas Media',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<h1>Resumo executivo</h1>',
                'canvas_blocks' => [
                    [
                        'id' => 'escaped-background',
                        'surface' => 'content',
                        'block_kind' => 'rich_text',
                        'content_html' => '<p>Conteúdo controlado</p>',
                        'background_image' => '/storage/media/fundo.svg" ); color:red; /*',
                        'x' => 4,
                        'y' => 8,
                        'width' => 80,
                        'min_height' => 120,
                    ],
                    [
                        'id' => 'escaped-image',
                        'surface' => 'content',
                        'block_kind' => 'image',
                        'image_url' => '/storage/media/selo.png" onerror="alert(1)',
                        'image_alt' => 'Selo "aprovado"',
                        'x' => 8,
                        'y' => 20,
                        'width' => 30,
                        'min_height' => 80,
                    ],
                    [
                        'id' => 'escaped-signature',
                        'surface' => 'content',
                        'block_kind' => 'signature',
                        'signature_image' => '/storage/signatures/director.png" onerror="alert(2)',
                        'signature_label' => '<strong>Direcção técnica</strong>',
                        'signature_name' => 'Ana <script>alert(4)</script>',
                        'signature_title' => 'Responsável "técnica"',
                        'signature_show_date' => true,
                        'signature_date_label' => 'Data <em>assinada</em>',
                        'x' => 48,
                        'y' => 20,
                        'width' => 30,
                        'min_height' => 100,
                    ],
                    [
                        'id' => 'escaped-chart',
                        'surface' => 'content',
                        'block_kind' => 'chart_snapshot',
                        'chart_title' => 'Tendência "mensal"',
                        'chart_image_url' => 'https://cdn.example.test/chart.png" onerror="alert(3)',
                        'x' => 8,
                        'y' => 40,
                        'width' => 40,
                        'min_height' => 140,
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload([
            'kpis' => [],
            'top_customers' => [],
        ], app(GeneralSettings::class), $template);
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $escapedBackgroundPath = str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            public_path('storage/media/fundo.svg" ); color:red; /*')
        );

        $this->assertStringContainsString('background-image: url("'.$escapedBackgroundPath.'")', $bodyHtml);
        $this->assertStringContainsString('src="'.e(public_path('storage/media/selo.png" onerror="alert(1)')).'"', $bodyHtml);
        $this->assertStringContainsString('alt="Selo &quot;aprovado&quot;"', $bodyHtml);
        $this->assertStringContainsString('src="'.e(public_path('storage/signatures/director.png" onerror="alert(2)')).'"', $bodyHtml);
        $this->assertStringContainsString('&lt;strong&gt;Direcção técnica&lt;/strong&gt;', $bodyHtml);
        $this->assertStringContainsString('Ana &lt;script&gt;alert(4)&lt;/script&gt;', $bodyHtml);
        $this->assertStringContainsString('Responsável &quot;técnica&quot;', $bodyHtml);
        $this->assertStringContainsString('Data &lt;em&gt;assinada&lt;/em&gt;', $bodyHtml);
        $this->assertStringContainsString('src="'.e(public_path('https://cdn.example.test/chart.png" onerror="alert(3)')).'"', $bodyHtml);
        $this->assertStringContainsString('alt="Tendência &quot;mensal&quot;"', $bodyHtml);
        $this->assertStringNotContainsString('" onerror="alert(1)', $bodyHtml);
        $this->assertStringNotContainsString('" onerror="alert(2)', $bodyHtml);
        $this->assertStringNotContainsString('" onerror="alert(3)', $bodyHtml);
        $this->assertStringNotContainsString('<script>alert(4)</script>', $bodyHtml);
        $this->assertStringNotContainsString('background-image: url("'.public_path('storage/media/fundo.svg').'" ); color:red;', $bodyHtml);
    }

    public function test_pdf_builder_resolves_same_host_public_media_urls_to_local_paths(): void
    {
        $originalAppUrl = config('app.url');
        Config::set('app.url', 'https://lims-unleashed.test');

        try {
            $method = new ReflectionMethod(ReportStudioPdfBuilder::class, 'resolvePdfImageSource');
            $method->setAccessible(true);

            $builder = app(ReportStudioPdfBuilder::class);
            $sameHostStorageUrl = 'https://lims-unleashed.test/storage/media/2026/01/01/5/logo.png';
            $relativeStorageUrl = '/storage/media/2026/01/01/5/logo.png';
            $externalStorageUrl = 'https://cdn.example.test/storage/media/2026/01/01/5/logo.png';

            $this->assertSame(
                public_path('storage/media/2026/01/01/5/logo.png'),
                $method->invoke($builder, $sameHostStorageUrl)
            );
            $this->assertSame(
                public_path('storage/media/2026/01/01/5/logo.png'),
                $method->invoke($builder, $relativeStorageUrl)
            );
            $this->assertSame($externalStorageUrl, $method->invoke($builder, $externalStorageUrl));
        } finally {
            Config::set('app.url', $originalAppUrl);
        }
    }

    public function test_admin_can_preview_analysis_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Analysis Report Lab Template',
            'studio_type' => 'analysis',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'laboratorial',
            'description' => 'Template laboratorial com paginação, cabeçalhos e resultados.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Relatório Analítico {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>{report_title}</h1><p><strong>Código:</strong> {certificate_code}</p><section>{results_table}</section><p>{uncertainty_statement}</p><div>{signature_block}</div>',
                'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 24,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 58,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertHeader('X-Report-Studio-Renderer', 'mpdf');
        $response->assertDownload('report-studio-'.$template->id.'-analysis-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_preview_unsaved_report_studio_draft_as_pdf(): void
    {
        $user = $this->verifiedAdmin();
        $templateCount = ReportStudioTemplate::query()->count();

        $response = $this->actingAs($user)->post(route('report-studios.preview-draft-pdf'), [
            'name' => '',
            'studio_type' => 'analysis',
            'renderer' => 'internal',
            'status' => 'draft',
            'is_default' => false,
            'theme_preset' => 'compliance',
            'canva_design_url' => '',
            'description' => 'Rascunho ainda não persistido no estúdio.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Rascunho {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>{report_title}</h1><section>{sample_details}</section><section>{results_table}</section><section>{analysis_chart_card}</section><div>{signature_block}</div>',
                'styles_css' => '.report-table th{background:#143d37;color:#fff;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 24,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 58,
            ],
        ]);

        $response->assertOk();
        $response->assertHeader('X-Report-Studio-Renderer', 'mpdf');
        $response->assertDownload('report-studio-draft-analysis-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
        $this->assertSame($templateCount, ReportStudioTemplate::query()->count());
    }

    public function test_admin_can_preview_analysis_report_studio_without_existing_certificates(): void
    {
        QualityCertificate::query()->whereNotNull('collection_id')->update(['collection_id' => null]);

        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Analysis Report Fallback Template',
            'studio_type' => 'analysis',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'laboratorial',
            'description' => 'Template laboratorial com fallback de pré-visualização.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Relatório Analítico {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>{report_title}</h1><p>{sample_entry_code}</p><section>{sample_details}</section><section>{results_table}</section><p>{decision_rule}</p>',
                'styles_css' => 'body{color:#0f172a;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 24,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 58,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertHeader('X-Report-Studio-Renderer', 'mpdf');
        $response->assertDownload('report-studio-'.$template->id.'-analysis-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_analysis_report_payload_includes_sample_context_and_custom_page_size(): void
    {
        $user = $this->verifiedAdmin();
        $certificate = QualityCertificate::query()->firstOrFail();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Analysis Context Custom Paper',
            'studio_type' => 'analysis',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'laboratorial',
            'description' => 'Template with sample context and custom page geometry.',
            'layout_schema' => [
                'first_page_header_html' => '<div>{{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>{report_title}</h1>{sample_details}{collection_details}{analytical_scope}',
                'document_font_family' => 'Manrope, DejaVu Sans, sans-serif',
                'page_background_color' => '#fff8e7',
            ],
            'export_settings' => [
                'paper_size' => 'custom',
                'custom_page_width' => 210,
                'custom_page_height' => 297,
                'orientation' => 'P',
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildAnalysisReportPayload(
            $certificate,
            app(GeneralSettings::class),
            $template
        );

        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertSame(210, data_get($payload, 'data.customPageWidth'));
        $this->assertSame(297, data_get($payload, 'data.customPageHeight'));
        $this->assertStringContainsString('font-family:Manrope, DejaVu Sans, sans-serif', (string) data_get($payload, 'data.styles'));
        $this->assertStringContainsString('@page{background-color:#fff8e7;}', (string) data_get($payload, 'data.styles'));
        $this->assertStringContainsString('background-color:#fff8e7 !important;', (string) data_get($payload, 'data.styles'));
        $this->assertStringContainsString('Identificação da amostra', $bodyHtml);
        $this->assertStringContainsString('Receção e cadeia de custódia', $bodyHtml);
        $this->assertStringContainsString('Âmbito analítico', $bodyHtml);
        $this->assertStringNotContainsString('{sample_details}', $bodyHtml);
        $this->assertStringNotContainsString('{collection_details}', $bodyHtml);
        $this->assertStringNotContainsString('{analytical_scope}', $bodyHtml);
    }

    public function test_analysis_report_payload_renders_result_state_chart_from_certificate_results(): void
    {
        $user = $this->verifiedAdmin();
        $labCode = LabCode::withoutEvents(fn (): LabCode => LabCode::query()->create([
            'code' => 'LAB-CHART-001',
            'cl_month' => '06/2026',
            'seq' => 1,
        ]));
        $certificate = QualityCertificate::query()->create([
            'user_id' => $user->id,
            'cl_id' => $labCode->id,
            'code' => 'BA-CHART-001',
        ]);
        $now = now();

        Result::query()->create([
            'code_id' => $labCode->id,
            'parameter_label' => 'Humidade',
            'approved_value' => '0.000012',
            'inserted_date' => $now,
            'verified_date' => $now,
            'approved_date' => $now,
            'extra_data' => [
                'display_format' => 'scientific',
            ],
        ]);
        Result::query()->create([
            'code_id' => $labCode->id,
            'parameter_label' => 'Cinzas',
            'inserted_date' => $now,
            'verified_date' => $now,
            'approved_date' => $now,
            'requested_counter_analysis' => true,
        ]);
        Result::query()->create([
            'code_id' => $labCode->id,
            'parameter_label' => 'Salmonella spp.',
            'inserted_date' => $now,
            'verified_date' => $now,
        ]);
        Result::query()->create([
            'code_id' => $labCode->id,
            'parameter_label' => 'Bolores e leveduras',
            'inserted_date' => $now,
        ]);
        Result::query()->create([
            'code_id' => $labCode->id,
            'parameter_label' => 'pH',
        ]);

        $template = new ReportStudioTemplate([
            'name' => 'Analysis Chart Template',
            'studio_type' => 'analysis',
            'renderer' => 'internal',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<h1>{certificate_code}</h1>{results_table}{analysis_chart_card}<p>{analysis_chart_values}</p>',
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildAnalysisReportPayload(
            $certificate,
            app(GeneralSettings::class),
            $template
        );

        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('data-chart-type="bar"', $bodyHtml);
        $this->assertStringContainsString('Estado dos resultados analíticos', $bodyHtml);
        $this->assertStringContainsString('Aprovados', $bodyHtml);
        $this->assertStringContainsString('Contra-análise', $bodyHtml);
        $this->assertStringContainsString('class="report-table studio-avoid-break"', $bodyHtml);
        $this->assertStringContainsString('Método', $bodyHtml);
        $this->assertStringContainsString('Method', $bodyHtml);
        $this->assertStringContainsString('Incerteza', $bodyHtml);
        $this->assertStringContainsString('1.20 × 10^-5', $bodyHtml);
        $this->assertStringContainsString('Contra-análise associada', $bodyHtml);
        $this->assertStringContainsString('2, 1, 1, 1, 1', $bodyHtml);
        $this->assertStringContainsString('5 parâmetro(s) associados ao certificado; 1 com contra-análise solicitada ou registada.', $bodyHtml);
        $this->assertStringNotContainsString('border:1px solid #cbd5e1', $bodyHtml);
        $this->assertStringNotContainsString('border-bottom:1px solid #cbd5e1', $bodyHtml);
        $this->assertStringNotContainsString('{results_table}', $bodyHtml);
        $this->assertStringNotContainsString('{analysis_chart_card}', $bodyHtml);
        $this->assertStringNotContainsString('{analysis_chart_values}', $bodyHtml);
    }

    public function test_chrome_renderer_requires_optional_server_driver_when_saving(): void
    {
        if (class_exists(BrowserFactory::class)) {
            $this->markTestSkipped('The optional chrome-php/chrome driver is installed in this environment.');
        }

        $this->actingAs($this->verifiedAdmin())
            ->post(route('report-studios.store'), [
                'name' => 'Chrome Renderer Guard',
                'studio_type' => 'executive',
                'renderer' => 'chrome',
                'status' => 'active',
                'is_default' => false,
                'theme_preset' => 'corporate',
                'canva_design_url' => null,
                'description' => 'Should not save without the optional Chrome driver.',
                'layout_schema' => [
                    'body_html' => '<h1>Resumo Executivo</h1>',
                ],
                'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
            ])
            ->assertSessionHasErrors('renderer');

        $this->assertDatabaseMissing('report_studio_templates', [
            'name' => 'Chrome Renderer Guard',
        ]);
    }

    public function test_admin_can_save_chrome_renderer_when_optional_driver_is_installed(): void
    {
        if (! class_exists(BrowserFactory::class)) {
            $this->markTestSkipped('The optional chrome-php/chrome driver is not installed in this environment.');
        }

        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('report-studios.store'), [
                'name' => 'Chrome Renderer Template',
                'studio_type' => 'executive',
                'renderer' => 'chrome',
                'status' => 'active',
                'is_default' => false,
                'theme_preset' => 'corporate',
                'canva_design_url' => null,
                'description' => 'Uses the Chrome PHP renderer for browser-grade PDF fidelity.',
                'layout_schema' => [
                    'body_html' => '<h1>Resumo Executivo</h1>',
                    'styles_css' => 'body{display:grid;color:#0f172a;}',
                ],
                'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('report_studio_templates', [
            'name' => 'Chrome Renderer Template',
            'renderer' => 'chrome',
        ]);
    }

    public function test_chrome_renderer_requires_executable_configured_binary(): void
    {
        if (! class_exists(BrowserFactory::class)) {
            $this->markTestSkipped('The optional chrome-php/chrome driver is not installed in this environment.');
        }

        $originalChromeBinary = config('laravel-pdf.chrome.chrome_binary');

        try {
            Config::set('laravel-pdf.chrome.chrome_binary', '/tmp/gestlab-missing-chrome-binary');

            $this->actingAs($this->verifiedAdmin())
                ->post(route('report-studios.store'), [
                    'name' => 'Chrome Missing Binary Guard',
                    'studio_type' => 'executive',
                    'renderer' => 'chrome',
                    'status' => 'active',
                    'is_default' => false,
                    'theme_preset' => 'corporate',
                    'canva_design_url' => null,
                    'description' => 'Should not save with a missing configured Chrome binary.',
                    'layout_schema' => [
                        'body_html' => '<h1>Resumo Executivo</h1>',
                    ],
                    'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
                ])
                ->assertSessionHasErrors('renderer');

            $this->assertDatabaseMissing('report_studio_templates', [
                'name' => 'Chrome Missing Binary Guard',
            ]);
        } finally {
            Config::set('laravel-pdf.chrome.chrome_binary', $originalChromeBinary);
        }
    }

    public function test_admin_can_preview_report_studio_with_configured_chrome_binary(): void
    {
        $chromeBinary = (string) config('laravel-pdf.chrome.chrome_binary');

        if (! class_exists(BrowserFactory::class) || $chromeBinary === '' || ! is_executable($chromeBinary)) {
            $this->markTestSkipped('The configured Chrome PDF binary is not available in this environment.');
        }

        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Chrome End-to-End Preview Template',
            'studio_type' => 'executive',
            'renderer' => 'chrome',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'executive',
            'description' => 'Exercises the configured Chrome binary with first-page and recurring PDF surfaces.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Primeira página {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{issue_date}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<section>Resumo executivo</section><pagebreak /><section>Continuação</section>',
                'canvas_blocks' => [
                    [
                        'id' => 'chrome-positioned-layer',
                        'surface' => 'content',
                        'block_kind' => 'rich_text',
                        'content_html' => '<strong>Camada posicionada</strong>',
                        'page_scope' => 'following',
                        'x' => 12,
                        'y' => 18,
                        'width' => 44,
                        'min_height' => 80,
                    ],
                ],
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 48,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());
        $response->assertHeader('X-Report-Studio-Renderer', 'chrome');
        $response->assertDownload('report-studio-'.$template->id.'-executive-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_chrome_preview_fails_with_clear_client_error_when_driver_is_missing(): void
    {
        if (class_exists(BrowserFactory::class)) {
            $this->markTestSkipped('The optional chrome-php/chrome driver is installed in this environment.');
        }

        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Existing Chrome Preview Guard',
            'studio_type' => 'executive',
            'renderer' => 'chrome',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'executive',
            'description' => 'Legacy template saved before the driver guard existed.',
            'layout_schema' => [
                'body_html' => '<h1>Resumo Executivo</h1>',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_left' => 14,
                'margin_right' => 14,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('report-studios.preview-pdf', $template))
            ->assertStatus(422)
            ->assertSee('chrome-php/chrome');
    }

    public function test_admin_can_preview_executive_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Executive Board Preview Template',
            'studio_type' => 'executive',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'executive',
            'description' => 'Template executivo para pré-visualização PDF.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Resumo Executivo {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{issue_date}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>Resumo Executivo</h1><p>KPIs e seguimento de gestão.</p>',
                'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 42,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertDownload('report-studio-'.$template->id.'-executive-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_preview_proposal_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();
        $settings = app(GeneralSettings::class);
        $original = [
            'app_client_lab_name' => $settings->app_client_lab_name,
            'app_client_lab_director' => $settings->app_client_lab_director,
            'app_bank_name' => $settings->app_bank_name,
            'app_bank_account_name' => $settings->app_bank_account_name,
            'app_bank_account_number' => $settings->app_bank_account_number,
            'app_bank_iban' => $settings->app_bank_iban,
            'app_bank_swift' => $settings->app_bank_swift,
            'app_bank_details' => $settings->app_bank_details,
            'app_document_keywords' => $settings->app_document_keywords,
        ];

        try {
            $settings->fill([
                'app_client_lab_name' => 'Laboratório Report Studio',
                'app_client_lab_director' => 'Direcção Técnica Report Studio',
                'app_bank_name' => 'Banco Report Studio',
                'app_bank_account_name' => 'Laboratório Report Studio',
                'app_bank_account_number' => '0011223344',
                'app_bank_iban' => 'AO06000000000000112233440',
                'app_bank_swift' => 'RPTSAOLU',
                'app_bank_details' => 'Pagamento por transferência com referência da proposta.',
                'app_document_keywords' => 'proposta, report studio, ISO 17025',
            ]);
            $settings->save();

            $template = ReportStudioTemplate::query()->create([
                'name' => 'Proposal Studio Preview Template',
                'studio_type' => 'proposal',
                'renderer' => 'internal',
                'status' => 'active',
                'is_default' => false,
                'theme_preset' => 'corporate',
                'description' => 'Template de proposta para pré-visualização PDF.',
                'layout_schema' => [
                    'first_page_header_html' => '<div>Proposta {{document_code}} · {{lab_name}}</div>',
                    'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                    'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                    'body_html' => '<h1>Proposta Técnica</h1><section>{items_table}</section><section>{summary_table}</section><section>{banking_details}</section><p>{{bank_iban}}</p><p>{bank_account_name}</p><p>{bank_account_number}</p><p>{bank_swift}</p><p>{bank_details}</p><section>{document_keywords}</section><div>{signature_block}</div>',
                    'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
                ],
                'export_settings' => [
                    'paper_size' => 'A4',
                    'orientation' => 'P',
                    'margin_top' => 20,
                    'margin_bottom' => 22,
                    'margin_left' => 14,
                    'margin_right' => 14,
                    'first_page_margin_top' => 56,
                ],
                'created_by_id' => $user->id,
                'updated_by_id' => $user->id,
            ]);

            $payload = app(ReportStudioPdfBuilder::class)->buildProposalStudioPreviewPayload($template, $settings);
            $bodyHtml = $payload['data']['bodyHtml'];

            $this->assertStringContainsString('Banco Report Studio', $bodyHtml);
            $this->assertStringContainsString('AO06000000000000112233440', $bodyHtml);
            $this->assertStringContainsString('Laboratório Report Studio', $bodyHtml);
            $this->assertStringContainsString('0011223344', $bodyHtml);
            $this->assertStringContainsString('RPTSAOLU', $bodyHtml);
            $this->assertStringContainsString('Pagamento por transferência com referência da proposta.', $bodyHtml);
            $this->assertStringContainsString('report studio', $bodyHtml);
            $this->assertStringContainsString('Direcção Técnica Report Studio', $bodyHtml);
            $this->assertStringNotContainsString('{banking_details}', $bodyHtml);
            $this->assertStringNotContainsString('{{bank_iban}}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_account_name}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_account_number}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_swift}', $bodyHtml);
            $this->assertStringNotContainsString('{bank_details}', $bodyHtml);
            $this->assertStringNotContainsString('{signature_block}', $bodyHtml);

            $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

            $response->assertOk();
            $response->assertDownload('report-studio-'.$template->id.'-proposal-preview.pdf');
            $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_admin_can_preview_export_certificate_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Export Certificate Preview Template',
            'studio_type' => 'export_certificate',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'corporate',
            'description' => 'Template de certificado de exportação para pré-visualização PDF.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Certificado {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{exporter_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>Certificado de Exportação</h1><section>{products_table}</section><div>{signature_block}</div>',
                'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 52,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertDownload('report-studio-'.$template->id.'-export-certificate-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_preview_import_certificate_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Import Certificate Preview Template',
            'studio_type' => 'import_certificate',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'corporate',
            'description' => 'Template de certificado de importação para pré-visualização PDF.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Certificado {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{importer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>Certificado de Importação</h1><section>{items_table}</section><div>{signature_block}</div>',
                'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 52,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertDownload('report-studio-'.$template->id.'-import-certificate-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_preview_quote_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Quote Preview Template',
            'studio_type' => 'quote',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'corporate',
            'description' => 'Template de proforma para pré-visualização PDF.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Proforma {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>Proforma</h1><section>{items_table}</section><section>{summary_table}</section><div>{signature_block}</div>',
                'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 22,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 56,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertDownload('report-studio-'.$template->id.'-quote-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_preview_invoice_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Invoice Preview Template',
            'studio_type' => 'invoice',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'corporate',
            'description' => 'Template de factura para pré-visualização PDF.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Factura {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>Factura</h1><section>{items_table}</section><section>{summary_table}</section><div>{signature_block}</div>',
                'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 22,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 56,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertDownload('report-studio-'.$template->id.'-invoice-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_preview_receipt_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Receipt Preview Template',
            'studio_type' => 'receipt',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'corporate',
            'description' => 'Template de recibo para pré-visualização PDF.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Recibo {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>Recibo</h1><section>{items_table}</section><section>{summary_table}</section><div>{signature_block}</div>',
                'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 22,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 56,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertDownload('report-studio-'.$template->id.'-receipt-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_preview_credit_note_report_studio_as_pdf(): void
    {
        $user = $this->verifiedAdmin();

        $template = ReportStudioTemplate::query()->create([
            'name' => 'Credit Note Preview Template',
            'studio_type' => 'credit_note',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => false,
            'theme_preset' => 'corporate',
            'description' => 'Template de nota de crédito para pré-visualização PDF.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Nota {{document_code}}</div>',
                'default_header_html' => '<div>{{document_code}} · {{customer_name}}</div>',
                'footer_html' => '<div>Página {PAGENO}/{nbpg}</div>',
                'body_html' => '<h1>Nota de Crédito</h1><section>{items_table}</section><section>{summary_table}</section><div>{signature_block}</div>',
                'styles_css' => 'body{color:#0f172a;} h1{font-size:18px;}',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
                'margin_top' => 20,
                'margin_bottom' => 22,
                'margin_left' => 14,
                'margin_right' => 14,
                'first_page_margin_top' => 56,
            ],
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('report-studios.preview-pdf', $template));

        $response->assertOk();
        $response->assertDownload('report-studio-'.$template->id.'-credit-note-preview.pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_export_executive_dashboard_as_pdf(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->get(route('dashboard.export', ['format' => 'pdf']));

        $response->assertOk();
        $response->assertHeader('X-Report-Studio-Renderer');
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_admin_can_create_proposal_studio_template(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->post(route('report-studios.store'), [
                'name' => 'Proposal Premium Layout',
                'studio_type' => 'proposal',
                'renderer' => 'internal',
                'status' => 'active',
                'is_default' => true,
                'theme_preset' => 'corporate',
                'canva_design_url' => null,
                'description' => 'Proposal multipage layout',
                'layout_schema' => [
                    'first_page_header_html' => '<div>Proposal cover</div>',
                    'default_header_html' => '<div>Proposal header</div>',
                    'footer_html' => '<div>Page {PAGENO}/{nbpg}</div>',
                ],
                'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('report_studio_templates', [
            'name' => 'Proposal Premium Layout',
            'studio_type' => 'proposal',
        ]);
    }
}
