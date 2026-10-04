<?php

namespace App\Actions;

use App\Events\AnalysisResultsApproved;
use App\Events\AnalysisResultsInserted;
use App\Events\AnalysisResultsValidated;
use App\Events\AnalysisResultsVerified;
use App\Events\CounterAnalysisResultsApproved;
use App\Events\CounterAnalysisResultsInserted;
use App\Events\CounterAnalysisResultsVerified;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\CounterAnalysis;
use App\Models\LabCode;
use App\Models\Parameter;
use App\Models\QualityCertificate;
use App\Models\Result;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPSampleEntry;
use App\Services\IssuedAnalyticalScope;
use App\Services\LaboratoryResultSignatures;
use App\Services\LaboratoryResultStageIntegrity;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use App\Support\EquipmentMetrologyGate;
use App\Support\PersonnelQualificationGate;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class ProcessLaboratoryResults
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly PersonnelQualificationGate $qualifications,
        private readonly EquipmentMetrologyGate $equipment,
        private readonly LaboratoryResultSignatures $signatures,
        private readonly IssuedAnalyticalScope $issuedScope,
        private readonly LaboratoryResultStageIntegrity $stages,
    ) {}

    /** @param array<int, array<string, mixed>> $rows */
    public function execute(int $labId, int $rootId, int $userId, string $workflow, string $stage, array $rows,
        ?string $signature = null, bool $individual = false): void
    {
        $permission = match ($stage) {
            'analyze' => 'insert_results', 'verify' => 'verify_results', 'approve' => 'approve_results',
            default => throw ValidationException::withMessages(['action' => 'Etapa inválida.']),
        };
        $staged = [];
        $committed = false;
        try {
            DB::transaction(function () use ($labId, $rootId, $userId, $workflow, $stage, $rows, $signature, $individual, $permission, &$staged, &$committed): void {
                DB::afterCommit(function () use (&$committed): void {
                    $committed = true;
                });
                DB::afterRollBack(function () use (&$staged): void {
                    $this->signatures->discard($staged);
                });
                $operator = $this->access->operator($userId, $labId, $permission);
                $root = match ($workflow) {
                    'analysis' => $this->ownership->analysesForLaboratory($labId)->lockForUpdate()->findOrFail($rootId),
                    'counter' => $this->ownership->counterAnalysesForLaboratory($labId)->lockForUpdate()->findOrFail($rootId),
                    default => throw ValidationException::withMessages(['sample_id' => 'Fluxo inválido.']),
                };
                $root->load('profile');
                $sample = $this->ownership->samplesForLaboratory($labId)->lockForUpdate()->findOrFail($root->sample_id);
                $code = $this->ownership->labCodesForLaboratory($labId)->lockForUpdate()->findOrFail($sample->cl_id);
                $product = CollectionProduct::query()->with('product', 'sampleEntry')->lockForUpdate()->findOrFail($code->collection_id);
                $root->setRelation('sample', $sample->setRelation('collection', $code));
                $this->qualifications->ensure($operator, $permission, $root->department_id, $labId);
                if (! $root->profile || ($root instanceof Analysis && $root->product_id && (int) $root->product_id !== (int) $product->product_id)) {
                    throw ValidationException::withMessages(['sample_id' => 'A linhagem analítica da amostra é inválida.']);
                }

                $workflowRecords = $this->lockWorkflowRecords($labId, $root, $sample, $code, $product);
                $signatureResults = Result::query()->where('sample_id', $root->sample_id)->orderBy('id')->lockForUpdate()->toBase()->get()
                    ->map($this->resultFromRow(...))->concat($workflowRecords->filter(fn (Model $record): bool => $record instanceof Result))->keyBy('id');
                $signatureEvidence = $this->signatures->lockEvidence($signatureResults);

                $root->profile->setRelation('parameters', $this->issuedScope->parametersFor($root, $product));

                $prepared = $this->preflight($labId, $root, $product, $rows, $stage, $individual);
                $this->equipment->ensureResultsReady($prepared->flatMap(fn (array $item): array => $item['equipment'])->all(), $labId);
                $signed = $stage === 'analyze' ? null : $this->signatures->prepare($operator, $signature);
                $changed = collect();
                $intendedAudits = collect();
                $intendedResults = Result::query()->where('sample_id', $root->sample_id)->get()->keyBy('id');
                foreach ($prepared as $item) {
                    $result = $item['result'];
                    $attributes = $this->attributes($root, $product, $item['parameter'], $item['row'], $stage, $operator, $individual, $result);
                    $prefix = $stage === 'analyze' ? 'inserted' : ($stage === 'verify' ? 'verified' : 'approved');
                    $replay = $result->exists && filled($result->{$prefix.'_date'})
                        && $this->sameEvidence($result, $attributes, $prefix)
                        && (! $signed || (blank($signature) && $result->hasMedia($prefix === 'verified' ? 'verification_signature' : 'approval_signature'))
                            || $result->getFirstMedia($prefix === 'verified' ? 'verification_signature' : 'approval_signature')?->getCustomProperty('signature_sha256') === $signed['hash']);
                    if ($replay) {
                        continue;
                    }
                    if ($result->exists && ($result->approved_date || ($stage === 'analyze' && $result->verified_date))) {
                        throw ValidationException::withMessages(['results' => 'O resultado revisto não pode ser reescrito sem um fluxo de revisão autorizado.']);
                    }
                    $this->stages->ensureIndependentReviewer($result, $stage, (int) $operator->id);
                    $result->fill($attributes);
                    $intended = clone $result;
                    if (! $result->save()) {
                        throw new LogicException('Result evidence was not persisted.');
                    }
                    $this->assertEvidence($intended, $result->fresh());
                    $intended->setAttribute($result->getKeyName(), $result->getKey());
                    $intended->exists = true;
                    $intendedResults[$result->id] = $intended;
                    $signatureResults[$result->id] = $intended;
                    if ($signed) {
                        $this->signatures->replace($result, $stage === 'verify' ? 'verification_signature' : 'approval_signature', $signed, $staged, $signatureEvidence);
                    }
                    $verb = match ($stage) {
                        'analyze' => 'Inseriu', 'verify' => 'Verificou', 'approve' => 'Validou'
                    };
                    $description = $verb.' o resultado '.$result->{$prefix.'_value'}.' no parâmetro: '.$result->parameter_label.' da CL: '.$code->code;
                    $intendedAudit = null;
                    $audit = activity()->causedBy($operator)->performedOn($result)
                        ->withProperties(['result_value' => $result->{$prefix.'_value'}, 'parameter_label' => $result->parameter_label, 'lab_code' => $code->code])
                        ->tap(function (Activity $activity) use ($description, &$intendedAudit): void {
                            $intendedAudit = clone $activity;
                            $intendedAudit->description = $description;
                        })->log($verb.' o resultado :properties.result_value no parâmetro: :properties.parameter_label da CL: :properties.lab_code');
                    if (! $audit?->exists || ! $intendedAudit) {
                        throw new LogicException('Result audit was not persisted.');
                    }
                    $intendedAudit->setAttribute($audit->getKeyName(), $audit->getKey());
                    $intendedAudit->exists = true;
                    $intendedAudits->push($intendedAudit);
                    $this->assertAuditEvidence($intendedAudits);
                    $changed->push($result);
                    $this->assertEvidence($intended, $result->fresh());
                }
                $this->assertBatchEvidence($root->sample_id, $intendedResults);
                $this->signatures->assertEvidence($signatureResults, $signatureEvidence, $staged);
                $this->assertWorkflowEvidence($workflowRecords, $product);
                if ($changed->isEmpty()) {
                    $operator = $this->access->operator($userId, $labId, $permission);
                    $this->qualifications->ensure($operator, $permission, $root->department_id, $labId);
                    $this->assertBatchEvidence($root->sample_id, $intendedResults);
                    $this->signatures->assertEvidence($signatureResults, $signatureEvidence, $staged);
                    $this->assertWorkflowEvidence($workflowRecords, $product);

                    return;
                }
                $this->updateWorkflow($root, $product, $stage, $operator, $changed->first(), $workflowRecords);
                $this->assertBatchEvidence($root->sample_id, $intendedResults);
                $this->signatures->assertEvidence($signatureResults, $signatureEvidence, $staged);
                $this->assertAuditEvidence($intendedAudits);
                $this->assertWorkflowEvidence($workflowRecords, $product);
                $operator = $this->access->operator($userId, $labId, $permission);
                $this->qualifications->ensure($operator, $permission, $root->department_id, $labId);
                $event = match ([$workflow, $stage]) {
                    ['analysis', 'analyze'] => new AnalysisResultsInserted($operator, $code),
                    ['analysis', 'verify'] => new AnalysisResultsVerified($operator, $code),
                    ['analysis', 'approve'] => new AnalysisResultsApproved($operator, $code),
                    ['counter', 'analyze'] => new CounterAnalysisResultsInserted($operator, $code),
                    ['counter', 'verify'] => new CounterAnalysisResultsVerified($operator, $code),
                    ['counter', 'approve'] => new CounterAnalysisResultsApproved($operator, $code),
                };
                event($event);
                $this->assertBatchEvidence($root->sample_id, $intendedResults);
                $this->signatures->assertEvidence($signatureResults, $signatureEvidence, $staged);
                $this->assertAuditEvidence($intendedAudits);
                $this->assertWorkflowEvidence($workflowRecords, $product);
            });
            if (DB::transactionLevel() > 0 && $staged !== []) {
                DB::afterRollBack(fn () => $this->signatures->discard($staged));
            }
        } catch (Throwable $exception) {
            if (! $committed) {
                $this->signatures->discard($staged);
            }
            throw $exception;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, array{row: array<string,mixed>, result: Result, parameter: Parameter, equipment: array<int,array<string,mixed>>}>
     */
    private function preflight(int $labId, Analysis|CounterAnalysis $root, CollectionProduct $product, array $rows, string $stage, bool $individual): Collection
    {
        $parameters = $root->profile->parameters->keyBy('id');
        $existingResults = $this->ownership->resultsForLaboratory($labId)->where('sample_id', $root->sample_id)
            ->with('media')->orderBy('results.id')->lockForUpdate()->get();
        $seenParameters = [];
        $seenResults = [];
        if ($rows === []) {
            throw ValidationException::withMessages(['results' => 'O lote de resultados é obrigatório.']);
        }
        $prepared = collect($rows)->map(function (mixed $row, int $index) use ($existingResults, $root, $product, $stage, $parameters, &$seenParameters, &$seenResults): array {
            if (! is_array($row)) {
                throw ValidationException::withMessages(["results.$index" => 'Resultado inválido.']);
            }
            $parameterId = (int) data_get($row, 'parameter_id.value', data_get($row, 'parameter_id', 0));
            $parameter = $parameters->get($parameterId);
            if (! $parameter || in_array($parameterId, $seenParameters, true)) {
                throw ValidationException::withMessages(["results.$index.parameter_id" => 'Parâmetro inválido ou repetido no lote.']);
            }
            $seenParameters[] = $parameterId;
            foreach (['sample_id' => $root->sample_id, 'code_id' => $root->cl_id, 'profile_id' => $root->profile_id,
                'collection_id' => $product->id, 'product_id' => $product->product_id] as $field => $id) {
                if (isset($row[$field]) && (int) data_get($row, "$field.value", $row[$field]) !== (int) $id) {
                    throw ValidationException::withMessages(["results.$index.$field" => 'A linhagem do resultado não corresponde à amostra.']);
                }
            }
            if ($stage === 'analyze') {
                $matches = $existingResults->where('parameter_id', $parameterId);
                if ($matches->count() > 1) {
                    throw ValidationException::withMessages(['results' => 'Existem resultados duplicados que precisam de revisão.']);
                }
                $result = $matches->first() ?? new Result;
            } else {
                $id = $row['result_id'] ?? null;
                if (filter_var($id, FILTER_VALIDATE_INT) === false) {
                    throw ValidationException::withMessages(["results.$index.result_id" => 'Identificador do resultado inválido.']);
                }
                $result = $existingResults->firstWhere('id', (int) $id);
                if (! $result) {
                    throw (new ModelNotFoundException)->setModel(Result::class, [(int) $id]);
                }
                if ((int) $result->parameter_id !== $parameterId || (int) $result->profile_id !== (int) $root->profile_id
                    || in_array($result->id, $seenResults, true)) {
                    throw ValidationException::withMessages(['results' => 'O resultado não pertence ao parâmetro/perfil atribuído.']);
                }
                $seenResults[] = $result->id;
            }
            if ($result->exists && $result->resultable_id && ((int) $result->resultable_id !== (int) $root->id || $result->resultable_type !== $root->getMorphClass())) {
                throw ValidationException::withMessages(['results' => 'A origem do resultado é inválida.']);
            }
            $this->stages->ensure($result, $stage, $row, $index);
            $equipmentIds = array_unique(array_filter([
                data_get($row, 'equipment_id.value', data_get($row, 'equipment_id')),
                data_get($row, 'extra_data.equipment.equipment_id'), data_get($result->extra_data, 'equipment.equipment_id'),
            ]));

            return ['row' => $row, 'result' => $result, 'parameter' => $parameter,
                'equipment' => array_map(fn (mixed $id): array => ['equipment_id' => $id], $equipmentIds)];
        });
        if (! $individual && $parameters->keys()->diff($seenParameters)->isNotEmpty()) {
            throw ValidationException::withMessages(['results' => 'Todos os parâmetros atribuídos devem permanecer no lote.']);
        }

        return $prepared;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function attributes(Analysis|CounterAnalysis $root, CollectionProduct $product, Parameter $parameter, array $row, string $stage, User $operator, bool $individual, Result $result): array
    {
        $prefix = match ($stage) {
            'analyze' => 'inserted', 'verify' => 'verified', 'approve' => 'approved'
        };
        $fields = match ($stage) {
            'analyze' => ['inserted_value', 'insertion_notes', 'uncertainty_value', 'method_deviation', 'count', 'min_ref_value', 'max_ref_value', 'ref_val_origin',
                'sumC', 'volume', 'n1', 'n2', 'dilution', 'd1', 'd2', 'cfu1', 'cfu2', 'is_calculated', 'is_override', 'calculation_metadata'],
            'verify' => ['verified_value', 'verification_notes', 'verification_status', 'uncertainty_value', 'method_deviation'],
            'approve' => ['approved_value', 'approval_notes', 'uncertainty_value', 'method_deviation'],
        };
        $attributes = Arr::only($row, $fields);
        if ($stage === 'analyze') {
            $attributes['min_ref_value'] = $parameter->pivot->min_ref_value;
            $attributes['max_ref_value'] = $parameter->pivot->max_ref_value;
            $attributes['ref_val_origin'] = $parameter->pivot->ref_val_origin;
            $attributes['accredited'] = $parameter->pivot->accredited;
            $attributes['subcontractor'] = $parameter->pivot->subcontractor;
            $attributes['uncertainty_coverage_factor'] = $parameter->pivot->uncertainty_coverage_factor;
        }
        $attributes[$prefix.'_by_id'] = $operator->id;
        $attributes[$prefix.'_by'] = $operator->name;
        $attributes[$prefix.'_date'] = now();
        $extra = $result->extra_data?->all() ?? [];
        $format = data_get($row, 'extra_data.display_format', data_get($extra, 'display_format'));
        $extra['display_format'] = $format === 'scientific' && ! $parameter->result_is_qualitative ? 'scientific' : 'standard';
        if ($stage === 'analyze') {
            $attributes = array_replace($attributes, [
                'sample_id' => $root->sample_id, 'code_id' => $root->cl_id, 'code_label' => $root->sample->collection->code,
                'collection_id' => $product->id, 'product_id' => $product->product_id, 'product_label' => $product->product?->name,
                'matrix_id' => $product->product?->matrix_id, 'profile_id' => $root->profile_id, 'parameter_id' => $parameter->id,
                'parameter_label' => $parameter->name, 'resultable_id' => $root->id, 'resultable_type' => $root->getMorphClass(),
                'insertion_method' => $individual ? 'individual' : 'batch', 'calculated_at' => now(),
            ]);
            foreach (['unit', 'protocol', 'standard', 'nwp'] as $reference) {
                $attributes[$reference.'_id'] = $parameter->pivot->{$reference.'_id'};
                $attributes[$reference.'_label'] = $parameter->pivot->{$reference.'_label'};
            }
            $attributes['type_id'] = $parameter->pivot->category_id;
            $attributes['category_label'] = $parameter->pivot->category_label;
            $extra['equipment'] = ['equipment_id' => data_get($row, 'equipment_id.value',
                data_get($row, 'equipment_id', data_get($row, 'extra_data.equipment.equipment_id')))];
        }
        $value = $attributes[$prefix.'_value'] ?? $result->{$prefix.'_value'};
        $min = array_key_exists('min_ref_value', $attributes) ? $attributes['min_ref_value'] : $result->min_ref_value;
        $max = array_key_exists('max_ref_value', $attributes) ? $attributes['max_ref_value'] : $result->max_ref_value;
        $withinLimits = is_numeric($value) ? ((! is_numeric($min) || (float) $value >= (float) $min)
            && (! is_numeric($max) || (float) $value <= (float) $max)) : null;
        $extra['specification_check'] = ['action' => $stage, 'value' => $value, 'min_ref_value' => $min,
            'max_ref_value' => $max, 'within_limits' => $withinLimits, 'unit_id' => $attributes['unit_id'] ?? $result->unit_id,
            'unit_label' => $attributes['unit_label'] ?? $result->unit_label, 'checked_at' => now()->toIso8601String()];
        $attributes['extra_data'] = $extra;

        return $attributes;
    }

    /** @param array<string,mixed> $attributes */
    private function sameEvidence(Result $result, array $attributes, string $prefix): bool
    {
        foreach (Arr::except($attributes, [$prefix.'_date', $prefix.'_by', 'calculated_at', 'extra_data']) as $field => $value) {
            if ($this->evidenceValue($result->getAttribute($field)) !== $this->evidenceValue($value)) {
                return false;
            }
        }

        return data_get($result->extra_data, 'display_format') === data_get($attributes, 'extra_data.display_format')
            && $this->evidenceValue(data_get($result->extra_data, 'equipment.equipment_id')) === $this->evidenceValue(data_get($attributes, 'extra_data.equipment.equipment_id'));
    }

    private function evidenceValue(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }
        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($this->evidenceValue(...), $value);
        }

        return $value === null || is_bool($value) || is_array($value) ? $value : (string) $value;
    }

    /** @param Collection<int, Result> $intendedResults */
    private function assertBatchEvidence(int $sampleId, Collection $intendedResults): void
    {
        $storedResults = Result::query()->where('sample_id', $sampleId)->toBase()->get()
            ->map(fn (object $row): Result => (new Result)->setRawAttributes((array) $row, true))->keyBy('id');
        if ($storedResults->keys()->sort()->values()->all() !== $intendedResults->keys()->sort()->values()->all()) {
            throw new LogicException('Persisted result set differs from the intended result set.');
        }
        foreach ($intendedResults as $intended) {
            $this->assertEvidence($intended, $storedResults->get($intended->getKey()));
        }
    }

    /** @param Collection<int, Activity> $intendedAudits */
    private function assertAuditEvidence(Collection $intendedAudits): void
    {
        foreach ($intendedAudits as $intended) {
            $row = $intended->newQueryWithoutScopes()->toBase()->find($intended->getKey());
            if (! $row) {
                throw new LogicException('Result audit was not persisted.');
            }
            $stored = (clone $intended)->setRawAttributes((array) $row, true);
            foreach (['log_name', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'event', 'properties', 'batch_uuid'] as $field) {
                if ($this->evidenceValue($intended->getAttribute($field)) !== $this->evidenceValue($stored->getAttribute($field))) {
                    throw new LogicException('Persisted result audit differs from the intended audit.');
                }
            }
        }
    }

    private function assertEvidence(Result $intended, ?Result $stored): void
    {
        if (! $stored) {
            throw new LogicException('Result evidence is no longer available.');
        }
        $fields = array_keys(Arr::except($intended->getAttributes(), ['id', 'created_at', 'updated_at']));
        foreach (['inserted', 'verified', 'approved'] as $prefix) {
            foreach (['value', 'date', 'by', 'by_id'] as $suffix) {
                $fields[] = $prefix.'_'.$suffix;
            }
        }
        foreach (array_unique($fields) as $field) {
            if ($this->evidenceValue($intended->getAttribute($field)) !== $this->evidenceValue($stored->getAttribute($field))) {
                throw new LogicException('Persisted result evidence differs from the intended result.');
            }
        }
    }

    /** @return Collection<string,Model> */
    private function lockWorkflowRecords(int $labId, Analysis|CounterAnalysis $root, Sample $sample, LabCode $code, CollectionProduct $product): Collection
    {
        $entry = VAPSampleEntry::query()->where('lab_id', $labId)->where('collection_product_id', $product->id)
            ->lockForUpdate()->firstOrFail();
        $product->setRelation('sampleEntry', $entry);
        $codes = LabCode::query()->where('collection_id', $product->id)->orderBy('id')->lockForUpdate()->get();
        $samples = Sample::query()->whereIn('cl_id', $codes->modelKeys())->orderBy('id')->lockForUpdate()->get();
        $analyses = Analysis::query()->whereIn('cl_id', $codes->modelKeys())->orderBy('id')->lockForUpdate()->get();
        $records = collect([$root, $sample, $code, $product, $entry])->merge($codes)->merge($samples)->merge($analyses);
        if ($root instanceof CounterAnalysis) {
            $sourceRow = $this->ownership->resultsForLaboratory($labId)->lockForUpdate()->toBase()->find($root->result_id);
            if (! $sourceRow) {
                throw (new ModelNotFoundException)->setModel(Result::class, [$root->result_id]);
            }
            $source = $this->resultFromRow($sourceRow);
            $root->setRelation('requested_result', $source);
            $sources = $this->ownership->resultsForLaboratory($labId)->withTrashed()->where('sample_id', $source->sample_id)
                ->orderBy('results.id')->lockForUpdate()->toBase()->get()->map($this->resultFromRow(...));
            $records = $records->merge($sources);
        }

        return $records->keyBy($this->workflowRecordKey(...))->map(fn (Model $record): Model => clone $record);
    }

    private function resultFromRow(object $row): Result
    {
        $result = new Result;
        $result->setRawAttributes((array) $row, true);
        $result->exists = true;

        return $result;
    }

    private function workflowRecordKey(Model $record): string
    {
        return $record->getTable().':'.$record->getKey();
    }

    /** @param Collection<string,Model> $records */
    private function assertWorkflowEvidence(Collection $records, CollectionProduct $product): void
    {
        foreach ($records->groupBy(fn (Model $record): string => $record->getTable()) as $expected) {
            $stored = $expected->first()->newQueryWithoutScopes()->whereKey($expected->pluck('id')->all())->toBase()->get()->keyBy('id');
            foreach ($expected as $intended) {
                $actual = $stored->get($intended->getKey());
                if (! $actual || $this->evidenceValue((array) $actual) !== $this->evidenceValue($intended->getAttributes())) {
                    throw new LogicException('Persisted scientific workflow differs from the intended workflow.');
                }
            }
        }
        $codes = LabCode::query()->where('collection_id', $product->id)->orderBy('id')->toBase()->pluck('id');
        foreach ([(new LabCode)->getTable() => $codes,
            (new Sample)->getTable() => Sample::query()->whereIn('cl_id', $codes)->orderBy('id')->toBase()->pluck('id'),
            (new Analysis)->getTable() => Analysis::query()->whereIn('cl_id', $codes)->orderBy('id')->toBase()->pluck('id')] as $type => $stored) {
            $expected = $records->filter(fn (Model $record): bool => $record->getTable() === $type)->pluck('id')->sort()->values()->all();
            if ($stored->all() !== $expected) {
                throw new LogicException('Persisted scientific workflow graph differs from the intended workflow.');
            }
        }
        $sources = $records->filter(fn (Model $record): bool => $record instanceof Result);
        foreach ($sources->groupBy('sample_id') as $sampleId => $expected) {
            $storedIds = Result::withTrashed()->where('sample_id', $sampleId)->orderBy('id')->toBase()->pluck('id')->all();
            if ($storedIds !== $expected->pluck('id')->sort()->values()->all()) {
                throw new LogicException('Persisted scientific workflow source set differs from the intended workflow.');
            }
        }
    }

    /** @param array<string,mixed> $attributes
     * @param  Collection<string,Model>  $records
     */
    private function saveWorkflowRecord(Model $record, array $attributes, Collection $records, CollectionProduct $product): void
    {
        $this->assertWorkflowEvidence($records, $product);
        foreach ($attributes as $field => $value) {
            if ($value instanceof \DateTimeInterface) {
                $attributes[$field] = $record->fromDateTime($value);
            }
        }
        $intended = clone $records->get($this->workflowRecordKey($record));
        $intended->forceFill($attributes);
        if (! $intended->isDirty()) {
            return;
        }
        $intended->setUpdatedAt($intended->freshTimestamp());
        $record->forceFill($attributes)->setUpdatedAt($intended->updated_at);
        $records->put($this->workflowRecordKey($record), $intended);
        if (! $record::withoutTimestamps(fn (): bool => $record->save())) {
            throw new LogicException('Scientific workflow parent was not persisted.');
        }
        $this->assertWorkflowEvidence($records, $product);
    }

    /** @param Collection<string,Model> $workflowRecords */
    private function updateWorkflow(Analysis|CounterAnalysis $root, CollectionProduct $product, string $stage, User $operator, Result $result, Collection $workflowRecords): void
    {
        if ($stage === 'analyze') {
            if (! $root->init_date) {
                $this->saveWorkflowRecord($root, ['init_date' => now()], $workflowRecords, $product);
            }
            if ($root instanceof Analysis) {
                $this->saveWorkflowRecord($product, ['analysis_start_date' => $product->analysis_start_date ?? now()->format('Y-m-d'), 'sample_status' => 'Em análise'], $workflowRecords, $product);
                $this->saveWorkflowRecord($product->sampleEntry, ['analysis_start_date' => $product->sampleEntry->analysis_start_date ?? now(), 'status' => 'EN_PROGRESO'], $workflowRecords, $product);
            }

            return;
        }
        $results = Result::query()->where('sample_id', $root->sample_id)->get(['parameter_id', 'approved_date']);
        if ($stage !== 'approve' || $results->contains(fn (Result $result): bool => blank($result->approved_date))
            || $root->profile->parameters->pluck('id')->diff($results->pluck('parameter_id'))->isNotEmpty()) {
            return;
        }
        $this->saveWorkflowRecord($root, ['end_date' => $root->end_date ?? now(), 'status' => true], $workflowRecords, $product);
        if ($root instanceof CounterAnalysis) {
            $this->saveWorkflowRecord($root->requested_result, ['requested_counter_analysis' => false], $workflowRecords, $product);

            return;
        }
        $analyses = $workflowRecords->filter(fn (Model $record): bool => $record instanceof Analysis);
        if ($analyses->isNotEmpty() && $analyses->every(fn (Analysis $item): bool => filled($item->end_date))) {
            $this->saveWorkflowRecord($product, ['status' => true, 'processed' => true, 'analysis_end_date' => $product->analysis_end_date ?? now()->format('Y-m-d'), 'sample_status' => 'Concluída'], $workflowRecords, $product);
            $this->saveWorkflowRecord($product->sampleEntry, ['analysis_end_date' => $product->sampleEntry->analysis_end_date ?? now(), 'status' => 'COMPLETADO'], $workflowRecords, $product);
            if (! QualityCertificate::query()->where('collection_id', $product->id)->exists()) {
                event(new AnalysisResultsValidated($result, $operator->id));
            }
        }
    }
}
