<?php

namespace App\Http\Controllers;

use App\Http\Requests\CounterAnalysisRequest;
use App\Http\Resources\CounterAnalysisResource;
use App\Jobs\RegisterCounterAnalysis;
use App\Models\CollectionProduct;
use App\Models\CounterAnalysis;
use App\Models\ReportStudioTemplate;
use App\Models\Result;
use App\Support\DuplicateSubmissionGuard;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CounterAnalysisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_counter_analysis'), 403, '');

        return Inertia::render('CounterAnalysis/Index', [
            'record' => CounterAnalysisResource::collection(
                CounterAnalysis::query()
                    ->with(
                        'department',
                        'sample.collection.collection.collection',
                        'sample.collection.collection.sampleEntry',
                        'profile',
                        'parameter',
                        'requested_result.code',
                        'requested_result.parameter',
                        'type',
                        'code',
                        'analysis',
                    )
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->whereRelation('code', 'code', 'like', "%{$search}%");
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => 'Código',
                    'value' => 'cl',
                ],
                [
                    'name' => trans('gestlab.general.labels.analysis.sample_entry'),
                    'value' => 'entry_lineage',
                ],
                [
                    'name' => trans('gestlab.general.labels.analysis.source_result'),
                    'value' => 'source_result_summary',
                ],
                [
                    'name' => 'Colheita',
                    'value' => 'col_date',
                ],
                [
                    'name' => 'Perfil',
                    'value' => 'profile',
                ],
                [
                    'name' => 'Departamento',
                    'value' => 'department',
                ],
                [
                    'name' => 'Estado',
                    'value' => 'status',
                ],
            ],
            'model' => CounterAnalysis::MENU_NAME,
            'abilities' => method_exists(CounterAnalysis::class, 'getAbilities') ? collect(CounterAnalysis::ABILITIES)->map(function ($item) {
                return $item.'_'.CounterAnalysis::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.CounterAnalysis::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
            'entrypoint' => [
                'label' => 'Contra-análises nascem de resultados existentes',
                'description' => 'Solicite uma contra-análise a partir do ecrã de gestão de resultados para preservar o resultado original, incerteza, amostra, lab code e decisão técnica.',
                'analysis_url' => route('analysis.index', ['category' => 'insert']),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_counter_analysis'), 403, '');

        return to_route('analysis.index', ['category' => 'insert'])->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Solicite a contra-análise a partir de um resultado existente.',
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        abort_if(! auth()->user()->can('add_counter_analysis'), 403, '');

        $validated = $request->validate([
            'result_id' => ['required', 'exists:results,id'],
        ]);

        $result = Result::query()->with('counter_analysis')->findOrFail($validated['result_id']);

        if ($result->counter_analysis()->exists() || $result->requested_counter_analysis) {
            return to_route('analysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'A contra-análise para este resultado já foi solicitada.',
                ],
            ]);
        }

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'counter-analysis-request', $validated, 60)) {
            return back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'O mesmo pedido de contra-análise já está a ser processado.',
                ],
            ]);
        }

        dispatch(new RegisterCounterAnalysis(
            $validated['result_id'],
            auth()->id()
        ));

        return to_route('analysis.index')->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = auth()->user();
        $canWorkResults = collect(['add_results', 'insert_results', 'verify_results', 'approve_results'])
            ->contains(fn (string $permission): bool => $user->can($permission));

        abort_if(! $user->can('edit_counter_analysis') || ! $canWorkResults, 403, '');

        // Find the record
        $record = CounterAnalysis::with(
            'department',
            'sample.results',
            'sample.results.parameter',
            'sample.collection.collection.collection',
            'sample.collection.collection.product',
            'sample.collection.collection.sampleEntry',
            'profile',
            'profile.parameters',
            'parameter',
            'requested_result.code',
            'requested_result.parameter',
            'type',
            'code',
            'analysis',
        )->findOrFail($id);

        if ($record->end_date !== null) {
            return to_route('counteranalysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'Esta contra-análise está concluída e não pode ser modificada.',
                ],
            ]);
        }

        $action = $this->resolveCounterAnalysisAction($record);

        if ($action === 'completed') {
            $record->update([
                'end_date' => now(),
                'status' => true,
            ]);

            return to_route('counteranalysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'A contra-análise está concluída e foi arquivada no fluxo validado.',
                ],
            ]);
        }

        return Inertia::render('Analysis/ResultsWorkflow', [
            'action' => $action,
            'record' => $this->formatWorkflowRecord($record),
            'results_summary' => $this->buildResultsSummary($record),
            'can_insert' => $user->can('insert_results'),
            'can_verify' => $user->can('verify_results'),
            'can_approve' => $user->can('approve_results'),
            'scope_audit' => $this->buildCounterAnalysisScopeAudit($record),
            'worksheet_brief' => null,
            'report_studio' => $this->resolveAnalysisReportStudio(),
            'result_data_url' => route('results.getCounterAnalysisDefaultResultsData'),
            'store_results_url' => route('results.storeCounterAnalysisResults'),
            'workflow_kind' => 'counter_analysis',
            'allow_worksheet_draft' => false,
        ]);
    }

    private function formatWorkflowRecord(CounterAnalysis $record): array
    {
        $sample = $record->sample;
        $labCode = $sample?->collection;
        /** @var CollectionProduct|null $collectionProduct */
        $collectionProduct = $labCode?->collection;
        $sampleEntry = $collectionProduct?->sampleEntry;
        $sampleEntryId = $sampleEntry?->id ?? data_get($collectionProduct?->extra_data, 'sample_entry_id');
        $collectionType = data_get($collectionProduct?->extra_data, 'collection_type') ?: $collectionProduct?->collection?->collectionable_type;
        $collectionType = in_array($collectionType, ['direct', 'programmed'], true) ? $collectionType : null;
        $sourceResult = $record->requested_result;
        $sourceValue = $sourceResult?->approved_value
            ?? $sourceResult?->verified_value
            ?? $sourceResult?->inserted_value;

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
            'parameter_id' => [
                'value' => $record->parameter_id,
                'label' => $record->parameter?->name,
            ],
            'result_id' => [
                'value' => $record->result_id,
                'label' => $sourceValue,
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
            'source_result' => $sourceResult ? [
                'id' => $sourceResult->id,
                'parameter' => $sourceResult?->parameter?->code ?? $sourceResult?->parameter?->name,
                'value' => $sourceValue,
                'uncertainty' => $sourceResult->uncertainty_value,
                'lab_code' => $sourceResult?->code?->code,
            ] : null,
            'entry_origin' => [
                'source' => $sampleEntryId ? 'sample_entry' : 'legacy_counter_analysis',
                'label' => $sampleEntryId
                    ? trans('gestlab.general.labels.analysis.sample_entry')
                    : trans('gestlab.general.labels.analysis.legacy_record'),
                'is_sample_entry_first' => (bool) $sampleEntryId,
                'collection_product_id' => $collectionProduct?->id,
                'collection_type' => $collectionType,
                'source_sample_id' => data_get($record->extra_data, 'source_sample_id'),
            ],
            'links' => [
                'counter_analysis_index' => route('counteranalysis.index'),
                'original_analysis_path' => $record->analysis_id ? route('analysis.edit', $record->analysis_id) : null,
                'sample_entry_show_path' => $sampleEntryId ? route('vap_samples.show', $sampleEntryId) : null,
                'collection_show_path' => $collectionProduct && $collectionType
                    ? route("{$collectionType}collections.show", $collectionProduct)
                    : null,
            ],
        ];
    }

    private function buildResultsSummary(CounterAnalysis $record): array
    {
        $results = collect($record->sample?->results ?? []);
        $totalResults = $results->count();
        $expectedResults = $record->profile?->parameters?->count() ?? 0;

        return [
            'total' => $totalResults,
            'pending_insertion' => $totalResults > 0 ? $results->whereNull('inserted_date')->count() : $expectedResults,
            'pending_verification' => $results->whereNotNull('inserted_date')->whereNull('verified_date')->count(),
            'pending_approval' => $results->whereNotNull('verified_date')->whereNull('approved_date')->count(),
            'approved' => $results->whereNotNull('approved_date')->count(),
        ];
    }

    private function buildCounterAnalysisScopeAudit(CounterAnalysis $record): array
    {
        $expectedParameters = collect($record->profile?->parameters ?? [])
            ->map(fn ($parameter) => [
                'id' => $parameter->id,
                'code' => $parameter->code,
                'name' => $parameter->name,
            ])
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
        $resultIds = $existingResults->pluck('id')->filter()->map(fn ($id) => (int) $id);

        return [
            'collection_product_id' => $record->sample?->collection?->collection?->id,
            'conditioning_status' => null,
            'packaging_condition' => null,
            'temperature_condition' => null,
            'integrity_observations' => 'Contra-análise solicitada a partir do resultado original.',
            'chain_of_custody_notes' => data_get($record->extra_data, 'request_reason'),
            'resolved_profiles' => $record->profile ? [[
                'id' => $record->profile->id,
                'name' => $record->profile->name,
            ]] : [],
            'expected_parameters' => $expectedParameters->all(),
            'reception_parameters' => $expectedParameters->all(),
            'existing_results' => $existingResults->all(),
            'expected_count' => $expectedParameters->count(),
            'reception_count' => $expectedParameters->count(),
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
                'reception_only' => [],
                'profile_only' => [],
            ],
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
        ];
    }

    private function resolveCounterAnalysisAction(CounterAnalysis $record): string
    {
        $results = $record->sample?->results;

        if (! $results || $results->isEmpty()) {
            return 'analyze';
        }

        if ($results->contains(fn (Result $result) => ! $result->inserted_date)) {
            return 'analyze';
        }

        if ($results->contains(fn (Result $result) => $result->inserted_date && ! $result->verified_date)) {
            return 'verify';
        }

        if ($results->contains(fn (Result $result) => $result->verified_date && ! $result->approved_date)) {
            return 'approve';
        }

        if ($results->every(fn (Result $result) => $result->approved_date !== null)) {
            return 'completed';
        }

        return 'approve';
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CounterAnalysisRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_counter_analysis'), 403, '');

        // Find the record
        $record = CounterAnalysis::findOrFail($id);

        $record->update($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        abort_if(! auth()->user()->can('delete_counter_analysis'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (CounterAnalysis::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

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
    public function restore()
    {
        abort_if(! auth()->user()->can('restore_counter_analysis'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (CounterAnalysis::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getAnalysis()
    {
        $search = request()->string('q')->trim()->toString();

        $data = CounterAnalysis::query()
            ->with(['code:id,code', 'sample:id,code', 'profile:id,name', 'requested_result.parameter:id,code,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query
                    ->whereRelation('code', 'code', 'like', "%{$search}%")
                    ->orWhereRelation('sample', 'code', 'like', "%{$search}%")
                    ->orWhereRelation('profile', 'name', 'like', "%{$search}%")
                    ->orWhereRelation('requested_result.parameter', 'code', 'like', "%{$search}%")
                    ->orWhereRelation('requested_result.parameter', 'name', 'like', "%{$search}%");
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (CounterAnalysis $counterAnalysis): array => [
                'id' => $counterAnalysis->id,
                'code' => $counterAnalysis->code?->code,
                'label' => collect([
                    $counterAnalysis->code?->code,
                    $counterAnalysis->sample?->code,
                    $counterAnalysis->profile?->name,
                ])->filter()->join(' / '),
                'sample_code' => $counterAnalysis->sample?->code,
                'profile_name' => $counterAnalysis->profile?->name,
                'source_parameter' => $counterAnalysis->requested_result?->parameter?->code
                    ?? $counterAnalysis->requested_result?->parameter?->name,
            ]);

        return response()->json($data);
    }
}
