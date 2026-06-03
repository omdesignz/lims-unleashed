<?php

namespace Tests\Feature;

use App\Models\GestlabMedia;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\ReportStudioTemplate;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioAssetLibrary;
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
                ->where('systemPresets.0.layout_schema.variable_catalog.0.value', '{document_code}')
                ->where('systemPresets.0.layout_schema.canvas_blocks.0.block_kind', 'qr_code')
                ->where('systemPresets.0.layout_schema.canvas_blocks.1.block_kind', 'signature')
                ->where('systemPresets.0.layout_schema.canvas_blocks.2.id', 'analysis-decision-rule-note')
                ->where('systemPresets.0.layout_schema.body_html', fn (string $bodyHtml): bool => str_contains($bodyHtml, '{results_table}'))
                ->where('systemPresets.0.export_settings.paper_size', 'A4')
            );
    }

    public function test_system_presets_include_typed_variable_catalogs(): void
    {
        $presets = collect(ReportStudioDefaultTemplates::presets())->keyBy('category');

        $analysisVariables = collect(data_get($presets->get('analysis'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $proposalVariables = collect(data_get($presets->get('proposal'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $exportVariables = collect(data_get($presets->get('export_certificate'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $importVariables = collect(data_get($presets->get('import_certificate'), 'layout_schema.variable_catalog'))->pluck('value')->all();
        $invoiceVariables = collect(data_get($presets->get('invoice'), 'layout_schema.variable_catalog'))->pluck('value')->all();

        $this->assertContains('{results_table}', $analysisVariables);
        $this->assertContains('{uncertainty_statement}', $analysisVariables);
        $this->assertContains('{sample_entry_code}', $analysisVariables);
        $this->assertContains('{banking_details}', $proposalVariables);
        $this->assertContains('{proposal_content}', $proposalVariables);
        $this->assertContains('{products_table}', $exportVariables);
        $this->assertContains('{items_table}', $importVariables);
        $this->assertContains('{due_date}', $invoiceVariables);
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
        $this->assertContains('executive-studio-chart', $executiveBlocks);
        $this->assertContains('proposal-banking-details', $proposalBlocks);
        $this->assertContains('proposal-client-acceptance', $proposalBlocks);
        $this->assertContains('invoice-banking-details', $invoiceBlocks);
        $this->assertSame('chart_snapshot', data_get($presets->get('executive'), 'layout_schema.canvas_blocks.2.block_kind'));
        $this->assertSame('signature', data_get($presets->get('proposal'), 'layout_schema.canvas_blocks.3.block_kind'));
    }

    public function test_system_presets_include_backend_body_templates_for_generated_documents(): void
    {
        $presets = collect(ReportStudioDefaultTemplates::presets())->keyBy('category');

        $this->assertStringContainsString('{results_table}', data_get($presets->get('analysis'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{executive_charts}', data_get($presets->get('executive'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{banking_details}', data_get($presets->get('proposal'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{proposal_content}', data_get($presets->get('proposal'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{products_table}', data_get($presets->get('export_certificate'), 'layout_schema.body_html'));
        $this->assertStringContainsString('{items_table}', data_get($presets->get('import_certificate'), 'layout_schema.body_html'));
        $this->assertStringContainsString('Proforma {quote_number}', data_get($presets->get('quote'), 'layout_schema.body_html'));
        $this->assertStringContainsString('Factura {document_number}', data_get($presets->get('invoice'), 'layout_schema.body_html'));
        $this->assertStringContainsString('Recibo {document_number}', data_get($presets->get('receipt'), 'layout_schema.body_html'));
        $this->assertStringContainsString('Nota de crédito {document_number}', data_get($presets->get('credit_note'), 'layout_schema.body_html'));
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

        $this->assertTrue((bool) data_get($template->layout_schema, 'show_canvas_grid'));
        $this->assertTrue((bool) data_get($template->layout_schema, 'show_canvas_rulers'));
        $this->assertTrue((bool) data_get($template->layout_schema, 'snap_to_grid'));
        $this->assertSame(12, data_get($template->layout_schema, 'snap_grid_size'));
        $this->assertTrue((bool) data_get($template->layout_schema, 'page_safe_area'));
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

    public function test_commercial_canvas_blocks_resolve_banking_details_in_generated_payloads(): void
    {
        $quote = Quote::query()->firstOrFail();
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
                        'content_html' => '<div class="banking-marker">{banking_details}</div>',
                    ],
                ],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $payload = app(ReportStudioPdfBuilder::class)->buildQuotePayload(
            $quote,
            app(GeneralSettings::class),
            $studio
        );
        $bodyHtml = (string) data_get($payload, 'data.bodyHtml');

        $this->assertStringContainsString('banking-marker', $bodyHtml);
        $this->assertStringContainsString('Dados bancários', $bodyHtml);
        $this->assertStringNotContainsString('{banking_details}', $bodyHtml);
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

    public function test_canvas_geometry_uses_custom_landscape_page_and_print_margins(): void
    {
        $method = new ReflectionMethod(ReportStudioPdfRenderer::class, 'withPrintableCanvasGeometry');
        $method->setAccessible(true);

        $payload = $method->invoke(app(ReportStudioPdfRenderer::class), [
            'view' => 'PDFs.studios.document',
            'data' => [
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

    public function test_chrome_header_footer_templates_follow_configured_pdf_margins(): void
    {
        $headerHtml = view('PDFs.studios.chrome-header', [
            'headerHtml' => '<div>Header</div>',
            'fontFamily' => 'Manrope, DejaVu Sans, sans-serif',
            'marginLeft' => 18,
            'marginRight' => 10,
        ])->render();
        $footerHtml = view('PDFs.studios.chrome-footer', [
            'footerHtml' => '<div>Footer</div>',
            'fontFamily' => 'Manrope, DejaVu Sans, sans-serif',
            'marginLeft' => 18,
            'marginRight' => 10,
        ])->render();

        $this->assertStringContainsString('padding: 0 10mm 0 18mm', $headerHtml);
        $this->assertStringContainsString('font-family: Manrope, DejaVu Sans, sans-serif', $headerHtml);
        $this->assertStringContainsString('padding: 0 10mm 0 18mm', $footerHtml);
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

        $response->assertRedirect()->assertSessionHas('success');

        $template = ReportStudioTemplate::query()->where('name', 'Overlay Stamp Signature Template')->firstOrFail();
        $this->assertTrue((bool) data_get($template->layout_schema, 'canvas_blocks.4.is_hidden'));
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
                        ],
                    ],
                ],
                'export_settings' => ['paper_size' => 'A4'],
            ]);

        $response->assertSessionHasErrors([
            'layout_schema.canvas_blocks.0.rotation_deg',
            'layout_schema.canvas_blocks.0.shadow_preset',
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
                    'table_header_background' => '#f8f4ea; background:red',
                    'table_header_text_color' => 'rgba(20,61,55,0.95); font-size:80px',
                    'table_border_color' => 'url(javascript:alert(1))',
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
        $this->assertStringContainsString('#143d37', $bodyHtml);
        $this->assertStringContainsString('#f8f4ea', $bodyHtml);
        $this->assertStringContainsString('Fluxo técnico', $bodyHtml);
        $this->assertStringContainsString('Recepção', $bodyHtml);
        $this->assertStringContainsString('Validação', $bodyHtml);
        $this->assertStringContainsString('Emissão', $bodyHtml);
        $this->assertStringContainsString('Recepção, validação e emissão no período.', $bodyHtml);
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

    public function test_media_store_returns_json_validation_errors_for_studio_uploads(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->postJson(route('media.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file'])
            ->assertJsonPath('errors.file.0', 'Seleccione um ficheiro para carregar.');
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
        $this->assertStringNotContainsString('background:url(https://bad.example.test/x)', $bodyHtml);
        $this->assertStringNotContainsString('cover;position:fixed', $bodyHtml);
        $this->assertStringNotContainsString('center; background:red', $bodyHtml);
        $this->assertStringNotContainsString('rgba(20,61,55,0.2); color:red', $bodyHtml);
        $this->assertStringNotContainsString('#143d37; position:fixed', $bodyHtml);
        $this->assertStringNotContainsString('url(javascript:alert(1))', $bodyHtml);
        $this->assertStringNotContainsString('37%; transform:rotate(45deg)', $bodyHtml);
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
        $this->assertStringContainsString('Identificação da amostra', $bodyHtml);
        $this->assertStringContainsString('Receção e cadeia de custódia', $bodyHtml);
        $this->assertStringContainsString('Escopo analítico', $bodyHtml);
        $this->assertStringNotContainsString('{sample_details}', $bodyHtml);
        $this->assertStringNotContainsString('{collection_details}', $bodyHtml);
        $this->assertStringNotContainsString('{analytical_scope}', $bodyHtml);
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
                    'body_html' => '<h1>Proposta Técnica</h1><section>{items_table}</section><section>{summary_table}</section><section>{banking_details}</section><p>{{bank_iban}}</p><section>{document_keywords}</section><div>{signature_block}</div>',
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
            $this->assertStringContainsString('report studio', $bodyHtml);
            $this->assertStringContainsString('Direcção Técnica Report Studio', $bodyHtml);
            $this->assertStringNotContainsString('{banking_details}', $bodyHtml);
            $this->assertStringNotContainsString('{{bank_iban}}', $bodyHtml);
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
