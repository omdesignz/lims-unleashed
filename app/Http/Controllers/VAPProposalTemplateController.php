<?php

namespace App\Http\Controllers;

use App\Exports\ProposalTemplatesExport;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Settings\GeneralSettings;
use App\Support\ProposalTemplatePresetLibrary;
use App\Support\ProposalTemplatesSpreadsheetImport;
use App\Support\ReportStudioAssetLibrary;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VAPProposalTemplateController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const CANVAS_SURFACES = [
        'content',
        'first_page_header_html',
        'default_header_html',
        'footer_html',
    ];

    // public function index()
    // {
    //     $templates = VAPProposalTemplate::with('user')
    //         ->withCount('proposals')
    //         ->latest()
    //         ->paginate(15);

    //     return Inertia::render('VAPProposalTemplates/Index', [
    //         'templates' => $templates,
    //     ]);
    // }

    public function index(Request $request)
    {
        $query = VAPProposalTemplate::with(['user'])
            ->withCount([
                'proposals',
                'proposals as accepted_proposals_count' => fn ($proposalQuery) => $proposalQuery->where('status', 'ACCEPTED'),
            ])
            ->latest();

        // Filtro de busca
        if ($request->filled('search')) {
            $search = $request->string('search')->value();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filtro de status
        if ($request->filled('status') && $request->string('status')->value() !== 'all') {
            $query->where('is_active', $request->string('status')->value() === 'active');
        }

        // Filtro de categoria
        if ($request->filled('category') && $request->string('category')->value() !== 'all') {
            $query->where('category', $request->string('category')->value());
        }

        // Ordenação
        if ($request->filled('sort')) {
            switch ($request->string('sort')->value()) {
                case 'name':
                    $query->orderBy('name');
                    break;
                case 'proposals_count':
                    $query->orderBy('proposals_count', 'desc');
                    break;
                case 'created_at':
                default:
                    $query->latest();
                    break;
            }
        }

        $templates = $query->paginate(12)->withQueryString();

        return Inertia::render('VAPProposalTemplates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'status', 'category', 'sort']),
            'presets' => ProposalTemplatePresetLibrary::summaries(),
        ]);
    }

    public function create(Request $request)
    {
        $preset = ProposalTemplatePresetLibrary::find($request->string('preset')->value());

        return Inertia::render('VAPProposalTemplates/Create', [
            'variables' => VAPProposalTemplate::getPlaceholders(),
            'initialDraft' => $preset,
            'presets' => ProposalTemplatePresetLibrary::all(),
            'studioAssets' => app(ReportStudioAssetLibrary::class)->assets(),
        ]);
    }

    public function store(Request $request)
    {
        $cssColorRule = 'regex:/\A(?:#[0-9a-fA-F]{3,8}|(?:rgb|rgba|hsl|hsla)\([0-9\s,.%+\-]+\)|[a-zA-Z][a-zA-Z0-9-]{2,32})\z/';
        $cssPositionRule = 'regex:/\A(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%)(?:\s+(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%))?\z/i';
        $mediaReferenceRule = $this->studioMediaReferenceRule();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:proposal_templates,name',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'theme_preset' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'content' => 'required|string',
            'layout_schema' => 'nullable|array',
            'layout_schema.first_page_header_html' => 'nullable|string',
            'layout_schema.default_header_html' => 'nullable|string',
            'layout_schema.footer_html' => 'nullable|string',
            'layout_schema.styles_css' => 'nullable|string',
            'layout_schema.document_font_family' => 'nullable|string|max:160',
            'layout_schema.variable_catalog' => 'nullable|array',
            'layout_schema.variable_catalog.*.value' => 'nullable|string|max:255',
            'layout_schema.variable_catalog.*.label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks' => 'nullable|array',
            'layout_schema.canvas_blocks.*.id' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.surface' => $this->canvasSurfaceRule(),
            'layout_schema.canvas_blocks.*.block_kind' => 'nullable|string|in:rich_text,signature,image,stamp,qr_code,chart_snapshot',
            'layout_schema.canvas_blocks.*.content_html' => 'nullable|string',
            'layout_schema.canvas_blocks.*.image_url' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.image_alt' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.qr_content' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.qr_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.qr_foreground_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_error_correction' => 'nullable|in:low,medium,quartile,high',
            'layout_schema.canvas_blocks.*.qr_margin' => 'nullable|integer|min:0|max:32',
            'layout_schema.canvas_blocks.*.chart_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.chart_caption' => 'nullable|string|max:1000',
            'layout_schema.canvas_blocks.*.chart_svg' => 'nullable|string',
            'layout_schema.canvas_blocks.*.chart_image_url' => ['nullable', 'string', 'max:65535', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.chart_type' => 'nullable|in:bar,line,doughnut',
            'layout_schema.canvas_blocks.*.chart_labels' => ['nullable', $this->chartTextListRule()],
            'layout_schema.canvas_blocks.*.chart_labels.*' => 'nullable|string|max:120',
            'layout_schema.canvas_blocks.*.chart_values' => ['nullable', $this->chartNumericListRule()],
            'layout_schema.canvas_blocks.*.chart_values.*' => ['nullable', $this->chartNumericOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_colors' => ['nullable', $this->chartHexColorListRule()],
            'layout_schema.canvas_blocks.*.chart_colors.*' => ['nullable', $this->chartHexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_primary_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_show_values' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.x' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.y' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.width' => 'nullable|numeric|min:1|max:100',
            'layout_schema.canvas_blocks.*.min_height' => 'nullable|numeric|min:0|max:4000',
            'layout_schema.canvas_blocks.*.z_index' => 'nullable|numeric|min:0|max:999',
            'layout_schema.canvas_blocks.*.padding' => 'nullable|numeric|min:0|max:400',
            'layout_schema.canvas_blocks.*.background_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.background_image' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.background_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.background_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.overlay_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'layout_schema.canvas_blocks.*.text_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_width' => 'nullable|numeric|min:0|max:40',
            'layout_schema.canvas_blocks.*.border_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_radius' => 'nullable|numeric|min:0|max:2000',
            'layout_schema.canvas_blocks.*.opacity' => 'nullable|numeric|min:0.05|max:1',
            'layout_schema.canvas_blocks.*.text_align' => 'nullable|string|in:left,center,right,justify',
            'layout_schema.canvas_blocks.*.font_size' => 'nullable|numeric|min:8|max:72',
            'layout_schema.canvas_blocks.*.line_height' => 'nullable|numeric|min:0.8|max:3',
            'layout_schema.canvas_blocks.*.signature_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_name' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_image' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.signature_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.signature_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.signature_image_width' => 'nullable|numeric|min:24|max:360',
            'layout_schema.canvas_blocks.*.signature_image_height' => 'nullable|numeric|min:16|max:240',
            'layout_schema.canvas_blocks.*.signature_line_style' => 'nullable|string|in:solid,dashed',
            'layout_schema.canvas_blocks.*.signature_align' => 'nullable|string|in:left,center,right',
            'layout_schema.canvas_blocks.*.signature_show_date' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.signature_date_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.is_locked' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.page_scope' => 'nullable|string|in:first,all,following,specific',
            'layout_schema.canvas_blocks.*.page_number' => 'nullable|integer|min:1|max:999',
            'layout_schema.background_image_path' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.page_background_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.background_size' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.background_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.background_repeat' => 'nullable|string|in:no-repeat,repeat,repeat-x,repeat-y',
            'layout_schema.table_header_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_header_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_border_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_font_size' => 'nullable|numeric|min:8|max:16',
            'layout_schema.table_cell_padding' => 'nullable|numeric|min:2|max:24',
            'layout_schema.table_summary_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_muted_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'export_settings' => 'nullable|array',
            'export_settings.paper_size' => 'nullable|string|in:A4,Letter,Legal,custom',
            'export_settings.custom_page_width' => 'nullable|required_if:export_settings.paper_size,custom|numeric|min:50|max:2000',
            'export_settings.custom_page_height' => 'nullable|required_if:export_settings.paper_size,custom|numeric|min:50|max:2000',
            'export_settings.orientation' => 'nullable|string|in:P,L',
            'export_settings.margin_top' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_right' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_bottom' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_left' => 'nullable|numeric|min:0|max:200',
            'export_settings.first_page_margin_top' => 'nullable|numeric|min:0|max:250',
        ]);

        $this->ensureRenderableCanvasBlockPlacement($validated);

        $template = VAPProposalTemplate::create([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? 'general',
            'content' => $validated['content'],
            'description' => $validated['description'] ?? null,
            'user_id' => auth()->id(),
            'theme_preset' => $validated['theme_preset'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'layout_schema' => $validated['layout_schema'] ?? [],
            'export_settings' => $validated['export_settings'] ?? [],
        ]);

        return redirect()->route('vap-proposals.templates.show', $template)
            ->with('success', 'Modelo de proposta criado com sucesso.');
    }

    public function show(VAPProposalTemplate $proposalTemplate)
    {
        $proposalTemplate->load(['user']);

        $proposalTemplate->loadCount([
            'proposals',
            'proposals as accepted_proposals_count' => fn ($query) => $query->where('status', 'ACCEPTED'),
            'proposals as pending_proposals_count' => fn ($query) => $query->whereIn('status', ['PENDING', 'SENT', 'VIEWED', 'REVISED']),
            'proposals as rejected_proposals_count' => fn ($query) => $query->where('status', 'REJECTED'),
        ]);

        // Carregar propostas recentes que usam este template
        $recentProposals = VAPProposal::where('template_id', $proposalTemplate->id)
            ->with(['customer'])
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('VAPProposalTemplates/Show', [
            'template' => $proposalTemplate,
            'recentProposals' => $recentProposals,
            'variables' => VAPProposalTemplate::getPlaceholderLabels(),
        ]);
    }

    public function exportPdf(
        VAPProposalTemplate $proposalTemplate,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        ReportStudioPdfRenderer $reportStudioPdfRenderer,
        GeneralSettings $settings
    ) {
        $proposalTemplate->load('user');

        $studioPayload = $reportStudioPdfBuilder->buildProposalTemplatePayload($proposalTemplate, $settings);
        $filename = 'proposal-template-'.str($proposalTemplate->name)->slug().'.pdf';
        $renderedPdf = $reportStudioPdfRenderer->renderDocument('proposal', $studioPayload, $filename);

        return response($renderedPdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
        ]);
    }

    public function previewDraftPdf(
        Request $request,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        ReportStudioPdfRenderer $reportStudioPdfRenderer,
        GeneralSettings $settings
    ) {
        $validated = $this->validateDraftPreview($request);
        $templateName = filled($validated['name'] ?? null)
            ? (string) $validated['name']
            : 'Pré-visualização do modelo de proposta';
        $templateContent = filled($validated['content'] ?? null)
            ? (string) $validated['content']
            : '<h1>Pré-visualização da proposta</h1><p>{lab_details}</p><p>{customer_details}</p><p>{items_table}</p><p>{summary_table}</p><p>{signature_block}</p>';

        $proposalTemplate = new VAPProposalTemplate([
            'name' => $templateName,
            'category' => $validated['category'] ?? 'general',
            'description' => $validated['description'] ?? null,
            'theme_preset' => $validated['theme_preset'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'content' => $templateContent,
            'user_id' => auth()->id(),
            'layout_schema' => $validated['layout_schema'] ?? [],
            'export_settings' => $validated['export_settings'] ?? [],
        ]);
        $proposalTemplate->setRelation('user', auth()->user());

        $studioPayload = $reportStudioPdfBuilder->buildProposalTemplatePayload($proposalTemplate, $settings);
        $filename = 'proposal-template-draft-'.str($templateName)->slug()->limit(72, '')->value().'.pdf';

        try {
            $renderedPdf = $reportStudioPdfRenderer->renderDocument('proposal', $studioPayload, $filename);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return response($renderedPdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
        ]);
    }

    public function edit(VAPProposalTemplate $proposalTemplate)
    {
        return Inertia::render('VAPProposalTemplates/Edit', [
            'template' => $proposalTemplate->loadCount('proposals'),
            'variables' => VAPProposalTemplate::getPlaceholders(),
            'presets' => ProposalTemplatePresetLibrary::all(),
            'studioAssets' => app(ReportStudioAssetLibrary::class)->assets(),
        ]);
    }

    public function update(Request $request, VAPProposalTemplate $proposalTemplate)
    {
        $cssColorRule = 'regex:/\A(?:#[0-9a-fA-F]{3,8}|(?:rgb|rgba|hsl|hsla)\([0-9\s,.%+\-]+\)|[a-zA-Z][a-zA-Z0-9-]{2,32})\z/';
        $cssPositionRule = 'regex:/\A(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%)(?:\s+(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%))?\z/i';
        $mediaReferenceRule = $this->studioMediaReferenceRule();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:proposal_templates,name,'.$proposalTemplate->id,
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'theme_preset' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'content' => 'required|string',
            'layout_schema' => 'nullable|array',
            'layout_schema.first_page_header_html' => 'nullable|string',
            'layout_schema.default_header_html' => 'nullable|string',
            'layout_schema.footer_html' => 'nullable|string',
            'layout_schema.styles_css' => 'nullable|string',
            'layout_schema.document_font_family' => 'nullable|string|max:160',
            'layout_schema.variable_catalog' => 'nullable|array',
            'layout_schema.variable_catalog.*.value' => 'nullable|string|max:255',
            'layout_schema.variable_catalog.*.label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks' => 'nullable|array',
            'layout_schema.canvas_blocks.*.id' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.surface' => $this->canvasSurfaceRule(),
            'layout_schema.canvas_blocks.*.block_kind' => 'nullable|string|in:rich_text,signature,image,stamp,qr_code,chart_snapshot',
            'layout_schema.canvas_blocks.*.content_html' => 'nullable|string',
            'layout_schema.canvas_blocks.*.image_url' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.image_alt' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.qr_content' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.qr_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.qr_foreground_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_error_correction' => 'nullable|in:low,medium,quartile,high',
            'layout_schema.canvas_blocks.*.qr_margin' => 'nullable|integer|min:0|max:32',
            'layout_schema.canvas_blocks.*.chart_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.chart_caption' => 'nullable|string|max:1000',
            'layout_schema.canvas_blocks.*.chart_svg' => 'nullable|string',
            'layout_schema.canvas_blocks.*.chart_image_url' => ['nullable', 'string', 'max:65535', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.chart_type' => 'nullable|in:bar,line,doughnut',
            'layout_schema.canvas_blocks.*.chart_labels' => ['nullable', $this->chartTextListRule()],
            'layout_schema.canvas_blocks.*.chart_labels.*' => 'nullable|string|max:120',
            'layout_schema.canvas_blocks.*.chart_values' => ['nullable', $this->chartNumericListRule()],
            'layout_schema.canvas_blocks.*.chart_values.*' => ['nullable', $this->chartNumericOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_colors' => ['nullable', $this->chartHexColorListRule()],
            'layout_schema.canvas_blocks.*.chart_colors.*' => ['nullable', $this->chartHexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_primary_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_show_values' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.x' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.y' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.width' => 'nullable|numeric|min:1|max:100',
            'layout_schema.canvas_blocks.*.min_height' => 'nullable|numeric|min:0|max:4000',
            'layout_schema.canvas_blocks.*.z_index' => 'nullable|numeric|min:0|max:999',
            'layout_schema.canvas_blocks.*.padding' => 'nullable|numeric|min:0|max:400',
            'layout_schema.canvas_blocks.*.background_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.background_image' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.background_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.background_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.overlay_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'layout_schema.canvas_blocks.*.text_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_width' => 'nullable|numeric|min:0|max:40',
            'layout_schema.canvas_blocks.*.border_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_radius' => 'nullable|numeric|min:0|max:2000',
            'layout_schema.canvas_blocks.*.opacity' => 'nullable|numeric|min:0.05|max:1',
            'layout_schema.canvas_blocks.*.text_align' => 'nullable|string|in:left,center,right,justify',
            'layout_schema.canvas_blocks.*.font_size' => 'nullable|numeric|min:8|max:72',
            'layout_schema.canvas_blocks.*.line_height' => 'nullable|numeric|min:0.8|max:3',
            'layout_schema.canvas_blocks.*.signature_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_name' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_image' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.signature_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.signature_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.signature_image_width' => 'nullable|numeric|min:24|max:360',
            'layout_schema.canvas_blocks.*.signature_image_height' => 'nullable|numeric|min:16|max:240',
            'layout_schema.canvas_blocks.*.signature_line_style' => 'nullable|string|in:solid,dashed',
            'layout_schema.canvas_blocks.*.signature_align' => 'nullable|string|in:left,center,right',
            'layout_schema.canvas_blocks.*.signature_show_date' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.signature_date_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.is_locked' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.page_scope' => 'nullable|string|in:first,all,following,specific',
            'layout_schema.canvas_blocks.*.page_number' => 'nullable|integer|min:1|max:999',
            'layout_schema.background_image_path' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.page_background_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.background_size' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.background_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.background_repeat' => 'nullable|string|in:no-repeat,repeat,repeat-x,repeat-y',
            'layout_schema.table_header_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_header_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_border_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_font_size' => 'nullable|numeric|min:8|max:16',
            'layout_schema.table_cell_padding' => 'nullable|numeric|min:2|max:24',
            'layout_schema.table_summary_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_muted_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'export_settings' => 'nullable|array',
            'export_settings.paper_size' => 'nullable|string|in:A4,Letter,Legal,custom',
            'export_settings.custom_page_width' => 'nullable|required_if:export_settings.paper_size,custom|numeric|min:50|max:2000',
            'export_settings.custom_page_height' => 'nullable|required_if:export_settings.paper_size,custom|numeric|min:50|max:2000',
            'export_settings.orientation' => 'nullable|string|in:P,L',
            'export_settings.margin_top' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_right' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_bottom' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_left' => 'nullable|numeric|min:0|max:200',
            'export_settings.first_page_margin_top' => 'nullable|numeric|min:0|max:250',
        ]);

        $this->ensureRenderableCanvasBlockPlacement($validated);

        $proposalTemplate->update($validated);

        return redirect()->route('vap-proposals.templates.show', $proposalTemplate)
            ->with('success', 'Modelo de proposta actualizado com sucesso.');
    }

    public function destroy(VAPProposalTemplate $proposalTemplate)
    {
        if ($proposalTemplate->proposals()->count() > 0) {
            return back()->with('error', 'Não é possível eliminar um modelo usado por propostas.');
        }

        $proposalTemplate->delete();

        return redirect()->route('vap-proposals.templates.index')
            ->with('success', 'Modelo de proposta eliminado com sucesso.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'template_file' => 'required|file|mimes:json,txt,xlsx,csv|max:5120',
        ]);

        try {
            $file = $request->file('template_file');
            $extension = strtolower((string) $file?->getClientOriginalExtension());

            if (in_array($extension, ['xlsx', 'csv'], true)) {
                Excel::import(new ProposalTemplatesSpreadsheetImport((int) auth()->id()), $file);
            } else {
                $content = json_decode(file_get_contents($file->path()), true, 512, JSON_THROW_ON_ERROR);

                if (! is_array($content)) {
                    throw new \RuntimeException('Formato de ficheiro inválido.');
                }

                foreach ($content as $templateData) {
                    VAPProposalTemplate::query()->updateOrCreate(
                        ['name' => $templateData['name']],
                        [
                            'content' => $templateData['content'],
                            'category' => $templateData['category'] ?? 'general',
                            'description' => $templateData['description'] ?? null,
                            'user_id' => auth()->id(),
                            'is_active' => $templateData['is_active'] ?? true,
                            'theme_preset' => $templateData['theme_preset'] ?? null,
                            'layout_schema' => $templateData['layout_schema'] ?? [],
                            'export_settings' => $templateData['export_settings'] ?? [],
                        ]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Modelos importados com sucesso.',
            ]);

        } catch (\JsonException|\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível importar os modelos: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível importar os modelos. Verifique o ficheiro e tente novamente.',
            ], 422);
        }
    }

    public function export(Request $request): BinaryFileResponse|StreamedResponse
    {
        $format = strtolower($request->string('format')->value() ?: 'xlsx');

        if (in_array($format, ['xlsx', 'csv'], true)) {
            $writerType = $format === 'csv'
                ? \Maatwebsite\Excel\Excel::CSV
                : \Maatwebsite\Excel\Excel::XLSX;

            return Excel::download(
                new ProposalTemplatesExport,
                'modelos-proposta-'.now()->format('Y-m-d').'.'.$format,
                $writerType
            );
        }

        $templates = VAPProposalTemplate::with('user')->get()->map(function (VAPProposalTemplate $template) {
            return [
                'name' => $template->name,
                'content' => $template->content,
                'category' => $template->category,
                'description' => $template->description,
                'is_active' => $template->is_active,
                'theme_preset' => $template->theme_preset,
                'layout_schema' => $template->layout_schema ?? [],
                'export_settings' => $template->export_settings ?? [],
                'created_by' => $template->user?->name,
                'created_at' => $template->created_at->toISOString(),
            ];
        });

        $filename = 'modelos-proposta-'.date('Y-m-d').'.json';

        return response()->streamDownload(function () use ($templates) {
            echo json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDraftPreview(Request $request): array
    {
        $cssColorRule = 'regex:/\A(?:#[0-9a-fA-F]{3,8}|(?:rgb|rgba|hsl|hsla)\([0-9\s,.%+\-]+\)|[a-zA-Z][a-zA-Z0-9-]{2,32})\z/';
        $cssPositionRule = 'regex:/\A(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%)(?:\s+(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%))?\z/i';
        $mediaReferenceRule = $this->studioMediaReferenceRule();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'theme_preset' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'content' => 'nullable|string',
            'layout_schema' => 'nullable|array',
            'layout_schema.first_page_header_html' => 'nullable|string',
            'layout_schema.default_header_html' => 'nullable|string',
            'layout_schema.footer_html' => 'nullable|string',
            'layout_schema.styles_css' => 'nullable|string',
            'layout_schema.document_font_family' => 'nullable|string|max:160',
            'layout_schema.variable_catalog' => 'nullable|array',
            'layout_schema.variable_catalog.*.value' => 'nullable|string|max:255',
            'layout_schema.variable_catalog.*.label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks' => 'nullable|array',
            'layout_schema.canvas_blocks.*.id' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.surface' => $this->canvasSurfaceRule(),
            'layout_schema.canvas_blocks.*.block_kind' => 'nullable|string|in:rich_text,signature,image,stamp,qr_code,chart_snapshot',
            'layout_schema.canvas_blocks.*.content_html' => 'nullable|string',
            'layout_schema.canvas_blocks.*.image_url' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.image_alt' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.qr_content' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.qr_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.qr_foreground_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_error_correction' => 'nullable|in:low,medium,quartile,high',
            'layout_schema.canvas_blocks.*.qr_margin' => 'nullable|integer|min:0|max:32',
            'layout_schema.canvas_blocks.*.chart_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.chart_caption' => 'nullable|string|max:1000',
            'layout_schema.canvas_blocks.*.chart_svg' => 'nullable|string',
            'layout_schema.canvas_blocks.*.chart_image_url' => ['nullable', 'string', 'max:65535', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.chart_type' => 'nullable|in:bar,line,doughnut',
            'layout_schema.canvas_blocks.*.chart_labels' => ['nullable', $this->chartTextListRule()],
            'layout_schema.canvas_blocks.*.chart_labels.*' => 'nullable|string|max:120',
            'layout_schema.canvas_blocks.*.chart_values' => ['nullable', $this->chartNumericListRule()],
            'layout_schema.canvas_blocks.*.chart_values.*' => ['nullable', $this->chartNumericOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_colors' => ['nullable', $this->chartHexColorListRule()],
            'layout_schema.canvas_blocks.*.chart_colors.*' => ['nullable', $this->chartHexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_primary_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_show_values' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.x' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.y' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.width' => 'nullable|numeric|min:1|max:100',
            'layout_schema.canvas_blocks.*.min_height' => 'nullable|numeric|min:0|max:4000',
            'layout_schema.canvas_blocks.*.z_index' => 'nullable|numeric|min:0|max:999',
            'layout_schema.canvas_blocks.*.padding' => 'nullable|numeric|min:0|max:400',
            'layout_schema.canvas_blocks.*.background_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.background_image' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.background_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.background_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.overlay_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'layout_schema.canvas_blocks.*.text_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_width' => 'nullable|numeric|min:0|max:40',
            'layout_schema.canvas_blocks.*.border_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_radius' => 'nullable|numeric|min:0|max:2000',
            'layout_schema.canvas_blocks.*.opacity' => 'nullable|numeric|min:0.05|max:1',
            'layout_schema.canvas_blocks.*.text_align' => 'nullable|string|in:left,center,right,justify',
            'layout_schema.canvas_blocks.*.font_size' => 'nullable|numeric|min:8|max:72',
            'layout_schema.canvas_blocks.*.line_height' => 'nullable|numeric|min:0.8|max:3',
            'layout_schema.canvas_blocks.*.signature_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_name' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_image' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.signature_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.signature_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.signature_image_width' => 'nullable|numeric|min:24|max:360',
            'layout_schema.canvas_blocks.*.signature_image_height' => 'nullable|numeric|min:16|max:240',
            'layout_schema.canvas_blocks.*.signature_line_style' => 'nullable|string|in:solid,dashed',
            'layout_schema.canvas_blocks.*.signature_align' => 'nullable|string|in:left,center,right',
            'layout_schema.canvas_blocks.*.signature_show_date' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.signature_date_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.is_locked' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.page_scope' => 'nullable|string|in:first,all,following,specific',
            'layout_schema.canvas_blocks.*.page_number' => 'nullable|integer|min:1|max:999',
            'layout_schema.background_image_path' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.page_background_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.background_size' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.background_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.background_repeat' => 'nullable|string|in:no-repeat,repeat,repeat-x,repeat-y',
            'layout_schema.table_header_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_header_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_border_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_font_size' => 'nullable|numeric|min:8|max:16',
            'layout_schema.table_cell_padding' => 'nullable|numeric|min:2|max:24',
            'layout_schema.table_summary_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_muted_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'export_settings' => 'nullable|array',
            'export_settings.paper_size' => 'nullable|string|in:A4,Letter,Legal,custom',
            'export_settings.custom_page_width' => 'nullable|required_if:export_settings.paper_size,custom|numeric|min:50|max:2000',
            'export_settings.custom_page_height' => 'nullable|required_if:export_settings.paper_size,custom|numeric|min:50|max:2000',
            'export_settings.orientation' => 'nullable|string|in:P,L',
            'export_settings.margin_top' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_right' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_bottom' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_left' => 'nullable|numeric|min:0|max:200',
            'export_settings.first_page_margin_top' => 'nullable|numeric|min:0|max:250',
        ]);

        $this->ensureRenderableCanvasBlockPlacement($validated);

        return $validated;
    }

    public function toggleStatus(VAPProposalTemplate $proposalTemplate)
    {
        try {
            $proposalTemplate->update([
                'is_active' => ! $proposalTemplate->is_active,
            ]);

            $status = $proposalTemplate->is_active ? 'ativado' : 'desativado';

            try {
                activity()
                    ->performedOn($proposalTemplate)
                    ->causedBy(auth()->user())
                    ->log("proposal_template_{$status}");
            } catch (\Throwable $exception) {
                report($exception);
            }

            return response()->json([
                'success' => true,
                'message' => "Modelo {$status} com sucesso.",
                'is_active' => $proposalTemplate->is_active,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível alterar o estado do modelo: '.$e->getMessage(),
            ], 500);
        }
    }

    private function canvasSurfaceRule(): string
    {
        return 'nullable|string|max:100|in:'.implode(',', self::CANVAS_SURFACES);
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    private function ensureRenderableCanvasBlockPlacement(array $validated): void
    {
        $blocks = data_get($validated, 'layout_schema.canvas_blocks', []);

        if (! is_array($blocks)) {
            return;
        }

        $errors = [];

        foreach ($blocks as $index => $block) {
            if (! is_array($block)) {
                continue;
            }

            if (
                ($block['surface'] ?? 'content') === 'content'
                && ($block['page_scope'] ?? null) === 'specific'
                && blank($block['page_number'] ?? null)
            ) {
                $errors["layout_schema.canvas_blocks.{$index}.page_number"] = 'Indique a página específica onde este objecto deve aparecer no PDF.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function chartTextListRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || is_array($value)) {
                return;
            }

            if (! is_string($value)) {
                $fail('A lista de etiquetas do gráfico deve ser texto ou uma lista.');

                return;
            }

            foreach ($this->splitChartStudioList($value) as $item) {
                if (mb_strlen($item) > 120) {
                    $fail('Cada etiqueta do gráfico deve ter no máximo 120 caracteres.');

                    return;
                }
            }
        };
    }

    private function chartNumericListRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || is_array($value)) {
                return;
            }

            if (! is_string($value)) {
                $fail('Os valores do gráfico devem ser numéricos ou variáveis do estúdio.');

                return;
            }

            foreach ($this->splitChartStudioList($value) as $item) {
                if (! $this->isNumericChartValue($item) && ! $this->isStudioPlaceholder($item)) {
                    $fail('Os valores do gráfico devem conter apenas números ou variáveis separados por vírgula, ponto e vírgula ou quebra de linha.');

                    return;
                }
            }
        };
    }

    private function chartHexColorListRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || is_array($value)) {
                return;
            }

            if (! is_string($value)) {
                $fail('A paleta do gráfico deve ser texto ou uma lista de cores.');

                return;
            }

            foreach ($this->splitChartStudioList($value) as $item) {
                if (! $this->isHexChartColor($item) && ! $this->isStudioPlaceholder($item)) {
                    $fail('A paleta do gráfico deve conter apenas cores HEX no formato #RRGGBB ou variáveis do estúdio.');

                    return;
                }
            }
        };
    }

    private function chartNumericOrPlaceholderRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $item = (string) $value;

            if (! $this->isNumericChartValue($item) && ! $this->isStudioPlaceholder($item)) {
                $fail('Os valores do gráfico devem ser numéricos ou variáveis do estúdio.');
            }
        };
    }

    private function chartHexColorOrPlaceholderRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $item = (string) $value;

            if (! $this->isHexChartColor($item) && ! $this->isStudioPlaceholder($item)) {
                $fail('A paleta do gráfico deve conter apenas cores HEX no formato #RRGGBB ou variáveis do estúdio.');
            }
        };
    }

    private function hexColorOrPlaceholderRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $item = (string) $value;

            if (! $this->isHexChartColor($item) && ! $this->isStudioPlaceholder($item)) {
                $fail('A cor deve ser HEX no formato #RRGGBB ou uma variável do estúdio.');
            }
        };
    }

    private function studioMediaReferenceRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value)) {
                $fail('A referência de media do estúdio deve ser um URL, caminho público, data URI de imagem ou variável do estúdio.');

                return;
            }

            $value = trim($value);

            if ($value === '' || $this->isStudioPlaceholder($value)) {
                return;
            }

            if (preg_match('/[\x00-\x1F\x7F<>"\']/', $value) === 1) {
                $fail('A referência de media do estúdio contém caracteres inseguros.');

                return;
            }

            if (preg_match('/\Adata:image\/(?:png|jpe?g|gif|webp|avif|svg\+xml);base64,[A-Za-z0-9+\/=\s]+\z/i', $value) === 1) {
                return;
            }

            if (preg_match('/\Adata:/i', $value) === 1) {
                $fail('Apenas data URIs de imagem em base64 são permitidos nos elementos de media do estúdio.');

                return;
            }

            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

                if (in_array($scheme, ['http', 'https'], true)) {
                    return;
                }
            }

            if (preg_match('/\A\/?(?:storage|images)\/[A-Za-z0-9._~!$&()*+,;=:@%\/-]+\z/', $value) === 1) {
                return;
            }

            $fail('A referência de media do estúdio deve apontar para uma imagem pública segura, URL HTTP(S), data URI de imagem ou variável do estúdio.');
        };
    }

    /**
     * @return array<int, string>
     */
    private function splitChartStudioList(string $value): array
    {
        return collect(preg_split('/[\r\n;]+|(?<!\d),|,(?!\d)/', $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function isNumericChartValue(string $value): bool
    {
        return is_numeric(str_replace(',', '.', trim($value)));
    }

    private function isHexChartColor(string $value): bool
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', trim($value)) === 1;
    }

    private function isStudioPlaceholder(string $value): bool
    {
        $value = trim($value);

        return preg_match('/^\{\{\s*[A-Za-z_][A-Za-z0-9_.-]*\s*\}\}$/', $value) === 1
            || preg_match('/^\{\s*[A-Za-z_][A-Za-z0-9_.-]*\s*\}$/', $value) === 1;
    }
}
