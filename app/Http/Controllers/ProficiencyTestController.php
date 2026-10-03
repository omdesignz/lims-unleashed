<?php

namespace App\Http\Controllers;

use App\Exports\ProficiencyTestResultsTemplateExport;
use App\Http\Requests\ProficiencyTestRequest;
use App\Http\Resources\ProficiencyTestResource;
use App\Imports\ProficiencyTestResultsImport;
use App\Models\ProficiencyTest;
use App\Services\SampleLaboratoryAccess;
use App\Support\ProficiencyTestNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProficiencyTestController extends Controller
{
    public function __construct(
        private readonly ProficiencyTestNotifier $notifier,
        private readonly SampleLaboratoryAccess $laboratoryAccess,
    ) {}

    private function can(string $permission): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can($permission);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        abort_if(! $this->can('view_proficiency_tests'), 403, '');

        return Inertia::render('ProficiencyTest/Index', [
            'record' => ProficiencyTestResource::collection(
                $this->labTests()
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where(function ($searchQuery) use ($search) {
                            $searchQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('provider_name', 'like', "%{$search}%")
                                ->orWhere('round_reference', 'like', "%{$search}%");
                        });
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->onlyTrashed();
                        }
                    })
                    ->when(request()->input('status'), function ($query, $status) {
                        $query->where('status', $status);
                    })
                    ->when(request()->input('scheme_type'), function ($query, $schemeType) {
                        $query->where('scheme_type', $schemeType);
                    })
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => true,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.proficiency_tests.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.proficiency_tests.date'),
                    'value' => 'date',
                ],
                [
                    'name' => 'Estado',
                    'value' => 'status',
                ],
            ],
            'model' => ProficiencyTest::MENU_NAME,
            'abilities' => method_exists(ProficiencyTest::class, 'getAbilities') ? collect(ProficiencyTest::ABILITIES)->map(function ($item) {
                return $item.'_'.ProficiencyTest::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.ProficiencyTest::MENU_NAME;
            }),
            'query' => request()->only(['search', 'filter', 'status', 'scheme_type']),
            'statusOptions' => ['planned', 'in_progress', 'completed', 'reviewed', 'closed'],
            'schemeOptions' => ['proficiency', 'interlaboratory'],
            'roleOptions' => ['participant', 'organizer'],
            'charts' => $this->charts(),
            'permissions' => [
                'add' => $this->can('add_proficiency_tests'),
                'edit' => $this->can('edit_proficiency_tests'),
                'delete' => $this->can('delete_proficiency_tests'),
                'restore' => $this->can('restore_proficiency_tests'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        abort_if(! $this->can('add_proficiency_tests'), 403, '');
        $this->laboratoryAccess->activeLabId();

        return redirect()->route('proficiency_tests.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProficiencyTestRequest $request): RedirectResponse
    {
        abort_if(! $this->can('add_proficiency_tests'), 403, '');

        $test = new ProficiencyTest([
            ...$request->validated(),
            'lab_id' => $this->laboratoryAccess->activeLabId(),
        ]);
        $test->performance_summary = $test->calculatePerformanceSummary();
        $test->save();

        $this->notifier->notifyCreated($test);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProficiencyTest $test): Response
    {
        abort_if(! $this->can('view_proficiency_tests'), 403, '');
        $this->assertOwnsTest($test);

        return Inertia::render('ProficiencyTest/Show', [
            'test' => ProficiencyTestResource::make($test)->resolve(),
            'charts' => $this->testCharts($test),
            'statusOptions' => ['planned', 'in_progress', 'completed', 'reviewed', 'closed'],
            'schemeOptions' => ['proficiency', 'interlaboratory'],
            'roleOptions' => ['participant', 'organizer'],
            'canEdit' => $this->can('edit_proficiency_tests'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProficiencyTest $test): RedirectResponse
    {
        abort_if(! $this->can('edit_proficiency_tests'), 403, '');
        $this->assertOwnsTest($test);

        return redirect()->route('proficiency_tests.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProficiencyTestRequest $request, ProficiencyTest $test): RedirectResponse
    {
        abort_if(! $this->can('edit_proficiency_tests'), 403, '');
        $this->assertOwnsTest($test);

        $before = $test->only(['status', 'outcome']);
        $validated = $request->validated();

        $test->fill($validated);
        $test->performance_summary = $test->calculatePerformanceSummary();
        $test->save();

        $this->notifier->notifyUpdated($test->refresh(), $before);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function updateResults(Request $request, ProficiencyTest $test): RedirectResponse
    {
        abort_if(! $this->can('edit_proficiency_tests'), 403, '');
        $this->assertOwnsTest($test);

        $validated = $request->validate([
            'participants' => ['nullable', 'array'],
            'participants.*.code' => ['nullable', 'string', 'max:100'],
            'participants.*.name' => ['nullable', 'string', 'max:255'],
            'participants.*.contact' => ['nullable', 'string', 'max:255'],
            'participants.*.status' => ['nullable', 'in:pending,enrolled,submitted,reviewed,requires_action'],
            'parameters' => ['nullable', 'array'],
            'parameters.*.code' => ['nullable', 'string', 'max:100'],
            'parameters.*.name' => ['nullable', 'string', 'max:255'],
            'parameters.*.unit' => ['nullable', 'string', 'max:100'],
            'parameters.*.assigned_value' => ['nullable', 'numeric'],
            'parameters.*.standard_deviation' => ['nullable', 'numeric'],
            'participant_results' => ['nullable', 'array'],
            'results' => ['nullable', 'array'],
            'z_score' => ['nullable', 'numeric', 'between:-10,10'],
            'outcome' => ['nullable', 'in:satisfactory,questionable,unsatisfactory,pending'],
            'corrective_actions' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $test->fill([
            'participants' => array_values($validated['participants'] ?? []),
            'parameters' => array_values($validated['parameters'] ?? []),
            'participant_results' => array_values($validated['participant_results'] ?? []),
            'results' => array_values($validated['results'] ?? []),
            'z_score' => $validated['z_score'] ?? $test->z_score,
            'outcome' => $validated['outcome'] ?? $test->outcome,
            'corrective_actions' => $validated['corrective_actions'] ?? $test->corrective_actions,
            'notes' => $validated['notes'] ?? $test->notes,
        ]);

        $test->performance_summary = $test->calculatePerformanceSummary();
        $test->save();

        $this->notifier->notifyResultsUpdated($test->refresh());

        return redirect()->route('proficiency_tests.show', $test)->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Resultados do ensaio de proficiência actualizados.',
            ],
        ]);
    }

    public function downloadResultsTemplate(ProficiencyTest $test): BinaryFileResponse
    {
        abort_if(! $this->can('view_proficiency_tests'), 403, '');
        $this->assertOwnsTest($test);

        $fileName = str($test->round_reference ?: $test->name)
            ->slug()
            ->append('-proficiency-results.xlsx')
            ->toString();

        return Excel::download(new ProficiencyTestResultsTemplateExport($test), $fileName);
    }

    public function importResults(Request $request, ProficiencyTest $test): RedirectResponse
    {
        abort_if(! $this->can('edit_proficiency_tests'), 403, '');
        $this->assertOwnsTest($test);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        Excel::import(new ProficiencyTestResultsImport($test), $validated['file']);

        $this->notifier->notifyResultsUpdated($test->refresh());

        return redirect()->route('proficiency_tests.show', $test)->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Resultados importados para a ronda de proficiência.',
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort_if(! $this->can('delete_proficiency_tests'), 403, '');
        $this->mutateRecords($request, restore: false);

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
    public function restore(Request $request): RedirectResponse
    {
        abort_if(! $this->can('restore_proficiency_tests'), 403, '');
        $this->mutateRecords($request, restore: true);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    private function charts(): array
    {
        $status = $this->labTests()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $outcome = $this->labTests()
            ->selectRaw('outcome, count(*) as aggregate')
            ->groupBy('outcome')
            ->pluck('aggregate', 'outcome');

        $roles = $this->labTests()
            ->selectRaw('role, count(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        return [
            'status' => [
                'labels' => ['Planeado', 'Em curso', 'Concluído', 'Revisto', 'Fechado'],
                'series' => collect(['planned', 'in_progress', 'completed', 'reviewed', 'closed'])->map(fn ($key) => (int) ($status[$key] ?? 0))->values()->all(),
            ],
            'outcome' => [
                'labels' => ['Pendente', 'Satisfatório', 'Questionável', 'Insatisfatório'],
                'series' => collect(['pending', 'satisfactory', 'questionable', 'unsatisfactory'])->map(fn ($key) => (int) ($outcome[$key] ?? 0))->values()->all(),
            ],
            'role' => [
                'labels' => ['Participante', 'Organizador'],
                'series' => collect(['participant', 'organizer'])->map(fn ($key) => (int) ($roles[$key] ?? 0))->values()->all(),
            ],
        ];
    }

    /** @return Builder<ProficiencyTest> */
    private function labTests(): Builder
    {
        return ProficiencyTest::query()->where('lab_id', $this->laboratoryAccess->activeLabId());
    }

    private function assertOwnsTest(ProficiencyTest $test): void
    {
        abort_unless((int) $test->lab_id === $this->laboratoryAccess->activeLabId(), 404);
    }

    private function mutateRecords(Request $request, bool $restore): void
    {
        $validated = $request->validate([
            'recordIds' => ['required', 'array', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'distinct'],
        ]);

        DB::transaction(function () use ($validated, $restore): void {
            $records = $this->labTests()
                ->withTrashed()
                ->whereKey($validated['recordIds'])
                ->lockForUpdate()
                ->get();

            abort_unless($records->count() === count($validated['recordIds']), 404);

            foreach ($records as $record) {
                if ($restore) {
                    $record->restore();
                } else {
                    $record->delete();
                }
            }
        });
    }

    private function testCharts(ProficiencyTest $test): array
    {
        $participantResults = collect($test->participant_results ?? []);
        $flatResults = $participantResults
            ->flatMap(fn (array $participant) => collect($participant['results'] ?? [])->map(fn (array $result) => [
                ...$result,
                'participant' => $participant['code'] ?? $participant['name'] ?? 'Lab',
            ]))
            ->values();

        $zScoreResults = $flatResults
            ->filter(fn (array $result) => is_numeric($result['z_score'] ?? null))
            ->values();

        return [
            'z_scores' => [
                'categories' => $zScoreResults->map(fn (array $result) => ($result['participant'] ?? 'Lab').' - '.($result['parameter_code'] ?? $result['parameter'] ?? 'Parâmetro'))->all(),
                'series' => [
                    [
                        'name' => 'z-score',
                        'data' => $zScoreResults->map(fn (array $result) => round((float) $result['z_score'], 2))->all(),
                    ],
                ],
            ],
            'performance' => [
                'labels' => ['Satisfatório', 'Questionável', 'Insatisfatório'],
                'series' => [
                    (int) ($test->performance_summary['satisfactory'] ?? 0),
                    (int) ($test->performance_summary['questionable'] ?? 0),
                    (int) ($test->performance_summary['unsatisfactory'] ?? 0),
                ],
            ],
            'participant_status' => [
                'labels' => ['Pendente', 'Inscrito', 'Submetido', 'Revisto', 'Requer acção'],
                'series' => collect(['pending', 'enrolled', 'submitted', 'reviewed', 'requires_action'])
                    ->map(fn (string $key) => (int) ($test->participantStatusCounts()[$key] ?? 0))
                    ->values()
                    ->all(),
            ],
        ];
    }
}
