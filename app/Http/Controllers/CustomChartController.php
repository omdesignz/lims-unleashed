<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomChartRequest;
use App\Metrics\ChartDatasets;
use App\Models\CustomChart;
use App\Services\LabNetworkAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The analytics board: charts each person builds for their active laboratory.
 * Values are computed live on every visit through the dataset registry, so a
 * saved chart shows only what its owner may still see.
 */
class CustomChartController extends Controller
{
    public function __construct(
        private readonly LabNetworkAccess $access,
        private readonly ChartDatasets $datasets,
    ) {}

    public function index(Request $request): Response
    {
        $labId = $this->activeLabId($request);
        $user = $request->user();

        $charts = CustomChart::query()->onBoard($user, $labId)->get()->map(function (CustomChart $chart) use ($user, $labId): array {
            $definition = $this->definitionOf($chart);
            $problems = $this->datasets->problems($user, $definition);

            return [
                'id' => $chart->id,
                'title' => $chart->title,
                ...$definition,
                'colors' => (object) ($chart->colors ?? []),
                'unavailable' => $problems === [] ? null : 'Já não tem acesso a estes dados ou a definição deixou de ser válida. Edite ou remova o gráfico.',
                'data' => $problems === [] ? $this->datasets->run($user, $labId, $definition) : null,
            ];
        });

        return Inertia::render('Analytics/Board', [
            'charts' => $charts,
            'datasets' => $this->datasets->available($user),
            'periods' => [
                ['key' => '30d', 'label' => 'Últimos 30 dias'],
                ['key' => '90d', 'label' => 'Últimos 90 dias'],
                ['key' => '12m', 'label' => 'Últimos 12 meses'],
                ['key' => 'all', 'label' => 'Todo o histórico'],
            ],
        ]);
    }

    /** Draws a definition without saving it, for the builder's live preview. */
    public function preview(CustomChartRequest $request): JsonResponse
    {
        return response()->json($this->datasets->run($request->user(), $this->activeLabId($request), $request->definition()));
    }

    public function store(CustomChartRequest $request): RedirectResponse
    {
        $labId = $this->activeLabId($request);
        $chart = new CustomChart([
            'title' => $request->string('title')->trim()->value(),
            ...$request->definition(),
            'colors' => $request->input('colors') ?: null,
            'position' => (int) CustomChart::query()->onBoard($request->user(), $labId)->max('position') + 1,
        ]);
        $chart->user_id = $request->user()->id;
        $chart->lab_id = $labId;
        $chart->save();

        return back()->with('success', 'Gráfico adicionado ao painel.');
    }

    public function update(CustomChartRequest $request, int $chart): RedirectResponse
    {
        $this->ownChart($request, $chart)->update([
            'title' => $request->string('title')->trim()->value(),
            ...$request->definition(),
            'colors' => $request->input('colors') ?: null,
        ]);

        return back()->with('success', 'Gráfico actualizado.');
    }

    public function destroy(Request $request, int $chart): RedirectResponse
    {
        $this->ownChart($request, $chart)->delete();

        return back()->with('success', 'Gráfico removido do painel.');
    }

    /** Saves the board order: every chart of the board, in its new order. */
    public function reorder(Request $request): RedirectResponse
    {
        $labId = $this->activeLabId($request);
        $owned = CustomChart::query()->onBoard($request->user(), $labId)->pluck('id')->all();
        $validated = $request->validate([
            'order' => ['required', 'array', 'size:'.count($owned)],
            'order.*' => ['integer', 'distinct', Rule::in($owned)],
        ]);

        foreach (array_values($validated['order']) as $position => $id) {
            CustomChart::query()->whereKey($id)->update(['position' => $position]);
        }

        return back();
    }

    private function ownChart(Request $request, int $chart): CustomChart
    {
        return CustomChart::query()->onBoard($request->user(), $this->activeLabId($request))->findOrFail($chart);
    }

    /**
     * @return array{dataset: string, measure: string, dimension: string, split: string|null, kind: string, period: string}
     */
    private function definitionOf(CustomChart $chart): array
    {
        return [
            'dataset' => $chart->dataset,
            'measure' => $chart->measure,
            'dimension' => $chart->dimension,
            'split' => $chart->split,
            'kind' => $chart->kind,
            'period' => $chart->period,
        ];
    }

    private function activeLabId(Request $request): int
    {
        $lab = $this->access->context($request->user(), $request->session()->get('active_lab_id'))['active_lab'];
        abort_if($lab === null, 403, 'Nenhum laboratório associado.');

        return (int) $lab['id'];
    }
}
