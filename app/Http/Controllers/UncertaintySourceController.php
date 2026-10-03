<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Parameter;
use App\Models\UncertaintySource;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UncertaintySourceController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function index(): Response
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $sources = UncertaintySource::query()
            ->where('lab_id', $labId)
            ->with([
                'department:id,name',
                'parameter:id,name,code',
                'inventoryItem:id,name,code',
            ])
            ->latest()
            ->get();

        return Inertia::render('UncertaintySources/Index', [
            'sources' => $sources,
            'departments' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'parameters' => Parameter::query()->select('id', 'name', 'code')->orderBy('name')->get(),
            'inventoryItems' => InventoryItem::query()->where('lab_id', $labId)->select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'parameter_id' => ['nullable', 'exists:parameters,id'],
            'inventory_item_id' => ['nullable', Rule::exists('i_items', 'id')->where('lab_id', $labId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'estimation_method' => ['nullable', 'string', 'max:5000'],
            'control_strategy' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        UncertaintySource::query()->create(array_merge($validated, ['lab_id' => $labId]));

        return back()->with('success', 'Fonte de incerteza registada.');
    }

    public function update(Request $request, UncertaintySource $uncertaintySource): RedirectResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        abort_unless($uncertaintySource->lab_id === $labId, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'parameter_id' => ['nullable', 'exists:parameters,id'],
            'inventory_item_id' => ['nullable', Rule::exists('i_items', 'id')->where('lab_id', $labId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'estimation_method' => ['nullable', 'string', 'max:5000'],
            'control_strategy' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $uncertaintySource->update($validated);

        return back()->with('success', 'Fonte de incerteza actualizada.');
    }

    public function destroy(UncertaintySource $uncertaintySource): RedirectResponse
    {
        abort_unless($uncertaintySource->lab_id === $this->laboratoryAccess->activeLabId(), 404);

        $uncertaintySource->delete();

        return back()->with('success', 'Fonte de incerteza arquivada.');
    }
}
