<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentalCondition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnvironmentalConditionController extends Controller
{
    public function index(Request $request)
    {
        abort_if( !auth()->user()->can('view_temperatures'), 403, '');

        $query = EnvironmentalCondition::query()
            ->with('recordedBy:id,name')
            ->when($request->string('search')->toString(), function ($builder, $search) {
                $builder->where(function ($nested) use ($search) {
                    $nested->where('area', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($builder) use ($request) {
                $builder->where('status', $request->input('status'));
            })
            ->latest('recorded_at');

        return Inertia::render('EnvironmentalConditions/Index', [
            'conditions' => $query->paginate($request->integer('per_page', 15))->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => EnvironmentalCondition::query()->count(),
                'critical' => EnvironmentalCondition::query()->where('status', 'critical')->count(),
                'within_limits' => EnvironmentalCondition::query()->where('status', 'within_limits')->count(),
                'today' => EnvironmentalCondition::query()->whereDate('recorded_at', today())->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_if( !auth()->user()->can('add_temperatures'), 403, '');

        $validated = $this->validatePayload($request);
        $condition = new EnvironmentalCondition($validated);
        $condition->recorded_by_id = auth()->id();
        $condition->status = $condition->evaluateStatus();
        $condition->save();

        return to_route('environmental-conditions.index')->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => 'Condição ambiental registada com sucesso.',
        ]);
    }

    public function update(Request $request, EnvironmentalCondition $environmentalCondition)
    {
        abort_if( !auth()->user()->can('edit_temperatures'), 403, '');

        $validated = $this->validatePayload($request);
        $environmentalCondition->fill($validated);
        $environmentalCondition->status = $environmentalCondition->evaluateStatus();
        $environmentalCondition->save();

        return to_route('environmental-conditions.index')->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => 'Condição ambiental atualizada com sucesso.',
        ]);
    }

    public function destroy(EnvironmentalCondition $environmentalCondition)
    {
        abort_if( !auth()->user()->can('delete_temperatures'), 403, '');

        $environmentalCondition->delete();

        return to_route('environmental-conditions.index')->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => 'Condição ambiental removida com sucesso.',
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'area' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'recorded_at' => ['required', 'date'],
            'temperature_c' => ['nullable', 'numeric'],
            'humidity_percent' => ['nullable', 'numeric', 'between:0,100'],
            'pressure_kpa' => ['nullable', 'numeric', 'min:0'],
            'co2_ppm' => ['nullable', 'numeric', 'min:0'],
            'temperature_min_c' => ['nullable', 'numeric'],
            'temperature_max_c' => ['nullable', 'numeric'],
            'humidity_min_percent' => ['nullable', 'numeric', 'between:0,100'],
            'humidity_max_percent' => ['nullable', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
