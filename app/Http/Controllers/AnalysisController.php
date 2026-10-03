<?php

namespace App\Http\Controllers;

use App\Actions\SetAnalysisArchived;
use App\Http\Resources\AnalysisResource;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\Department;
use App\Models\ReportStudioTemplate;
use App\Models\User;
use App\Services\IssuedAnalyticalScope;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\LaboratoryWorksheetAccess;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\QueryBuilder;

class AnalysisController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratory,
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly LaboratoryWorksheetAccess $worksheets,
        private readonly IssuedAnalyticalScope $issuedScope,
    ) {}

    /** @return Builder<Analysis> */
    private function records(): Builder
    {
        return $this->ownership->analysesForLaboratory($this->laboratory->activeLabId());
    }

    private function readOperator(string $permission): User
    {
        $operator = $this->ownership->eligibleUsers($this->laboratory->activeLabId())->find(auth()->id());
        abort_unless($operator?->can($permission), 403);

        return $operator;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->readOperator('view_analysis');

        $category = request()->query('category', 'insert');
        $category = in_array($category, ['insert', 'verify', 'approve', 'archived'], true) ? $category : 'insert';
        $scope = ucfirst($category);

        $baseQuery = $this->records()
            ->{$scope}()
            ->with('department', 'sample.collection.collection.collection', 'sample.collection.collection.sampleEntry', 'profile', 'type', 'code', 'product')
            ->when(! is_null(request()->department), function ($query) {
                $query->where('department_id', request()->input('department.value'));
            });

        // Let QueryBuilder apply the category filter from request
        $records = QueryBuilder::for($baseQuery)
            ->allowedFilters(Analysis::getAllowedFilters())
            ->allowedSorts(Analysis::getAllowedSorts())
            ->orderBy('analysis.id')
            ->paginate(request()->query('per_page', 10));

        return Inertia::render('Analysis/Index', [
            'record' => AnalysisResource::collection($records),
            'departments' => Department::all()->map(fn ($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'initialFilters' => request()->query('filter', [
                'department.name' => '',
                'product.name' => '',
                'code.code' => '',
                'created_at' => '',
                'globalFilter' => '',
                // 'category' => 'insert',  // Default category
                'col_date' => ['start' => null, 'end' => null],
            ]),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 10),
            'slideOverEdit' => false,
            'entrypoint' => [
                'label' => 'Novas análises começam pela entrada de amostra',
                'description' => 'Registe a amostra na recepção para gerar os códigos e as análises a partir do âmbito autorizado.',
                'create_sample_url' => route('vap_samples.index'),
            ],
            'trashedFilter' => true,
            'trashedOptions' => Analysis::getTrashedOptions(),
            'fields' => Analysis::getColumns(),
            'model' => Analysis::MENU_NAME,
            'abilities' => method_exists(Analysis::class, 'getAbilities')
                ? collect(Analysis::ABILITIES)->map(fn ($item) => $item.'_'.Analysis::MENU_NAME)
                : collect(config('gestlab.default_abilities'))->map(fn ($item) => $item.'_'.Analysis::MENU_NAME),
            'query' => array_merge(
                request()->only(['search', 'trashed', 'date', 'orderBy', 'department']),
                ['category' => $category],
            ),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        $this->readOperator('add_analysis');

        return to_route('vap_samples.index')->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'As novas análises devem começar pela entrada de amostra para manter a rastreabilidade completa.',
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): Response|RedirectResponse
    {
        $user = $this->readOperator('edit_analysis');
        $canWorkResults = collect(['add_results', 'insert_results', 'verify_results', 'approve_results'])
            ->contains(fn (string $permission): bool => $user->can($permission));

        abort_unless($canWorkResults, 403);

        // Eager load all necessary relationships
        $record = $this->records()->with([
            'sample.results',
            'sample.results.parameter', // If you need parameter details
            'sample.collection.collection.collection',
            'sample.collection.collection.product',
            'sample.collection.collection.sampleEntry',
            'department',
            'profile',
            'type',
            'code',
            'product',
        ])->findOrFail($id);

        /** @var CollectionProduct|null $collectionProduct */
        $collectionProduct = $record->sample?->collection?->collection;
        abort_unless($record->profile && $collectionProduct, 404);
        $record->profile->setRelation('parameters', $this->issuedScope->parametersFor($record, $collectionProduct));
        $record->sample->setRelation('results', $record->sample->results
            ->filter(fn ($result): bool => (int) $result->profile_id === $record->profile_id
                && (int) $result->code_id === $record->cl_id
                && $result->resultable_type === $record->getMorphClass()
                && (int) $result->resultable_id === $record->id)
            ->values());

        // Check if analysis is archived/completed
        if ($record->end_date !== null) {
            return redirect()->route('analysis.index', ['category' => 'archived'])
                ->with('toast', [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'Esta análise está concluída (resultados validados) e não pode ser modificada.',
                ]);
        }

        $sample = $record->sample;
        $results = $sample->results;

        // Handle case where no results exist yet
        if ($results->isEmpty()) {
            return Inertia::render('Analysis/ResultsWorkflow', [
                'action' => 'analyze',
                'record' => $this->formatRecord($record),
                'expected_parameters_count' => $record->profile?->parameters?->count() ?? 0,
                'actual_results_count' => 0,
                'results_summary' => [
                    'total' => 0,
                    'pending_insertion' => $record->profile?->parameters?->count() ?? 0,
                    'pending_verification' => 0,
                    'pending_approval' => 0,
                    'approved' => 0,
                ],
                'can_insert' => $user->can('insert_results'),
                'can_verify' => $user->can('verify_results'),
                'can_approve' => $user->can('approve_results'),
                'scope_audit' => $this->buildScopeAudit($record),
                'worksheet_brief' => $this->buildWorksheetBrief($record),
                'allow_worksheet_draft' => $user->can('add_worksheets') && $user->can('view_worksheets'),
                'report_studio' => $this->resolveAnalysisReportStudio(),
            ]);
        }

        // Calculate status counts
        $totalResults = $results->count();
        $approvedCount = $results->whereNotNull('approved_date')->count();
        $pendingInsertion = $results->whereNull('inserted_date')->count();
        $pendingVerification = $results->whereNotNull('inserted_date')->whereNull('verified_date')->count();
        $pendingApproval = $results->whereNotNull('verified_date')->whereNull('approved_date')->count();

        // Determine workflow stage based on ALL results status
        $action = match (true) {
            $pendingInsertion > 0 => 'analyze',      // Something still needs insertion
            $pendingVerification > 0 => 'verify',    // All inserted, something needs verification
            $pendingApproval > 0 => 'approve',       // All verified, something needs approval
            $approvedCount === $totalResults => 'completed', // Everything done
            default => 'unknown'                     // Shouldn't happen, but safe fallback
        };

        return Inertia::render('Analysis/ResultsWorkflow', [
            'action' => $action,
            'record' => $this->formatRecord($record),
            'results_summary' => [
                'total' => $totalResults,
                'pending_insertion' => $pendingInsertion,
                'pending_verification' => $pendingVerification,
                'pending_approval' => $pendingApproval,
                'approved' => $approvedCount,
            ],
            'can_insert' => $user->can('insert_results'),
            'can_verify' => $user->can('verify_results'),
            'can_approve' => $user->can('approve_results'),
            'scope_audit' => $this->buildScopeAudit($record),
            'worksheet_brief' => $this->buildWorksheetBrief($record),
            'allow_worksheet_draft' => $user->can('add_worksheets') && $user->can('view_worksheets'),
            'report_studio' => $this->resolveAnalysisReportStudio(),
        ]);
    }

    // Extract record formatting to reduce duplication
    private function formatRecord(Analysis $record): array
    {
        $sample = $record->sample;
        $labCode = $sample?->collection;
        /** @var CollectionProduct|null $collectionProduct */
        $collectionProduct = $labCode?->collection;
        $sampleEntry = $collectionProduct?->sampleEntry;
        $sampleEntryId = $sampleEntry?->id ?? data_get($collectionProduct?->extra_data, 'sample_entry_id');
        $collectionType = data_get($collectionProduct?->extra_data, 'collection_type') ?: $collectionProduct?->collection?->collectionable_type;
        $collectionType = in_array($collectionType, ['direct', 'programmed'], true) ? $collectionType : null;

        return [
            'id' => $record->id,
            'code' => $record->code?->code,
            'cl_id' => [
                'value' => $record->cl_id,
                'label' => $record->code?->code,
            ],
            'profile_id' => [
                'value' => $record->profile_id,
                'label' => $record->profile?->name,
            ],
            'type_id' => [
                'value' => $record->type_id,
                'label' => $record->type?->name,
            ],
            'sample_id' => [
                'value' => $record->sample_id,
                'label' => $record->sample?->code,
            ],
            'department_id' => [
                'value' => $record->department_id,
                'label' => $record->department?->name,
            ],
            'product_id' => [
                'value' => $record->product_id,
                'label' => $record->product?->name,
            ],
            'collection_product_id' => $collectionProduct?->id ?? $record->code?->collection_id,
            'sample' => $sample ? [
                'id' => $sample->id,
                'code' => $sample->code,
            ] : null,
            'sample_entry' => $sampleEntryId ? [
                'id' => $sampleEntryId,
                'code' => $sampleEntry?->code,
                'name' => $sampleEntry?->name,
                'status' => $sampleEntry?->status,
                'sample_type' => $sampleEntry?->sample_type,
                'show_url' => route('vap_samples.show', $sampleEntryId),
            ] : null,
            'entry_origin' => [
                'source' => $sampleEntryId ? 'sample_entry' : 'legacy_analysis',
                'label' => $sampleEntryId
                    ? trans('gestlab.general.labels.analysis.sample_entry')
                    : trans('gestlab.general.labels.analysis.legacy_record'),
                'is_sample_entry_first' => (bool) $sampleEntryId,
                'collection_product_id' => $collectionProduct?->id,
                'collection_type' => $collectionType,
            ],
            'links' => [
                'analysis_index' => route('analysis.index'),
                'sample_entry_show_path' => $sampleEntryId ? route('vap_samples.show', $sampleEntryId) : null,
                'collection_show_path' => $collectionProduct && $collectionType
                    ? route("{$collectionType}collections.show", $collectionProduct)
                    : null,
                'counter_analysis_index' => route('counteranalysis.index'),
            ],
        ];
    }

    private function buildScopeAudit(Analysis $record): array
    {
        $expectedParameters = collect($record->profile?->parameters ?? [])
            ->map(fn ($parameter) => [
                'id' => $parameter->id,
                'code' => $parameter->code,
                'name' => $parameter->name,
            ])
            ->unique('id')
            ->values();

        /** @var CollectionProduct|null $collectionProduct */
        $collectionProduct = $record->sample?->collection?->collection;

        $receptionParameters = collect(data_get($collectionProduct?->sampleEntry?->client_submitted_info, 'required_parameters', []))
            ->filter(fn (array $parameter): bool => in_array((int) $record->profile_id,
                array_map('intval', $parameter['profile_ids'] ?? []), true))
            ->map(fn ($parameter) => [
                'id' => data_get($parameter, 'id'),
                'code' => data_get($parameter, 'code'),
                'name' => data_get($parameter, 'name'),
                'profiles' => data_get($parameter, 'profiles', []),
            ])
            ->filter(fn (array $parameter) => ! empty($parameter['id']))
            ->unique('id')
            ->values();

        $existingResults = collect($record->sample?->results ?? [])
            ->map(fn ($result) => [
                'id' => $result->parameter_id,
                'code' => $result->parameter?->code,
                'name' => $result->parameter_label,
            ])
            ->filter(fn (array $parameter) => ! empty($parameter['id']))
            ->unique('id')
            ->values();

        $expectedIds = $expectedParameters->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $receptionIds = $receptionParameters->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $resultIds = $existingResults->pluck('id')->filter()->map(fn ($id) => (int) $id);

        return [
            'collection_product_id' => $collectionProduct?->id,
            'conditioning_status' => data_get($collectionProduct?->extra_data, 'submitted_payload.conditioning_status'),
            'packaging_condition' => data_get($collectionProduct?->extra_data, 'submitted_payload.packaging_condition'),
            'temperature_condition' => data_get($collectionProduct?->extra_data, 'submitted_payload.temperature_condition'),
            'integrity_observations' => data_get($collectionProduct?->extra_data, 'submitted_payload.integrity_observations'),
            'chain_of_custody_notes' => data_get($collectionProduct?->extra_data, 'submitted_payload.chain_of_custody_notes'),
            'resolved_profiles' => data_get($collectionProduct?->sampleEntry?->client_submitted_info, 'resolved_profiles', []),
            'expected_parameters' => $expectedParameters->all(),
            'reception_parameters' => $receptionParameters->all(),
            'existing_results' => $existingResults->all(),
            'expected_count' => $expectedParameters->count(),
            'reception_count' => $receptionParameters->count(),
            'results_count' => $existingResults->count(),
            'missing_from_results' => $expectedParameters
                ->whereIn('id', $expectedIds->diff($resultIds))
                ->values()
                ->all(),
            'outside_profile_scope' => $existingResults
                ->whereIn('id', $resultIds->diff($expectedIds))
                ->values()
                ->all(),
            'scope_drift' => [
                'reception_only' => $receptionParameters
                    ->whereIn('id', $receptionIds->diff($expectedIds))
                    ->values()
                    ->all(),
                'profile_only' => $expectedParameters
                    ->whereIn('id', $expectedIds->diff($receptionIds))
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function buildWorksheetBrief(Analysis $record): ?array
    {
        if (! $this->readOperator('edit_analysis')->can('view_worksheets')) {
            return null;
        }

        $collectionProductId = $record->code?->collection_id;

        if (! $collectionProductId) {
            return null;
        }

        $worksheet = $this->worksheets->records($this->laboratory->activeLabId())
            ->where('analysis_id', $record->id)
            ->where('worksheets->collection_product_id', $collectionProductId)
            ->latest('updated_at')
            ->latest('id')
            ->first();

        if (! $worksheet) {
            return [
                'exists' => false,
                'analysis_id' => $record->id,
                'collection_product_id' => $collectionProductId,
            ];
        }

        return [
            'exists' => true,
            'id' => $worksheet->id,
            'name' => $worksheet->name,
            'updated_at' => optional($worksheet->updated_at)?->toIso8601String(),
        ];
    }

    private function resolveAnalysisReportStudio(): ?array
    {
        $template = ReportStudioTemplate::resolveDefaultFor('analysis');

        if (! $template) {
            return null;
        }

        return [
            'id' => $template->id,
            'name' => $template->name,
            'renderer' => $template->renderer,
            'theme_preset' => $template->theme_preset,
            'canva_design_url' => $template->canva_design_url,
            'description' => $template->description,
            'layout_schema' => $template->layout_schema ?? [],
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, SetAnalysisArchived $archive): RedirectResponse
    {
        $this->archive($request, $archive, true);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore(Request $request, SetAnalysisArchived $archive): RedirectResponse
    {
        $this->archive($request, $archive, false);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    private function archive(Request $request, SetAnalysisArchived $archive, bool $archived): void
    {
        $this->readOperator($archived ? 'delete_analysis' : 'restore_analysis');
        $validated = $request->validate([
            'recordIds' => ['required', 'array', 'min:1', 'max:500'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX, 'distinct'],
        ], [], ['recordIds' => 'análises', 'recordIds.*' => 'identificador da análise']);

        $archive->execute($this->laboratory->activeLabId(), (int) $request->user()->id,
            array_map(intval(...), $validated['recordIds']), $archived);
    }

    public function getAnalysis(Request $request): JsonResponse
    {
        $this->readOperator('view_analysis');
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $records = $this->records()->matchingSearch($validated['q'] ?? '')->orderBy('analysis.id')->get();

        return response()->json($records);
    }
}
