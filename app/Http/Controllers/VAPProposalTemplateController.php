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
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VAPProposalTemplateController extends Controller
{
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
            'layout_schema.canvas_blocks.*.surface' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.block_kind' => 'nullable|string|in:rich_text,signature,image,stamp,qr_code',
            'layout_schema.canvas_blocks.*.content_html' => 'nullable|string',
            'layout_schema.canvas_blocks.*.image_url' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.image_alt' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.image_position' => 'nullable|string|max:50',
            'layout_schema.canvas_blocks.*.qr_content' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.qr_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.qr_foreground_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'layout_schema.canvas_blocks.*.qr_background_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'layout_schema.canvas_blocks.*.qr_error_correction' => 'nullable|in:low,medium,quartile,high',
            'layout_schema.canvas_blocks.*.qr_margin' => 'nullable|integer|min:0|max:32',
            'layout_schema.canvas_blocks.*.x' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.y' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.width' => 'nullable|numeric|min:1|max:100',
            'layout_schema.canvas_blocks.*.min_height' => 'nullable|numeric|min:0|max:4000',
            'layout_schema.canvas_blocks.*.z_index' => 'nullable|numeric|min:0|max:999',
            'layout_schema.canvas_blocks.*.padding' => 'nullable|numeric|min:0|max:400',
            'layout_schema.canvas_blocks.*.background_color' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.background_image' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.background_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.background_image_position' => 'nullable|string|max:50',
            'layout_schema.canvas_blocks.*.overlay_color' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'layout_schema.canvas_blocks.*.text_color' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.border_width' => 'nullable|numeric|min:0|max:40',
            'layout_schema.canvas_blocks.*.border_color' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.border_radius' => 'nullable|numeric|min:0|max:2000',
            'layout_schema.canvas_blocks.*.opacity' => 'nullable|numeric|min:0.05|max:1',
            'layout_schema.canvas_blocks.*.text_align' => 'nullable|string|in:left,center,right,justify',
            'layout_schema.canvas_blocks.*.font_size' => 'nullable|numeric|min:8|max:72',
            'layout_schema.canvas_blocks.*.line_height' => 'nullable|numeric|min:0.8|max:3',
            'layout_schema.canvas_blocks.*.signature_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_name' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_image' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.signature_line_style' => 'nullable|string|in:solid,dashed',
            'layout_schema.canvas_blocks.*.signature_align' => 'nullable|string|in:left,center,right',
            'layout_schema.canvas_blocks.*.signature_show_date' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.signature_date_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.is_locked' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.page_scope' => 'nullable|string|in:first,all,following,specific',
            'layout_schema.canvas_blocks.*.page_number' => 'nullable|integer|min:1|max:999',
            'layout_schema.background_image_path' => 'nullable|string|max:2048',
            'layout_schema.background_size' => 'nullable|string|max:50',
            'layout_schema.background_position' => 'nullable|string|max:50',
            'layout_schema.background_repeat' => 'nullable|string|max:50',
            'layout_schema.table_header_background' => 'nullable|string|max:50',
            'layout_schema.table_header_text_color' => 'nullable|string|max:50',
            'layout_schema.table_border_color' => 'nullable|string|max:50',
            'layout_schema.table_font_size' => 'nullable|numeric|min:8|max:16',
            'layout_schema.table_cell_padding' => 'nullable|numeric|min:2|max:24',
            'export_settings' => 'nullable|array',
            'export_settings.paper_size' => 'nullable|string|max:20',
            'export_settings.orientation' => 'nullable|string|max:5',
            'export_settings.margin_top' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_right' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_bottom' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_left' => 'nullable|numeric|min:0|max:200',
            'export_settings.first_page_margin_top' => 'nullable|numeric|min:0|max:250',
        ]);

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

        // Variáveis disponíveis para templates
        $variables = [
            '{proposal_number}' => 'Número da proposta',
            '{customer_name}' => 'Nome do cliente',
            '{customer_code}' => 'Código do cliente',
            '{service_location}' => 'Local do serviço',
            '{department}' => 'Departamento',
            '{warehouse}' => 'Armazém',
            '{created_at}' => 'Data de criação',
            '{created_by}' => 'Responsável comercial',
            '{tolerance_days}' => 'Dias de tolerância',
            '{expiry_date}' => 'Data de validade',
            '{days_until_expiry}' => 'Dias até expirar',
            '{sub_total}' => 'Subtotal',
            '{total}' => 'Total',
            '{tax}' => 'Imposto',
            '{discount}' => 'Desconto',
            '{global_discount_amount}' => 'Desconto global',
            '{global_discount_percentage}' => 'Percentagem do desconto global',
            '{withholding_tax_amount}' => 'Retenção na fonte',
            '{withholding_tax_percentage}' => 'Percentagem da retenção',
            '{pricing_mode}' => 'Modo de preço',
            '{withhold_tax}' => 'Aplicação de retenção',
            '{observations}' => 'Observações',
            '{total_items}' => 'Total de itens',
            '{taxable_items}' => 'Itens tributáveis',
            '{items_table}' => 'Tabela de itens/serviços',
            '{items_list}' => 'Lista de itens/serviços',
            '{summary_table}' => 'Resumo financeiro',
            '{banking_details}' => 'Dados bancários',
            '{document_keywords}' => 'Palavras-chave documentais',
            '{lab_signature}' => 'Assinatura do laboratório',
            '{client_signature}' => 'Assinatura do cliente',
            '{signature_block}' => 'Bloco de assinatura',
            '{lab_name}' => 'Nome do laboratório',
            '{lab_details}' => 'Dados do laboratório',
            '{customer_details}' => 'Dados do cliente',
            '{bank_name}' => 'Nome do banco',
            '{bank_iban}' => 'IBAN',
        ];

        return Inertia::render('VAPProposalTemplates/Show', [
            'template' => $proposalTemplate,
            'recentProposals' => $recentProposals,
            'variables' => $variables,
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
            'layout_schema.canvas_blocks.*.surface' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.block_kind' => 'nullable|string|in:rich_text,signature,image,stamp,qr_code',
            'layout_schema.canvas_blocks.*.content_html' => 'nullable|string',
            'layout_schema.canvas_blocks.*.image_url' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.image_alt' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.image_position' => 'nullable|string|max:50',
            'layout_schema.canvas_blocks.*.qr_content' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.qr_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.qr_foreground_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'layout_schema.canvas_blocks.*.qr_background_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'layout_schema.canvas_blocks.*.qr_error_correction' => 'nullable|in:low,medium,quartile,high',
            'layout_schema.canvas_blocks.*.qr_margin' => 'nullable|integer|min:0|max:32',
            'layout_schema.canvas_blocks.*.x' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.y' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.width' => 'nullable|numeric|min:1|max:100',
            'layout_schema.canvas_blocks.*.min_height' => 'nullable|numeric|min:0|max:4000',
            'layout_schema.canvas_blocks.*.z_index' => 'nullable|numeric|min:0|max:999',
            'layout_schema.canvas_blocks.*.padding' => 'nullable|numeric|min:0|max:400',
            'layout_schema.canvas_blocks.*.background_color' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.background_image' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.background_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.background_image_position' => 'nullable|string|max:50',
            'layout_schema.canvas_blocks.*.overlay_color' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'layout_schema.canvas_blocks.*.text_color' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.border_width' => 'nullable|numeric|min:0|max:40',
            'layout_schema.canvas_blocks.*.border_color' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.border_radius' => 'nullable|numeric|min:0|max:2000',
            'layout_schema.canvas_blocks.*.opacity' => 'nullable|numeric|min:0.05|max:1',
            'layout_schema.canvas_blocks.*.text_align' => 'nullable|string|in:left,center,right,justify',
            'layout_schema.canvas_blocks.*.font_size' => 'nullable|numeric|min:8|max:72',
            'layout_schema.canvas_blocks.*.line_height' => 'nullable|numeric|min:0.8|max:3',
            'layout_schema.canvas_blocks.*.signature_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_name' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_image' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.signature_line_style' => 'nullable|string|in:solid,dashed',
            'layout_schema.canvas_blocks.*.signature_align' => 'nullable|string|in:left,center,right',
            'layout_schema.canvas_blocks.*.signature_show_date' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.signature_date_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.is_locked' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.page_scope' => 'nullable|string|in:first,all,following,specific',
            'layout_schema.canvas_blocks.*.page_number' => 'nullable|integer|min:1|max:999',
            'layout_schema.background_image_path' => 'nullable|string|max:2048',
            'layout_schema.background_size' => 'nullable|string|max:50',
            'layout_schema.background_position' => 'nullable|string|max:50',
            'layout_schema.background_repeat' => 'nullable|string|max:50',
            'layout_schema.table_header_background' => 'nullable|string|max:50',
            'layout_schema.table_header_text_color' => 'nullable|string|max:50',
            'layout_schema.table_border_color' => 'nullable|string|max:50',
            'layout_schema.table_font_size' => 'nullable|numeric|min:8|max:16',
            'layout_schema.table_cell_padding' => 'nullable|numeric|min:2|max:24',
            'export_settings' => 'nullable|array',
            'export_settings.paper_size' => 'nullable|string|max:20',
            'export_settings.orientation' => 'nullable|string|max:5',
            'export_settings.margin_top' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_right' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_bottom' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_left' => 'nullable|numeric|min:0|max:200',
            'export_settings.first_page_margin_top' => 'nullable|numeric|min:0|max:250',
        ]);

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
}
