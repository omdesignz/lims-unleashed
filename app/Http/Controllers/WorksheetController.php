<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Illuminate\Http\Request;
use App\Models\Worksheet;
use Inertia\Inertia;

class WorksheetController extends Controller
{

    public function index()
    {
        $worksheets = Worksheet::query()
            ->latest('updated_at')
            ->get(['id', 'name', 'worksheets', 'updated_at']);

        return inertia('Worksheets/Index', [
            'worksheets' => $worksheets,
        ]);
    }
    // Store a new worksheet file
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'worksheets' => 'required|array|min:1',
        ]);

        $worksheet = Worksheet::create([
            'name' => $validated['name'],
            'worksheets' => $validated['worksheets'],
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    // Retrieve a worksheet file
    public function show($id)
    {
        $worksheet = Worksheet::findOrFail($id);

        return Inertia::render('Worksheets/Edit', ['worksheet' => $worksheet]);
    }

    // Update an existing worksheet file
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'worksheets' => 'sometimes|array|min:1',
        ]);

        $worksheet = Worksheet::findOrFail($id);
        $worksheet->update($validated);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    public function destroy(Worksheet $worksheet)
    {
        $worksheet->delete();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    public function restore() {

    }

    public function storeAnalysisDraft(Analysis $analysis)
    {
        $analysis->load([
            'profile.parameters.pivot.unit',
            'type',
            'department',
            'product',
            'sample.results.parameter',
            'sample.collection.collection',
        ]);

        $existingWorksheet = Worksheet::query()
            ->where('worksheets->analysis_id', $analysis->id)
            ->latest('updated_at')
            ->first();

        if ($existingWorksheet) {
            return redirect()->route('worksheets.show', $existingWorksheet);
        }

        $expectedParameters = $analysis->profile?->parameters
            ? $analysis->profile->parameters
                ->unique('id')
                ->sortBy('name')
                ->values()
            : collect();

        $existingResults = $analysis->sample?->results
            ? $analysis->sample->results
                ->sortByDesc(fn ($result) => $result->approved_date ?? $result->verified_date ?? $result->inserted_date)
                ->unique('parameter_id')
                ->keyBy('parameter_id')
            : collect();

        $scopeControl = $this->buildWorksheetScopeControl($analysis, $expectedParameters, $existingResults);

        $parameterRows = $expectedParameters
            ->map(function ($parameter, int $index) use ($existingResults) {
                $result = $existingResults->get($parameter->id);
                $currentValue = $result?->approved_value ?? $result?->verified_value ?? $result?->inserted_value;
                $workflowStatus = $result?->approved_date
                    ? 'Aprovado'
                    : ($result?->verified_date
                        ? 'Verificado'
                        : ($result?->inserted_date ? 'Inserido' : 'Pendente'));

                return [
                    $index + 1,
                    $parameter->code,
                    $parameter->name,
                    $parameter->pivot?->unit?->code,
                    $parameter->requires_calculation ? 'Calculado' : 'Manual',
                    $parameter->pivot?->min_ref_value,
                    $parameter->pivot?->max_ref_value,
                    $currentValue ?? '',
                    $workflowStatus,
                    $result?->verification_notes ?? $result?->approval_notes ?? $result?->insertion_notes ?? '',
                ];
            })
            ->all();

        $worksheet = Worksheet::query()->create([
            'name' => 'Worksheet - ' . ($analysis->code?->code ?? ('Análise #' . $analysis->id)),
            'worksheets' => [
                'analysis_id' => $analysis->id,
                'collection_product_id' => $analysis->code?->collection_id,
                'sample_id' => $analysis->sample_id,
                'profile_id' => $analysis->profile_id,
                'generated_from' => 'analysis_scope',
                'scope_control' => $scopeControl,
                'sheets' => [
                    [
                        'id' => 'scope-control',
                        'name' => 'Scope Control',
                        'data' => array_merge([
                            ['Sample Code', $analysis->code?->code],
                            ['Department', $analysis->department?->name],
                            ['Profile', $analysis->profile?->name],
                            ['Product', $analysis->product?->name],
                            ['Reception Conditioning', data_get($analysis->sample?->collection?->collection?->extra_data, 'submitted_payload.conditioning_status', 'not_evaluated')],
                            ['Scope Status', $scopeControl['status_label']],
                            ['Expected Parameters', $scopeControl['expected_count']],
                            ['Completed Results', $scopeControl['completed_count']],
                            ['Missing Parameters', $scopeControl['missing_count']],
                            [''],
                            ['#', 'Code', 'Parameter', 'Unit', 'Type', 'Min Ref', 'Max Ref', 'Current Value', 'Workflow Status', 'Notes'],
                        ], $parameterRows),
                    ],
                ],
            ],
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('worksheets.show', $worksheet)->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Worksheet analítica criada com base no escopo controlado da análise.',
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Parameter>  $expectedParameters
     * @param  \Illuminate\Support\Collection<int, \App\Models\Result>  $existingResults
     * @return array<string, mixed>
     */
    private function buildWorksheetScopeControl(Analysis $analysis, $expectedParameters, $existingResults): array
    {
        $missingParameters = $expectedParameters
            ->reject(fn ($parameter) => $existingResults->has($parameter->id))
            ->map(fn ($parameter) => [
                'id' => $parameter->id,
                'code' => $parameter->code,
                'name' => $parameter->name,
            ])
            ->values()
            ->all();

        $completedCount = $existingResults
            ->filter(fn ($result) => filled($result->approved_value) || filled($result->verified_value) || filled($result->inserted_value))
            ->count();

        $expectedCount = $expectedParameters->count();
        $missingCount = count($missingParameters);
        $status = $missingCount === 0
            ? 'complete'
            : ($completedCount > 0 ? 'partial' : 'pending');

        return [
            'status' => $status,
            'status_label' => match ($status) {
                'complete' => 'Completo',
                'partial' => 'Parcial',
                default => 'Pendente',
            },
            'expected_count' => $expectedCount,
            'completed_count' => $completedCount,
            'missing_count' => $missingCount,
            'missing_parameters' => $missingParameters,
            'conditioning_status' => data_get($analysis->sample?->collection?->collection?->extra_data, 'submitted_payload.conditioning_status'),
        ];
    }
}
