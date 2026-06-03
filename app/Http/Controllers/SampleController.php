<?php

namespace App\Http\Controllers;

use App\Http\Resources\SampleResource;
use App\Models\Parameter;
use App\Models\Sample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\QueryBuilder;

class SampleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        abort_if(! auth()->user()->can('view_samples'), 403, '');

        $parameterIds = $this->parameterIdsFromRequest();
        $perPage = max(1, min(100, (int) request()->query('per_page', 10)));
        $sort = request()->query('sort', '');
        $sort = is_string($sort) ? $sort : '';

        $records = QueryBuilder::for(Sample::class)
            ->with(['collection', 'analysis.profile.parameters'])
            ->when($parameterIds !== [], function ($query) use ($parameterIds): void {
                $query->whereHas('analysis.profile.parameters', function ($query) use ($parameterIds): void {
                    $query->whereIn('parameter_id', $parameterIds);
                });
            })
            ->allowedFilters(Sample::getAllowedFilters())
            ->allowedSorts(Sample::getAllowedSorts())
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $selectedParameters = Parameter::query()
            ->whereIn('id', $parameterIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Parameter $parameter): array => [
                'value' => $parameter->id,
                'label' => collect([$parameter->code, $parameter->name])->filter()->join(' - '),
            ])
            ->values();

        return Inertia::render('Samples/Index', [
            'record' => SampleResource::collection($records),
            'parameters' => $selectedParameters,
            'query' => request()->only(['filter', 'sort', 'includes', 'globalFilter', 'parameters', 'per_page']),
            'initialFilters' => request()->query('filter', ['collection.code' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => $sort !== '' ? ($sort[0] === '-' ? ltrim($sort, '-') : $sort) : '',
            'initialSortDirection' => $sort !== '' ? ($sort[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => $perPage,
            'slideOverEdit' => false,
            'createAction' => false,
            'trashedFilter' => true,
            'trashedOptions' => Sample::getTrashedOptions(),
            'fields' => Sample::getColumns(),
            'model' => Sample::MENU_NAME,
            'abilities' => method_exists(Sample::class, 'getAbilities') ? collect(Sample::ABILITIES)->map(function ($item) {
                return $item.'_'.Sample::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Sample::MENU_NAME;
            }),
            'entrypoint' => [
                'create_sample_url' => route('vap_samples.index'),
                'label' => trans('gestlab.general.labels.sample_entry'),
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()
            ->route('vap_samples.index')
            ->with('type', 'info')
            ->with('message', trans('gestlab.general.labels.sample_entry'));
    }

    public function store(): RedirectResponse
    {
        return redirect()
            ->route('vap_samples.index')
            ->with('type', 'info')
            ->with('message', trans('gestlab.general.labels.sample_entry'));
    }

    public function edit(Sample $sample): RedirectResponse
    {
        return redirect()
            ->route('vap_samples.index')
            ->with('type', 'info')
            ->with('message', trans('gestlab.general.labels.sample_entry'));
    }

    public function update(Sample $sample): RedirectResponse
    {
        return redirect()
            ->route('vap_samples.index')
            ->with('type', 'info')
            ->with('message', trans('gestlab.general.labels.sample_entry'));
    }

    public function destroy(): RedirectResponse
    {
        return redirect()
            ->route('samples.index')
            ->with('type', 'warning')
            ->with('message', trans('gestlab.general.labels.sample_entry'));
    }

    public function restore(): RedirectResponse
    {
        return redirect()
            ->route('samples.index')
            ->with('type', 'warning')
            ->with('message', trans('gestlab.general.labels.sample_entry'));
    }

    public function getCode(): JsonResponse
    {
        $search = request()->string('q')->trim()->toString();

        $data = Sample::query()
            ->select(['id', 'code'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('code', 'LIKE', "%{$search}%");
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (Sample $sample): array => [
                'id' => $sample->id,
                'value' => $sample->id,
                'code' => $sample->code,
                'label' => $sample->code,
            ]);

        return response()->json($data);
    }

    /**
     * @return array<int, int>
     */
    private function parameterIdsFromRequest(): array
    {
        $parameters = request()->query('parameters', []);
        $values = is_array($parameters) ? $parameters : explode(',', (string) $parameters);

        return collect($values)
            ->flatten()
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
