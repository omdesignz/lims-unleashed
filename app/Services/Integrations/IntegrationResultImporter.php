<?php

namespace App\Services\Integrations;

use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\IntegrationTransmission;
use App\Models\Result;
use App\Models\User;
use App\Support\EquipmentMetrologyGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IntegrationResultImporter
{
    public function __construct(private readonly EquipmentMetrologyGate $equipmentMetrologyGate) {}

    public function import(IntegrationTransmission $transmission, User $reviewer): Result
    {
        return DB::transaction(function () use ($transmission, $reviewer): Result {
            $lockedTransmission = IntegrationTransmission::query()
                ->with(['connector.equipment', 'mapping'])
                ->lockForUpdate()
                ->findOrFail($transmission->id);

            if ($lockedTransmission->status !== 'matched' || ! $lockedTransmission->matched_result_id) {
                throw ValidationException::withMessages([
                    'transmission' => 'A transmissão ainda não possui uma correspondência completa para importação.',
                ]);
            }

            $result = Result::query()->lockForUpdate()->findOrFail($lockedTransmission->matched_result_id);

            if (filled($result->inserted_value) || filled($result->inserted_date)) {
                throw ValidationException::withMessages([
                    'transmission' => 'O resultado correspondente já possui um valor inserido e não pode ser substituído automaticamente.',
                ]);
            }

            if ($lockedTransmission->connector->inventory_item_id) {
                $this->equipmentMetrologyGate->ensureResultsReady([[
                    'equipment_id' => $lockedTransmission->connector->inventory_item_id,
                ]]);
            }

            $existingExtraData = $result->extra_data?->toArray() ?? [];
            $result->forceFill([
                'inserted_value' => $lockedTransmission->measured_value,
                'inserted_by_id' => $reviewer->id,
                'inserted_by' => $reviewer->name,
                'inserted_date' => now(),
                'insertion_method' => 'individual',
                'insertion_notes' => 'Importado do conector '.$lockedTransmission->connector->name.'.',
                'calculated_at' => now(),
                'extra_data' => array_merge($existingExtraData, [
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
                ]),
            ])->save();

            $lockedTransmission->forceFill([
                'status' => 'imported',
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'processed_at' => now(),
            ])->save();

            $analysis = Analysis::query()->with('sample.collection')->where('sample_id', $result->sample_id)->first();

            if ($analysis && is_null($analysis->init_date)) {
                $analysis->update(['init_date' => now()]);
            }

            $collectionProduct = $analysis?->sample?->collection?->collection_id
                ? CollectionProduct::query()->find($analysis->sample->collection->collection_id)
                : null;

            if ($collectionProduct && is_null($collectionProduct->analysis_start_date)) {
                $collectionProduct->update(['analysis_start_date' => now()->toDateString()]);
            }

            activity()
                ->causedBy($reviewer)
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
