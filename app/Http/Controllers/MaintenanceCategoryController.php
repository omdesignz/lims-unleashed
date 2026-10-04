<?php

namespace App\Http\Controllers;

use App\Actions\ArchiveMaintenanceCategory;
use App\Actions\SaveMaintenanceCategory;
use App\Http\Requests\MaintenanceCategoryIndexRequest;
use App\Http\Requests\MaintenanceCategoryRequest;
use App\Http\Resources\MaintenanceCategoryResource;
use App\Models\MaintenanceCategory;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceCategoryController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $access) {}

    public function index(MaintenanceCategoryIndexRequest $request): Response
    {
        $labId = $this->access->activeLabId();
        $query = MaintenanceCategory::availableToLaboratory($labId)
            ->when($request->boolean('archived'), fn ($query) => $query->onlyTrashed())
            ->when($request->validated('search'), fn ($query, $search) => $query
                ->where(fn ($query) => $query->where('name', 'ilike', '%'.$search.'%')
                    ->orWhere('code', 'ilike', '%'.$search.'%')->orWhere('description', 'ilike', '%'.$search.'%')));
        $stats = [
            'total' => (clone $query)->count(),
            'presets' => (clone $query)->whereNull('lab_id')->count(),
            'owned' => (clone $query)->where('lab_id', $labId)->count(),
        ];

        return Inertia::render('VAPMaintenance/Categories/Index', [
            'breadcrumbs' => [
                ['title' => 'Tarefas de manutenção', 'url' => route('vap-maintenance.tasks'), 'current' => false],
                ['title' => 'Categorias', 'current' => true],
            ],
            'categories' => $query->withExists(['tasks as code_locked' => fn ($query) => $query->withTrashed()])
                ->orderBy('name')->orderBy('id')->paginate($request->validated('per_page') ?? 15)->withQueryString()
                ->through(fn ($category) => MaintenanceCategoryResource::make($category)->resolve($request)),
            'stats' => $stats,
            'filters' => $request->safe()->except(['page', 'per_page']),
            'can' => collect(['create' => 'add', 'edit' => 'edit', 'archive' => 'delete', 'restore' => 'restore'])
                ->map(fn ($ability) => ! $request->session()->has('impersonate') && $request->user()->can($ability.'_maintenance_categories')),
        ]);
    }

    public function store(MaintenanceCategoryRequest $request, SaveMaintenanceCategory $save): RedirectResponse
    {
        $save->execute($request->user()->id, $this->access->activeLabId(), $request->validated());

        return to_route('vap-maintenance.categories')->with('toast', ['message' => 'Categoria criada.']);
    }

    public function update(MaintenanceCategoryRequest $request, MaintenanceCategory $category, SaveMaintenanceCategory $save): RedirectResponse
    {
        $save->execute($request->user()->id, $this->access->activeLabId(), $request->validated(), $category->id);

        return back()->with('toast', ['message' => 'Categoria actualizada.']);
    }

    public function destroy(Request $request, MaintenanceCategory $category, ArchiveMaintenanceCategory $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $this->access->activeLabId(), $category->id);

        return back()->with('toast', ['message' => 'Categoria arquivada. Os registos existentes foram preservados.']);
    }

    public function restore(Request $request, MaintenanceCategory $category, ArchiveMaintenanceCategory $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $this->access->activeLabId(), $category->id, true);

        return back()->with('toast', ['message' => 'Categoria restaurada.']);
    }

    public function legacyIndex(): RedirectResponse
    {
        $this->access->activeLabId();

        return to_route('vap-maintenance.categories');
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $query = MaintenanceCategory::availableToLaboratory($this->access->activeLabId());

        return response()->json(blank($data['q'] ?? null) ? [] : $query
            ->where(fn ($query) => $query->where('name', 'ilike', '%'.$data['q'].'%')->orWhere('code', 'ilike', '%'.$data['q'].'%'))
            ->orderBy('name')->limit(25)->get(['id', 'name', 'code', 'description']));
    }
}
