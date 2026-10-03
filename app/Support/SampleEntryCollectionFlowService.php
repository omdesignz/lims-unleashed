<?php

namespace App\Support;

use App\Enums\Collections\CollectionProductTrackingStatus;
use App\Models\Analysis;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\DirectCollection;
use App\Models\LabCode;
use App\Models\Product;
use App\Models\Profile;
use App\Models\ProgrammedCollection;
use App\Models\Sample;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SampleEntryCollectionFlowService
{
    private const METADATA_FIELDS = [
        'qty' => 'quantity', 'collected_qty' => 'collected_qty', 'lot' => 'lot',
        'origin' => 'origin', 'location' => 'location', 'temperature_value' => 'temperature_value',
        'container_no' => 'container_no', 'du_no' => 'du_no', 'term_no' => 'term_no', 'bl' => 'bl',
        'sampling_plan_ref' => 'sampling_plan_ref', 'customer_submitted_info' => 'customer_submitted_info',
        'expiry_date' => 'expiry_date', 'production_date' => 'production_date', 'comercial_brand' => 'product_name',
    ];

    public function __construct(private readonly LaboratoryWorkflowOwnership $ownership) {}

    public function sync(VAPSampleEntry $sampleEntry): ?CollectionProduct
    {
        return DB::transaction(function () use ($sampleEntry): ?CollectionProduct {
            $labId = (int) $sampleEntry->getOriginal('lab_id');

            if (! VAPLab::query()->whereKey($labId)->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['lab_id' => 'O laboratório da amostra não está disponível.']);
            }

            $entry = VAPSampleEntry::withTrashed()->whereKey($sampleEntry->getKey())
                ->where('lab_id', $labId)->lockForUpdate()->first();

            if (! $entry) {
                throw ValidationException::withMessages(['lab_id' => 'A amostra já não pertence ao laboratório indicado.']);
            }

            if ($entry->collection_product_id) {
                return $this->existingCollection($entry);
            }

            if ($entry->trashed()) {
                throw ValidationException::withMessages(['collection_product_id' => 'Uma amostra arquivada não pode gerar uma nova recolha.']);
            }

            return $this->createCollection($entry);
        });
    }

    private function existingCollection(VAPSampleEntry $sampleEntry): CollectionProduct
    {
        $type = data_get($sampleEntry->client_submitted_info, 'linked_collection_type', data_get($sampleEntry->client_submitted_info, 'collection_type', 'direct'));
        $conflictingOwner = VAPSampleEntry::withTrashed()
            ->where('collection_product_id', $sampleEntry->collection_product_id)
            ->whereKeyNot($sampleEntry->id)->exists();

        $record = $this->ownership
            ->collectionAccessionsForLaboratory((int) $sampleEntry->lab_id, $type, includeArchivedEntries: true)
            ->withTrashed()->whereKey($sampleEntry->collection_product_id)
            ->where('customer_id', $sampleEntry->customer_id)
            ->where('warehouse_id', $sampleEntry->warehouse_id)
            ->whereHas('code')->lockForUpdate()->first();

        if ($conflictingOwner || ! $record) {
            throw ValidationException::withMessages(['collection_product_id' => 'A recolha não tem uma ligação única e válida a esta amostra.']);
        }

        return $record->load(['code', 'product']);
    }

    /**
     * The caller holds the laboratory, intake, and accession locks in a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function correctMetadata(VAPSampleEntry $entry, CollectionProduct $record, array $data): void
    {
        $changes = [];
        $submitted = $data['client_submitted_info'] ?? [];

        foreach (self::METADATA_FIELDS as $collectionField => $entryField) {
            if (array_key_exists($entryField, $submitted)) {
                $changes[$collectionField] = $submitted[$entryField];
            }
        }

        foreach (['packaging_id' => 'pack_id', 'obs' => 'obs', 'collected_by_lab' => 'collected_by_lab'] as $entryField => $collectionField) {
            if (array_key_exists($entryField, $data)) {
                $changes[$collectionField] = $data[$entryField];
            }
        }

        if (array_key_exists('collected_at', $data)) {
            $changes['collection_date'] = $entry->collected_at?->toDateString();
        }

        $record->update($changes);
        $collection = Collection::query()->lockForUpdate()->findOrFail($record->collection_id);
        $subject = $collection->collectionable()->lockForUpdate()->firstOrFail();
        $subjectChanges = [];

        if (array_key_exists('collection_date', $changes)) {
            $subjectChanges['col_date'] = $changes['collection_date'];
            $analyses = $this->ownership->analysesForLaboratory((int) $entry->lab_id)
                ->where('cl_id', $record->code->id)->orderBy('id')->lockForUpdate()->get();

            foreach ($analyses as $analysis) {
                $analysis->update(['col_date' => $changes['collection_date']]);
            }
        }

        if ($collection->collectionable_type === 'programmed') {
            foreach (['collection_location', 'vehicle_reference'] as $field) {
                if (array_key_exists($field, $submitted)) {
                    $subjectChanges[$field] = $submitted[$field];
                }
            }

            if (array_key_exists('received_at', $data)) {
                $subjectChanges['entry_date'] = $entry->received_at?->toDateString();
            }
        }

        $subject->update($subjectChanges);
    }

    /** @param array<string, mixed> $data */
    public function mirrorAccessionMetadata(VAPSampleEntry $entry, array $data): void
    {
        $changes = [];
        $payload = $entry->client_submitted_info ?? [];

        foreach (self::METADATA_FIELDS as $collectionField => $entryField) {
            if (array_key_exists($collectionField, $data)) {
                $payload[$entryField] = $data[$collectionField];
            }
        }

        foreach (['collection_location', 'vehicle_reference'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        foreach (['pack_id' => 'packaging_id', 'obs' => 'obs', 'collected_by_lab' => 'collected_by_lab'] as $collectionField => $entryField) {
            if (array_key_exists($collectionField, $data)) {
                $changes[$entryField] = $data[$collectionField];
            }
        }

        if (array_key_exists('collection_date', $data) && $entry->collected_at?->toDateString() !== $data['collection_date']) {
            $changes['collected_at'] = $data['collection_date'];
        }

        $entry->update($changes + ['client_submitted_info' => $payload]);
    }

    private function createCollection(VAPSampleEntry $sampleEntry): ?CollectionProduct
    {
        $productId = data_get($sampleEntry->client_submitted_info, 'product_id');

        if (! $productId) {
            return null;
        }

        if (! Warehouse::query()->whereKey($sampleEntry->warehouse_id)
            ->where('customer_id', $sampleEntry->customer_id)->lockForUpdate()->first()) {
            throw ValidationException::withMessages(['warehouse_id' => 'O local deve pertencer ao cliente da amostra.']);
        }

        $product = Product::query()->with('matrix.profiles.type')->lockForUpdate()->find($productId);

        if (! $product) {
            throw ValidationException::withMessages(['client_submitted_info.product_id' => 'O produto seleccionado não está disponível.']);
        }

        $matrixId = data_get($sampleEntry->client_submitted_info, 'matrix_id');

        if ($matrixId && (int) $matrixId !== (int) $product->matrix_id) {
            throw ValidationException::withMessages(['client_submitted_info.matrix_id' => 'A matriz seleccionada não corresponde ao produto.']);
        }

        $availableProfiles = ($product->matrix?->profiles ?? collect())
            ->when($sampleEntry->department_id, function ($profiles) use ($sampleEntry) {
                return $profiles->filter(
                    fn (Profile $profile) => (int) $profile->type?->department_id === (int) $sampleEntry->department_id
                );
            })
            ->values();

        $profileIds = collect(
            data_get($sampleEntry->client_submitted_info, 'requested_profile_ids', [])
        )
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($profileIds->isEmpty()) {
            $profileIds = $availableProfiles->pluck('id')->values();
        }

        if ($profileIds->isEmpty() || $profileIds->diff($availableProfiles->pluck('id'))->isNotEmpty()) {
            throw ValidationException::withMessages(['client_submitted_info.requested_profile_ids' => 'Seleccione perfis válidos para a matriz e o departamento da amostra.']);
        }

        $profiles = $availableProfiles->whereIn('id', $profileIds)->sortBy('id')->values();
        $profileIds = $profiles->pluck('id')->values();

        $collectionType = data_get($sampleEntry->client_submitted_info, 'collection_type', 'direct') === 'programmed'
            ? 'programmed'
            : 'direct';

        $collectionSubject = $this->createCollectionSubject($sampleEntry, $collectionType);

        $collection = $collectionSubject->collection()->save(new Collection([
            'customer_id' => $sampleEntry->customer_id,
            'warehouse_id' => $sampleEntry->warehouse_id,
        ]));

        $collectionProduct = CollectionProduct::query()->create([
            'collection_id' => $collection->id,
            'customer_id' => $sampleEntry->customer_id,
            'warehouse_id' => $sampleEntry->warehouse_id,
            'product_id' => $product->id,
            'pack_id' => $sampleEntry->packaging_id,
            'obs' => $sampleEntry->obs,
            'qty' => data_get($sampleEntry->client_submitted_info, 'quantity', 1),
            'collected_qty' => data_get($sampleEntry->client_submitted_info, 'collected_qty'),
            'lot' => data_get($sampleEntry->client_submitted_info, 'lot'),
            'origin' => data_get($sampleEntry->client_submitted_info, 'origin'),
            'location' => data_get($sampleEntry->client_submitted_info, 'location'),
            'temperature_value' => data_get($sampleEntry->client_submitted_info, 'temperature_value'),
            'container_no' => data_get($sampleEntry->client_submitted_info, 'container_no'),
            'du_no' => data_get($sampleEntry->client_submitted_info, 'du_no'),
            'term_no' => data_get($sampleEntry->client_submitted_info, 'term_no'),
            'bl' => data_get($sampleEntry->client_submitted_info, 'bl'),
            'sampling_plan_ref' => data_get($sampleEntry->client_submitted_info, 'sampling_plan_ref'),
            'customer_submitted_info' => data_get($sampleEntry->client_submitted_info, 'customer_submitted_info')
                ?: data_get($sampleEntry->client_submitted_info, 'integrity_observations')
                ?: data_get($sampleEntry->client_submitted_info, 'chain_of_custody_notes'),
            'expiry_date' => data_get($sampleEntry->client_submitted_info, 'expiry_date'),
            'production_date' => data_get($sampleEntry->client_submitted_info, 'production_date'),
            'comercial_brand' => data_get($sampleEntry->client_submitted_info, 'product_name', $sampleEntry->name),
            'processed' => true,
            'collected_by_lab' => (bool) $sampleEntry->collected_by_lab,
            'collection_date' => optional($sampleEntry->collected_at ?? $sampleEntry->received_at)->toDateString(),
            'progress' => $this->resolveTrackingProgress($sampleEntry),
            'sample_status' => $sampleEntry->status,
            'analysis_start_date' => optional($sampleEntry->analysis_start_date)?->toDateString(),
            'analysis_end_date' => optional($sampleEntry->analysis_end_date)?->toDateString(),
            'extra_data' => [
                'sample_entry_id' => $sampleEntry->id,
                'analysis_start_date' => optional($sampleEntry->analysis_start_date)?->toDateString(),
                'sample_status' => $sampleEntry->status,
                'request_reference' => data_get($sampleEntry->client_submitted_info, 'request_reference'),
                'request_title' => data_get($sampleEntry->client_submitted_info, 'request_title'),
                'request_origin' => data_get($sampleEntry->client_submitted_info, 'request_origin', 'client'),
                'collection_type' => $collectionType,
                'sampling_plan_ref' => data_get($sampleEntry->client_submitted_info, 'sampling_plan_ref'),
                'submitted_payload' => $sampleEntry->client_submitted_info,
            ],
        ]);

        $code = LabCode::query()->create([
            'code' => '',
            'codeable_type' => 'analysis',
            'cl_month' => Carbon::now()->format('y/m'),
            'collection_id' => $collectionProduct->id,
        ]);

        $samples = [];

        foreach ($profiles as $profile) {
            $sample = Sample::query()->create([
                'code' => '',
                'sample_month' => Carbon::now()->format('y/m'),
                'cl_id' => $code->id,
            ]);

            $analysis = Analysis::query()->create([
                'department_id' => $profile->type?->department_id,
                'sample_id' => $sample->id,
                'profile_id' => $profile->id,
                'col_date' => optional($sampleEntry->collected_at ?? $sampleEntry->received_at)->toDateString() ?? now()->toDateString(),
                'entry_date' => now()->toDateString(),
                'type_id' => $profile->category_id,
                'cl_id' => $code->id,
                'product_id' => $product->id,
            ]);

            $analysis->codeable()->save($code);
            $samples[] = $sample->id;
        }

        $payload = collect($sampleEntry->client_submitted_info ?? [])
            ->merge([
                'product_id' => $product->id,
                'matrix_id' => $product->matrix_id,
                'requested_profile_ids' => $profileIds->all(),
                'resolved_profile_ids' => $profileIds->all(),
                'linked_lab_code_id' => $code->id,
                'linked_sample_ids' => $samples,
                'linked_collection_type' => $collectionType,
            ])
            ->all();

        $sampleEntry->forceFill([
            'collection_product_id' => $collectionProduct->id,
            'client_submitted_info' => $payload,
        ])->save();

        return $collectionProduct->fresh(['code', 'product']);
    }

    private function createCollectionSubject(VAPSampleEntry $sampleEntry, string $collectionType): DirectCollection|ProgrammedCollection
    {
        if ($collectionType === 'programmed') {
            return ProgrammedCollection::query()->create([
                'user_id' => $sampleEntry->received_by_id,
                'col_date' => optional($sampleEntry->collected_at ?? $sampleEntry->received_at)->toDateString() ?? now()->toDateString(),
                'entry_date' => optional($sampleEntry->received_at)->toDateString() ?? now()->toDateString(),
                'collection_location' => data_get($sampleEntry->client_submitted_info, 'collection_location', data_get($sampleEntry->client_submitted_info, 'location')),
                'vehicle_reference' => data_get($sampleEntry->client_submitted_info, 'vehicle_reference'),
                'placed_analysis' => true,
                'status' => true,
            ]);
        }

        return DirectCollection::query()->create([
            'description' => 'Fluxo originado pela validação da amostra '.($sampleEntry->code ?: $sampleEntry->name),
            'col_date' => optional($sampleEntry->collected_at ?? $sampleEntry->received_at)->toDateString() ?? now()->toDateString(),
        ]);
    }

    private function resolveTrackingProgress(VAPSampleEntry $sampleEntry): CollectionProductTrackingStatus
    {
        return match ($sampleEntry->status) {
            'EN_PROGRESO' => CollectionProductTrackingStatus::ANALYSIS_IN_PROGRESS,
            'COMPLETADO' => CollectionProductTrackingStatus::ANALYSIS_COMPLETED,
            default => CollectionProductTrackingStatus::PENDING_ANALYSIS,
        };
    }
}
