<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportStudioTemplateRequest;
use App\Models\QualityCertificate;
use App\Models\ReportStudioTemplate;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioAssetLibrary;
use App\Support\ReportStudioDefaultTemplates;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use HeadlessChromium\BrowserFactory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;
use Spatie\Browsershot\Browsershot;

class ReportStudioController extends Controller
{
    public function index(Request $request)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $templates = ReportStudioTemplate::query()
            ->with(['createdBy:id,name', 'updatedBy:id,name'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->input('studio_type'), function ($query, $studioType) {
                $query->where('studio_type', $studioType);
            })
            ->when($request->input('status_filter'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest('updated_at')
            ->get()
            ->map(function (ReportStudioTemplate $template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'studio_type' => $template->studio_type,
                    'renderer' => $template->renderer,
                    'status' => $template->status,
                    'is_default' => $template->is_default,
                    'theme_preset' => $template->theme_preset,
                    'canva_design_url' => $template->canva_design_url,
                    'description' => $template->description,
                    'layout_schema' => $template->layout_schema ?? [],
                    'export_settings' => $template->export_settings ?? [],
                    'created_by' => $template->createdBy?->name,
                    'updated_by' => $template->updatedBy?->name,
                    'updated_at' => optional($template->updated_at)?->toIso8601String(),
                    'preview_pdf_path' => route('report-studios.preview-pdf', $template),
                ];
            });

        $chromeBinary = config('laravel-pdf.chrome.chrome_binary');
        $chromeBinaryConfigured = is_string($chromeBinary) && $chromeBinary !== '';
        $chromeBinaryExecutable = ! $chromeBinaryConfigured || is_executable($chromeBinary);
        $chromeAvailable = class_exists(BrowserFactory::class) && $chromeBinaryExecutable;

        return Inertia::render('ReportStudios/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'studio_type', 'status_filter']),
            'summary' => [
                'total' => $templates->count(),
                'analysis' => $templates->where('studio_type', 'analysis')->count(),
                'executive' => $templates->where('studio_type', 'executive')->count(),
                'proposal' => $templates->where('studio_type', 'proposal')->count(),
                'export_certificate' => $templates->where('studio_type', 'export_certificate')->count(),
                'import_certificate' => $templates->where('studio_type', 'import_certificate')->count(),
                'quote' => $templates->where('studio_type', 'quote')->count(),
                'invoice' => $templates->where('studio_type', 'invoice')->count(),
                'receipt' => $templates->where('studio_type', 'receipt')->count(),
                'credit_note' => $templates->where('studio_type', 'credit_note')->count(),
                'canva' => $templates->where('renderer', 'canva')->count(),
                'chrome' => $templates->whereIn('renderer', ['chrome', 'browsershot'])->count(),
            ],
            'systemPresets' => ReportStudioDefaultTemplates::presets(),
            'studioAssets' => app(ReportStudioAssetLibrary::class)->assets(),
            'rendererCapabilities' => [
                'mpdf' => [
                    'available' => true,
                    'label' => 'mPDF interno',
                    'description' => 'Estável para PDFs clássicos, headers/footers e CSS compatível com mPDF/CSS 2.1. Não é 1:1 com o preview browser quando usa flex, grid, filtros, transforms ou CSS moderno.',
                ],
                'chrome' => [
                    'available' => $chromeAvailable,
                    'label' => 'Spatie Laravel PDF · Chrome',
                    'description' => $chromeAvailable
                        ? 'Melhor candidato para saída 1:1 com o canvas: suporta CSS moderno, SVG e snapshots de gráficos Apex. Usa templates de header/footer do Chrome com paginação pageNumber/totalPages.'
                        : 'Requer chrome-php/chrome e um Chrome/Chromium executável no servidor. Configure LARAVEL_PDF_CHROME_BINARY quando a autodeteção não for suficiente.',
                    'binary_path' => $chromeBinaryConfigured ? $chromeBinary : null,
                    'binary_configured' => $chromeBinaryConfigured,
                    'binary_executable' => $chromeBinaryExecutable,
                ],
                'browsershot' => [
                    'available' => class_exists(Browsershot::class),
                    'label' => 'Spatie Laravel PDF · Browsershot',
                    'description' => 'Alternativa Chromium com Puppeteer/Browsershot para maior fidelidade visual. Requer spatie/browsershot, Node e Chrome/Chromium no servidor.',
                ],
            ],
        ]);
    }

    public function store(ReportStudioTemplateRequest $request)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $validated = $request->validated();

        if ($validated['is_default']) {
            ReportStudioTemplate::query()
                ->where('studio_type', $validated['studio_type'])
                ->update(['is_default' => false]);
        }

        ReportStudioTemplate::query()->create(array_merge($validated, [
            'created_by_id' => auth()->id(),
            'updated_by_id' => auth()->id(),
        ]));

        return back()->with('success', 'Template de estúdio guardado com sucesso.');
    }

    public function update(ReportStudioTemplateRequest $request, ReportStudioTemplate $reportStudio)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $validated = $request->validated();

        if ($validated['is_default']) {
            ReportStudioTemplate::query()
                ->where('studio_type', $validated['studio_type'])
                ->whereKeyNot($reportStudio->id)
                ->update(['is_default' => false]);
        }

        $reportStudio->update(array_merge($validated, [
            'updated_by_id' => auth()->id(),
        ]));

        return back()->with('success', 'Template de estúdio atualizado com sucesso.');
    }

    public function destroy(ReportStudioTemplate $reportStudio)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        $reportStudio->delete();

        return back()->with('success', 'Template de estúdio arquivado com sucesso.');
    }

    public function previewPdf(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        ReportStudioPdfRenderer $reportStudioPdfRenderer,
        GeneralSettings $settings
    ) {
        abort_if(! auth()->user()->hasRole('admin'), 403);

        [$payload, $filename] = match ($reportStudio->studio_type) {
            'analysis' => $this->analysisPreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            'executive' => $this->executivePreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            'proposal' => $this->proposalPreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            'export_certificate' => $this->exportCertificatePreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            'import_certificate' => $this->importCertificatePreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            'quote' => $this->quotePreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            'invoice' => $this->invoicePreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            'receipt' => $this->receiptPreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            'credit_note' => $this->creditNotePreviewPayload($reportStudio, $reportStudioPdfBuilder, $settings),
            default => abort(422, 'Tipo de estúdio não suportado para pré-visualização PDF.'),
        };

        try {
            $renderedPdf = $reportStudioPdfRenderer->renderPreview($reportStudio, $payload, $filename);
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response($renderedPdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
        ]);
    }

    private function analysisPreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        $certificate = QualityCertificate::query()
            ->with(['collection', 'lab_code', 'customer', 'warehouse', 'user', 'validated_by_user'])
            ->whereNotNull('collection_id')
            ->latest('id')
            ->first();

        return [
            $certificate
                ? $reportStudioPdfBuilder->buildAnalysisReportPayload($certificate, $settings, $reportStudio)
                : $reportStudioPdfBuilder->buildAnalysisStudioPreviewPayload($reportStudio, $settings),
            'report-studio-'.$reportStudio->id.'-analysis-preview.pdf',
        ];
    }

    private function executivePreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        $payload = [
            'period_label' => now()->translatedFormat('F Y'),
            'executive_summary' => 'Painel executivo preparado para decisão: capacidade operacional, trabalhos autorizados, amostras em curso, recebíveis e pressão de risco ficam consolidados numa leitura auditável.',
            'kpis' => [
                ['label' => 'Propostas aceites', 'value' => 18, 'hint' => 'Trabalhos autorizados pelo cliente'],
                ['label' => 'Pedidos do portal abertos', 'value' => 7, 'hint' => 'Pedidos pendentes de resposta ou validação'],
                ['label' => 'Amostras ativas', 'value' => 14, 'hint' => 'Fluxo operacional ainda em curso'],
                ['label' => 'Recebível em aberto', 'value' => 'AOA 12.500.000,00', 'hint' => 'Montante agregado de faturas por liquidar'],
            ],
            'charts' => [
                'throughput' => [
                    'title' => 'Capacidade técnica por etapa',
                    'labels' => ['Recepção', 'Preparação', 'Ensaio', 'Verificação', 'Emissão'],
                    'series' => [24, 21, 18, 16, 12],
                    'unit' => 'amostras',
                ],
                'quality_pressure' => [
                    'title' => 'Pressão de qualidade',
                    'labels' => ['No prazo', 'Atenção', 'Risco', 'Atrasado'],
                    'series' => [62, 24, 9, 5],
                    'unit' => '%',
                ],
            ],
            'top_customers' => [
                ['name' => 'Cliente Exemplo, Lda.', 'code' => 'CLI-001', 'warehouses_count' => 4],
                ['name' => 'Agro Industrial do Sul', 'code' => 'CLI-014', 'warehouses_count' => 3],
                ['name' => 'Alimentos do Norte', 'code' => 'CLI-023', 'warehouses_count' => 2],
            ],
        ];

        return [
            $reportStudioPdfBuilder->buildExecutiveReportPayload($payload, $settings, $reportStudio),
            'report-studio-'.$reportStudio->id.'-executive-preview.pdf',
        ];
    }

    private function proposalPreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        return [
            $reportStudioPdfBuilder->buildProposalStudioPreviewPayload($reportStudio, $settings),
            'report-studio-'.$reportStudio->id.'-proposal-preview.pdf',
        ];
    }

    private function exportCertificatePreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        return [
            $reportStudioPdfBuilder->buildExportCertificatePreviewPayload($reportStudio, $settings),
            'report-studio-'.$reportStudio->id.'-export-certificate-preview.pdf',
        ];
    }

    private function importCertificatePreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        return [
            $reportStudioPdfBuilder->buildImportCertificatePreviewPayload($reportStudio, $settings),
            'report-studio-'.$reportStudio->id.'-import-certificate-preview.pdf',
        ];
    }

    private function quotePreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        return [
            $reportStudioPdfBuilder->buildQuotePreviewPayload($reportStudio, $settings),
            'report-studio-'.$reportStudio->id.'-quote-preview.pdf',
        ];
    }

    private function invoicePreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        return [
            $reportStudioPdfBuilder->buildInvoicePreviewPayload($reportStudio, $settings),
            'report-studio-'.$reportStudio->id.'-invoice-preview.pdf',
        ];
    }

    private function receiptPreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        return [
            $reportStudioPdfBuilder->buildReceiptPreviewPayload($reportStudio, $settings),
            'report-studio-'.$reportStudio->id.'-receipt-preview.pdf',
        ];
    }

    private function creditNotePreviewPayload(
        ReportStudioTemplate $reportStudio,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        GeneralSettings $settings
    ): array {
        return [
            $reportStudioPdfBuilder->buildCreditNotePreviewPayload($reportStudio, $settings),
            'report-studio-'.$reportStudio->id.'-credit-note-preview.pdf',
        ];
    }
}
