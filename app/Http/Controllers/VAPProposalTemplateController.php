<?php

namespace App\Http\Controllers;

use App\Actions\ImportProposalTemplates;
use App\Actions\SaveProposalTemplate;
use App\Actions\SetProposalTemplateActiveStatus;
use App\Actions\SetProposalTemplatesArchived;
use App\Exports\ProposalTemplatesExport;
use App\Http\Requests\ProposalTemplateAuthoringRequest;
use App\Http\Requests\ProposalTemplateImportRequest;
use App\Http\Requests\ProposalTemplateStatusRequest;
use App\Http\Resources\VAPProposalSummaryResource;
use App\Http\Resources\VAPProposalTemplateResource;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Settings\GeneralSettings;
use App\Support\ProposalTemplateImportFileReader;
use App\Support\ProposalTemplatePresetLibrary;
use App\Support\ReportStudioAssetLibrary;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VAPProposalTemplateController extends Controller
{
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

        $templates = $query->paginate(12)->withQueryString()
            ->through(fn (VAPProposalTemplate $template): array => VAPProposalTemplateResource::make($template)->resolve($request));

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

    public function store(ProposalTemplateAuthoringRequest $request, SaveProposalTemplate $save): RedirectResponse
    {
        $template = $save->execute($request->user()->id, $request->templatePayload());

        return redirect()->route('vap-proposals.templates.show', $template)
            ->with('success', 'Modelo de proposta criado com sucesso.');
    }

    public function show(Request $request, VAPProposalTemplate $proposalTemplate)
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
            'template' => VAPProposalTemplateResource::make($proposalTemplate)->resolve($request),
            'recentProposals' => $recentProposals->map(fn (VAPProposal $proposal): array => VAPProposalSummaryResource::make($proposal)->resolve($request)),
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
        ProposalTemplateAuthoringRequest $request,
        ReportStudioPdfBuilder $reportStudioPdfBuilder,
        ReportStudioPdfRenderer $reportStudioPdfRenderer,
        GeneralSettings $settings
    ): Response {
        $validated = $request->templatePayload();
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

    public function edit(Request $request, VAPProposalTemplate $proposalTemplate)
    {
        return Inertia::render('VAPProposalTemplates/Edit', [
            'template' => VAPProposalTemplateResource::make($proposalTemplate->load('user:id,name')->loadCount('proposals'))->resolve($request),
            'variables' => VAPProposalTemplate::getPlaceholders(),
            'presets' => ProposalTemplatePresetLibrary::all(),
            'studioAssets' => app(ReportStudioAssetLibrary::class)->assets(),
        ]);
    }

    public function update(ProposalTemplateAuthoringRequest $request, VAPProposalTemplate $proposalTemplate, SaveProposalTemplate $save): RedirectResponse
    {
        $template = $save->execute($request->user()->id, $request->templatePayload(), $proposalTemplate->id);

        return redirect()->route('vap-proposals.templates.show', $template)
            ->with('success', 'Modelo de proposta actualizado com sucesso.');
    }

    public function destroy(Request $request, VAPProposalTemplate $proposalTemplate, SetProposalTemplatesArchived $archive)
    {
        $archive->execute($request->user()->id, [$proposalTemplate->id], true);

        return redirect()->route('vap-proposals.templates.index')
            ->with('success', 'Modelo de proposta eliminado com sucesso.');
    }

    public function import(ProposalTemplateImportRequest $request, ProposalTemplateImportFileReader $reader, ImportProposalTemplates $import): JsonResponse
    {
        try {
            $result = $import->execute($request->user()->id, $reader->read($request->file('template_file')));
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Modelos importados com sucesso.', 'counts' => $result]);
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
    public function toggleStatus(ProposalTemplateStatusRequest $request, VAPProposalTemplate $proposalTemplate, SetProposalTemplateActiveStatus $action): JsonResponse
    {
        $template = $action->execute((int) $request->user()->id, (int) $proposalTemplate->id, (bool) $request->validated('is_active'));
        $status = $template->is_active ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'message' => "Modelo {$status} com sucesso.",
            'is_active' => $template->is_active,
        ]);
    }
}
