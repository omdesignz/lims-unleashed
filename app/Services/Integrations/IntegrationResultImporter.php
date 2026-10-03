<?php

namespace App\Services\Integrations;

use App\Actions\ProcessLaboratoryResults;
use App\Models\Analysis;
use App\Models\IntegrationTransmission;
use App\Models\Result;
use App\Models\User;
use App\Services\IssuedAnalyticalScope;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IntegrationResultImporter
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly IssuedAnalyticalScope $issuedScope,
        private readonly ProcessLaboratoryResults $results,
    ) {}

    public function import(IntegrationTransmission $transmission, User $reviewer, int $labId): Result
    {
        return DB::transaction(function () use ($transmission, $reviewer, $labId): Result {
            $lockedTransmission = IntegrationTransmission::query()
                ->with(['connector.equipment', 'mapping'])
                ->lockForUpdate()
                ->findOrFail($transmission->id);

            if ($lockedTransmission->status !== 'matched' || ! $lockedTransmission->matched_result_id) {
                throw ValidationException::withMessages([
                    'transmission' => 'A transmissão ainda não possui uma correspondência completa para importação.',
                ]);
            }

            abort_unless((int) $lockedTransmission->connector?->lab_id === $labId, 404);

            $operator = $this->access->operator($reviewer->id, $labId, 'insert_results');
            $candidate = Result::query()->findOrFail($lockedTransmission->matched_result_id);
            if ($candidate->resultable_type !== (new Analysis)->getMorphClass() || ! $candidate->resultable_id) {
                throw ValidationException::withMessages(['transmission' => 'O resultado não tem uma análise de origem válida.']);
            }

            $analysis = $this->ownership->analysesForLaboratory($labId)
                ->lockForUpdate()->findOrFail($candidate->resultable_id);
            $sample = $this->ownership->samplesForLaboratory($labId)
                ->lockForUpdate()->findOrFail($analysis->sample_id);
            $code = $this->ownership->labCodesForLaboratory($labId)
                ->lockForUpdate()->findOrFail($sample->cl_id);
            $product = $this->ownership->collectionProductsForLaboratory($labId)
                ->with('sampleEntry')->lockForUpdate()->findOrFail($code->collection_id);
            $result = $this->ownership->resultsForLaboratory($labId)
                ->lockForUpdate()->findOrFail($candidate->id);

            if ((int) $result->sample_id !== (int) $analysis->sample_id
                || (int) $result->code_id !== (int) $code->id
                || (int) $result->profile_id !== (int) $analysis->profile_id
                || (int) $result->collection_id !== (int) $product->id
                || (int) $result->product_id !== (int) $product->product_id
                || (int) $result->resultable_id !== (int) $analysis->id
                || $result->resultable_type !== $analysis->getMorphClass()
                || ($analysis->product_id && (int) $analysis->product_id !== (int) $product->product_id)) {
                throw ValidationException::withMessages(['transmission' => 'A linhagem do resultado não corresponde à análise emitida.']);
            }

            $parameter = $this->issuedScope->parametersFor($analysis, $product)
                ->firstWhere('id', $result->parameter_id);
            if (! $parameter
                || (int) $lockedTransmission->matched_sample_id !== (int) $sample->id
                || (int) $lockedTransmission->matched_parameter_id !== (int) $parameter->id
                || $lockedTransmission->sample_code !== $sample->code
                || $lockedTransmission->parameter_code !== $parameter->code
                || ! $lockedTransmission->connector
                || ($lockedTransmission->mapping_id
                    && (int) $lockedTransmission->mapping?->connector_id !== (int) $lockedTransmission->connector_id)) {
                throw ValidationException::withMessages(['transmission' => 'A correspondência da transmissão já não coincide com a amostra emitida.']);
            }

            if (filled($result->inserted_value) || filled($result->inserted_date)
                || filled($result->verified_date) || filled($result->approved_date)) {
                throw ValidationException::withMessages([
                    'transmission' => 'O resultado correspondente já possui um valor inserido e não pode ser substituído automaticamente.',
                ]);
            }

            $value = $lockedTransmission->measured_value;
            $isQualitative = (bool) $parameter->result_is_qualitative || $parameter->result_type === 'qualitative';
            if (($isQualitative && ! in_array($value, ['Presença', 'Ausência'], true))
                || (! $isQualitative && ! is_numeric($value))) {
                throw ValidationException::withMessages(['transmission' => 'O valor recebido não corresponde ao tipo do parâmetro emitido.']);
            }

            $issuedUnit = $parameter->pivot->unit_label;
            if (filled($lockedTransmission->measured_unit)
                && $lockedTransmission->measured_unit !== $issuedUnit) {
                throw ValidationException::withMessages(['transmission' => 'A unidade recebida não corresponde à unidade emitida.']);
            }

            $this->results->execute($labId, $analysis->id, $operator->id, 'analysis', 'analyze', [[
                'sample_id' => $sample->id,
                'code_id' => $code->id,
                'profile_id' => $analysis->profile_id,
                'collection_id' => $product->id,
                'product_id' => $product->product_id,
                'parameter_id' => $parameter->id,
                'inserted_value' => $value,
                'insertion_notes' => 'Importado do conector '.$lockedTransmission->connector->name.'.',
                'equipment_id' => $lockedTransmission->connector->inventory_item_id,
            ]], individual: true);

            $result->refresh();
            $existingExtraData = $result->extra_data?->toArray() ?? [];
            $result->forceFill(['extra_data' => array_merge($existingExtraData, [
                'integration' => [
                    'connector_uuid' => $lockedTransmission->connector->uuid,
                    'connector_name' => $lockedTransmission->connector->name,
                    'transmission_id' => $lockedTransmission->id,
                    'external_id' => $lockedTransmission->external_id,
                    'mapping_version' => $lockedTransmission->mapping?->version,
                    'measured_at' => $lockedTransmission->measured_at?->toIso8601String(),
                    'measured_unit' => $lockedTransmission->measured_unit,
                    'checksum' => $lockedTransmission->checksum,
                ],
            ])])->save();

            $lockedTransmission->forceFill([
                'status' => 'imported',
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'processed_at' => now(),
            ])->save();

            activity()
                ->causedBy($operator)
                ->performedOn($result)
                ->withProperties([
                    'integration_transmission_id' => $lockedTransmission->id,
                    'connector_uuid' => $lockedTransmission->connector->uuid,
                ])
                ->log('Importou um resultado recebido através do Integration Hub.');

            return $result->fresh();
        });
    }
}
