<?php

namespace App\Http\Controllers;

use App\Models\VAPLab;
use App\Models\VAPLabelTemplate;
use Inertia\Inertia;
use Illuminate\Http\Request;

class VAPLabelTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = VAPLabelTemplate::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($request->category, function ($query, $category) {
                $query->where('category', $category);
            })
            ->when($request->featured, function ($query, $featured) {
                $query->where('is_featured', $featured === 'yes');
            })
            ->when($request->status, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            });

        $templates = $query->latest()->paginate(20);

        $categories = VAPLabelTemplate::distinct()->pluck('category');

        return Inertia::render('VAPLabelTemplates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'category', 'featured', 'status']),
            'categories' => $categories,
            'stats' => [
                'total' => VAPLabelTemplate::count(),
                'active' => VAPLabelTemplate::where('is_active', true)->count(),
                'featured' => VAPLabelTemplate::where('is_featured', true)->count(),
            ],
        ]);
    }

    public function create()
    {
        $labs = VAPLab::active()->get(['id', 'name']);
        
        return Inertia::render('VAPLabelTemplates/Create', [
            'categories' => [
                'equipment' => 'Equipamento',
                'consumables' => 'Consumíveis',
                'samples' => 'Amostras',
                'storage' => 'Armazenamento',
                'safety' => 'Segurança',
                'general' => 'Geral',
                'custom' => 'Personalizado',
            ],
            'labs' => $labs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'template_data' => 'required|array',
            'template_data.content' => 'required|string',
            'template_data.type' => 'nullable|string',
            'template_data.width' => 'required|numeric|min:1|max:1000',
            'template_data.height' => 'required|numeric|min:1|max:1000',
            'template_data.background_color' => 'nullable|string',
            'template_data.text_color' => 'nullable|string',
            'template_data.font_size' => 'nullable|integer|min:6|max:72',
            'template_data.border_width' => 'nullable|integer|min:0|max:10',
            'template_data.border_color' => 'nullable|string',
            'template_data.text_alignment' => 'nullable|in:left,center,right,justify',
            'template_data.has_qr_code' => 'nullable|boolean',
            'template_data.qr_code_content' => 'nullable|string',
            'template_data.qr_code_size' => 'nullable|numeric|min:1|max:50',
            'template_data.has_barcode' => 'nullable|boolean',
            'template_data.barcode_content' => 'nullable|string',
            'template_data.barcode_type' => 'nullable|string',
            'template_data.barcode_width' => 'nullable|numeric|min:1|max:50',
            'template_data.barcode_height' => 'nullable|numeric|min:1|max:50',
            'template_data.logo_path' => 'nullable|string',
            'template_data.logo_size' => 'nullable|numeric|min:1|max:50',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        VAPLabelTemplate::create($validated);

        return redirect()->route('vap_labels.label-templates.index')
            ->with('success', 'Modelo criado com sucesso.');
    }

    public function edit(VAPLabelTemplate $labelTemplate)
    {
        return Inertia::render('VAPLabelTemplates/Edit', [
            'template' => $labelTemplate,
            'categories' => [
                'equipment' => 'Equipamento',
                'consumables' => 'Consumíveis',
                'samples' => 'Amostras',
                'storage' => 'Armazenamento',
                'safety' => 'Segurança',
                'general' => 'Geral',
                'custom' => 'Personalizado',
            ],
        ]);
    }

    public function update(Request $request, VAPLabelTemplate $labelTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'template_data' => 'required|array',
            'template_data.content' => 'required|string',
            'template_data.type' => 'nullable|string',
            'template_data.width' => 'required|numeric|min:1|max:1000',
            'template_data.height' => 'required|numeric|min:1|max:1000',
            'template_data.background_color' => 'nullable|string',
            'template_data.text_color' => 'nullable|string',
            'template_data.font_size' => 'nullable|integer|min:6|max:72',
            'template_data.border_width' => 'nullable|integer|min:0|max:10',
            'template_data.border_color' => 'nullable|string',
            'template_data.text_alignment' => 'nullable|in:left,center,right,justify',
            'template_data.has_qr_code' => 'nullable|boolean',
            'template_data.qr_code_content' => 'nullable|string',
            'template_data.qr_code_size' => 'nullable|numeric|min:1|max:50',
            'template_data.has_barcode' => 'nullable|boolean',
            'template_data.barcode_content' => 'nullable|string',
            'template_data.barcode_type' => 'nullable|string',
            'template_data.barcode_width' => 'nullable|numeric|min:1|max:50',
            'template_data.barcode_height' => 'nullable|numeric|min:1|max:50',
            'template_data.logo_path' => 'nullable|string',
            'template_data.logo_size' => 'nullable|numeric|min:1|max:50',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $labelTemplate->update($validated);

        return redirect()->route('vap_labels.label-templates.index')
            ->with('success', 'Modelo atualizado com sucesso.');
    }

    public function destroy(VAPLabelTemplate $labelTemplate)
    {
        $labelTemplate->delete();

        return redirect()->route('vap_labels.label-templates.index')
            ->with('success', 'Modelo eliminado com sucesso.');
    }

    public function toggleStatus(VAPLabelTemplate $labelTemplate)
    {
        $labelTemplate->update(['is_active' => !$labelTemplate->is_active]);

        return back()->with('success', 'Estado do modelo atualizado.');
    }

    public function toggleFeatured(VAPLabelTemplate $labelTemplate)
    {
        $labelTemplate->update(['is_featured' => !$labelTemplate->is_featured]);

        return back()->with('success', 'Estado de destaque atualizado.');
    }
}
