<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportStudioTemplateRequest;
use App\Models\ReportStudioTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
                ];
            });

        return Inertia::render('ReportStudios/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'studio_type', 'status_filter']),
            'summary' => [
                'total' => $templates->count(),
                'analysis' => $templates->where('studio_type', 'analysis')->count(),
                'executive' => $templates->where('studio_type', 'executive')->count(),
                'proposal' => $templates->where('studio_type', 'proposal')->count(),
                'canva' => $templates->where('renderer', 'canva')->count(),
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
}
