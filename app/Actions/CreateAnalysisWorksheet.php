<?php

namespace App\Actions;

use App\Models\Analysis;
use App\Models\Result;
use App\Models\Worksheet;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorksheetAccess;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateAnalysisWorksheet
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly LaboratoryWorksheetAccess $worksheets,
    ) {}

    public function execute(int $labId, int $userId, int $analysisId): Worksheet
    {
        return DB::transaction(function () use ($labId, $userId, $analysisId): Worksheet {
            $operator = $this->access->operator($userId, $labId, 'add_worksheets');
            abort_unless($operator->can('view_worksheets'), 403);
            $analysis = $this->worksheets->lockAnalysis($labId, $analysisId);
            $existing = Worksheet::withTrashed()->where('analysis_id', $analysisId)->lockForUpdate()->first();

            if ($existing) {
                abort_unless($this->worksheets->records($labId)->withTrashed()->whereKey($existing->id)->exists(), 404);
                if ($existing->trashed()) {
                    throw ValidationException::withMessages(['worksheet' => 'A folha desta análise está arquivada. Restaure-a antes de continuar.']);
                }

                return $existing;
            }

            $analysis->load([
                'profile', 'type', 'department', 'product', 'code',
                'sample.collection.collection.collection', 'sample.collection.collection.sampleEntry',
                'sample.results' => fn (HasMany $results): HasMany => $results
                    ->where('profile_id', $analysis->profile_id)->where('code_id', $analysis->cl_id)
                    ->where('collection_id', $analysis->code->collection_id)
                    ->where('resultable_type', $analysis->getMorphClass())->where('resultable_id', $analysisId),
            ]);

            $expectedParameters = $this->issuedParameters($analysis);

            $existingResults = $analysis->sample?->results
                ? $analysis->sample->results
                    ->whereIn('parameter_id', $expectedParameters->pluck('id'))
                    ->sortByDesc(fn ($result) => $result->approved_date ?? $result->verified_date ?? $result->inserted_date)
                    ->unique('parameter_id')
                    ->keyBy('parameter_id')
                : collect();

            $scopeControl = $this->buildWorksheetScopeControl($analysis, $expectedParameters, $existingResults);

            $parameterRows = $expectedParameters
                ->map(function ($parameter, int $index) use ($existingResults) {
                    $result = $existingResults->get($parameter['id']);
                    $currentValue = $result?->approved_value ?? $result?->verified_value ?? $result?->inserted_value;
                    $workflowStatus = $result?->approved_date
                        ? 'Aprovado'
                        : ($result?->verified_date
                            ? 'Verificado'
                            : ($result?->inserted_date ? 'Inserido' : 'Pendente'));

                    return [
                        $index + 1,
                        $parameter['code'],
                        $parameter['name'],
                        $parameter['unit_code'],
                        match ($parameter['requires_calculation']) {
                            true => 'Calculado',
                            false => 'Manual',
                            default => 'Não registado',
                        },
                        $parameter['min_ref_value'],
                        $parameter['max_ref_value'],
                        $currentValue ?? '',
                        $workflowStatus,
                        $result?->verification_notes ?? $result?->approval_notes ?? $result?->insertion_notes ?? '',
                    ];
                })
                ->all();

            $worksheet = Worksheet::query()->create([
                'name' => 'Folha de trabalho - '.($analysis->code?->code ?? ('Análise #'.$analysis->id)),
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
                            'name' => 'Controlo do âmbito',
                            'data' => array_merge([
                                ['Código da amostra', $analysis->code?->code],
                                ['Departamento', $analysis->department?->name],
                                ['Perfil', $analysis->profile?->name],
                                ['Produto', $analysis->product?->name],
                                ['Condicionamento na recepção', data_get($analysis->sample?->collection?->collection?->extra_data, 'submitted_payload.conditioning_status', 'not_evaluated')],
                                ['Estado do âmbito', $scopeControl['status_label']],
                                ['Parâmetros previstos', $scopeControl['expected_count']],
                                ['Resultados concluídos', $scopeControl['completed_count']],
                                ['Parâmetros em falta', $scopeControl['missing_count']],
                                [''],
                                ['#', 'Código', 'Parâmetro', 'Unidade', 'Tipo', 'Referência mínima', 'Referência máxima', 'Valor actual', 'Estado do fluxo', 'Notas'],
                            ], $parameterRows),
                        ],
                    ],
                ],
                'user_id' => $operator->id,
                'lab_id' => $labId,
                'analysis_id' => $analysis->id,
            ]);

            abort_unless($worksheet->exists, 409, 'A criação da folha de trabalho foi cancelada.');
            activity()->causedBy($operator)->performedOn($worksheet)
                ->withProperties(['lab_id' => $labId, 'analysis_id' => $analysisId])
                ->log('criou a folha de trabalho analítica');

            return $worksheet;
        }, 3);
    }

    /**
     * @return Collection<int, array{id: int, code: ?string, name: string, unit_code: ?string, requires_calculation: ?bool, min_ref_value: mixed, max_ref_value: mixed}>
     */
    private function issuedParameters(Analysis $analysis): Collection
    {
        $snapshot = data_get($analysis->sample?->collection?->collection?->sampleEntry?->client_submitted_info, 'required_parameters');
        $validation = Validator::make(['parameters' => $snapshot], [
            'parameters' => ['required', 'array', 'list', 'min:1'],
            'parameters.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'parameters.*.code' => ['nullable', 'string'],
            'parameters.*.name' => ['required', 'string'],
            'parameters.*.profile_ids' => ['required', 'array', 'list', 'min:1'],
            'parameters.*.profile_ids.*' => ['required', 'integer', 'min:1'],
            'parameters.*.requires_calculation' => ['sometimes', 'boolean'],
            'parameters.*.profile_definitions' => ['sometimes', 'array', 'list'],
            'parameters.*.profile_definitions.*.profile_id' => ['required', 'integer', 'min:1'],
            'parameters.*.profile_definitions.*.unit_code' => ['nullable', 'string'],
            'parameters.*.profile_definitions.*.min_ref_value' => ['nullable', 'string'],
            'parameters.*.profile_definitions.*.max_ref_value' => ['nullable', 'string'],
        ]);

        if ($validation->fails()) {
            throw ValidationException::withMessages(['worksheet' => 'O âmbito emitido da amostra está ausente ou inválido. Não é possível preparar a folha de trabalho.']);
        }

        $parameters = collect($snapshot)
            ->filter(fn (array $parameter): bool => in_array((int) $analysis->profile_id,
                array_map('intval', $parameter['profile_ids']), true))
            ->map(function (array $parameter) use ($analysis): array {
                $definition = collect($parameter['profile_definitions'] ?? [])
                    ->first(fn (array $definition): bool => (int) $definition['profile_id'] === (int) $analysis->profile_id);

                return [
                    'id' => (int) $parameter['id'],
                    'code' => $parameter['code'] ?? null,
                    'name' => $parameter['name'],
                    'unit_code' => $definition['unit_code'] ?? null,
                    'requires_calculation' => array_key_exists('requires_calculation', $parameter) ? (bool) $parameter['requires_calculation'] : null,
                    'min_ref_value' => $definition['min_ref_value'] ?? null,
                    'max_ref_value' => $definition['max_ref_value'] ?? null,
                ];
            })->sortBy('name')->values();

        if ($parameters->isEmpty()) {
            throw ValidationException::withMessages(['worksheet' => 'O perfil desta análise não consta do âmbito emitido da amostra.']);
        }

        return $parameters;
    }

    /**
     * @param  Collection<int, array{id: int, code: ?string, name: string}>  $expectedParameters
     * @param  Collection<int, Result>  $existingResults
     * @return array<string, mixed>
     */
    private function buildWorksheetScopeControl(Analysis $analysis, Collection $expectedParameters, Collection $existingResults): array
    {
        $missingParameters = $expectedParameters
            ->reject(fn (array $parameter): bool => $existingResults->has($parameter['id']))
            ->map(fn ($parameter) => [
                'id' => $parameter['id'],
                'code' => $parameter['code'],
                'name' => $parameter['name'],
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
