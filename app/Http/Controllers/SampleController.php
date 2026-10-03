<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSpecimensRequest;
use App\Http\Resources\SampleResource;
use App\Models\Parameter;
use App\Models\Sample;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\QueryBuilder;

class SampleController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratory,
        private readonly LaboratoryWorkflowOwnership $ownership,
    ) {}

    /** @return Builder<Sample> */
    private function records(): Builder
    {
        $labId = $this->laboratory->activeLabId();

        return $this->ownership->samplesForLaboratory($labId)
            ->whereHas('collection', fn (Builder $code): Builder => $code
                ->where(fn (Builder $accession): Builder => $accession
                    ->whereIn('lab_codes.collection_id', $this->ownership->collectionAccessionsForLaboratory($labId, 'direct')->select('collection_product.id'))
                    ->orWhereIn('lab_codes.collection_id', $this->ownership->collectionAccessionsForLaboratory($labId, 'programmed')->select('collection_product.id'))));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ListSpecimensRequest $request): Response
    {
        $input = $request->validated();
        $parameterIds = $input['parameters'] ?? [];
        $perPage = (int) ($input['per_page'] ?? 10);
        $sort = $input['sort'] ?? '';

        $records = QueryBuilder::for($this->records())
            ->with('collection.collection.sampleEntry')
            ->byParameters($parameterIds)
            ->bySearch($input['globalFilter'] ?? '')
            ->allowedFilters(Sample::getAllowedFilters())
            ->allowedSorts(Sample::getAllowedSorts())
            ->latest('samples.id')
            ->paginate($perPage)
            ->withQueryString();

        $selectedIds = $parameterIds !== [] ? $parameterIds : data_get($input, 'filter.parameters', []);
        $parameterOptions = Parameter::query()
            ->whereIn('id', $selectedIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->keyBy('id');
        $selectedParameters = collect($selectedIds)->map(fn (int $id): array => [
            'value' => $id,
            'label' => $parameterOptions->has($id)
                ? collect([$parameterOptions[$id]->code, $parameterOptions[$id]->name])->filter()->join(' - ')
                : 'Parâmetro #'.$id,
        ])->values();

        return Inertia::render('Samples/Index', [
            'record' => SampleResource::collection($records),
            'parameters' => $selectedParameters,
            'query' => $request->only(['filter', 'sort', 'includes', 'globalFilter', 'parameters', 'per_page']),
            'initialFilters' => $input['filter'] ?? ['collection.code' => '', 'created_at' => '', 'globalFilter' => ''],
            'initialSortField' => $sort !== '' ? ($sort[0] === '-' ? ltrim($sort, '-') : $sort) : '',
            'initialSortDirection' => $sort !== '' ? ($sort[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => $input['includes'] ?? [],
            'initialGlobalFilter' => $input['globalFilter'] ?? data_get($input, 'filter.globalFilter', ''),
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

    public function getCode(ListSpecimensRequest $request): JsonResponse
    {
        $search = $request->validated('q') ?? '';
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

        $data = $this->records()
            ->select(['samples.id', 'samples.code'])
            ->when($search !== '', fn (Builder $query): Builder => $query->whereLike('samples.code', $like))
            ->latest('samples.id')
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
}
