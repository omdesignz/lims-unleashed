<?php

namespace App\Http\Controllers;

use App\Exports\ProposalTemplatesExport;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Support\ProposalTemplatePresetLibrary;
use App\Support\ProposalTemplatesSpreadsheetImport;
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
            ->withCount('proposals')
            ->latest();

        // Filtro de busca
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filtro de status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        // Filtro de categoria
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Ordenação
        if ($request->has('sort')) {
            switch ($request->sort) {
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

        $templates = $query->paginate(12);

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
            'presets' => ProposalTemplatePresetLibrary::summaries(),
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
            'export_settings' => 'nullable|array',
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
            ->with('success', 'Template created successfully.');
    }

    public function show(VAPProposalTemplate $proposalTemplate)
    {
        $proposalTemplate->load(['user']);
    
        // Carregar contagem de propostas
        $proposalTemplate->loadCount('proposals');
        
        // Carregar propostas recentes que usam este template
        $recentProposals = VAPProposal::where('template_id', $proposalTemplate->id)
            ->with(['customer'])
            ->latest()
            ->limit(10)
            ->get();
        
        // Variáveis disponíveis para templates
        $variables = [
            '{client_name}' => 'Nome do Cliente',
            '{client_code}' => 'Código do Cliente',
            '{client_address}' => 'Endereço do Cliente',
            '{client_contact}' => 'Pessoa de Contato do Cliente',
            '{proposal_number}' => 'Número da Proposta',
            '{proposal_date}' => 'Data da Proposta',
            '{expiry_date}' => 'Data de Validade',
            '{service_location}' => 'Local do Serviço',
            '{lab_name}' => 'Nome do Laboratório',
            '{lab_address}' => 'Endereço do Laboratório',
            '{lab_phone}' => 'Telefone do Laboratório',
            '{lab_email}' => 'E-mail do Laboratório',
            '{department}' => 'Departamento',
            '{warehouse}' => 'Armazém',
            '{subtotal}' => 'Subtotal',
            '{total}' => 'Total',
            '{tax_amount}' => 'Valor do Imposto',
            '{discount_amount}' => 'Valor do Desconto',
            '{items_table}' => 'Tabela de Itens/Serviços',
            '{items_count}' => 'Número de Itens',
            '{terms_conditions}' => 'Termos e Condições',
            '{payment_terms}' => 'Termos de Pagamento',
            '{compliance_section}' => 'Seção de Conformidade',
            '{lab_signature}' => 'Assinatura do Laboratório',
            '{client_signature}' => 'Assinatura do Cliente',
        ];

        return Inertia::render('VAPProposalTemplates/Show', [
            'template' => $proposalTemplate,
            'recentProposals' => $recentProposals,
            'variables' => $variables,
        ]);
    }

    public function edit(VAPProposalTemplate $proposalTemplate)
    {
        return Inertia::render('VAPProposalTemplates/Edit', [
            'template' => $proposalTemplate->loadCount('proposals'),
            'presets' => ProposalTemplatePresetLibrary::summaries(),
        ]);
    }

    public function update(Request $request, VAPProposalTemplate $proposalTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:proposal_templates,name,' . $proposalTemplate->id,
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'theme_preset' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'content' => 'required|string',
            'layout_schema' => 'nullable|array',
            'export_settings' => 'nullable|array',
        ]);

        $proposalTemplate->update($validated);

        return redirect()->route('vap-proposals.templates.show', $proposalTemplate)
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(VAPProposalTemplate $proposalTemplate)
    {
        if ($proposalTemplate->proposals()->count() > 0) {
            return back()->with('error', 'Cannot delete template that is being used by proposals.');
        }

        $proposalTemplate->delete();

        return redirect()->route('vap-proposals.templates.index')
            ->with('success', 'Template deleted successfully.');
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
                'message' => 'Templates importados com sucesso!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao importar templates: ' . $e->getMessage(),
            ], 500);
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
                new ProposalTemplatesExport(),
                'proposal-templates-' . now()->format('Y-m-d') . '.' . $format,
                $writerType
            );
        }

        $templates = VAPProposalTemplate::with('user')->get()->map(function($template) {
            return [
                'name' => $template->name,
                'content' => $template->content,
                'category' => $template->category,
                'description' => $template->description,
                'is_active' => $template->is_active,
                'theme_preset' => $template->theme_preset,
                'layout_schema' => $template->layout_schema ?? [],
                'export_settings' => $template->export_settings ?? [],
                'created_by' => $template->user->name,
                'created_at' => $template->created_at->toISOString(),
            ];
        });

        $filename = 'templates-' . date('Y-m-d') . '.json';

        return response()->streamDownload(function() use ($templates) {
            echo json_encode($templates, JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function toggleStatus(VAPProposalTemplate $proposalTemplate)
    {
        try {
            $proposalTemplate->update([
                'is_active' => !$proposalTemplate->is_active
            ]);
            
            $status = $proposalTemplate->is_active ? 'ativado' : 'desativado';
            
            activity()
                ->performedOn($proposalTemplate)
                ->causedBy(auth()->user())
                ->log("template_{$status}");
            
            return response()->json([
                'success' => true,
                'message' => "Template {$status} com sucesso.",
                'is_active' => $proposalTemplate->is_active
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status do template: ' . $e->getMessage()
            ], 500);
        }
    }
}
