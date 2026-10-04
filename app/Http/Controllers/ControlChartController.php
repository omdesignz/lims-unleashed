<?php

namespace App\Http\Controllers;

use App\Http\Requests\ControlChartRequest;
use App\Models\ControlChart;
use App\Models\ControlChartPoint;
use App\Models\Product;
use App\Models\VAPSampleEntry;
use App\Services\ControlChartFeed;
use App\Services\SampleLaboratoryAccess;
use App\Support\ControlChartDocument;
use App\Support\ControlChartEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;

/**
 * Control charts of the active laboratory: their limits, the control values
 * recorded on them and the action taken when a value is out of control.
 * A recorded value is never edited: a wrong value is excluded, with its
 * reason, and the right one recorded.
 */
class ControlChartController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly ControlChartFeed $feed,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeAbility('view');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,archived'],
        ]);
        $status = $filters['status'] ?? 'active';

        $charts = $this->labCharts()
            ->with(['parameter:id,name,code', 'controlProduct:id,name', 'points'])
            ->where('status', $status)
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('method', 'ilike', "%{$search}%")
                ->orWhere('control_material', 'ilike', "%{$search}%")))
            ->orderBy('name')
            ->get()
            ->map(fn (ControlChart $chart): array => $this->summary($chart))
            ->values();

        return Inertia::render('ControlCharts/Index', [
            'charts' => $charts,
            'filters' => ['search' => $filters['search'] ?? '', 'status' => $status],
            'totals' => [
                'active' => $this->labCharts()->where('status', 'active')->count(),
                'out_of_control' => $charts->where('state', ControlChartEvaluation::OUT_OF_CONTROL)->count(),
                'open_actions' => $charts->sum('open_actions'),
                'without_limits' => $charts->where('has_limits', false)->count(),
            ],
            'types' => $this->options(ControlChart::TYPES),
            'permissions' => $this->permissions(),
        ]);
    }

    public function store(ControlChartRequest $request): RedirectResponse
    {
        $this->authorizeAbility('add');
        $data = $request->validated();

        $chart = ControlChart::query()->create([
            ...$data,
            ...$this->enteredLimits($data, $request),
            'lab_id' => $this->laboratoryAccess->activeLabId(),
            'status' => 'active',
            'created_by_id' => $request->user()->id,
        ]);

        $imported = $this->feed->feed($chart);

        return to_route('control-charts.show', $chart)->with('toast', [
            'title' => 'Carta de controlo criada',
            'message' => match (true) {
                $imported > 0 => $imported.' resultados aprovados das amostras de controlo foram trazidos para a carta.',
                ControlChartFeed::feedsFromResults($chart) => 'Os resultados aprovados das amostras de controlo entram na carta automaticamente.',
                $chart->limits() !== null => 'Registe os valores de controlo à medida que são obtidos.',
                default => 'Defina os limites, ou calcule-os quando tiver pelo menos '.ControlChartEvaluation::MINIMUM_POINTS_FOR_LIMITS.' pontos.',
            },
        ]);
    }

    public function show(ControlChart $chart): Response
    {
        $this->authorizeAbility('view');
        $this->assertOwns($chart);
        $chart->load(['parameter:id,name,code', 'controlProduct:id,name', 'points.recordedBy:id,name', 'points.correctiveActionBy:id,name', 'points.sampleEntry:id,code', 'limitsSetBy:id,name']);
        $document = new ControlChartDocument($chart);

        return Inertia::render('ControlCharts/Show', [
            'chart' => [
                ...$this->summary($chart),
                'chart_type' => $chart->chart_type,
                'parameter_id' => $chart->parameter ? ['value' => $chart->parameter->id, 'label' => trim($chart->parameter->code.' · '.$chart->parameter->name, ' ·')] : null,
                'control_product_id' => $chart->controlProduct ? ['value' => $chart->controlProduct->id, 'label' => $chart->controlProduct->name] : null,
                'feeds_from_results' => ControlChartFeed::feedsFromResults($chart),
                'matrix' => $chart->matrix,
                'material_lot' => $chart->material_lot,
                'centre_line' => $chart->centre_line,
                'standard_deviation' => $chart->standard_deviation,
                'limits_source' => $chart->limits_source,
                'limits_source_label' => ControlChart::LIMIT_SOURCES[$chart->limits_source] ?? null,
                'limits_basis' => $chart->limits_basis,
                'limits_point_count' => $chart->limits_point_count,
                'limits_set_at' => $chart->limits_set_at?->format('d/m/Y H:i'),
                'limits_set_by' => $chart->limitsSetBy?->name,
                'notes' => $chart->notes,
                'status' => $chart->status,
            ],
            'limits' => $chart->limits(),
            'points' => $document->points(),
            'statistics' => $document->statistics(),
            'limitHistory' => $this->limitHistory($chart),
            'controlSamples' => $this->controlSamples($chart),
            'rules' => ControlChartEvaluation::RULES,
            'minimumPointsForLimits' => ControlChartEvaluation::MINIMUM_POINTS_FOR_LIMITS,
            'recommendedPointsForLimits' => ControlChartEvaluation::RECOMMENDED_POINTS_FOR_LIMITS,
            'permissions' => $this->permissions(),
        ]);
    }

    public function update(ControlChartRequest $request, ControlChart $chart): RedirectResponse
    {
        $this->authorizeAbility('edit');
        $this->assertOwns($chart);
        $data = $request->validated();

        DB::transaction(function () use ($chart, $data, $request): void {
            $chart = ControlChart::query()->lockForUpdate()->findOrFail($chart->id);
            $limitsChanged = (array_key_exists('centre_line', $data) && $data['centre_line'] != $chart->centre_line)
                || (array_key_exists('standard_deviation', $data) && $data['standard_deviation'] != $chart->standard_deviation);

            $chart->fill($data);
            if ($limitsChanged) {
                $chart->fill($this->enteredLimits($data, $request));
            }
            $chart->save();
        });

        $imported = $this->feed->feed($chart->fresh());

        return back()->with('toast', ['title' => 'Carta de controlo actualizada', 'message' => $imported > 0
            ? $imported.' resultados aprovados das amostras de controlo foram trazidos para a carta.'
            : 'As alterações aos limites ficam no histórico da carta.']);
    }

    /**
     * Brings any approved result of the chart's quality control samples that
     * is not yet on it. Approval does this on its own; this repeats it.
     */
    public function feedResults(ControlChart $chart): RedirectResponse
    {
        $this->authorizeAbility('edit');
        $this->assertOwns($chart);
        abort_unless(ControlChartFeed::feedsFromResults($chart), 409, 'A carta não tem parâmetro e material de controlo definidos.');

        $imported = $this->feed->feed($chart);

        return back()->with('toast', ['title' => 'Resultados das amostras de controlo', 'message' => $imported > 0
            ? $imported.' novos pontos a partir de resultados aprovados.'
            : 'Não há resultados aprovados novos para esta carta.']);
    }

    /**
     * Catalogue products marked as control material, for choosing a chart's.
     */
    public function materials(Request $request): JsonResponse
    {
        $this->authorizeAbility('view');
        $search = trim((string) $request->query('q', ''));

        return response()->json(Product::query()
            ->where('is_control_material', true)
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']));
    }

    /**
     * Sets the limits from the included points: mean and sample standard
     * deviation (mean chart) or mean range (range chart).
     */
    public function computeLimits(Request $request, ControlChart $chart): RedirectResponse
    {
        $this->authorizeAbility('edit');
        $this->assertOwns($chart);
        $validated = $request->validate(['limits_basis' => ['nullable', 'string', 'max:2000']], [], ['limits_basis' => 'fundamento dos limites']);

        DB::transaction(function () use ($chart, $validated, $request): void {
            $chart = ControlChart::query()->lockForUpdate()->findOrFail($chart->id);
            $values = $chart->points()->where('excluded', false)->pluck('value')->all();

            try {
                $computed = ControlChartEvaluation::computeLimits($chart->chart_type, $values);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['limits' => $exception->getMessage()]);
            }

            $first = $chart->points()->where('excluded', false)->min('measured_at');
            $last = $chart->points()->where('excluded', false)->max('measured_at');

            $chart->fill([
                'centre_line' => $computed['centre_line'],
                'standard_deviation' => $computed['standard_deviation'],
                'limits_source' => 'computed',
                'limits_basis' => filled($validated['limits_basis'] ?? null)
                    ? $validated['limits_basis']
                    : sprintf('Calculados a partir de %d pontos incluídos, de %s a %s.', $computed['point_count'],
                        date('d/m/Y', strtotime((string) $first)), date('d/m/Y', strtotime((string) $last))),
                'limits_point_count' => $computed['point_count'],
                'limits_set_at' => now(),
                'limits_set_by_id' => $request->user()->id,
            ])->save();
        });

        return back()->with('toast', ['title' => 'Limites calculados', 'message' => 'Os pontos seguintes são avaliados contra os novos limites.']);
    }

    public function storePoint(Request $request, ControlChart $chart): RedirectResponse
    {
        $this->authorizeAbility('edit');
        $this->assertOwns($chart);
        abort_if($chart->status !== 'active', 409, 'Uma carta arquivada não recebe novos pontos.');

        $decimal = fn (mixed $value): mixed => is_string($value) ? (trim($value) === '' ? null : str_replace(',', '.', trim($value))) : $value;
        $request->merge(collect(['value', 'replicate_a', 'replicate_b'])->filter(fn (string $key): bool => $request->has($key))
            ->mapWithKeys(fn (string $key): array => [$key => $decimal($request->input($key))])->all());
        $validated = $request->validate([
            'measured_at' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateString()],
            'value' => [$chart->isRange() ? 'prohibited' : 'required', 'numeric', 'between:-1000000000,1000000000'],
            'replicate_a' => [$chart->isRange() ? 'required' : 'prohibited', 'numeric', 'between:-1000000000,1000000000'],
            'replicate_b' => [$chart->isRange() ? 'required' : 'prohibited', 'numeric', 'between:-1000000000,1000000000'],
            'run_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'measured_at' => 'data da medição',
            'value' => 'valor',
            'replicate_a' => 'primeira réplica',
            'replicate_b' => 'segunda réplica',
            'run_reference' => 'corrida ou lote analítico',
            'notes' => 'notas',
        ]);

        $chart->points()->create([
            ...$validated,
            'value' => $chart->isRange() ? abs((float) $validated['replicate_a'] - (float) $validated['replicate_b']) : $validated['value'],
            'recorded_by_id' => $request->user()->id,
        ]);

        return back()->with('toast', ['title' => 'Ponto registado', 'message' => 'O ponto foi avaliado contra os limites da carta.']);
    }

    /**
     * Excludes or includes a point, with its reason, and records the action
     * taken on it. The value itself never changes.
     */
    public function updatePoint(Request $request, ControlChart $chart, ControlChartPoint $point): RedirectResponse
    {
        $this->authorizeAbility('edit');
        $this->assertOwns($chart);
        abort_unless((int) $point->control_chart_id === (int) $chart->id, 404);

        $validated = $request->validate([
            'excluded' => ['required', 'boolean'],
            'exclusion_reason' => ['nullable', 'string', 'max:2000', 'required_if:excluded,true'],
            'corrective_action' => ['nullable', 'string', 'max:4000'],
        ], [
            'exclusion_reason.required_if' => 'Indique porque o ponto é excluído.',
        ], [
            'exclusion_reason' => 'motivo da exclusão',
            'corrective_action' => 'acção tomada',
        ]);

        $point->fill([
            'excluded' => $validated['excluded'],
            'exclusion_reason' => $validated['excluded'] ? $validated['exclusion_reason'] : null,
        ]);
        $action = filled($validated['corrective_action'] ?? null) ? trim($validated['corrective_action']) : null;
        if ($action !== $point->corrective_action) {
            $point->fill([
                'corrective_action' => $action,
                'corrective_action_at' => $action === null ? null : now(),
                'corrective_action_by_id' => $action === null ? null : $request->user()->id,
            ]);
        }
        $point->save();

        return back()->with('toast', ['title' => 'Ponto actualizado', 'message' => $point->excluded ? 'O ponto deixou de contar para as regras e para os limites calculados.' : 'O registo do ponto foi actualizado.']);
    }

    public function destroy(ControlChart $chart): RedirectResponse
    {
        $this->authorizeAbility('delete');
        $this->assertOwns($chart);
        $chart->delete();

        return to_route('control-charts.index')->with('toast', ['title' => 'Carta de controlo eliminada', 'message' => $chart->name]);
    }

    public function exportPdf(ControlChart $chart): HttpResponse
    {
        $this->authorizeAbility('view');
        $this->assertOwns($chart);
        $chart->load(['parameter:id,name,code', 'controlProduct:id,name', 'points.recordedBy:id,name', 'points.correctiveActionBy:id,name', 'points.sampleEntry:id,code', 'limitsSetBy:id,name', 'lab:id,name']);

        return (new ControlChartDocument($chart))->download();
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(ControlChart $chart): array
    {
        $document = new ControlChartDocument($chart);
        $points = collect($document->points());
        $included = $points->where('excluded', false);
        $latest = $included->last();

        return [
            'id' => $chart->id,
            'name' => $chart->name,
            'type_label' => ControlChart::TYPES[$chart->chart_type] ?? $chart->chart_type,
            'is_range' => $chart->isRange(),
            'parameter' => $chart->parameter ? trim(($chart->parameter->code ? $chart->parameter->code.' · ' : '').$chart->parameter->name) : null,
            'method' => $chart->method,
            'control_material' => $chart->control_material ?: $chart->controlProduct?->name,
            'control_product' => $chart->controlProduct?->name,
            'unit' => $chart->unit,
            'has_limits' => $chart->limits() !== null,
            'point_count' => $included->count(),
            'last_measured_at' => $latest['measured_at'] ?? null,
            'state' => $latest['state'] ?? null,
            'state_label' => $latest ? ControlChartEvaluation::STATES[$latest['state']] : null,
            'open_actions' => $points->where('needs_action', true)->count(),
        ];
    }

    /**
     * Limits typed by a person: who and when, never a point count.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function enteredLimits(array $data, Request $request): array
    {
        $set = filled($data['centre_line'] ?? null);

        return [
            'limits_source' => $set ? 'entered' : null,
            'limits_point_count' => null,
            'limits_set_at' => $set ? now() : null,
            'limits_set_by_id' => $set ? $request->user()->id : null,
        ];
    }

    /**
     * The latest quality control samples of the chart's control material in
     * this laboratory, with how many of their results are on the chart.
     *
     * @return list<array<string, mixed>>
     */
    private function controlSamples(ControlChart $chart): array
    {
        if (! $chart->control_product_id) {
            return [];
        }

        $plotted = $chart->points->whereNotNull('sample_entry_id')->countBy('sample_entry_id');

        return VAPSampleEntry::query()
            ->where('lab_id', $chart->lab_id)
            ->whereHas('collectionProduct', fn (Builder $query) => $query->where('product_id', $chart->control_product_id))
            ->latest('received_at')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'code', 'status', 'received_at'])
            ->map(fn (VAPSampleEntry $entry): array => [
                'id' => $entry->id,
                'code' => $entry->code,
                'received_at' => $entry->received_at?->format('d/m/Y'),
                'points' => (int) ($plotted[$entry->id] ?? 0),
                'url' => route('vap_samples.show', $entry->id),
            ])
            ->all();
    }

    /**
     * Every change to the chart's limits, newest first.
     *
     * @return list<array<string, mixed>>
     */
    private function limitHistory(ControlChart $chart): array
    {
        return Activity::query()
            ->where('subject_type', $chart->getMorphClass())
            ->where('subject_id', $chart->id)
            ->with('causer:id,name')
            ->latest('id')
            ->limit(50)
            ->get()
            ->filter(fn (Activity $activity): bool => array_intersect(['centre_line', 'standard_deviation'], array_keys((array) $activity->properties->get('attributes', []))) !== [])
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'at' => $activity->created_at?->format('d/m/Y H:i'),
                'by' => $activity->causer?->name,
                'centre_line' => data_get($activity->properties, 'attributes.centre_line'),
                'standard_deviation' => data_get($activity->properties, 'attributes.standard_deviation'),
                'source' => ControlChart::LIMIT_SOURCES[data_get($activity->properties, 'attributes.limits_source')] ?? null,
                'basis' => data_get($activity->properties, 'attributes.limits_basis'),
            ])
            ->values()
            ->all();
    }

    /** @return Builder<ControlChart> */
    private function labCharts(): Builder
    {
        return ControlChart::query()->where('lab_id', $this->laboratoryAccess->activeLabId());
    }

    private function assertOwns(ControlChart $chart): void
    {
        abort_unless((int) $chart->lab_id === $this->laboratoryAccess->activeLabId(), 404);
    }

    private function authorizeAbility(string $ability): void
    {
        $user = auth()->user();
        abort_unless($user->hasRole('admin') || $user->can($ability.'_'.ControlChart::MENU_NAME), 403);
    }

    /**
     * @return array{add: bool, edit: bool, delete: bool}
     */
    private function permissions(): array
    {
        $user = auth()->user();
        $can = fn (string $ability): bool => $user->hasRole('admin') || $user->can($ability.'_'.ControlChart::MENU_NAME);

        return ['add' => $can('add'), 'edit' => $can('edit'), 'delete' => $can('delete')];
    }

    /**
     * @param  array<string, string>  $labels
     * @return list<array{value: string, label: string}>
     */
    private function options(array $labels): array
    {
        return collect($labels)->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])->values()->all();
    }
}
