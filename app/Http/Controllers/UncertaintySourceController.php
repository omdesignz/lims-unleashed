<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Parameter;
use App\Models\UncertaintySource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UncertaintySourceController extends Controller
{
    public function index()
    {
        $sources = UncertaintySource::query()
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
            'inventoryItems' => InventoryItem::query()->select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'parameter_id' => ['nullable', 'exists:parameters,id'],
            'inventory_item_id' => ['nullable', 'exists:i_items,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'estimation_method' => ['nullable', 'string', 'max:5000'],
            'control_strategy' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        UncertaintySource::query()->create($validated);

        return back()->with('success', 'Fonte de incerteza registada.');
    }

    public function update(Request $request, UncertaintySource $uncertaintySource)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'parameter_id' => ['nullable', 'exists:parameters,id'],
            'inventory_item_id' => ['nullable', 'exists:i_items,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'estimation_method' => ['nullable', 'string', 'max:5000'],
            'control_strategy' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $uncertaintySource->update($validated);

        return back()->with('success', 'Fonte de incerteza atualizada.');
    }

    public function destroy(UncertaintySource $uncertaintySource)
    {
        $uncertaintySource->delete();

        return back()->with('success', 'Fonte de incerteza arquivada.');
    }
}
