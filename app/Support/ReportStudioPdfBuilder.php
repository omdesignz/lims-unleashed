<?php

namespace App\Support;

use App\Models\CreditNote;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\ReportStudioTemplate;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Settings\GeneralSettings;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

class ReportStudioPdfBuilder
{
    private const PAGE_BREAK_PATTERN = '/<pagebreak\b[^>]*\/?>/i';

    private const DEFAULT_CHART_PALETTE = ['#143d37', '#d9b05f', '#0f766e', '#475569', '#7c2d12', '#3f6f58'];

    public function buildAnalysisReportPayload(
        QualityCertificate $certificate,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $certificate->loadMissing([
            'collection.product.matrix',
            'collection.packaging',
            'collection.sampleEntry.customerRequest',
            'collection.code',
            'results.parameter',
            'results.unit',
            'results.profile',
            'results.standard',
            'results.protocol',
            'results.counter_analysis',
        ]);

        $studio = $this->resolveStudio('analysis', $overrideStudio);
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $placeholderValues = $this->analysisPlaceholderValues($certificate, $settings);
        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $certificate->code,
            'customer_name' => $certificate->customer?->name,
            'warehouse_name' => $certificate->warehouse?->name,
            'issue_date' => optional($certificate->created_at)->format('d/m/Y'),
        ];
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));

        $resultId = data_get($certificate->collection, 'result_id');
        $bodyView = $resultId
            ? "PDFs.includes.analysisreport.templates.{$resultId}"
            : null;
        $defaultBodyHtml = $bodyView && View::exists($bodyView)
            ? View::make($bodyView, ['model' => $certificate])->render()
            : $this->defaultAnalysisBodyHtml();
        $bodyHtml = data_get($layout, 'body_html') ?: $defaultBodyHtml;
        $bodyHtml = $this->renderTemplateHtml((string) $bodyHtml, $placeholderValues);

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Relatório Analítico',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultAnalysisFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · Emitido em {{issue_date}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultAnalysisFooter($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 24),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 58),
                ],
            ],
        ];
    }

    public function buildAnalysisStudioPreviewPayload(
        ReportStudioTemplate $studio,
        GeneralSettings $settings
    ): array {
        $layout = $studio->layout_schema ?? [];
        $export = $studio->export_settings ?? [];
        $placeholderValues = $this->analysisPreviewPlaceholderValues($settings);
        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $placeholderValues['{document_code}'],
            'customer_name' => $placeholderValues['{customer_name}'],
            'warehouse_name' => $placeholderValues['{warehouse_name}'],
            'issue_date' => $placeholderValues['{issue_date}'],
        ];
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));
        $bodyHtml = $this->renderTemplateHtml(
            (string) (data_get($layout, 'body_html') ?: $this->defaultAnalysisBodyHtml()),
            $placeholderValues
        );

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio->name ?: 'Pré-visualização de Relatório Analítico',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultAnalysisFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · Emitido em {{issue_date}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultAnalysisFooter($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 24),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 58),
                ],
            ],
        ];
    }

    public function buildExecutiveReportPayload(
        array $payload,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $studio = $this->resolveStudio('executive', $overrideStudio);
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => 'EXEC-'.now()->format('Ymd'),
            'issue_date' => now()->format('d/m/Y'),
        ];

        $bodyHtml = data_get($layout, 'body_html');
        $placeholderValues = $this->executivePlaceholderValues($payload);
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));

        if (! filled($bodyHtml)) {
            $bodyHtml = View::make('PDFs.studios.executive-summary-body', [
                'payload' => $payload,
            ])->render();
        } else {
            $bodyHtml = $this->renderTemplateHtml((string) $bodyHtml, $placeholderValues);
        }

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Relatório Executivo',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: '<h2 style="margin:0;">{{lab_name}}</h2><p style="margin-top:6px;">Relatório executivo emitido em {{issue_date}}</p>',
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">Resumo executivo · {{issue_date}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: '<div style="font-size:9px; color:#475a53;">Documento reservado para gestão. Página {PAGENO}/{nbpg}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 20),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 42),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function executivePlaceholderValues(array $payload): array
    {
        $chart = $this->executiveCanvasChartData($payload);

        return [
            '{executive_summary}' => e((string) data_get($payload, 'executive_summary', 'Resumo consolidado de desempenho, risco e capacidade operacional.')),
            '{executive_kpis}' => View::make('PDFs.studios.partials.executive-kpis', [
                'payload' => $payload,
            ])->render(),
            '{executive_charts}' => View::make('PDFs.studios.partials.executive-charts', [
                'payload' => $payload,
            ])->render(),
            '{top_customers_table}' => View::make('PDFs.studios.partials.executive-customers', [
                'payload' => $payload,
            ])->render(),
            '{executive_chart_title}' => $chart['title'],
            '{executive_chart_labels}' => implode(', ', $chart['labels']),
            '{executive_chart_values}' => implode(', ', $chart['values']),
            '{executive_chart_caption}' => $chart['caption'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{title: string, labels: array<int, string>, values: array<int, string>, caption: string}
     */
    private function executiveCanvasChartData(array $payload): array
    {
        $chart = data_get($payload, 'charts.throughput', []);
        $labels = collect(data_get($chart, 'labels', ['Recepção', 'Preparação', 'Ensaio', 'Verificação', 'Emissão']))
            ->map(fn (mixed $label): string => trim((string) $label))
            ->filter()
            ->take(12)
            ->values()
            ->all();
        $values = collect(data_get($chart, 'series', [24, 21, 18, 16, 12]))
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): string => $this->formatChartValue((float) $value))
            ->take(12)
            ->values()
            ->all();

        return [
            'title' => trim((string) data_get($chart, 'title', 'Capacidade técnica por etapa')) ?: 'Capacidade técnica por etapa',
            'labels' => $labels !== [] ? $labels : ['Recepção', 'Preparação', 'Ensaio', 'Verificação', 'Emissão'],
            'values' => $values !== [] ? $values : ['24', '21', '18', '16', '12'],
            'caption' => trim((string) data_get($chart, 'caption', 'Dados provenientes da série executiva do período seleccionado.')) ?: 'Dados provenientes da série executiva do período seleccionado.',
        ];
    }

    public function buildProposalPayload(
        VAPProposal $proposal,
        string $parsedContent,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $studio = $this->resolveStudio('proposal', $overrideStudio);
        $templateLayout = $this->normalizeStructuredArray($proposal->template?->layout_schema);
        $templateExport = $this->normalizeStructuredArray($proposal->template?->export_settings);
        $layout = $this->mergeLayoutSchema($studio?->layout_schema ?? [], $templateLayout);
        $export = array_replace($studio?->export_settings ?? [], $templateExport);

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $proposal->proposal_no ?: $proposal->proposal_number ?: ('PROP-'.$proposal->id),
            'customer_name' => $proposal->customer?->name,
            'customer_code' => $proposal->customer?->code,
            'service_location' => $proposal->service_location,
            'department' => $proposal->department?->name,
            'warehouse' => $proposal->warehouse?->address ?: $proposal->warehouse?->name,
            'issue_date' => optional($proposal->created_at)->format('d/m/Y'),
            'expiry_date' => optional($proposal->expiry_date)->format('d/m/Y'),
            'bank_name' => $settings->app_bank_name,
            'bank_iban' => $settings->app_bank_iban,
            'document_keywords' => $settings->app_document_keywords,
        ];
        $proposalPlaceholderValues = array_merge(
            VAPProposalTemplate::placeholderValues($proposal, $settings),
            $this->bankPlaceholderValues($settings),
            [
                '{proposal_content}' => $parsedContent,
                '{parsed_content}' => $parsedContent,
                '{banking_details}' => $this->bankingDetailsHtml($settings),
                '{document_keywords}' => $this->documentKeywordsHtml($settings, 'proposta, análise, laboratório, ISO 17025'),
            ]
        );
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($proposalPlaceholderValues));

        $bodyTemplate = data_get($layout, 'body_html');
        $bodyHtml = filled($bodyTemplate)
            ? $this->renderTemplateHtml((string) $bodyTemplate, $proposalPlaceholderValues)
            : View::make('PDFs.studios.proposal-body', [
                'proposal' => $proposal,
                'parsedContent' => $parsedContent,
                'settings' => $settings,
                'proposalAuthenticity' => $proposalPlaceholderValues['{proposal_authenticity}'] ?? '',
                'proposalAcceptanceEvidence' => $proposalPlaceholderValues['{proposal_acceptance_evidence}'] ?? '',
            ])->render();

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $proposal->template?->name ?? $studio?->name ?? 'Proposta Comercial',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultProposalFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultProposalFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    public function buildProposalTemplatePayload(VAPProposalTemplate $template, GeneralSettings $settings): array
    {
        $studio = $this->resolveStudio('proposal');
        $templateLayout = $this->normalizeStructuredArray($template->layout_schema);
        $templateExport = $this->normalizeStructuredArray($template->export_settings);
        $layout = $this->mergeLayoutSchema($studio?->layout_schema ?? [], $templateLayout);
        $export = array_replace($studio?->export_settings ?? [], $templateExport);

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => 'TPL-'.str_pad((string) $template->id, 4, '0', STR_PAD_LEFT),
            'customer_name' => 'Cliente industrial',
            'issue_date' => now()->format('d/m/Y'),
            'expiry_date' => now()->addDays(15)->format('d/m/Y'),
        ];

        $previewValues = $this->proposalPreviewPlaceholderValues(
            $settings,
            $template->user?->name ?? 'Utilizador do sistema',
            'Pré-visualização do modelo com estrutura editorial, blocos de assinatura e variáveis activas.'
        );
        $parsedContent = $this->renderTemplateHtml($template->content, $previewValues);
        $previewValues['{proposal_content}'] = $parsedContent;
        $previewValues['{parsed_content}'] = $parsedContent;
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($previewValues));

        $bodyTemplate = data_get($layout, 'body_html');
        $bodyHtml = $this->renderTemplateHtml(
            filled($bodyTemplate) ? (string) $bodyTemplate : $template->content,
            $previewValues
        );

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $template->name,
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultProposalFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · Template de proposta</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultProposalFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    public function buildProposalStudioPreviewPayload(
        ReportStudioTemplate $studio,
        GeneralSettings $settings
    ): array {
        $layout = $studio->layout_schema ?? [];
        $export = $studio->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => 'PROP-2026-001',
            'customer_name' => 'Cliente industrial, Lda.',
            'issue_date' => now()->format('d/m/Y'),
            'expiry_date' => now()->addDays(15)->format('d/m/Y'),
        ];

        $previewValues = $this->proposalPreviewPlaceholderValues(
            $settings,
            'Direcção Comercial',
            'Pré-visualização do estúdio de proposta com paginação, composição editorial e assinatura.'
        );
        $previewValues['{proposal_content}'] = '<section class="document-callout studio-avoid-break"><strong>Cláusulas técnicas e comerciais</strong><br>Âmbito, métodos, decisão, prazos e aceite preparados para validação do cliente.</section>';
        $previewValues['{parsed_content}'] = $previewValues['{proposal_content}'];
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($previewValues));
        $bodyHtml = $this->renderTemplateHtml(
            (string) (data_get($layout, 'body_html') ?: $this->defaultProposalPreviewBodyHtml()),
            $previewValues
        );

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio->name ?: 'Pré-visualização de proposta',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultProposalFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultProposalFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    public function buildExportCertificatePayload(
        ExportCertificate $certificate,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $studio = $this->resolveStudio('export_certificate', $overrideStudio);
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $certificate->cert_no,
            'customer_name' => $certificate->exporter?->name,
            'issue_date' => optional($certificate->date ?? $certificate->created_at)->format('d/m/Y'),
            'exporter_name' => $certificate->exporter?->name,
            'origin_country' => $certificate->country_origin?->name,
            'destination_country' => $certificate->country_destination?->name,
        ];

        $placeholderValues = $this->exportCertificatePlaceholderValues($certificate, $settings);
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));
        $bodyHtml = data_get($layout, 'body_html') ?: $this->defaultExportCertificateBodyHtml();
        $bodyHtml = $this->renderTemplateHtml((string) $bodyHtml, $placeholderValues);

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Certificado de Exportação',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultExportCertificateFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{exporter_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultExportCertificateFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 20),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 52),
                ],
            ],
        ];
    }

    public function buildExportCertificatePreviewPayload(
        ReportStudioTemplate $studio,
        GeneralSettings $settings
    ): array {
        $layout = $studio->layout_schema ?? [];
        $export = $studio->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => 'EXP-2026-0041',
            'customer_name' => 'Exportador alimentar certificado, Lda.',
            'issue_date' => now()->format('d/m/Y'),
            'exporter_name' => 'Exportador alimentar certificado, Lda.',
            'origin_country' => 'Angola',
            'destination_country' => 'Namíbia',
        ];

        $bodyHtml = $this->renderTemplateHtml(
            data_get($layout, 'body_html') ?: $this->defaultExportCertificateBodyHtml(),
            [
                '{certificate_number}' => 'EXP-2026-0041',
                '{exporter_name}' => 'Exportador alimentar certificado, Lda.',
                '{origin_country}' => 'Angola',
                '{destination_country}' => 'Namíbia',
                '{origin_city}' => 'Luanda',
                '{destination_city}' => 'Windhoek',
                '{transport_type}' => 'Rodoviário',
                '{authorized_personnel}' => 'Direcção Técnica',
                '{expedition_date}' => now()->format('d/m/Y'),
                '{expedition_location}' => 'Porto de Luanda',
                '{products_table}' => $this->reportTableHtml(
                    [
                        ['label' => 'Produto', 'translation' => 'Product'],
                        ['label' => 'Quantidade', 'translation' => 'Quantity', 'align' => 'right'],
                    ],
                    [
                        ['Milho', '250 sacos'],
                        ['Farinha', '120 caixas'],
                    ]
                ),
                '{remarks}' => 'Pré-visualização do certificado de exportação com composição editorial, dados logísticos e assinatura.',
                '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Técnica</strong><br />Validação do certificado de exportação</div>',
                '{lab_details}' => $this->labDetailsHtml($settings),
                '{customer_details}' => $this->customerDetailsHtml(null, 'Exportador alimentar certificado, Lda.'),
                '{document_keywords}' => $this->documentKeywordsHtml($settings, 'certificado, exportação, rastreabilidade, controlo documental'),
            ]
        );

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio->name ?: 'Pré-visualização de certificado de exportação',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultExportCertificateFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $headerContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{exporter_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $headerContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultExportCertificateFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $headerContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $headerContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 20),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 52),
                ],
            ],
        ];
    }

    public function buildImportCertificatePayload(
        ImportCertificate $certificate,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $studio = $this->resolveStudio('import_certificate', $overrideStudio);
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $certificate->cert_no,
            'customer_name' => $certificate->importer?->name,
            'issue_date' => optional($certificate->date ?? $certificate->created_at)->format('d/m/Y'),
            'importer_name' => $certificate->importer?->name,
            'exporter_name' => $certificate->exporter?->name,
            'destination_country' => $certificate->destination_country?->name,
        ];

        $placeholderValues = $this->importCertificatePlaceholderValues($certificate, $settings);
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));
        $bodyHtml = data_get($layout, 'body_html') ?: $this->defaultImportCertificateBodyHtml();
        $bodyHtml = $this->renderTemplateHtml((string) $bodyHtml, $placeholderValues);

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Certificado de Importação',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultImportCertificateFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{importer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultImportCertificateFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 20),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 52),
                ],
            ],
        ];
    }

    public function buildImportCertificatePreviewPayload(
        ReportStudioTemplate $studio,
        GeneralSettings $settings
    ): array {
        $layout = $studio->layout_schema ?? [];
        $export = $studio->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => 'IMP-2026-0038',
            'customer_name' => 'Importador alimentar certificado, Lda.',
            'issue_date' => now()->format('d/m/Y'),
            'importer_name' => 'Importador alimentar certificado, Lda.',
            'exporter_name' => 'Fornecedor Internacional GmbH',
            'destination_country' => 'Angola',
        ];

        $bodyHtml = $this->renderTemplateHtml(
            data_get($layout, 'body_html') ?: $this->defaultImportCertificateBodyHtml(),
            [
                '{certificate_number}' => 'IMP-2026-0038',
                '{importer_name}' => 'Importador alimentar certificado, Lda.',
                '{exporter_name}' => 'Fornecedor Internacional GmbH',
                '{destination_country}' => 'Angola',
                '{port_entry}' => 'Porto de Luanda',
                '{port_exit}' => 'Porto de Hamburgo',
                '{transport_type}' => 'Marítimo',
                '{authorized_personnel}' => 'Direcção Técnica',
                '{issue_date}' => now()->format('d/m/Y'),
                '{items_table}' => $this->reportTableHtml(
                    [
                        ['label' => 'Produto', 'translation' => 'Product'],
                        ['label' => 'Lote', 'translation' => 'Lot'],
                        ['label' => 'Validade', 'translation' => 'Validity'],
                        ['label' => 'Quantidade', 'translation' => 'Quantity', 'align' => 'right'],
                    ],
                    [
                        ['Aditivo alimentar', 'LT-8841', '12/2027', '48 caixas'],
                        ['Reagente técnico', 'RG-2017', '08/2028', '20 bidões'],
                    ]
                ),
                '{remarks}' => 'Pré-visualização do certificado de importação com composição logística, lotes, validade e assinatura técnica.',
                '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Técnica</strong><br />Validação do certificado de importação</div>',
                '{lab_details}' => $this->labDetailsHtml($settings),
                '{customer_details}' => $this->customerDetailsHtml(null, 'Importador alimentar certificado, Lda.'),
                '{document_keywords}' => $this->documentKeywordsHtml($settings, 'certificado, importação, rastreabilidade, controlo documental'),
            ]
        );

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio->name ?: 'Pré-visualização de certificado de importação',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultImportCertificateFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $headerContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{importer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $headerContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultImportCertificateFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $headerContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $headerContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 20),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 52),
                ],
            ],
        ];
    }

    public function buildQuotePayload(
        Quote $quote,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $studio = $this->resolveStudio('quote', $overrideStudio);
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $quote->quote_no,
            'customer_name' => $quote->customer?->name,
            'issue_date' => optional($quote->date ?? $quote->created_at)->format('d/m/Y'),
            'service_location' => $quote->warehouse?->name,
            'expiry_date' => optional($quote->due_date)->format('d/m/Y'),
        ];

        $placeholderValues = $this->quotePlaceholderValues($quote, $settings);
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));
        $bodyHtml = data_get($layout, 'body_html') ?: $this->defaultQuoteBodyHtml();
        $bodyHtml = $this->renderTemplateHtml((string) $bodyHtml, $placeholderValues);

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Proforma / Orçamento',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultQuoteFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultQuoteFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    public function buildQuotePreviewPayload(
        ReportStudioTemplate $studio,
        GeneralSettings $settings
    ): array {
        $layout = $studio->layout_schema ?? [];
        $export = $studio->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => 'PP 05/2026/0048',
            'customer_name' => 'Cliente industrial de referência, Lda.',
            'issue_date' => now()->format('d/m/Y'),
            'service_location' => 'Luanda · Unidade Industrial',
            'expiry_date' => now()->addDays(15)->format('d/m/Y'),
        ];

        $placeholderValues = [
            '{quote_number}' => 'PP 05/2026/0048',
            '{document_number}' => 'PP 05/2026/0048',
            '{customer_name}' => 'Cliente industrial de referência, Lda.',
            '{service_location}' => 'Luanda · Unidade Industrial',
            '{issue_date}' => now()->format('d/m/Y'),
            '{expiry_date}' => now()->addDays(15)->format('d/m/Y'),
            '{items_table}' => $this->reportTableHtml(
                [
                    ['label' => 'Serviço', 'translation' => 'Service'],
                    ['label' => 'Qtd.', 'translation' => 'Qty.', 'align' => 'right'],
                    ['label' => 'Valor', 'translation' => 'Amount', 'align' => 'right'],
                ],
                [
                    ['Ensaios microbiológicos', '1', 'AOA 52.500,00'],
                    ['Metais pesados', '1', 'AOA 45.000,00'],
                ]
            ),
            '{summary_table}' => $this->financialSummaryTableHtml([
                ['label' => 'Subtotal', 'value' => 'AOA 97.500,00'],
                ['label' => 'IVA', 'value' => 'AOA 13.650,00'],
                ['label' => 'Total', 'value' => 'AOA 111.150,00', 'emphasis' => true],
            ]),
            '{observations}' => 'Pré-visualização de proforma com composição comercial, paginação, resumo financeiro e assinatura.',
            '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Comercial</strong><br />Validação e emissão da proforma</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml(null, 'Cliente industrial de referência, Lda.'),
            '{banking_details}' => $this->bankingDetailsHtml($settings),
            ...$this->bankPlaceholderValues($settings),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'comercial, proforma, proposta'),
        ];
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));
        $bodyHtml = $this->renderTemplateHtml(
            data_get($layout, 'body_html') ?: $this->defaultQuoteBodyHtml(),
            $placeholderValues
        );

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio->name ?: 'Pré-visualização de proforma',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultQuoteFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultQuoteFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    public function buildInvoicePayload(
        Invoice $invoice,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $studio = $this->resolveStudio('invoice', $overrideStudio);
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $invoice->inv_no,
            'customer_name' => $invoice->customer?->name,
            'issue_date' => optional($invoice->date ?? $invoice->created_at)->format('d/m/Y'),
            'due_date' => optional($invoice->due_date)->format('d/m/Y'),
            'service_location' => $invoice->warehouse?->name,
        ];

        $placeholderValues = $this->invoicePlaceholderValues($invoice, $settings);
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));
        $bodyHtml = data_get($layout, 'body_html') ?: $this->defaultInvoiceBodyHtml();
        $bodyHtml = $this->renderTemplateHtml((string) $bodyHtml, $placeholderValues);

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Factura',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultInvoiceFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultInvoiceFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    public function buildReceiptPayload(
        Receipt $receipt,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $studio = $this->resolveStudio('receipt', $overrideStudio);
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $receipt->rec_no,
            'customer_name' => $receipt->customer?->name,
            'issue_date' => optional($receipt->date ?? $receipt->created_at)->format('d/m/Y'),
            'service_location' => $receipt->warehouse?->name,
        ];

        $placeholderValues = $this->receiptPlaceholderValues($receipt, $settings);
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));
        $bodyHtml = data_get($layout, 'body_html') ?: $this->defaultReceiptBodyHtml();
        $bodyHtml = $this->renderTemplateHtml((string) $bodyHtml, $placeholderValues);

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Recibo',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultReceiptFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultReceiptFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    public function buildCreditNotePayload(
        CreditNote $creditNote,
        GeneralSettings $settings,
        ?ReportStudioTemplate $overrideStudio = null
    ): array {
        $studio = $this->resolveStudio('credit_note', $overrideStudio);
        $layout = $studio?->layout_schema ?? [];
        $export = $studio?->export_settings ?? [];

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $creditNote->note_no,
            'customer_name' => $creditNote->customer?->name,
            'issue_date' => optional($creditNote->date ?? $creditNote->created_at)->format('d/m/Y'),
            'service_location' => $creditNote->warehouse?->name,
        ];

        $placeholderValues = $this->creditNotePlaceholderValues($creditNote, $settings);
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholderValues));
        $bodyHtml = data_get($layout, 'body_html') ?: $this->defaultCreditNoteBodyHtml();
        $bodyHtml = $this->renderTemplateHtml((string) $bodyHtml, $placeholderValues);

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio?->name ?? 'Nota de Crédito',
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $this->defaultCreditNoteFirstPageHeader($settings),
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $this->defaultCreditNoteFooter(),
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    public function buildInvoicePreviewPayload(ReportStudioTemplate $studio, GeneralSettings $settings): array
    {
        return $this->buildCommercialStudioPreviewPayload(
            $studio,
            $settings,
            'Pré-visualização de factura',
            $this->defaultInvoiceFirstPageHeader($settings),
            $this->defaultInvoiceFooter(),
            $this->defaultInvoiceBodyHtml(),
            [
                '{document_number}' => 'FT 05/2026/0091',
                '{customer_name}' => 'Cliente industrial de referência, Lda.',
                '{issue_date}' => now()->format('d/m/Y'),
                '{due_date}' => now()->addDays(30)->format('d/m/Y'),
                '{service_location}' => 'Luanda · Unidade Industrial',
                '{items_table}' => $this->reportTableHtml(
                    [
                        ['label' => 'Item', 'translation' => 'Item'],
                        ['label' => 'Qtd.', 'translation' => 'Qty.', 'align' => 'right'],
                        ['label' => 'Valor', 'translation' => 'Amount', 'align' => 'right'],
                    ],
                    [
                        ['Ensaios microbiológicos', '1', 'AOA 52.500,00'],
                    ]
                ),
                '{summary_table}' => $this->financialSummaryTableHtml([
                    ['label' => 'Subtotal', 'value' => 'AOA 52.500,00'],
                    ['label' => 'IVA', 'value' => 'AOA 7.350,00'],
                    ['label' => 'Total', 'value' => 'AOA 59.850,00', 'emphasis' => true],
                ]),
                '{observations}' => 'Pré-visualização de factura com narrativa comercial, resumo financeiro e paginação.',
                '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Financeira</strong><br />Emissão da factura</div>',
                '{lab_details}' => $this->labDetailsHtml($settings),
                '{customer_details}' => $this->customerDetailsHtml(null, 'Cliente industrial de referência, Lda.'),
                '{banking_details}' => $this->bankingDetailsHtml($settings),
                '{document_keywords}' => $this->documentKeywordsHtml($settings, 'comercial, fiscal, factura'),
            ]
        );
    }

    public function buildReceiptPreviewPayload(ReportStudioTemplate $studio, GeneralSettings $settings): array
    {
        return $this->buildCommercialStudioPreviewPayload(
            $studio,
            $settings,
            'Pré-visualização de recibo',
            $this->defaultReceiptFirstPageHeader($settings),
            $this->defaultReceiptFooter(),
            $this->defaultReceiptBodyHtml(),
            [
                '{document_number}' => 'RG 05/2026/0042',
                '{customer_name}' => 'Cliente industrial de referência, Lda.',
                '{issue_date}' => now()->format('d/m/Y'),
                '{service_location}' => 'Luanda · Unidade Industrial',
                '{payment_type}' => 'Transferência bancária',
                '{items_table}' => $this->reportTableHtml(
                    [
                        ['label' => 'Factura', 'translation' => 'Invoice'],
                        ['label' => 'Valor pago', 'translation' => 'Paid amount', 'align' => 'right'],
                    ],
                    [
                        ['FT 05/2026/0091', 'AOA 59.850,00'],
                    ]
                ),
                '{summary_table}' => $this->financialSummaryTableHtml([
                    ['label' => 'Total recebido', 'value' => 'AOA 59.850,00', 'emphasis' => true],
                ]),
                '{observations}' => 'Pré-visualização de recibo com rastreio de liquidação e confirmação financeira.',
                '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Tesouraria</strong><br />Confirmação do recebimento</div>',
                '{lab_details}' => $this->labDetailsHtml($settings),
                '{customer_details}' => $this->customerDetailsHtml(null, 'Cliente industrial de referência, Lda.'),
                '{banking_details}' => $this->bankingDetailsHtml($settings),
                '{document_keywords}' => $this->documentKeywordsHtml($settings, 'comercial, tesouraria, recibo'),
            ]
        );
    }

    public function buildCreditNotePreviewPayload(ReportStudioTemplate $studio, GeneralSettings $settings): array
    {
        return $this->buildCommercialStudioPreviewPayload(
            $studio,
            $settings,
            'Pré-visualização de nota de crédito',
            $this->defaultCreditNoteFirstPageHeader($settings),
            $this->defaultCreditNoteFooter(),
            $this->defaultCreditNoteBodyHtml(),
            [
                '{document_number}' => 'NC 05/2026/0017',
                '{customer_name}' => 'Cliente industrial de referência, Lda.',
                '{issue_date}' => now()->format('d/m/Y'),
                '{service_location}' => 'Luanda · Unidade Industrial',
                '{reason_label}' => 'Rectificação comercial',
                '{items_table}' => $this->reportTableHtml(
                    [
                        ['label' => 'Item', 'translation' => 'Item'],
                        ['label' => 'Valor', 'translation' => 'Amount', 'align' => 'right'],
                    ],
                    [
                        ['Rectificação de preço', 'AOA 7.500,00'],
                    ]
                ),
                '{summary_table}' => $this->financialSummaryTableHtml([
                    ['label' => 'Total da nota', 'value' => 'AOA 7.500,00', 'emphasis' => true],
                ]),
                '{observations}' => 'Pré-visualização de nota de crédito com motivo, impacto financeiro e validação.',
                '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Financeira</strong><br />Emissão da nota de crédito</div>',
                '{lab_details}' => $this->labDetailsHtml($settings),
                '{customer_details}' => $this->customerDetailsHtml(null, 'Cliente industrial de referência, Lda.'),
                '{banking_details}' => $this->bankingDetailsHtml($settings),
                '{document_keywords}' => $this->documentKeywordsHtml($settings, 'comercial, rectificação, nota de crédito'),
            ]
        );
    }

    private function interpolate(string $template, array $data): string
    {
        $replacements = collect($data)
            ->mapWithKeys(fn ($value, $key) => [
                '{{'.$key.'}}' => (string) ($value ?? ''),
                '{'.$key.'}' => (string) ($value ?? ''),
            ])
            ->all();

        return strtr($this->resolveConditionalBlocks($template, $data), $replacements);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function placeholderContextFromValues(array $values): array
    {
        return collect($values)
            ->mapWithKeys(fn ($value, string $key) => [trim($key, '{}') => $value])
            ->all();
    }

    /**
     * @return array<string, string|int>
     */
    private function proposalPreviewPlaceholderValues(
        GeneralSettings $settings,
        string $createdBy,
        string $observations
    ): array {
        $labName = $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório';
        $customerName = 'Cliente industrial, Lda.';
        $labSigner = $settings->app_client_lab_director ?: 'Direcção Técnica';
        $verificationUrl = url('/vap-proposals/proposal/preview-prop-2026-001');
        $qrBlock = $this->renderQrCodeBlock([
            'qr_content' => $verificationUrl,
            'qr_label' => 'Verificar proposta PROP-2026-001',
            'qr_foreground_color' => '#143d37',
            'qr_background_color' => '#fffdf7',
            'qr_error_correction' => 'medium',
            'qr_margin' => 8,
        ], []);

        return [
            '{proposal_number}' => 'PROP-2026-001',
            '{client_name}' => $customerName,
            '{customer_name}' => $customerName,
            '{customer_code}' => 'CLI-001',
            '{service_location}' => 'Luanda · Unidade Industrial Principal',
            '{department}' => 'Controlo de Qualidade',
            '{warehouse}' => 'Luanda · Armazém Central',
            '{created_at}' => now()->format('d/m/Y'),
            '{created_by}' => $createdBy,
            '{tolerance_days}' => '15',
            '{expiry_date}' => now()->addDays(15)->format('d/m/Y'),
            '{days_until_expiry}' => '15',
            '{sub_total}' => '125.000,00',
            '{total}' => '142.500,00',
            '{tax}' => '17.500,00',
            '{discount}' => '0,00',
            '{global_discount_amount}' => '0,00',
            '{global_discount_percentage}' => '0,00',
            '{withholding_tax_amount}' => '0,00',
            '{withholding_tax_percentage}' => '0,00',
            '{pricing_mode}' => 'Preço de Matriz',
            '{withhold_tax}' => 'Não',
            '{observations}' => $observations,
            '{total_items}' => '3',
            '{taxable_items}' => '3',
            '{items_table}' => $this->reportTableHtml(
                [
                    ['label' => 'Serviço', 'translation' => 'Service'],
                    ['label' => 'Norma', 'translation' => 'Standard'],
                    ['label' => 'Valor', 'translation' => 'Amount', 'align' => 'right'],
                ],
                [
                    ['Metais pesados', 'ISO 17294', 'AOA 45.000,00'],
                    ['Microbiologia', 'ISO 4833', 'AOA 52.500,00'],
                    ['Contra-análise', 'Fluxo dedicado', 'AOA 45.000,00'],
                ]
            ),
            '{items_list}' => '<ul style="padding-left:18px; margin:0;"><li>Metais pesados</li><li>Microbiologia</li><li>Contra-análise</li></ul>',
            '{summary_table}' => $this->financialSummaryTableHtml([
                ['label' => 'Subtotal', 'value' => 'AOA 125.000,00'],
                ['label' => 'IVA', 'value' => 'AOA 17.500,00'],
                ['label' => 'Total', 'value' => 'AOA 142.500,00', 'emphasis' => true],
            ]),
            '{banking_details}' => $this->bankingDetailsHtml($settings),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'proposta, análise, laboratório, ISO 17025'),
            '{lab_signature}' => $labSigner,
            '{client_signature}' => 'Representante do Cliente',
            '{signature_block}' => '<section style="margin-top:24px;"><table style="width:100%; border-collapse:collapse;"><tr><td style="width:48%; padding-top:26px; border-top:1px solid #143d37; color:#20332f;"><strong>'.e($labSigner).'</strong><br><span style="color:#58665f;">Validação técnica / comercial</span></td><td style="width:4%;"></td><td style="width:48%; padding-top:26px; border-top:1px solid #143d37; color:#20332f;"><strong>Representante do Cliente</strong><br><span style="color:#58665f;">Aceitação da proposta</span></td></tr></table></section>',
            '{verification_url}' => $verificationUrl,
            '{proposal_authenticity}' => '<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7;"><table style="width:100%; border-collapse:collapse;"><tr><td style="width:68%; vertical-align:top; padding-right:14px;"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Verificação da proposta</div><div style="margin-top:8px; color:#20332f;">Documento verificável por código QR e ligação pública segura.</div><div style="margin-top:8px; color:#58665f;">Estado: <strong style="color:#143d37;">Pré-visualização</strong></div><div style="margin-top:8px; font-size:9px; color:#58665f; word-break:break-all;">'.e($verificationUrl).'</div></td><td style="width:32%; vertical-align:top; text-align:right;">'.$qrBlock.'</td></tr></table></section>',
            '{proposal_acceptance_evidence}' => '<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#ffffff;"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Evidência de aceite</div><div style="margin-top:8px; font-weight:800; color:#9a7a2f;">Aceite pendente</div><div style="margin-top:6px; color:#58665f;">A proposta aguarda validação do cliente no portal.</div></section>',
            '{lab_name}' => $labName,
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml(null, $customerName),
            ...$this->bankPlaceholderValues($settings),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function renderTemplateHtml(string $template, array $values): string
    {
        return strtr(
            $this->resolveConditionalBlocks($template, $values),
            $this->placeholderReplacements($values)
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function resolveConditionalBlocks(string $template, array $values): string
    {
        $blocks = [
            ['pattern' => '/\{if:([a-zA-Z0-9_]+)\}([\s\S]*?)\{endif:\1\}/', 'invert' => false],
            ['pattern' => '/\{\{if:([a-zA-Z0-9_]+)\}\}([\s\S]*?)\{\{endif:\1\}\}/', 'invert' => false],
            ['pattern' => '/\{ifnot:([a-zA-Z0-9_]+)\}([\s\S]*?)\{endif:\1\}/', 'invert' => true],
            ['pattern' => '/\{\{ifnot:([a-zA-Z0-9_]+)\}\}([\s\S]*?)\{\{endif:\1\}\}/', 'invert' => true],
        ];

        foreach ($blocks as $block) {
            $template = (string) preg_replace_callback(
                $block['pattern'],
                function (array $matches) use ($values, $block): string {
                    $isTruthy = $this->placeholderIsTruthy(
                        $this->valueForConditionalPlaceholder($values, (string) $matches[1])
                    );
                    $shouldRender = (bool) $block['invert'] ? ! $isTruthy : $isTruthy;

                    return $shouldRender ? (string) $matches[2] : '';
                },
                $template
            );
        }

        return $template;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function valueForConditionalPlaceholder(array $values, string $key): mixed
    {
        foreach ([$key, '{'.$key.'}', '{{'.$key.'}}'] as $candidate) {
            if (array_key_exists($candidate, $values)) {
                return $values[$candidate];
            }
        }

        return null;
    }

    private function placeholderIsTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value !== 0.0;
        }

        $normalized = str(trim(strip_tags((string) $value)))->lower()->toString();

        return ! in_array($normalized, ['', '0', '0.0', '0.00', '0,0', '0,00', 'false', 'no', 'não', 'nao', '—', '-'], true);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private function placeholderReplacements(array $values): array
    {
        $replacements = [];

        foreach ($values as $key => $value) {
            $placeholder = trim($key, '{}');

            $replacements[$key] = (string) $value;
            $replacements['{{'.$placeholder.'}}'] = (string) $value;
        }

        return $replacements;
    }

    private function resolveStudio(string $studioType, ?ReportStudioTemplate $overrideStudio = null): ?ReportStudioTemplate
    {
        if ($overrideStudio && $overrideStudio->studio_type === $studioType) {
            return $overrideStudio;
        }

        return ReportStudioTemplate::resolveDefaultFor($studioType)
            ?? ReportStudioDefaultTemplates::make($studioType);
    }

    private function defaultProposalPreviewBodyHtml(): string
    {
        return <<<'HTML'
<section style="margin-bottom:18px;">
    <div class="document-hero studio-avoid-break" style="padding:22px 24px;">
        <div class="document-kicker">Proposta laboratorial · Documento controlado</div>
        <h1 style="margin:8px 0 8px;">Proposta {proposal_number}</h1>
        <p class="studio-lead">Âmbito técnico, condições comerciais, decisão de regra e aceite do cliente num documento único e rastreável.</p>
    </div>
</section>
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Cliente / Customer</span>
                <span class="value">{customer_name}</span>
                <div class="muted" style="margin-top:6px;">{service_location}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Condições / Terms</span>
                <span class="value">Válida até {expiry_date}</span>
                <div class="muted" style="margin-top:6px;">Tolerância: {tolerance_days} dias<br>Regra de decisão conforme proposta aprovada.</div>
            </td>
        </tr>
    </table>
</section>
<section>{items_table}</section>
<section style="margin-top:20px;">{summary_table}</section>
<section class="document-callout studio-avoid-break" style="margin-top:20px;">{observations}</section>
<section style="margin-top:20px;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:50%; vertical-align:top; padding-right:8px;">{proposal_acceptance_evidence}</td>
            <td style="width:50%; vertical-align:top; padding-left:8px;">{proposal_authenticity}</td>
        </tr>
    </table>
</section>
<section style="margin-top:24px;">{signature_block}</section>
HTML;
    }

    private function defaultAnalysisBodyHtml(): string
    {
        return <<<'HTML'
<section style="margin-bottom:18px;">
    <div class="document-hero studio-avoid-break" style="padding:22px 24px;">
        <div class="document-kicker">Relatório analítico / Analysis report</div>
        <h1 style="margin:8px 0 8px;">{report_title} {certificate_code}</h1>
        <p class="studio-lead">Resultados emitidos para <strong>{customer_name}</strong>, com rastreabilidade ao código laboratorial <strong>{lab_code}</strong> e à entrada de amostra <strong>{sample_entry_code}</strong>.</p>
    </div>
</section>

<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Cliente / Customer</span>
                <span class="value">{customer_name}</span>
                <div class="muted" style="margin-top:6px;">{customer_details}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Laboratório / Laboratory</span>
                <span class="value">{lab_name}</span>
                <div class="muted" style="margin-top:6px;">{lab_details}</div>
            </td>
        </tr>
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Amostra / Sample</span>
                <span class="value">{sample_name}</span>
                <div class="muted" style="margin-top:6px;">Produto: {sample_product}<br>Matriz: {sample_matrix}<br>Lote: {sample_lot}<br>Origem: {sample_origin}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Validação / Validation</span>
                <span class="value">{validated_by}</span>
                <div class="muted" style="margin-top:6px;">Emissão: {issue_date}<br>Regra de decisão: {decision_rule}</div>
            </td>
        </tr>
    </table>
</section>

{sample_details}
{collection_details}
{analytical_scope}

<section style="margin:20px 0;">{results_table}</section>
<section style="margin:20px 0;">{analysis_chart_card}</section>
<section class="document-callout studio-avoid-break" style="margin-top:20px;">{uncertainty_statement}</section>
<section style="margin-top:18px;">{conclusion}</section>
<section style="margin-top:18px;">{document_keywords}</section>
<section style="margin-top:24px;">{signature_block}</section>
HTML;
    }

    private function defaultCommercialFirstPageHeader(
        GeneralSettings $settings,
        string $title,
        string $subtitle,
        string $controlNote
    ): string {
        $labName = e($settings->app_client_lab_name ?: $settings->app_client_name ?: $settings->app_name ?: 'Laboratório');
        $labSlogan = e($settings->app_client_lab_slogan ?: 'Gestão laboratorial, rastreabilidade e conformidade documental.');

        return <<<HTML
<div style="border:1px solid #d8cbb8; border-radius:18px; background:#fffdf7;">
    <div style="background:#143d37; color:#fffdf7; padding:18px 22px; border-radius:17px 17px 0 0;">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <div style="font-size:9px; letter-spacing:0.2em; text-transform:uppercase; color:#d9b05f; font-weight:800;">{$labName}</div>
                    <h2 style="margin:8px 0 0; font-size:22px; line-height:1.15; color:#ffffff;">{$title}</h2>
                    <p style="margin:8px 0 0; font-size:11px; color:#e7efe8;">{$subtitle}</p>
                </td>
                <td style="width:34%; vertical-align:top; text-align:right; font-size:9px; color:#dbe8df;">
                    <div style="font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#d9b05f;">Documento controlado</div>
                    <div style="margin-top:6px;">{$labSlogan}</div>
                </td>
            </tr>
        </table>
    </div>
    <div style="padding:10px 22px; font-size:10px; color:#475a53; background:#f7f1e7; border-radius:0 0 17px 17px;">{$controlNote}</div>
</div>
HTML;
    }

    private function defaultCertificateFirstPageHeader(
        GeneralSettings $settings,
        string $title,
        string $subtitle,
        string $controlNote
    ): string {
        $labName = e($settings->app_client_lab_name ?: $settings->app_client_name ?: $settings->app_name ?: 'Laboratório');
        $labSlogan = e($settings->app_client_lab_slogan ?: 'Certificação, rastreabilidade e validação técnica.');

        return <<<HTML
<div style="border:1px solid #d8cbb8; border-radius:18px; background:#fffdf7;">
    <div style="background:#143d37; color:#fffdf7; padding:18px 22px; border-radius:17px 17px 0 0;">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <div style="font-size:9px; letter-spacing:0.2em; text-transform:uppercase; color:#d9b05f; font-weight:800;">{$labName}</div>
                    <h2 style="margin:8px 0 0; font-size:21px; line-height:1.15; color:#ffffff;">{$title}</h2>
                    <p style="margin:8px 0 0; font-size:11px; color:#e7efe8;">{$subtitle}</p>
                </td>
                <td style="width:32%; vertical-align:top; text-align:right; font-size:9px; color:#dbe8df;">
                    <div style="font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#d9b05f;">Certificado controlado</div>
                    <div style="margin-top:6px;">{$labSlogan}</div>
                </td>
            </tr>
        </table>
    </div>
    <div style="padding:10px 22px; font-size:10px; color:#475a53; background:#f7f1e7; border-radius:0 0 17px 17px;">{$controlNote}</div>
</div>
HTML;
    }

    private function labDetailsHtml(GeneralSettings $settings): string
    {
        $lines = array_filter([
            $settings->app_client_lab_name ?: $settings->app_client_name ?: $settings->app_name,
            $settings->app_client_address,
            $settings->app_client_nif ? 'NIF: '.$settings->app_client_nif : ($settings->app_nif ? 'NIF: '.$settings->app_nif : null),
            $settings->app_client_contact ?: $settings->app_contact,
            $settings->app_client_email ?: $settings->app_email,
            $settings->app_client_lab_director ? 'Direção técnica: '.$settings->app_client_lab_director : null,
            $settings->app_client_lab_province ? 'Província: '.$settings->app_client_lab_province : null,
        ]);

        return $this->detailsLinesHtml($lines, 'Laboratório');
    }

    private function customerDetailsHtml(?object $customer, string $fallbackName = 'Cliente'): string
    {
        $lines = array_filter([
            data_get($customer, 'name') ?: $fallbackName,
            data_get($customer, 'address'),
            data_get($customer, 'nif') ? 'NIF: '.data_get($customer, 'nif') : null,
            data_get($customer, 'primary_phone') ?: data_get($customer, 'contact') ?: data_get($customer, 'phone'),
            data_get($customer, 'email') ?: data_get($customer, 'invoicing_email'),
        ]);

        return $this->detailsLinesHtml($lines, $fallbackName);
    }

    private function bankingDetailsHtml(GeneralSettings $settings): string
    {
        $lines = array_filter([
            $settings->app_bank_name ? 'Banco: '.$settings->app_bank_name : null,
            $settings->app_bank_account_name ? 'Titular: '.$settings->app_bank_account_name : null,
            $settings->app_bank_account_number ? 'Conta: '.$settings->app_bank_account_number : null,
            $settings->app_bank_iban ? 'IBAN: '.$settings->app_bank_iban : null,
            $settings->app_bank_swift ? 'SWIFT/BIC: '.$settings->app_bank_swift : null,
            $settings->app_bank_details,
        ]);

        if ($lines === []) {
            $lines = ['Dados bancários a configurar nas definições gerais.'];
        }

        return $this->detailsLinesHtml($lines, 'Dados bancários');
    }

    /**
     * @return array<string, string>
     */
    private function bankPlaceholderValues(GeneralSettings $settings): array
    {
        return [
            '{bank_name}' => $settings->app_bank_name ?: '',
            '{bank_account_name}' => $settings->app_bank_account_name ?: '',
            '{bank_account_number}' => $settings->app_bank_account_number ?: '',
            '{bank_iban}' => $settings->app_bank_iban ?: '',
            '{bank_swift}' => $settings->app_bank_swift ?: '',
            '{bank_details}' => $settings->app_bank_details ?: '',
        ];
    }

    private function documentKeywordsHtml(GeneralSettings $settings, string $fallback): string
    {
        $keywords = $settings->app_document_keywords ?: $fallback;

        return '<div style="font-size:9px; color:#6b7b74;"><strong style="color:#143d37;">Palavras-chave / Keywords:</strong> '.e($keywords).'</div>';
    }

    /**
     * @param  array<int, string|null>  $lines
     */
    private function detailsLinesHtml(array $lines, string $fallback): string
    {
        $lines = array_values(array_filter($lines, fn ($line) => filled($line)));

        if ($lines === []) {
            $lines = [$fallback];
        }

        return collect($lines)
            ->map(fn ($line) => nl2br(e((string) $line), false))
            ->implode('<br>');
    }

    /**
     * @param  array<int, array{label: string, translation?: string, align?: string}>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function reportTableHtml(array $columns, array $rows, string $emptyMessage = 'Sem dados registados.'): string
    {
        $columns = array_values($columns);
        $columnCount = max(count($columns), 1);
        $headerHtml = collect($columns)
            ->map(function (array $column): string {
                $alignment = $this->pdfTableAlignment($column['align'] ?? 'left');
                $translation = trim((string) ($column['translation'] ?? ''));
                $translationHtml = $translation !== ''
                    ? '<br><span class="bilingual-label">'.e($translation).'</span>'
                    : '';

                return '<th style="text-align:'.$alignment.';">'.e($column['label']).$translationHtml.'</th>';
            })
            ->implode('');
        $bodyHtml = collect($rows)
            ->map(function (array $row) use ($columns): string {
                $cells = collect($columns)
                    ->map(function (array $column, int $index) use ($row): string {
                        $alignment = $this->pdfTableAlignment($column['align'] ?? 'left');

                        return '<td style="text-align:'.$alignment.';">'.$this->pdfTableCellValue($row[$index] ?? '').'</td>';
                    })
                    ->implode('');

                return '<tr>'.$cells.'</tr>';
            })
            ->implode('');

        if ($bodyHtml === '') {
            $bodyHtml = '<tr><td colspan="'.$columnCount.'" class="muted">'.e($emptyMessage).'</td></tr>';
        }

        return <<<HTML
<table class="report-table studio-avoid-break" style="width:100%; border-collapse:collapse;">
    <thead>
        <tr>{$headerHtml}</tr>
    </thead>
    <tbody>
        {$bodyHtml}
    </tbody>
</table>
HTML;
    }

    /**
     * @param  array<int, array{label: string, value: mixed, emphasis?: bool}>  $rows
     */
    private function financialSummaryTableHtml(array $rows): string
    {
        $bodyHtml = collect($rows)
            ->map(function (array $row): string {
                $isEmphasis = (bool) ($row['emphasis'] ?? false);
                $labelStyle = $isEmphasis ? 'font-weight:800; color:#143d37;' : '';
                $valueStyle = $isEmphasis ? 'font-weight:800; color:#143d37;' : 'font-weight:700;';

                return '<tr>'
                    .'<td style="'.$labelStyle.'">'.e((string) $row['label']).'</td>'
                    .'<td style="text-align:right; '.$valueStyle.'">'.e((string) $row['value']).'</td>'
                    .'</tr>';
            })
            ->implode('');

        if ($bodyHtml === '') {
            $bodyHtml = '<tr><td colspan="2" class="muted">Sem resumo financeiro registado.</td></tr>';
        }

        return <<<HTML
<table class="report-table document-financial-summary studio-avoid-break" style="width:100%; border-collapse:collapse;">
    <tbody>
        {$bodyHtml}
    </tbody>
</table>
HTML;
    }

    private function pdfTableCellValue(mixed $value): string
    {
        return nl2br(e((string) $value), false);
    }

    private function pdfTableAlignment(string $alignment): string
    {
        return in_array($alignment, ['left', 'center', 'right'], true) ? $alignment : 'left';
    }

    private function defaultExportCertificateFirstPageHeader(GeneralSettings $settings): string
    {
        return $this->defaultCertificateFirstPageHeader(
            $settings,
            'Certificado de Exportação {{document_code}}',
            '{{exporter_name}} · {{origin_country}} → {{destination_country}} · {{issue_date}}',
            'Documento controlado para rastreabilidade logística e validação técnica de exportação.'
        );
    }

    private function defaultExportCertificateFooter(): string
    {
        return '<table style="width:100%; border-top:1px solid #ded3bf; padding-top:6px; font-size:9px; color:#475a53;"><tr><td>Documento controlado de exportação</td><td style="text-align:right;">Página {PAGENO}/{nbpg}</td></tr></table>';
    }

    private function defaultExportCertificateBodyHtml(): string
    {
        return <<<'HTML'
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Exportador / Exporter</span>
                <span class="value">{exporter_name}</span>
                <div class="muted" style="margin-top:6px;">{customer_details}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Laboratório / Laboratory</span>
                <div class="muted" style="margin-top:6px;">{lab_details}</div>
            </td>
        </tr>
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Origem / Origin</span>
                <span class="value">{origin_city}, {origin_country}</span>
                <div class="muted" style="margin-top:6px;">Expedidor: {exporter_name}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Destino / Destination</span>
                <span class="value">{destination_city}, {destination_country}</span>
                <div class="muted" style="margin-top:6px;">Transporte: {transport_type}</div>
            </td>
        </tr>
    </table>
</section>
<section style="margin:20px 0;">{products_table}</section>
<section class="studio-avoid-break" style="margin-top:18px; border-left:4px solid #143d37; background:#fffdf7; padding:14px 16px; border-radius:14px;">
    <strong>Pessoal autorizado:</strong> {authorized_personnel}<br>
    <strong>Expedição:</strong> {expedition_location} · {expedition_date}
</section>
<section style="margin-top:20px;">{remarks}</section>
<section style="margin-top:18px;">{document_keywords}</section>
<section style="margin-top:24px;">{signature_block}</section>
HTML;
    }

    private function defaultImportCertificateFirstPageHeader(GeneralSettings $settings): string
    {
        return $this->defaultCertificateFirstPageHeader(
            $settings,
            'Certificado de Importação {{document_code}}',
            '{{importer_name}} · {{exporter_name}} → {{destination_country}} · {{issue_date}}',
            'Documento controlado para rastreabilidade logística, lotes e validação técnica de importação.'
        );
    }

    private function defaultImportCertificateFooter(): string
    {
        return '<table style="width:100%; border-top:1px solid #ded3bf; padding-top:6px; font-size:9px; color:#475a53;"><tr><td>Documento controlado de importação</td><td style="text-align:right;">Página {PAGENO}/{nbpg}</td></tr></table>';
    }

    private function defaultImportCertificateBodyHtml(): string
    {
        return <<<'HTML'
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Importador / Importer</span>
                <span class="value">{importer_name}</span>
                <div class="muted" style="margin-top:6px;">{customer_details}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Laboratório / Laboratory</span>
                <div class="muted" style="margin-top:6px;">{lab_details}</div>
            </td>
        </tr>
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Importação / Import</span>
                <span class="value">{destination_country}</span>
                <div class="muted" style="margin-top:6px;">Exportador: {exporter_name}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Logística / Logistics</span>
                <span class="value">{transport_type}</span>
                <div class="muted" style="margin-top:6px;">Porto de saída: {port_exit}<br>Porto de entrada: {port_entry}</div>
            </td>
        </tr>
    </table>
</section>
<section style="margin:20px 0;">{items_table}</section>
<section class="studio-avoid-break" style="margin-top:18px; border-left:4px solid #143d37; background:#fffdf7; padding:14px 16px; border-radius:14px;">
    <strong>Pessoal autorizado:</strong> {authorized_personnel}<br>
    <strong>Emissão:</strong> {issue_date}
</section>
<section style="margin-top:20px;">{remarks}</section>
<section style="margin-top:18px;">{document_keywords}</section>
<section style="margin-top:24px;">{signature_block}</section>
HTML;
    }

    private function defaultQuoteFirstPageHeader(GeneralSettings $settings): string
    {
        return $this->defaultCommercialFirstPageHeader(
            $settings,
            'Factura Proforma {{document_code}}',
            '{{customer_name}} · Emitida em {{issue_date}} · Válida até {{expiry_date}}',
            'Documento comercial controlado para aprovação de âmbito, condições e valores.'
        );
    }

    private function defaultQuoteFooter(): string
    {
        return '<table style="width:100%; border-top:1px solid #ded3bf; padding-top:6px; font-size:9px; color:#475a53;"><tr><td>Documento comercial controlado</td><td style="text-align:right;">Página {PAGENO}/{nbpg}</td></tr></table>';
    }

    private function defaultQuoteBodyHtml(): string
    {
        return <<<'HTML'
<section style="margin-bottom:18px;">
    <div class="document-hero studio-avoid-break" style="padding:22px 24px;">
        <div class="document-kicker">Proposta comercial · Commercial proposal</div>
        <h1 style="margin:8px 0 8px;">Proforma {quote_number}</h1>
        <p class="studio-lead">Preparada para <strong>{customer_name}</strong>, com âmbito, condições e valores sujeitos à aceitação formal do cliente.</p>
    </div>
</section>

<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Cliente / Customer</span>
                <span class="value">{customer_name}</span>
                <div class="muted" style="margin-top:6px;">{customer_details}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Laboratório / Laboratory</span>
                <div class="muted" style="margin-top:6px;">{lab_details}</div>
            </td>
        </tr>
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Condições / Terms</span>
                <div class="muted" style="margin-top:6px;">Emissão: {issue_date}<br>Validade: {expiry_date}<br>Local: {service_location}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Pagamento / Banking</span>
                <div class="muted" style="margin-top:6px;">{banking_details}</div>
            </td>
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

    private function defaultInvoiceFirstPageHeader(GeneralSettings $settings): string
    {
        return $this->defaultCommercialFirstPageHeader(
            $settings,
            'Factura {{document_code}}',
            '{{customer_name}} · Emitida em {{issue_date}} · Vencimento {{due_date}}',
            'Documento fiscal controlado com dados comerciais, fiscais e bancários.'
        );
    }

    private function defaultInvoiceFooter(): string
    {
        return '<table style="width:100%; border-top:1px solid #ded3bf; padding-top:6px; font-size:9px; color:#475a53;"><tr><td>Documento fiscal controlado</td><td style="text-align:right;">Página {PAGENO}/{nbpg}</td></tr></table>';
    }

    private function defaultInvoiceBodyHtml(): string
    {
        return $this->defaultCommercialDocumentBodyHtml();
    }

    private function defaultReceiptFirstPageHeader(GeneralSettings $settings): string
    {
        return $this->defaultCommercialFirstPageHeader(
            $settings,
            'Recibo {{document_code}}',
            '{{customer_name}} · Recebido em {{issue_date}}',
            'Comprovativo de recebimento com rastreabilidade financeira.'
        );
    }

    private function defaultReceiptFooter(): string
    {
        return '<table style="width:100%; border-top:1px solid #ded3bf; padding-top:6px; font-size:9px; color:#475a53;"><tr><td>Comprovativo de recebimento</td><td style="text-align:right;">Página {PAGENO}/{nbpg}</td></tr></table>';
    }

    private function defaultReceiptBodyHtml(): string
    {
        return $this->defaultCommercialDocumentBodyHtml('Recebimento / Payment', 'Data: {issue_date}<br>Forma de pagamento: {payment_type}<br>Local: {service_location}');
    }

    private function defaultCreditNoteFirstPageHeader(GeneralSettings $settings): string
    {
        return $this->defaultCommercialFirstPageHeader(
            $settings,
            'Nota de Crédito {{document_code}}',
            '{{customer_name}} · Emitida em {{issue_date}}',
            'Documento de rectificação financeira com motivo e impacto rastreáveis.'
        );
    }

    private function defaultCreditNoteFooter(): string
    {
        return '<table style="width:100%; border-top:1px solid #ded3bf; padding-top:6px; font-size:9px; color:#475a53;"><tr><td>Documento de rectificação</td><td style="text-align:right;">Página {PAGENO}/{nbpg}</td></tr></table>';
    }

    private function defaultCreditNoteBodyHtml(): string
    {
        return $this->defaultCommercialDocumentBodyHtml('Motivo / Reason', '{reason_label}<br>Data: {issue_date}<br>Local: {service_location}');
    }

    private function defaultCommercialDocumentBodyHtml(
        string $termsTitle = 'Condições / Terms',
        string $termsBody = 'Emissão: {issue_date}<br>Vencimento: {due_date}<br>Local: {service_location}'
    ): string {
        return <<<HTML
<section style="margin-bottom:18px;">
    <table class="document-summary-table studio-avoid-break">
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Cliente / Customer</span>
                <span class="value">{customer_name}</span>
                <div class="muted" style="margin-top:6px;">{customer_details}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Laboratório / Laboratory</span>
                <div class="muted" style="margin-top:6px;">{lab_details}</div>
            </td>
        </tr>
        <tr>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">{$termsTitle}</span>
                <div class="muted" style="margin-top:6px;">{$termsBody}</div>
            </td>
            <td class="document-summary-cell" style="width:50%;">
                <span class="label">Pagamento / Banking</span>
                <div class="muted" style="margin-top:6px;">{banking_details}</div>
            </td>
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

    private function exportCertificatePlaceholderValues(ExportCertificate $certificate, GeneralSettings $settings): array
    {
        $productsTable = $this->reportTableHtml(
            [
                ['label' => 'Produto', 'translation' => 'Product'],
                ['label' => 'Quantidade', 'translation' => 'Quantity', 'align' => 'right'],
            ],
            collect($certificate->items ?? [])
                ->map(fn ($item): array => [
                    $item->product?->name ?: 'Produto',
                    (string) $item->qty,
                ])
                ->values()
                ->all(),
            'Sem produtos registados no certificado.'
        );

        return [
            '{certificate_number}' => $certificate->cert_no,
            '{exporter_name}' => $certificate->exporter?->name ?: 'Exportador',
            '{origin_country}' => $certificate->country_origin?->name ?: 'País de origem',
            '{destination_country}' => $certificate->country_destination?->name ?: 'País de destino',
            '{origin_city}' => $certificate->origin_city ?: 'Cidade de origem',
            '{destination_city}' => $certificate->destination_city ?: 'Cidade de destino',
            '{transport_type}' => $certificate->trans_type?->name ?: 'Transporte',
            '{authorized_personnel}' => $certificate->authorized_personnel ?: 'Pessoal autorizado',
            '{expedition_date}' => optional($certificate->expedition_date ?: $certificate->date)->format('d/m/Y') ?: now()->format('d/m/Y'),
            '{expedition_location}' => $certificate->expedition_location ?: 'Local de expedição',
            '{products_table}' => $productsTable,
            '{remarks}' => $certificate->obs ?: 'Sem observações adicionais.',
            '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>'.e($certificate->authorized_personnel ?: 'Direcção Técnica').'</strong><br />Validação do certificado de exportação</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml($certificate->exporter, $certificate->exporter?->name ?: 'Exportador'),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'certificado, exportação, rastreabilidade, controlo documental'),
        ];
    }

    private function importCertificatePlaceholderValues(ImportCertificate $certificate, GeneralSettings $settings): array
    {
        $itemsTable = $this->reportTableHtml(
            [
                ['label' => 'Produto', 'translation' => 'Product'],
                ['label' => 'Lote', 'translation' => 'Lot'],
                ['label' => 'Validade', 'translation' => 'Validity'],
                ['label' => 'Quantidade', 'translation' => 'Quantity', 'align' => 'right'],
            ],
            collect($certificate->items ?? [])
                ->map(fn ($item): array => [
                    $item->product?->description ?: $item->product?->name ?: 'Produto',
                    $item->lot ?: '—',
                    $item->validity ?: '—',
                    (string) ($item->qty ?: '—'),
                ])
                ->values()
                ->all(),
            'Sem lotes registados no certificado.'
        );

        return [
            '{certificate_number}' => $certificate->cert_no,
            '{importer_name}' => $certificate->importer?->name ?: 'Importador',
            '{exporter_name}' => $certificate->exporter?->name ?: 'Exportador',
            '{destination_country}' => $certificate->destination_country?->name ?: 'Destino',
            '{port_entry}' => $certificate->port_entry ?: '—',
            '{port_exit}' => $certificate->port_exit ?: '—',
            '{transport_type}' => $certificate->trans?->description ?: $certificate->trans?->name ?: 'Transporte',
            '{authorized_personnel}' => $certificate->authorized_personnel ?: 'Pessoal autorizado',
            '{issue_date}' => optional($certificate->date ?: $certificate->created_at)->format('d/m/Y') ?: now()->format('d/m/Y'),
            '{items_table}' => $itemsTable,
            '{remarks}' => $certificate->obs ?: 'Sem observações adicionais.',
            '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>'.e($certificate->authorized_personnel ?: 'Direcção Técnica').'</strong><br />Validação do certificado de importação</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml($certificate->importer, $certificate->importer?->name ?: 'Importador'),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'certificado, importação, rastreabilidade, controlo documental'),
        ];
    }

    private function quotePlaceholderValues(Quote $quote, GeneralSettings $settings): array
    {
        $itemsTable = $this->reportTableHtml(
            [
                ['label' => 'Serviço', 'translation' => 'Service'],
                ['label' => 'Qtd.', 'translation' => 'Qty.', 'align' => 'right'],
                ['label' => 'Valor', 'translation' => 'Amount', 'align' => 'right'],
            ],
            collect($quote->items ?? [])
                ->map(fn ($item): array => [
                    $item->description ?? $item->itemable?->description ?? $item->itemable?->name ?? 'Serviço',
                    (string) ($item->qty ?? 1),
                    number_format((float) ($item->total ?? $item->price ?? 0), 2, ',', '.'),
                ])
                ->values()
                ->all(),
            'Sem serviços registados.'
        );

        $summaryTable = $this->financialSummaryTableHtml([
            ['label' => 'Subtotal', 'value' => number_format((float) ($quote->sub_total ?? 0), 2, ',', '.')],
            ['label' => 'IVA', 'value' => number_format((float) ($quote->tax ?? 0), 2, ',', '.')],
            ['label' => 'Total', 'value' => number_format((float) ($quote->total ?? 0), 2, ',', '.'), 'emphasis' => true],
        ]);

        return [
            '{quote_number}' => $quote->quote_no,
            '{customer_name}' => $quote->customer?->name ?: 'Cliente',
            '{service_location}' => $quote->warehouse?->name ?: 'Local do serviço',
            '{issue_date}' => optional($quote->date ?: $quote->created_at)->format('d/m/Y') ?: now()->format('d/m/Y'),
            '{expiry_date}' => optional($quote->due_date)->format('d/m/Y') ?: now()->addDays(15)->format('d/m/Y'),
            '{items_table}' => $itemsTable,
            '{summary_table}' => $summaryTable,
            '{observations}' => $quote->obs ?: 'Proforma preparada com base no âmbito e nas condições comerciais acordadas.',
            '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>'.e($quote->user?->name ?: 'Direcção Comercial').'</strong><br />Validação e emissão da proforma</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml($quote->customer, $quote->customer?->name ?: 'Cliente'),
            '{banking_details}' => $this->bankingDetailsHtml($settings),
            ...$this->bankPlaceholderValues($settings),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'comercial, proforma, proposta'),
        ];
    }

    private function invoicePlaceholderValues(Invoice $invoice, GeneralSettings $settings): array
    {
        $itemsTable = $this->reportTableHtml(
            [
                ['label' => 'Item', 'translation' => 'Item'],
                ['label' => 'Qtd.', 'translation' => 'Qty.', 'align' => 'right'],
                ['label' => 'Valor', 'translation' => 'Amount', 'align' => 'right'],
            ],
            collect($invoice->items ?? [])
                ->map(fn ($item): array => [
                    $item->description ?? $item->itemable?->description ?? $item->itemable?->name ?? 'Serviço',
                    (string) ($item->qty ?? 1),
                    number_format((float) ($item->total ?? $item->price ?? 0), 2, ',', '.'),
                ])
                ->values()
                ->all(),
            'Sem itens registados.'
        );

        return [
            '{document_number}' => $invoice->inv_no,
            '{customer_name}' => $invoice->customer?->name ?: 'Cliente',
            '{service_location}' => $invoice->warehouse?->name ?: 'Local do serviço',
            '{issue_date}' => optional($invoice->date ?: $invoice->created_at)->format('d/m/Y') ?: now()->format('d/m/Y'),
            '{due_date}' => optional($invoice->due_date)->format('d/m/Y') ?: now()->addDays(30)->format('d/m/Y'),
            '{items_table}' => $itemsTable,
            '{summary_table}' => $this->financialSummaryTableHtml([
                ['label' => 'Subtotal', 'value' => number_format((float) ($invoice->sub_total ?? 0), 2, ',', '.')],
                ['label' => 'IVA', 'value' => number_format((float) ($invoice->tax ?? 0), 2, ',', '.')],
                ['label' => 'Total', 'value' => number_format((float) ($invoice->total ?? 0), 2, ',', '.'), 'emphasis' => true],
            ]),
            '{observations}' => $invoice->obs ?: 'Factura emitida com base no documento comercial aprovado e no âmbito executado.',
            '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>'.e($invoice->user?->name ?: 'Direcção Financeira').'</strong><br />Emissão da factura</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml($invoice->customer, $invoice->customer?->name ?: 'Cliente'),
            '{banking_details}' => $this->bankingDetailsHtml($settings),
            ...$this->bankPlaceholderValues($settings),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'comercial, fiscal, factura'),
        ];
    }

    private function receiptPlaceholderValues(Receipt $receipt, GeneralSettings $settings): array
    {
        $itemsTable = $this->reportTableHtml(
            [
                ['label' => 'Factura', 'translation' => 'Invoice'],
                ['label' => 'Valor pago', 'translation' => 'Paid amount', 'align' => 'right'],
            ],
            collect($receipt->items ?? [])
                ->map(fn ($item): array => [
                    $item->invoice?->inv_no ?: 'Factura',
                    number_format((float) ($item->paid_amount ?? 0), 2, ',', '.'),
                ])
                ->values()
                ->all(),
            'Sem facturas associadas ao recibo.'
        );

        $totalPaid = $receipt->items->sum('paid_amount');

        return [
            '{document_number}' => $receipt->rec_no,
            '{customer_name}' => $receipt->customer?->name ?: 'Cliente',
            '{service_location}' => $receipt->warehouse?->name ?: 'Local',
            '{issue_date}' => optional($receipt->date ?: $receipt->created_at)->format('d/m/Y') ?: now()->format('d/m/Y'),
            '{payment_type}' => $receipt->type?->description ?: $receipt->type?->name ?: 'Forma de pagamento',
            '{items_table}' => $itemsTable,
            '{summary_table}' => $this->financialSummaryTableHtml([
                ['label' => 'Total recebido', 'value' => number_format((float) $totalPaid, 2, ',', '.'), 'emphasis' => true],
            ]),
            '{observations}' => $receipt->obs ?: 'Recibo emitido como comprovativo do recebimento financeiro.',
            '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>'.e($receipt->user?->name ?: 'Tesouraria').'</strong><br />Confirmação do recebimento</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml($receipt->customer, $receipt->customer?->name ?: 'Cliente'),
            '{banking_details}' => $this->bankingDetailsHtml($settings),
            ...$this->bankPlaceholderValues($settings),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'comercial, tesouraria, recibo'),
        ];
    }

    private function creditNotePlaceholderValues(CreditNote $creditNote, GeneralSettings $settings): array
    {
        $itemsTable = $this->reportTableHtml(
            [
                ['label' => 'Item', 'translation' => 'Item'],
                ['label' => 'Valor', 'translation' => 'Amount', 'align' => 'right'],
            ],
            collect($creditNote->items ?? [])
                ->map(fn ($item): array => [
                    $item->description ?? $item->itemable?->description ?? $item->itemable?->name ?? 'Rectificação',
                    number_format((float) ($item->total ?? $item->price ?? 0), 2, ',', '.'),
                ])
                ->values()
                ->all(),
            'Sem itens de rectificação registados.'
        );

        return [
            '{document_number}' => $creditNote->note_no,
            '{customer_name}' => $creditNote->customer?->name ?: 'Cliente',
            '{service_location}' => $creditNote->warehouse?->name ?: 'Local',
            '{issue_date}' => optional($creditNote->date ?: $creditNote->created_at)->format('d/m/Y') ?: now()->format('d/m/Y'),
            '{reason_label}' => $creditNote->reason === CreditNote::REASON_CANCELATION ? 'Cancelamento' : 'Rectificação',
            '{items_table}' => $itemsTable,
            '{summary_table}' => $this->financialSummaryTableHtml([
                ['label' => 'Total da nota', 'value' => number_format((float) ($creditNote->total ?? 0), 2, ',', '.'), 'emphasis' => true],
            ]),
            '{observations}' => $creditNote->obs ?: 'Nota de crédito emitida para rectificação financeira/documental.',
            '{signature_block}' => '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>'.e($creditNote->user?->name ?: 'Direcção Financeira').'</strong><br />Emissão da nota de crédito</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml($creditNote->customer, $creditNote->customer?->name ?: 'Cliente'),
            '{banking_details}' => $this->bankingDetailsHtml($settings),
            ...$this->bankPlaceholderValues($settings),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'comercial, rectificação, nota de crédito'),
        ];
    }

    private function buildCommercialStudioPreviewPayload(
        ReportStudioTemplate $studio,
        GeneralSettings $settings,
        string $documentTitle,
        string $defaultFirstPageHeader,
        string $defaultFooter,
        string $defaultBodyHtml,
        array $placeholders
    ): array {
        $layout = $studio->layout_schema ?? [];
        $export = $studio->export_settings ?? [];
        $placeholders = array_merge($placeholders, $this->bankPlaceholderValues($settings));

        $headerContext = [
            'lab_name' => $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório',
            'document_code' => $placeholders['{document_number}'] ?? 'DOC-2026-001',
            'customer_name' => $placeholders['{customer_name}'] ?? 'Cliente industrial de referência, Lda.',
            'issue_date' => $placeholders['{issue_date}'] ?? now()->format('d/m/Y'),
            'due_date' => $placeholders['{due_date}'] ?? now()->addDays(30)->format('d/m/Y'),
            'service_location' => $placeholders['{service_location}'] ?? 'Luanda',
        ];
        $surfaceContext = array_merge($headerContext, $this->placeholderContextFromValues($placeholders));

        $bodyHtml = $this->renderTemplateHtml(
            data_get($layout, 'body_html') ?: $defaultBodyHtml,
            $placeholders
        );

        return [
            'view' => 'PDFs.studios.document',
            'data' => [
                'documentTitle' => $studio->name ?: $documentTitle,
                'firstPageHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'first_page_header_html') ?: $defaultFirstPageHeader,
                    data_get($layout, 'canvas_blocks', []),
                    'first_page_header_html',
                    $surfaceContext
                ),
                'defaultHeader' => $this->buildSurfaceHtml(
                    data_get($layout, 'default_header_html') ?: '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
                    data_get($layout, 'canvas_blocks', []),
                    'default_header_html',
                    $surfaceContext
                ),
                'footerHtml' => $this->buildSurfaceHtml(
                    data_get($layout, 'footer_html') ?: $defaultFooter,
                    data_get($layout, 'canvas_blocks', []),
                    'footer_html',
                    $surfaceContext
                ),
                'bodyHtml' => $this->buildSurfaceHtml(
                    $bodyHtml,
                    data_get($layout, 'canvas_blocks', []),
                    'content',
                    $surfaceContext
                ),
                'styles' => $this->stylesCss($layout),
                'backgroundImage' => data_get($layout, 'background_image_path'),
                'backgroundSize' => data_get($layout, 'background_size', 'cover'),
                'backgroundPosition' => data_get($layout, 'background_position', 'center center'),
                'backgroundRepeat' => data_get($layout, 'background_repeat', 'no-repeat'),
                'orientation' => data_get($export, 'orientation', 'P'),
                'format' => data_get($export, 'paper_size', 'A4'),
                'customPageWidth' => data_get($export, 'custom_page_width'),
                'customPageHeight' => data_get($export, 'custom_page_height'),
                'margins' => [
                    'top' => data_get($export, 'margin_top', 20),
                    'bottom' => data_get($export, 'margin_bottom', 22),
                    'left' => data_get($export, 'margin_left', 14),
                    'right' => data_get($export, 'margin_right', 14),
                    'first_top' => data_get($export, 'first_page_margin_top', 56),
                ],
            ],
        ];
    }

    private function analysisPlaceholderValues(QualityCertificate $certificate, GeneralSettings $settings): array
    {
        $resultId = data_get($certificate->collection, 'result_id');
        $labName = $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório';
        $customerName = $certificate->customer?->name ?: 'Cliente';
        $issueDate = optional($certificate->created_at)->format('d/m/Y') ?: now()->format('d/m/Y');
        $labCode = $certificate->lab_code?->code ?: 'Sem código';
        $warehouse = $certificate->warehouse?->name ?: ($certificate->warehouse?->address ?: 'Sem local');
        $validation = $certificate->validated_by ?: ($certificate->validated_by_user?->name ?: 'Validação pendente');
        $collectionProduct = $certificate->collection;
        $sampleEntry = $collectionProduct?->sampleEntry;
        $clientInfo = $sampleEntry?->client_submitted_info ?? data_get($collectionProduct?->extra_data, 'submitted_payload', []);
        $productName = $collectionProduct?->product?->name
            ?: $certificate->product?->name
            ?: data_get($clientInfo, 'product_name')
            ?: 'Produto não declarado';
        $matrixName = $collectionProduct?->product?->matrix?->description
            ?: data_get($clientInfo, 'matrix_description')
            ?: data_get($clientInfo, 'matrix')
            ?: 'Matriz não declarada';
        $sampleEntryCode = $sampleEntry?->code ?: data_get($collectionProduct?->extra_data, 'sample_entry_code', 'Sem Sample Entry');
        $sampleDetails = $this->analysisSampleDetailsHtml($certificate);
        $collectionDetails = $this->analysisCollectionDetailsHtml($certificate);
        $analyticalScope = $this->analysisScopeDetailsHtml($certificate);
        $resultsTable = $this->analysisResultsTableHtml($certificate, $resultId);
        $analysisChart = $this->analysisResultChartData($certificate, $resultId);

        return [
            '{report_title}' => 'Relatório Analítico',
            '{certificate_code}' => (string) $certificate->code,
            '{document_code}' => (string) $certificate->code,
            '{customer_name}' => $customerName,
            '{lab_name}' => $labName,
            '{issue_date}' => $issueDate,
            '{lab_code}' => $labCode,
            '{warehouse_name}' => $warehouse,
            '{sample_entry_code}' => (string) $sampleEntryCode,
            '{sample_code}' => (string) $sampleEntryCode,
            '{sample_name}' => (string) ($sampleEntry?->name ?: $collectionProduct?->comercial_brand ?: $productName),
            '{sample_type}' => (string) ($sampleEntry?->sample_type ?: data_get($clientInfo, 'sample_type', 'Amostra')),
            '{sample_product}' => (string) $productName,
            '{sample_matrix}' => (string) $matrixName,
            '{sample_lot}' => (string) ($collectionProduct?->lot ?: data_get($clientInfo, 'lot', 'Sem lote')),
            '{sample_origin}' => (string) ($collectionProduct?->origin ?: data_get($clientInfo, 'origin', 'Sem origem declarada')),
            '{sampling_plan_ref}' => (string) ($collectionProduct?->sampling_plan_ref ?: data_get($clientInfo, 'sampling_plan_ref', 'Sem plano declarado')),
            '{collection_date}' => $this->analysisDateValue($collectionProduct?->collection_date) ?: (string) data_get($clientInfo, 'received_at', 'Sem data'),
            '{received_at}' => $this->analysisDateValue($sampleEntry?->received_at) ?: (string) data_get($clientInfo, 'received_at', 'Sem receção'),
            '{validated_by}' => $validation,
            '{sample_details}' => $sampleDetails,
            '{collection_details}' => $collectionDetails,
            '{analytical_scope}' => $analyticalScope,
            '{results_table}' => $resultsTable,
            '{analysis_chart_title}' => $analysisChart['title'],
            '{analysis_chart_labels}' => implode(', ', $analysisChart['labels']),
            '{analysis_chart_values}' => implode(', ', $analysisChart['values']),
            '{analysis_chart_caption}' => $analysisChart['caption'],
            '{analysis_chart_card}' => $this->analysisChartCardHtml($analysisChart),
            '{uncertainty_statement}' => 'As incertezas de medição aplicáveis são apresentadas de acordo com o método validado e a política do laboratório.',
            '{decision_rule}' => 'A decisão declarada segue a regra de decisão configurada para o ensaio e o âmbito aplicável.',
            '{conclusion}' => 'Conclusão emitida com base no âmbito analítico validado e na rastreabilidade documental do processo.',
            '{signature_block}' => '<div style="margin-top:24px; border-top:1px solid #0f172a; padding-top:10px; font-size:11px;">'.e($validation).'</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml($certificate->customer, $customerName),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'ISO 17025, relatório analítico, rastreabilidade, incerteza de medição'),
        ];
    }

    private function analysisPreviewPlaceholderValues(GeneralSettings $settings): array
    {
        $labName = $settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório';
        $issueDate = now()->format('d/m/Y');
        $sampleDetails = $this->analysisDetailTableHtml([
            ['Código da Sample Entry', 'Sample entry code', 'SE-2026-001'],
            ['Nome da amostra', 'Sample name', 'Farinha de trigo para controlo de qualidade'],
            ['Tipo de amostra', 'Sample type', 'Matéria-prima / Raw material'],
            ['Produto', 'Product', 'Farinha de trigo tipo 65'],
            ['Matriz', 'Matrix', 'Cereais e derivados'],
            ['Lote', 'Lot', 'LT-26-041'],
            ['Batch / OP', 'Batch', 'OP-2026-019'],
            ['Origem', 'Origin', 'Recepção interna'],
            ['Fornecedor', 'Supplier', 'Fornecedor homologado'],
            ['Quantidade recebida', 'Received quantity', '2 kg'],
            ['Data de receção', 'Reception date', now()->subDays(2)->format('d/m/Y')],
            ['Data de colheita', 'Sampling date', now()->subDay()->format('d/m/Y')],
        ], 'Identificação da amostra / Sample identification');
        $collectionDetails = $this->analysisDetailTableHtml([
            ['Lab code', 'Laboratory code', 'LAB-2026-042'],
            ['Armazém / local', 'Warehouse / site', 'Laboratório central'],
            ['Local de colheita', 'Sampling location', 'Armazém de matéria-prima'],
            ['Plano de amostragem', 'Sampling plan', 'PA-ISO17025-MP-01'],
            ['Condição da embalagem', 'Packaging condition', 'Íntegra, selada e identificada'],
            ['Condição térmica', 'Thermal condition', 'Ambiente controlado'],
            ['Cadeia de custódia', 'Chain of custody', 'Recepção, triagem, validação técnica e emissão registadas no SGQ.'],
            ['Observações de integridade', 'Integrity observations', 'Sem sinais de violação ou contaminação visível.'],
        ], 'Receção e cadeia de custódia / Reception and chain of custody');
        $analyticalScope = $this->analysisDetailTableHtml([
            ['Perfis analíticos', 'Analytical profiles', 'Microbiologia; Físico-química'],
            ['Parâmetros previstos', 'Expected parameters', 'Humidade; Cinzas; Salmonella spp.; Bolores e leveduras'],
            ['Total de parâmetros', 'Parameter count', '4'],
            ['Serviços solicitados', 'Requested services', 'Controlo interno de matéria-prima'],
            ['Regra de decisão', 'Decision rule', 'Aplicar critério definido no método validado e no plano de controlo interno.'],
            ['Observações', 'Observations', 'Pré-visualização técnica para validar estrutura, campos e paginação do template.'],
        ], 'Âmbito analítico / Analytical scope');
        $resultsTable = $this->reportTableHtml(
            [
                ['label' => 'Parâmetro', 'translation' => 'Parameter'],
                ['label' => 'Método', 'translation' => 'Method'],
                ['label' => 'Resultado', 'translation' => 'Result'],
                ['label' => 'Unidade', 'translation' => 'Unit'],
                ['label' => 'Incerteza', 'translation' => 'Uncertainty'],
                ['label' => 'Estado', 'translation' => 'Status'],
            ],
            [
                ['Humidade', 'ISO 712', '13,2', '%', '±0,4', 'Aprovado'],
                ['Cinzas', 'ISO 2171', '0,62', '%', '±0,03', 'Verificado'],
                ['Salmonella spp.', 'ISO 6579-1', 'Ausente', '25 g', 'Qualitativo', 'Aprovado'],
            ]
        );
        $analysisChart = $this->analysisPreviewChartData();

        return [
            '{report_title}' => 'Relatório Analítico',
            '{certificate_code}' => 'BA-2026-001',
            '{document_code}' => 'BA-2026-001',
            '{customer_name}' => 'Cliente laboratorial de referência',
            '{lab_name}' => $labName,
            '{issue_date}' => $issueDate,
            '{lab_code}' => 'LAB-2026-042',
            '{warehouse_name}' => 'Laboratório central',
            '{sample_entry_code}' => 'SE-2026-001',
            '{sample_code}' => 'SE-2026-001',
            '{sample_name}' => 'Farinha de trigo para controlo de qualidade',
            '{sample_type}' => 'Matéria-prima',
            '{sample_product}' => 'Farinha de trigo tipo 65',
            '{sample_matrix}' => 'Cereais e derivados',
            '{sample_lot}' => 'LT-26-041',
            '{sample_origin}' => 'Recepção interna',
            '{sampling_plan_ref}' => 'PA-ISO17025-MP-01',
            '{collection_date}' => now()->subDay()->format('d/m/Y'),
            '{received_at}' => now()->subDays(2)->format('d/m/Y'),
            '{validated_by}' => 'Direcção técnica',
            '{sample_details}' => $sampleDetails,
            '{collection_details}' => $collectionDetails,
            '{analytical_scope}' => $analyticalScope,
            '{results_table}' => $resultsTable,
            '{analysis_chart_title}' => $analysisChart['title'],
            '{analysis_chart_labels}' => implode(', ', $analysisChart['labels']),
            '{analysis_chart_values}' => implode(', ', $analysisChart['values']),
            '{analysis_chart_caption}' => $analysisChart['caption'],
            '{analysis_chart_card}' => $this->analysisChartCardHtml($analysisChart),
            '{uncertainty_statement}' => 'As incertezas de medição apresentadas são estimativas de pré-visualização para validar o template. Na emissão real, o valor vem do método, cálculo ou fonte de incerteza configurada.',
            '{decision_rule}' => 'A decisão de conformidade segue a regra definida no método validado e no contrato/proposta aprovada.',
            '{conclusion}' => 'Pré-visualização controlada para confirmar que amostra, cadeia de custódia, resultados, incerteza, decisão e assinatura permanecem legíveis no PDF final.',
            '{signature_block}' => '<div style="margin-top:24px; border-top:1px solid #0f172a; padding-top:10px; font-size:11px;"><strong>Direcção técnica</strong><br />Validação da pré-visualização do relatório</div>',
            '{lab_details}' => $this->labDetailsHtml($settings),
            '{customer_details}' => $this->customerDetailsHtml(null, 'Cliente laboratorial de referência'),
            '{document_keywords}' => $this->documentKeywordsHtml($settings, 'ISO 17025, relatório analítico, rastreabilidade, incerteza de medição'),
        ];
    }

    /**
     * @return array{title: string, labels: array<int, string>, values: array<int, string>, caption: string}
     */
    private function analysisResultChartData(QualityCertificate $certificate, mixed $resultId): array
    {
        $results = collect($certificate->results ?? []);
        $approved = $results->filter(fn ($result): bool => filled($result->approved_date))->count();
        $verified = $results
            ->filter(fn ($result): bool => ! filled($result->approved_date) && filled($result->verified_date))
            ->count();
        $inserted = $results
            ->filter(fn ($result): bool => ! filled($result->approved_date) && ! filled($result->verified_date) && filled($result->inserted_date))
            ->count();
        $pending = $results
            ->filter(fn ($result): bool => ! filled($result->approved_date) && ! filled($result->verified_date) && ! filled($result->inserted_date))
            ->count();
        $counterAnalysis = $results
            ->filter(fn ($result): bool => (bool) $result->requested_counter_analysis || filled($result->counter_analysis))
            ->count();

        if ($results->isEmpty() && filled($resultId)) {
            $pending = 1;
        }

        $total = $results->count() ?: ($pending > 0 ? $pending : 0);

        return [
            'title' => 'Estado dos resultados analíticos',
            'labels' => ['Aprovados', 'Verificados', 'Inseridos', 'Pendentes', 'Contra-análise'],
            'values' => array_map(
                fn (int $value): string => (string) $value,
                [$approved, $verified, $inserted, $pending, $counterAnalysis]
            ),
            'caption' => "{$total} parâmetro(s) associados ao certificado; {$counterAnalysis} com contra-análise solicitada ou registada.",
        ];
    }

    /**
     * @return array{title: string, labels: array<int, string>, values: array<int, string>, caption: string}
     */
    private function analysisPreviewChartData(): array
    {
        return [
            'title' => 'Estado dos resultados analíticos',
            'labels' => ['Aprovados', 'Verificados', 'Inseridos', 'Pendentes', 'Contra-análise'],
            'values' => ['2', '1', '1', '0', '1'],
            'caption' => 'Pré-visualização com estados típicos de inserção, verificação, aprovação e contra-análise.',
        ];
    }

    /**
     * @param  array{title: string, labels: array<int, string>, values: array<int, string>, caption: string}  $chart
     */
    private function analysisChartCardHtml(array $chart): string
    {
        return $this->renderChartSnapshotBlock([
            'chart_title' => $chart['title'],
            'chart_caption' => $chart['caption'],
            'chart_type' => 'bar',
            'chart_labels' => $chart['labels'],
            'chart_values' => $chart['values'],
            'chart_colors' => ['#143d37', '#3f6f58', '#d9b05f', '#94a3b8', '#9f1d1d'],
            'chart_primary_color' => '#143d37',
            'chart_background_color' => '#f8f4ea',
            'chart_show_values' => true,
        ], []);
    }

    private function analysisSampleDetailsHtml(QualityCertificate $certificate): string
    {
        $collectionProduct = $certificate->collection;
        $sampleEntry = $collectionProduct?->sampleEntry;
        $clientInfo = $sampleEntry?->client_submitted_info ?? data_get($collectionProduct?->extra_data, 'submitted_payload', []);

        return $this->analysisDetailTableHtml([
            ['Código da Sample Entry', 'Sample entry code', $sampleEntry?->code ?: data_get($collectionProduct?->extra_data, 'sample_entry_id')],
            ['Nome da amostra', 'Sample name', $sampleEntry?->name ?: $collectionProduct?->comercial_brand],
            ['Tipo de amostra', 'Sample type', $sampleEntry?->sample_type ?: data_get($clientInfo, 'sample_type')],
            ['Produto', 'Product', $collectionProduct?->product?->name ?: data_get($clientInfo, 'product_name')],
            ['Matriz', 'Matrix', $collectionProduct?->product?->matrix?->description ?: data_get($clientInfo, 'matrix_description', data_get($clientInfo, 'matrix'))],
            ['Lote', 'Lot', $collectionProduct?->lot ?: data_get($clientInfo, 'lot')],
            ['Batch / OP', 'Batch', data_get($clientInfo, 'batch')],
            ['Origem', 'Origin', $collectionProduct?->origin ?: data_get($clientInfo, 'origin')],
            ['Fornecedor', 'Supplier', data_get($clientInfo, 'supplier_name')],
            ['Quantidade recebida', 'Received quantity', $collectionProduct?->qty ?: data_get($clientInfo, 'quantity')],
            ['Quantidade colhida', 'Collected quantity', $collectionProduct?->collected_qty ?: data_get($clientInfo, 'collected_qty')],
            ['Data de receção', 'Reception date', $this->analysisDateValue($sampleEntry?->received_at)],
            ['Data de colheita', 'Sampling date', $this->analysisDateValue($collectionProduct?->collection_date ?: $sampleEntry?->collected_at)],
        ], 'Identificação da amostra / Sample identification');
    }

    private function analysisCollectionDetailsHtml(QualityCertificate $certificate): string
    {
        $collectionProduct = $certificate->collection;
        $sampleEntry = $collectionProduct?->sampleEntry;
        $clientInfo = $sampleEntry?->client_submitted_info ?? data_get($collectionProduct?->extra_data, 'submitted_payload', []);

        return $this->analysisDetailTableHtml([
            ['Lab code', 'Laboratory code', $certificate->lab_code?->code ?: $collectionProduct?->code?->code],
            ['Armazém / local', 'Warehouse / site', $certificate->warehouse?->name ?: $certificate->warehouse?->address],
            ['Local de colheita', 'Sampling location', $collectionProduct?->location ?: data_get($clientInfo, 'location', data_get($clientInfo, 'collection_location'))],
            ['Plano de amostragem', 'Sampling plan', $collectionProduct?->sampling_plan_ref ?: data_get($clientInfo, 'sampling_plan_ref')],
            ['Condição da embalagem', 'Packaging condition', data_get($clientInfo, 'packaging_condition')],
            ['Condição térmica', 'Thermal condition', data_get($clientInfo, 'temperature_condition') ?: $collectionProduct?->temperature_value],
            ['Contentor', 'Container', $collectionProduct?->container_no],
            ['DU', 'Customs document', $collectionProduct?->du_no],
            ['Termo', 'Term', $collectionProduct?->term_no],
            ['BL', 'Bill of lading', $collectionProduct?->bl],
            ['Produção', 'Production date', $this->analysisDateValue($collectionProduct?->production_date ?: data_get($clientInfo, 'production_date'))],
            ['Validade', 'Expiry date', $this->analysisDateValue($collectionProduct?->expiry_date ?: data_get($clientInfo, 'expiry_date'))],
            ['Cadeia de custódia', 'Chain of custody', data_get($clientInfo, 'chain_of_custody_notes')],
            ['Observações de integridade', 'Integrity observations', data_get($clientInfo, 'integrity_observations')],
        ], 'Receção e cadeia de custódia / Reception and chain of custody');
    }

    private function analysisScopeDetailsHtml(QualityCertificate $certificate): string
    {
        $collectionProduct = $certificate->collection;
        $sampleEntry = $collectionProduct?->sampleEntry;
        $clientInfo = $sampleEntry?->client_submitted_info ?? data_get($collectionProduct?->extra_data, 'submitted_payload', []);
        $profiles = collect(data_get($clientInfo, 'resolved_profiles', []))
            ->map(fn (array $profile) => trim(($profile['name'] ?? '').(($profile['analysis_type'] ?? null) ? ' · '.$profile['analysis_type'] : '')))
            ->filter()
            ->values()
            ->implode('; ');
        $parameters = collect(data_get($clientInfo, 'required_parameters', []))
            ->map(fn (array $parameter) => trim(($parameter['code'] ?? '').' '.($parameter['name'] ?? '')))
            ->filter()
            ->take(18)
            ->values()
            ->implode('; ');

        return $this->analysisDetailTableHtml([
            ['Perfis analíticos', 'Analytical profiles', $profiles],
            ['Parâmetros previstos', 'Expected parameters', $parameters],
            ['Total de parâmetros', 'Parameter count', data_get($clientInfo, 'required_parameter_count')],
            ['Serviços solicitados', 'Requested services', is_array($sampleEntry?->requested_services) ? implode('; ', $sampleEntry->requested_services) : $sampleEntry?->requested_services],
            ['Regra de decisão', 'Decision rule', data_get($clientInfo, 'decision_rule')],
            ['Observações', 'Observations', $sampleEntry?->obs ?: $collectionProduct?->obs],
        ], 'Âmbito analítico / Analytical scope');
    }

    private function analysisResultsTableHtml(QualityCertificate $certificate, mixed $resultId): string
    {
        $rows = collect($certificate->results ?? [])
            ->sortBy(fn ($result) => $result->parameter?->name ?: $result->parameter_label ?: $result->id)
            ->map(function ($result): array {
                $parameter = $result->parameter_label ?: $result->parameter?->name ?: 'Parâmetro';
                $method = $result->standard_label ?: $result->standard?->name ?: $result->protocol_label ?: $result->protocol?->name ?: 'Método registado';
                $value = $this->formatAnalysisResultValue($result);
                $unit = $result->unit_label ?: $result->unit?->name ?: '—';
                $uncertainty = $result->uncertainty_value ?: data_get($result->calculation_metadata, 'uncertainty') ?: '—';
                $status = $result->approved_date ? 'Aprovado' : ($result->verified_date ? 'Verificado' : ($result->inserted_date ? 'Inserido' : 'Pendente'));
                $counterAnalysis = $result->requested_counter_analysis || $result->counter_analysis ? "\nContra-análise associada" : '';

                return [
                    $parameter,
                    $method,
                    (string) $value,
                    (string) $unit,
                    (string) $uncertainty,
                    $status.$counterAnalysis,
                ];
            })
            ->values()
            ->all();

        if ($rows === []) {
            $rows = [[
                'Resultado associado #'.(string) $resultId,
                'Método registado',
                'Conforme o método registado',
                'N/A',
                'Consultar método / cálculo',
                'Pendente',
            ]];
        }

        return $this->reportTableHtml(
            [
                ['label' => 'Parâmetro', 'translation' => 'Parameter'],
                ['label' => 'Método', 'translation' => 'Method'],
                ['label' => 'Resultado', 'translation' => 'Result'],
                ['label' => 'Unidade', 'translation' => 'Unit'],
                ['label' => 'Incerteza', 'translation' => 'Uncertainty'],
                ['label' => 'Estado', 'translation' => 'Status'],
            ],
            $rows,
            'Sem resultados registados.'
        );
    }

    private function formatAnalysisResultValue(mixed $result): string
    {
        $value = $result->approved_value ?: $result->verified_value ?: $result->inserted_value;

        if (blank($value)) {
            return '—';
        }

        if (data_get($result->extra_data, 'display_format') !== 'scientific' || ! is_numeric((string) $value)) {
            return (string) $value;
        }

        $decimalPlaces = (int) ($result->parameter?->decimal_places ?? $result->decimal_places ?? 2);
        $precision = min(max($decimalPlaces, 0), 8);

        return str_replace('E', ' × 10^', sprintf('%.'.$precision.'E', (float) $value));
    }

    /**
     * @param  array<int, array{0:string, 1:string, 2:mixed}>  $rows
     */
    private function analysisDetailTableHtml(array $rows, string $title): string
    {
        $bodyRows = collect($rows)
            ->filter(fn (array $row) => filled($row[2] ?? null))
            ->map(function (array $row): string {
                return '<tr>'
                    .'<th style="width:34%; text-align:left; padding:7px; border-bottom:1px solid #e2e8f0; color:#334155;">'.e($row[0]).'<br><span class="bilingual-label">'.e($row[1]).'</span></th>'
                    .'<td style="padding:7px; border-bottom:1px solid #e2e8f0;">'.nl2br(e((string) $row[2]), false).'</td>'
                    .'</tr>';
            })
            ->implode('');

        if ($bodyRows === '') {
            $bodyRows = '<tr><td style="padding:7px; color:#64748b;">Sem dados registados.</td></tr>';
        }

        return <<<HTML
<section class="studio-avoid-break" style="margin:18px 0;">
    <h2 style="margin:0 0 8px; font-size:14px; color:#0f172a;">{$title}</h2>
    <table class="report-table" style="width:100%; border-collapse:collapse; font-size:10px;">
        <tbody>
            {$bodyRows}
        </tbody>
    </table>
</section>
HTML;
    }

    private function analysisDateValue(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $canvasBlocks
     * @param  array<string, mixed>  $data
     */
    private function buildSurfaceHtml(string $baseHtml, array $canvasBlocks, string $surface, array $data): string
    {
        if ($surface === 'content') {
            return $this->buildContentSurfaceHtml($baseHtml, $canvasBlocks, $data);
        }

        $interpolatedBase = $this->interpolate($baseHtml, $data);
        $blocks = $this->renderCanvasBlocks($canvasBlocks, $surface, $data);

        if ($blocks === '') {
            return $interpolatedBase;
        }

        return <<<HTML
<div style="position:relative;">
    {$interpolatedBase}
    {$blocks}
</div>
HTML;
    }

    /**
     * @param  array<int, array<string, mixed>>  $canvasBlocks
     * @param  array<string, mixed>  $data
     */
    private function buildContentSurfaceHtml(string $baseHtml, array $canvasBlocks, array $data): string
    {
        $interpolatedBase = $this->interpolate($baseHtml, $data);
        $segments = preg_split(self::PAGE_BREAK_PATTERN, $interpolatedBase) ?: [$interpolatedBase];
        $pageSeparator = '<pagebreak />';

        $pages = collect($segments)->values()->map(function (string $segment, int $index) use ($canvasBlocks, $data): string {
            $pageNumber = $index + 1;
            $blocks = $this->renderCanvasBlocks($canvasBlocks, 'content', $data, $pageNumber);

            if ($blocks === '') {
                return $segment;
            }

            return <<<HTML
<div class="studio-canvas-page studio-canvas-page-{$pageNumber}" style="position:relative;">
    {$segment}
    {$blocks}
</div>
HTML;
        });

        return $pages->implode($pageSeparator);
    }

    /**
     * @param  array<int, array<string, mixed>>  $canvasBlocks
     * @param  array<string, mixed>  $data
     */
    private function renderCanvasBlocks(array $canvasBlocks, string $surface, array $data, ?int $pageNumber = null): string
    {
        $blocksForSurface = collect($canvasBlocks)
            ->filter(function (array $block) use ($surface, $pageNumber): bool {
                if (! empty($block['is_hidden'])) {
                    return false;
                }

                if (($block['surface'] ?? null) !== $surface) {
                    return false;
                }

                if ($surface !== 'content' || $pageNumber === null) {
                    return true;
                }

                return $this->contentBlockAppliesToPage($block, $pageNumber);
            })
            ->sortBy('z_index')
            ->values();

        if ($blocksForSurface->isEmpty()) {
            return '';
        }

        return $blocksForSurface->map(function (array $block) use ($data): string {
            $content = $this->renderCanvasBlockContent($block, $data);
            $blockKind = (string) ($block['block_kind'] ?? 'rich_text');
            $x = (float) ($block['x'] ?? 0);
            $y = (float) ($block['y'] ?? 0);
            $width = (float) ($block['width'] ?? 100);
            $minHeight = (float) ($block['min_height'] ?? 0);
            $zIndex = (int) ($block['z_index'] ?? 10);
            $padding = (float) ($block['padding'] ?? 0);
            $borderRadius = (float) ($block['border_radius'] ?? 0);
            $backgroundColor = $this->safeCssColor((string) ($block['background_color'] ?? ''), '');
            $backgroundImage = (string) ($block['background_image'] ?? '');
            $backgroundImageFit = $this->safeCssImageFit((string) ($block['background_image_fit'] ?? 'cover'), 'cover');
            $backgroundImagePosition = $this->safeCssPosition((string) ($block['background_image_position'] ?? 'center center'), 'center center');
            $overlayColor = $this->safeCssColor((string) ($block['overlay_color'] ?? ''), '');
            $overlayOpacity = max(0, min(1, (float) ($block['overlay_opacity'] ?? 0.35)));
            $textColor = $this->safeCssColor((string) ($block['text_color'] ?? ''), '');
            $borderWidth = (float) ($block['border_width'] ?? 0);
            $borderColor = $this->safeCssColor((string) ($block['border_color'] ?? ''), 'rgba(148,163,184,0.35)');
            $opacity = max(0.05, min(1, (float) ($block['opacity'] ?? 1)));
            $rotation = max(-45, min(45, (float) ($block['rotation_deg'] ?? 0)));
            $shadowPreset = (string) ($block['shadow_preset'] ?? 'soft');
            $textAlign = (string) ($block['text_align'] ?? 'left');
            $fontSize = isset($block['font_size']) && $block['font_size'] !== '' ? (float) $block['font_size'] : null;
            $lineHeight = isset($block['line_height']) && $block['line_height'] !== '' ? (float) $block['line_height'] : null;

            $backgroundStyle = $backgroundColor !== '' ? "background: {$backgroundColor};" : '';
            $backgroundImageStyle = '';

            if ($backgroundImage !== '') {
                $resolvedBackgroundImage = $this->resolvePdfImageSource($backgroundImage);

                $backgroundImageStyle = sprintf(
                    'background-image: url("%s"); background-size: %s; background-position: %s; background-repeat: no-repeat;',
                    $this->safeCssUrlString($resolvedBackgroundImage),
                    $backgroundImageFit,
                    $backgroundImagePosition,
                );
            }

            $textColorStyle = $textColor !== '' ? "color: {$textColor};" : '';
            $borderStyle = $borderWidth > 0
                ? sprintf('border: %spx solid %s;', $borderWidth, $borderColor)
                : '';
            $fontSizeStyle = $fontSize !== null ? "font-size: {$fontSize}px;" : '';
            $lineHeightStyle = $lineHeight !== null ? "line-height: {$lineHeight};" : '';
            $textAlignStyle = in_array($textAlign, ['left', 'center', 'right', 'justify'], true)
                ? "text-align: {$textAlign};"
                : '';
            $opacityStyle = "opacity: {$opacity};";
            $transformStyle = abs($rotation) > 0
                ? 'transform: rotate('.round($rotation, 2).'deg); transform-origin: center center;'
                : '';
            $shadowStyle = $this->canvasBlockShadowStyle($shadowPreset);
            $mediaFrameStyle = in_array($blockKind, ['image', 'stamp'], true) ? 'overflow: hidden;' : '';
            $overlayHtml = '';

            if ($overlayColor !== '') {
                $overlayHtml = sprintf(
                    '<div style="position:absolute; inset:0; border-radius:%spx; background:%s; opacity:%s;"></div>',
                    $borderRadius,
                    $overlayColor,
                    $overlayOpacity
                );
            }

            return <<<HTML
<div style="position:absolute; left: {$x}%; top: {$y}%; width: {$width}%; min-height: {$minHeight}px; z-index: {$zIndex}; padding: {$padding}px; border-radius: {$borderRadius}px; {$backgroundStyle} {$backgroundImageStyle} {$textColorStyle} {$borderStyle} {$fontSizeStyle} {$lineHeightStyle} {$textAlignStyle} {$opacityStyle} {$transformStyle} {$shadowStyle} {$mediaFrameStyle}">
    {$overlayHtml}
    <div style="position:relative; z-index:1; min-height:inherit;">
        {$content}
    </div>
</div>
HTML;
        })->implode("\n");
    }

    private function canvasBlockShadowStyle(string $preset): string
    {
        return match ($preset) {
            'none' => 'box-shadow: none;',
            'elevated' => 'box-shadow: 0 22px 55px rgba(15, 23, 42, 0.18);',
            'stamp' => 'box-shadow: 0 10px 22px rgba(20, 61, 55, 0.22);',
            'glow' => 'box-shadow: 0 0 0 6px rgba(217, 176, 95, 0.16), 0 20px 45px rgba(15, 23, 42, 0.16);',
            default => 'box-shadow: 0 14px 32px rgba(15, 23, 42, 0.10);',
        };
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $data
     */
    private function renderCanvasBlockContent(array $block, array $data): string
    {
        return match ($block['block_kind'] ?? 'rich_text') {
            'signature' => $this->renderSignatureBlock($block, $data),
            'image', 'stamp' => $this->renderImageBlock($block, $data),
            'qr_code' => $this->renderQrCodeBlock($block, $data),
            'chart_snapshot' => $this->renderChartSnapshotBlock($block, $data),
            default => $this->interpolate((string) ($block['content_html'] ?? ''), $data),
        };
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $data
     */
    private function renderSignatureBlock(array $block, array $data): string
    {
        $signatureLabel = $this->interpolate((string) ($block['signature_label'] ?? ''), $data);
        $signatureName = $this->interpolate((string) ($block['signature_name'] ?? ''), $data);
        $signatureTitle = $this->interpolate((string) ($block['signature_title'] ?? ''), $data);
        $signatureDateLabel = $this->interpolate(
            (string) ($block['signature_date_label'] ?? 'Data: ____ / ____ / ______'),
            $data
        );
        $signatureAlign = (string) ($block['signature_align'] ?? 'left');
        $signatureLineStyle = (string) ($block['signature_line_style'] ?? 'solid');
        $signatureImage = (string) ($block['signature_image'] ?? '');
        $signatureImagePosition = $this->safeCssPosition((string) ($block['signature_image_position'] ?? 'center center'), 'center center');
        $signatureImageWidth = $this->clampedCssNumber($block['signature_image_width'] ?? null, 24, 360, 180);
        $signatureImageHeight = $this->clampedCssNumber($block['signature_image_height'] ?? null, 16, 240, 72);
        $signatureImageFit = match ((string) ($block['signature_image_fit'] ?? 'contain')) {
            'cover' => 'cover',
            'auto' => 'scale-down',
            default => 'contain',
        };

        $justify = match ($signatureAlign) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };

        $textAlign = in_array($signatureAlign, ['left', 'center', 'right'], true) ? $signatureAlign : 'left';
        $lineBorder = $signatureLineStyle === 'dashed' ? '2px dashed rgba(148,163,184,0.85)' : '2px solid rgba(15,23,42,0.85)';
        $signatureImageHtml = '';

        if ($signatureImage !== '') {
            $resolvedSignatureImage = $this->resolvePdfImageSource($signatureImage);

            $signatureImageHtml = sprintf(
                '<img src="%s" alt="Assinatura" style="display:block; width:%spx; max-width:100%%; height:%spx; object-fit:%s; object-position:%s;" />',
                e($resolvedSignatureImage),
                $signatureImageWidth,
                $signatureImageHeight,
                $signatureImageFit,
                $signatureImagePosition,
            );
        }

        $dateHtml = ! empty($block['signature_show_date'])
            ? sprintf(
                '<div style="margin-top:8px; font-size:11px; color:#64748b;">%s</div>',
                e($signatureDateLabel)
            )
            : '';
        $labelHtml = $signatureLabel !== ''
            ? sprintf(
                '<div style="font-size:11px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase; color:#64748b;">%s</div>',
                e($signatureLabel)
            )
            : '';
        $nameHtml = $signatureName !== ''
            ? sprintf('<div style="font-size:14px; font-weight:700; color:#0f172a;">%s</div>', e($signatureName))
            : '';
        $titleHtml = $signatureTitle !== ''
            ? sprintf('<div style="font-size:12px; color:#475569;">%s</div>', e($signatureTitle))
            : '';

        return <<<HTML
<div style="display:flex; min-height:100%; flex-direction:column; justify-content:flex-end; text-align: {$textAlign};">
    <div style="display:flex; justify-content: {$justify};">
        <div style="width:min(100%, 280px);">
            <div style="display:flex; justify-content: {$justify}; margin-bottom:10px;">
                {$signatureImageHtml}
            </div>
            <div style="width:100%; border-top: {$lineBorder};"></div>
            <div style="margin-top:10px;">
                {$labelHtml}
                {$nameHtml}
                {$titleHtml}
                {$dateHtml}
            </div>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $data
     */
    private function renderChartSnapshotBlock(array $block, array $data): string
    {
        $title = $this->interpolate((string) ($block['chart_title'] ?? ''), $data);
        $caption = $this->interpolate((string) ($block['chart_caption'] ?? ''), $data);
        $chartSvg = $this->sanitizeChartSvg(trim($this->interpolate((string) ($block['chart_svg'] ?? ''), $data)));
        $chartImageUrl = trim($this->interpolate((string) ($block['chart_image_url'] ?? ''), $data));
        $generatedChartSvg = $this->renderGeneratedChartSvg($block, $data);

        $titleHtml = $title !== ''
            ? sprintf('<div style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:8px;">%s</div>', e($title))
            : '';
        $captionHtml = $caption !== ''
            ? sprintf('<div style="font-size:10px; color:#64748b; margin-top:8px;">%s</div>', e($caption))
            : '';

        if ($chartSvg !== '') {
            $chartHtml = $chartSvg;
        } elseif ($chartImageUrl !== '') {
            $resolvedChartImage = $this->resolvePdfImageSource($chartImageUrl);

            $chartHtml = sprintf(
                '<img src="%s" alt="%s" style="display:block; width:100%%; max-width:100%%; height:auto; object-fit:contain;" />',
                e($resolvedChartImage),
                e($title ?: 'Gráfico')
            );
        } elseif ($generatedChartSvg !== '') {
            $chartHtml = $generatedChartSvg;
        } else {
            $chartHtml = '<div style="display:flex; min-height:140px; align-items:center; justify-content:center; border:1px dashed #94a3b8; border-radius:16px; color:#64748b; font-size:11px;">Adicione dados, SVG exportado do ApexCharts ou uma imagem do gráfico.</div>';
        }

        return <<<HTML
<div class="report-chart studio-avoid-break" style="width:100%;">
    {$titleHtml}
    <div style="width:100%; overflow:hidden;">
        {$chartHtml}
    </div>
    {$captionHtml}
</div>
HTML;
    }

    private function sanitizeChartSvg(string $svg): string
    {
        if ($svg === '' || preg_match('/\A\s*<svg(?:\s|>)/i', $svg) !== 1) {
            return '';
        }

        $sanitized = (string) preg_replace(
            [
                '/<\s*(script|iframe|object|embed|foreignObject|link|meta)\b[^>]*>.*?<\s*\/\s*\1\s*>/is',
                '/<\s*(script|iframe|object|embed|foreignObject|link|meta)\b[^>]*\/?\s*>/is',
                '/\s+on[a-zA-Z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
                '/\s+(?:xlink:href|href)\s*=\s*("[\']?)\s*javascript:[^"\'>\s]*\1/i',
                '/url\(\s*([\'"]?)\s*javascript:[^)]+\)/i',
            ],
            '',
            $svg
        );

        return preg_match('/\A[\s\S]*?<\s*\/\s*svg\s*>/i', $sanitized, $matches) === 1
            ? trim($matches[0])
            : '';
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $data
     */
    private function renderGeneratedChartSvg(array $block, array $data): string
    {
        $values = $this->chartNumericValues($block, $data);

        if ($values === []) {
            return '';
        }

        $labels = $this->chartLabelValues($block, count($values), $data);
        $colors = $this->chartColorValues($block, $data);
        $type = in_array(($block['chart_type'] ?? 'bar'), ['bar', 'line', 'doughnut'], true)
            ? (string) $block['chart_type']
            : 'bar';
        $title = e($this->interpolate((string) ($block['chart_title'] ?? $block['title'] ?? 'Gráfico'), $data));
        $backgroundColor = $this->hexColor((string) ($block['chart_background_color'] ?? '#f8f4ea'), '#f8f4ea');
        $primaryColor = $this->hexColor((string) ($block['chart_primary_color'] ?? ($colors[0] ?? '#143d37')), '#143d37');
        $showValues = (bool) ($block['chart_show_values'] ?? true);
        $palette = $this->chartSvgPalette($backgroundColor);

        return match ($type) {
            'line' => $this->renderLineChartSvg($values, $labels, $colors, $title, $backgroundColor, $primaryColor, $showValues, $data, $palette),
            'doughnut' => $this->renderDoughnutChartSvg($values, $labels, $colors, $title, $backgroundColor, $data, $palette),
            default => $this->renderBarChartSvg($values, $labels, $colors, $title, $backgroundColor, $showValues, $data, $palette),
        };
    }

    /**
     * @param  array<int, float>  $values
     * @param  array<int, string>  $labels
     * @param  array<int, string>  $colors
     * @param  array{ink:string, muted:string, grid:string, marker_stroke:string}  $palette
     */
    private function renderBarChartSvg(array $values, array $labels, array $colors, string $title, string $backgroundColor, bool $showValues, array $data, array $palette): string
    {
        $maxValue = max(max($values), 1);
        $slot = 470 / max(count($values), 1);
        $bars = collect($values)->map(function (float $value, int $index) use ($maxValue, $slot, $labels, $colors, $showValues, $data, $palette): string {
            $height = max(6, ($value / $maxValue) * 132);
            $x = 58 + ($index * $slot) + max(6, $slot * 0.15);
            $y = 202 - $height;
            $width = max(18, $slot * 0.7);
            $valueText = $showValues ? sprintf(
                '<text x="%.1f" y="%.1f" text-anchor="middle" font-size="11" font-weight="700" fill="%s">%s</text>',
                $x + ($width / 2),
                $y - 10,
                $palette['ink'],
                e($this->formatChartValue($value))
            ) : '';

            return sprintf(
                '<g><rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="10" fill="%s"/>%s<text x="%.1f" y="230" text-anchor="middle" font-size="10" fill="%s">%s</text></g>',
                $x,
                $y,
                $width,
                $height,
                $colors[$index % count($colors)],
                $valueText,
                $x + ($width / 2),
                $palette['muted'],
                e($this->interpolate($labels[$index] ?? 'S'.($index + 1), $data))
            );
        })->implode('');

        return <<<SVG
<svg class="report-chart-svg" data-chart-type="bar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="{$title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="{$backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="{$palette['ink']}">{$title}</text><line x1="52" y1="202" x2="532" y2="202" stroke="{$palette['grid']}"/><line x1="52" y1="70" x2="52" y2="202" stroke="{$palette['grid']}"/>{$bars}</svg>
SVG;
    }

    /**
     * @param  array<int, float>  $values
     * @param  array<int, string>  $labels
     * @param  array<int, string>  $colors
     * @param  array{ink:string, muted:string, grid:string, marker_stroke:string}  $palette
     */
    private function renderLineChartSvg(array $values, array $labels, array $colors, string $title, string $backgroundColor, string $primaryColor, bool $showValues, array $data, array $palette): string
    {
        $maxValue = max(max($values), 1);
        $pointCount = max(count($values) - 1, 1);
        $points = collect($values)->map(function (float $value, int $index) use ($maxValue, $pointCount): string {
            $x = 58 + ($index * (470 / $pointCount));
            $y = 202 - (($value / $maxValue) * 132);

            return sprintf('%.1f,%.1f', $x, $y);
        })->implode(' ');
        $markers = collect($values)->map(function (float $value, int $index) use ($maxValue, $pointCount, $labels, $colors, $showValues, $data, $palette): string {
            $x = 58 + ($index * (470 / $pointCount));
            $y = 202 - (($value / $maxValue) * 132);
            $valueText = $showValues ? sprintf(
                '<text x="%.1f" y="%.1f" text-anchor="middle" font-size="10" font-weight="700" fill="%s">%s</text>',
                $x,
                $y - 12,
                $palette['ink'],
                e($this->formatChartValue($value))
            ) : '';

            return sprintf(
                '<g><circle cx="%.1f" cy="%.1f" r="5" fill="%s" stroke="%s" stroke-width="2"/>%s<text x="%.1f" y="230" text-anchor="middle" font-size="10" fill="%s">%s</text></g>',
                $x,
                $y,
                $colors[$index % count($colors)],
                $palette['marker_stroke'],
                $valueText,
                $x,
                $palette['muted'],
                e($this->interpolate($labels[$index] ?? 'S'.($index + 1), $data))
            );
        })->implode('');

        return <<<SVG
<svg class="report-chart-svg" data-chart-type="line" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="{$title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="{$backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="{$palette['ink']}">{$title}</text><line x1="52" y1="202" x2="532" y2="202" stroke="{$palette['grid']}"/><line x1="52" y1="70" x2="52" y2="202" stroke="{$palette['grid']}"/><polyline points="{$points}" fill="none" stroke="{$primaryColor}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>{$markers}</svg>
SVG;
    }

    /**
     * @param  array<int, float>  $values
     * @param  array<int, string>  $labels
     * @param  array<int, string>  $colors
     * @param  array{ink:string, muted:string, grid:string, marker_stroke:string}  $palette
     */
    private function renderDoughnutChartSvg(array $values, array $labels, array $colors, string $title, string $backgroundColor, array $data, array $palette): string
    {
        $total = array_sum(array_map(fn (float $value): float => max($value, 0), $values)) ?: 1;
        $circumference = 251.2;
        $offset = 0.0;
        $rings = '';

        foreach ($values as $index => $value) {
            $segment = (max($value, 0) / $total) * $circumference;
            $rings .= sprintf(
                '<circle cx="130" cy="128" r="40" fill="none" stroke="%s" stroke-width="18" stroke-dasharray="%.2f %.2f" stroke-dashoffset="%.2f" transform="rotate(-90 130 128)" />',
                $colors[$index % count($colors)],
                $segment,
                $circumference - $segment,
                -$offset
            );
            $offset += $segment;
        }

        $legend = collect($values)->map(function (float $value, int $index) use ($labels, $colors, $data, $palette): string {
            $y = 70 + ($index * 26);

            return sprintf(
                '<g><rect x="250" y="%d" width="12" height="12" rx="3" fill="%s"/><text x="272" y="%d" font-size="12" fill="%s">%s</text><text x="520" y="%d" font-size="12" font-weight="700" fill="%s" text-anchor="end">%s</text></g>',
                $y - 10,
                $colors[$index % count($colors)],
                $y,
                $palette['muted'],
                e($this->interpolate($labels[$index] ?? 'S'.($index + 1), $data)),
                $y,
                $palette['ink'],
                e($this->formatChartValue($value))
            );
        })->implode('');
        $totalText = e($this->formatChartValue($total));

        return <<<SVG
<svg class="report-chart-svg" data-chart-type="doughnut" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="{$title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="{$backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="{$palette['ink']}">{$title}</text><circle cx="130" cy="128" r="40" fill="none" stroke="{$palette['grid']}" stroke-width="18"/>{$rings}<text x="130" y="126" text-anchor="middle" font-size="18" font-weight="800" fill="{$palette['ink']}">{$totalText}</text><text x="130" y="144" text-anchor="middle" font-size="10" fill="{$palette['muted']}">total</text>{$legend}</svg>
SVG;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<int, float>
     */
    private function chartNumericValues(array $block, array $data): array
    {
        return collect($this->chartListValue($block['chart_values'] ?? $block['chart_series'] ?? [], $data))
            ->filter(fn (string $value): bool => is_numeric(str_replace(',', '.', $value)))
            ->map(fn (string $value): float => (float) str_replace(',', '.', $value))
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<int, string>
     */
    private function chartLabelValues(array $block, int $count, array $data): array
    {
        $labels = $this->chartListValue($block['chart_labels'] ?? [], $data);

        if ($labels !== []) {
            return array_slice($labels, 0, max($count, 1));
        }

        return collect(range(1, max($count, 1)))
            ->map(fn (int $index): string => 'S'.$index)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<int, string>
     */
    private function chartColorValues(array $block, array $data): array
    {
        $colors = collect($this->chartListValue($block['chart_colors'] ?? [], $data))
            ->filter(fn (string $value): bool => preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1)
            ->values()
            ->all();

        return $colors !== [] ? $colors : self::DEFAULT_CHART_PALETTE;
    }

    /**
     * @return array<int, string>
     */
    private function chartListValue(mixed $value, array $data = []): array
    {
        if (is_array($value)) {
            return collect($value)
                ->flatMap(fn (mixed $item): array => $this->splitChartListString($this->interpolate((string) $item, $data)))
                ->filter()
                ->values()
                ->all();
        }

        return collect($this->splitChartListString($this->interpolate((string) $value, $data)))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function splitChartListString(string $value): array
    {
        return collect(preg_split('/[\r\n;]+|(?<!\d),|,(?!\d)/', $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function hexColor(string $value, string $fallback): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : $fallback;
    }

    /**
     * @return array{ink:string, muted:string, grid:string, marker_stroke:string}
     */
    private function chartSvgPalette(string $backgroundColor): array
    {
        $rgb = $this->hexColorRgb($backgroundColor) ?? $this->hexColorRgb('#f8f4ea');
        $luminance = $this->relativeLuminance($rgb ?? [248, 244, 234]);

        if ($luminance < 0.42) {
            return [
                'ink' => '#fffdf7',
                'muted' => '#d7e3dc',
                'grid' => '#48615a',
                'marker_stroke' => '#07110f',
            ];
        }

        return [
            'ink' => '#0f172a',
            'muted' => '#64748b',
            'grid' => '#cbd5e1',
            'marker_stroke' => '#fffaf0',
        ];
    }

    /**
     * @return array{0:int, 1:int, 2:int}|null
     */
    private function hexColorRgb(string $hexColor): ?array
    {
        if (preg_match('/^#([0-9a-fA-F]{6})$/', trim($hexColor), $matches) !== 1) {
            return null;
        }

        return [
            hexdec(substr($matches[1], 0, 2)),
            hexdec(substr($matches[1], 2, 2)),
            hexdec(substr($matches[1], 4, 2)),
        ];
    }

    /**
     * @param  array{0:int, 1:int, 2:int}  $rgb
     */
    private function relativeLuminance(array $rgb): float
    {
        $channels = array_map(function (int $value): float {
            $normalized = $value / 255;

            return $normalized <= 0.03928
                ? $normalized / 12.92
                : (($normalized + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    private function formatChartValue(float $value): string
    {
        return floor($value) === $value
            ? (string) (int) $value
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $data
     */
    private function renderImageBlock(array $block, array $data): string
    {
        $imageUrl = $this->interpolate((string) ($block['image_url'] ?? $block['background_image'] ?? ''), $data);

        if ($imageUrl === '') {
            return '';
        }

        $resolvedImageUrl = $this->resolvePdfImageSource($imageUrl);
        $imageFit = (string) ($block['image_fit'] ?? $block['background_image_fit'] ?? 'contain');
        $imagePosition = $this->safeCssPosition((string) ($block['image_position'] ?? 'center center'), 'center center');
        $imageAlt = e($this->interpolate((string) ($block['image_alt'] ?? $block['title'] ?? 'Imagem'), $data));
        $objectFit = match ($imageFit) {
            'cover' => 'cover',
            'auto' => 'scale-down',
            default => 'contain',
        };

        return sprintf(
            '<img src="%s" alt="%s" style="display:block; width:100%%; height:100%%; min-height:inherit; object-fit:%s; object-position:%s;" />',
            e($resolvedImageUrl),
            $imageAlt,
            $objectFit,
            $imagePosition,
        );
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $data
     */
    private function renderQrCodeBlock(array $block, array $data): string
    {
        $qrContent = $this->interpolate((string) ($block['qr_content'] ?? $block['content_html'] ?? '{document_code}'), $data);

        if (trim($qrContent) === '') {
            return '';
        }

        $qrMargin = max(0, min(32, (int) ($block['qr_margin'] ?? 8)));

        $qrCode = new QrCode(
            data: $qrContent,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: $this->qrCodeErrorCorrectionLevel((string) ($block['qr_error_correction'] ?? 'low')),
            size: 300,
            margin: $qrMargin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: $this->qrCodeColor((string) ($block['qr_foreground_color'] ?? '#0f172a'), [15, 23, 42]),
            backgroundColor: $this->qrCodeColor((string) ($block['qr_background_color'] ?? '#ffffff'), [255, 255, 255]),
        );

        $qrDataUri = (new SvgWriter)->write($qrCode)->getDataUri();
        $label = $this->interpolate((string) ($block['qr_label'] ?? ''), $data);
        $labelHtml = $label !== ''
            ? sprintf('<div style="margin-top:6px; font-size:10px; color:#475569; text-align:center;">%s</div>', e($label))
            : '';

        return <<<HTML
<div style="display:flex; min-height:100%; flex-direction:column; align-items:center; justify-content:center;">
    <img src="{$qrDataUri}" alt="QR code" style="display:block; width:100%; max-width:148px; height:auto;" />
    {$labelHtml}
</div>
HTML;
    }

    /**
     * @param  array{0:int, 1:int, 2:int}  $fallback
     */
    private function qrCodeColor(string $hexColor, array $fallback): Color
    {
        if (! preg_match('/^#([0-9a-fA-F]{6})$/', $hexColor, $matches)) {
            return new Color(...$fallback);
        }

        return new Color(
            hexdec(substr($matches[1], 0, 2)),
            hexdec(substr($matches[1], 2, 2)),
            hexdec(substr($matches[1], 4, 2)),
        );
    }

    private function qrCodeErrorCorrectionLevel(string $value): ErrorCorrectionLevel
    {
        return match ($value) {
            'high' => ErrorCorrectionLevel::High,
            'medium' => ErrorCorrectionLevel::Medium,
            'quartile' => ErrorCorrectionLevel::Quartile,
            default => ErrorCorrectionLevel::Low,
        };
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function contentBlockAppliesToPage(array $block, int $pageNumber): bool
    {
        $pageScope = (string) ($block['page_scope'] ?? 'first');

        return match ($pageScope) {
            'all' => true,
            'following' => $pageNumber > 1,
            'specific' => (int) ($block['page_number'] ?? 0) === $pageNumber,
            'first' => $pageNumber === 1,
            default => $pageNumber === 1,
        };
    }

    private function resolvePdfImageSource(string $source): string
    {
        $source = trim($source);

        if ($source === '' || str_starts_with($source, 'data:image/')) {
            return $source;
        }

        $localPublicPath = $this->resolveLocalPublicAssetPath($source);

        if ($localPublicPath !== null) {
            return $localPublicPath;
        }

        return filter_var($source, FILTER_VALIDATE_URL)
            ? $source
            : public_path(ltrim($source, '/'));
    }

    private function resolveLocalPublicAssetPath(string $source): ?string
    {
        $path = parse_url($source, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $host = parse_url($source, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            $allowedHosts = array_filter([
                parse_url((string) config('app.url'), PHP_URL_HOST),
                request()->getHost(),
            ]);

            if (! in_array($host, $allowedHosts, true)) {
                return null;
            }
        }

        $publicRelativePath = ltrim($path, '/');

        if (! str_starts_with($publicRelativePath, 'storage/') && ! str_starts_with($publicRelativePath, 'images/')) {
            return null;
        }

        return public_path($publicRelativePath);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeStructuredArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function mergeLayoutSchema(array $base, array $override): array
    {
        $layout = array_replace_recursive($base, $override);

        foreach (['canvas_blocks', 'sections', 'variable_catalog'] as $replaceableListKey) {
            if (array_key_exists($replaceableListKey, $override)) {
                $layout[$replaceableListKey] = $override[$replaceableListKey];
            }
        }

        return $layout;
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function stylesCss(array $layout): string
    {
        $styles = $this->stripGeneratedStudioCss((string) data_get($layout, 'styles_css', ''));
        $generatedStyles = array_filter([
            $this->documentControlCss($layout),
            $this->tableControlCss($layout),
        ]);

        return trim(implode("\n\n", array_filter([
            implode("\n\n", $generatedStyles),
            $styles,
        ])));
    }

    private function stripGeneratedStudioCss(string $styles): string
    {
        return trim((string) preg_replace(
            [
                '/\/\* studio-document-controls:start \*\/[\s\S]*?\/\* studio-document-controls:end \*\//',
                '/\/\* studio-table-controls:start \*\/[\s\S]*?\/\* studio-table-controls:end \*\//',
            ],
            '',
            $styles
        ));
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function documentControlCss(array $layout): string
    {
        $fontFamily = $this->safeCssFontFamily((string) data_get($layout, 'document_font_family', ''));

        if ($fontFamily === '') {
            return '';
        }

        return trim(<<<CSS
/* studio-document-controls:start */
body.pdf-document,
.pdf-document{font-family:{$fontFamily} !important;}
/* studio-document-controls:end */
CSS);
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function tableControlCss(array $layout): string
    {
        $hasTableControls = collect([
            'table_header_background',
            'table_header_text_color',
            'table_border_color',
            'table_font_size',
            'table_cell_padding',
            'table_summary_background',
            'table_summary_text_color',
            'table_summary_muted_color',
        ])->contains(fn (string $key): bool => filled(data_get($layout, $key)));

        if (! $hasTableControls) {
            return '';
        }

        $headerBackground = $this->safeCssColor((string) data_get($layout, 'table_header_background', '#143d37'), '#143d37');
        $headerTextColor = $this->safeCssColor((string) data_get($layout, 'table_header_text_color', '#ffffff'), '#ffffff');
        $borderColor = $this->safeCssColor((string) data_get($layout, 'table_border_color', '#ded3bf'), '#ded3bf');
        $fontSize = $this->clampedCssNumber(data_get($layout, 'table_font_size', 10), 8, 16, 10);
        $cellPadding = $this->clampedCssNumber(data_get($layout, 'table_cell_padding', 8), 2, 24, 8);
        $summaryBackground = $this->safeCssColor((string) data_get($layout, 'table_summary_background', '#fffdf7'), '#fffdf7');
        $summaryTextColor = $this->safeCssColor((string) data_get($layout, 'table_summary_text_color', '#15231f'), '#15231f');
        $summaryMutedColor = $this->safeCssColor((string) data_get($layout, 'table_summary_muted_color', '#64748b'), '#64748b');
        $secondaryFontSize = max(7, round($fontSize * 0.82, 2));

        return trim(<<<CSS
/* studio-table-controls:start */
.pdf-document table{width:100%;font-size:{$fontSize}px;}
.pdf-document table:not(.document-summary-table){border-collapse:collapse;}
.pdf-document table:not(.document-summary-table) th,
.pdf-document .data-table th,
.pdf-document .worksheet-table th,
.pdf-document .report-table th,
.pdf-document .tg thead th{background:{$headerBackground} !important;color:{$headerTextColor} !important;border:1px solid {$borderColor} !important;padding:{$cellPadding}px !important;font-size:{$fontSize}px !important;letter-spacing:0.04em;text-transform:uppercase;}
.pdf-document table:not(.document-summary-table) td,
.pdf-document .data-table td,
.pdf-document .worksheet-table td,
.pdf-document .report-table td,
.pdf-document .tg td{border:1px solid {$borderColor} !important;padding:{$cellPadding}px !important;font-size:{$fontSize}px !important;vertical-align:top;}
.pdf-document .document-summary-table{border-collapse:separate !important;border-spacing:0 8px !important;}
.pdf-document .document-summary-table td{border:0 !important;padding:4px !important;}
.pdf-document .document-summary-cell{background:{$summaryBackground} !important;border:1px solid {$borderColor} !important;border-radius:18px !important;padding:14px !important;vertical-align:top;}
.pdf-document .document-summary-cell .label{display:block;color:{$summaryMutedColor} !important;font-size:9px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;}
.pdf-document .document-summary-cell .value{display:block;color:{$summaryTextColor} !important;font-size:12px;font-weight:800;margin-top:4px;}
.pdf-document .document-summary-cell .muted{display:block;color:{$summaryMutedColor} !important;font-size:10px;line-height:1.55;margin-top:6px;}
.pdf-document .document-financial-summary td{color:{$summaryTextColor};}
.pdf-document .bilingual-label,
.pdf-document .tg small{font-size:{$secondaryFontSize}px !important;}
/* studio-table-controls:end */
CSS);
    }

    private function safeCssColor(string $value, string $fallback): string
    {
        $value = trim($value);

        if ($value === '') {
            return $fallback;
        }

        if (preg_match('/\A#[0-9a-fA-F]{3,8}\z/', $value) === 1) {
            return $value;
        }

        if (preg_match('/\A(?:rgb|rgba|hsl|hsla)\([0-9\s,.%+-]+\)\z/i', $value) === 1) {
            return $value;
        }

        if (preg_match('/\A[a-zA-Z][a-zA-Z0-9-]{2,32}\z/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }

    private function safeCssFontFamily(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return preg_match('/\A[-_a-zA-Z0-9\s,"\']+\z/', $value) === 1 ? $value : '';
    }

    private function safeCssImageFit(string $value, string $fallback): string
    {
        $value = trim($value);

        return in_array($value, ['cover', 'contain', 'auto'], true) ? $value : $fallback;
    }

    private function safeCssUrlString(string $value): string
    {
        $value = (string) preg_replace('/[\x00-\x1F\x7F]/', '', $value);

        return str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            $value
        );
    }

    private function safeCssPosition(string $value, string $fallback): string
    {
        $value = trim($value);

        if ($value === '') {
            return $fallback;
        }

        $positionToken = '(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%)';

        return preg_match('/\A'.$positionToken.'(?:\s+'.$positionToken.')?\z/i', $value) === 1
            ? $value
            : $fallback;
    }

    private function clampedCssNumber(mixed $value, float $min, float $max, float $fallback): float|int
    {
        $number = is_numeric($value) ? (float) $value : $fallback;
        $number = max($min, min($max, $number));

        return (float) (int) $number === $number ? (int) $number : round($number, 2);
    }

    private function defaultAnalysisFirstPageHeader(GeneralSettings $settings): string
    {
        return $this->defaultCertificateFirstPageHeader(
            $settings,
            'Relatório de Análise {{document_code}}',
            '{{customer_name}} · Emitido em {{issue_date}} · {{warehouse_name}}',
            'Relatório técnico controlado com rastreabilidade, incerteza e regra de decisão documentadas.'
        );
    }

    private function defaultAnalysisFooter(GeneralSettings $settings): string
    {
        $contact = e($settings->app_contact ?: $settings->app_email ?: $settings->app_client_email ?: '');

        return <<<HTML
<table style="width:100%; border-top:1px solid #ded3bf; padding-top:6px; font-size:9px; color:#475a53;">
    <tr>
        <td>{$contact}</td>
        <td style="text-align:right;">Documento controlado · Página {PAGENO}/{nbpg}</td>
    </tr>
</table>
HTML;
    }

    private function defaultProposalFirstPageHeader(GeneralSettings $settings): string
    {
        $labName = e($settings->app_client_lab_name ?: $settings->app_name ?: 'Laboratório');

        return <<<HTML
<div style="border:1px solid #d8cbb8; border-radius:18px; background:#fffdf7;">
    <div style="background:#143d37; color:#fffdf7; padding:16px 20px; border-radius:17px 17px 0 0;">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#d9b05f; font-weight:800;">{$labName}</div>
                    <h2 style="margin:7px 0 0; color:#ffffff; font-size:18px;">Proposta {{document_code}}</h2>
                    <p style="margin:6px 0 0; font-size:10px; color:#e7efe8;">Enquadramento técnico, decisão de regra e condições documentadas.</p>
                </td>
                <td style="width:30%; vertical-align:top; text-align:right; font-size:9px; color:#dbe8df;">
                    <div style="font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#d9b05f;">Emitida em</div>
                    <div style="margin-top:6px;">{{issue_date}}</div>
                </td>
            </tr>
        </table>
    </div>
</div>
HTML;
    }

    private function defaultProposalFooter(): string
    {
        return <<<'HTML'
<table style="width:100%; border-top:1px solid #ded3bf; padding-top:6px; font-size:9px; color:#475a53;">
    <tr>
        <td>Documento controlado de proposta comercial</td>
        <td style="text-align:right;">Página {PAGENO}/{nbpg}</td>
    </tr>
</table>
HTML;
    }
}
