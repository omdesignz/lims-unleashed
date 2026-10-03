<?php

namespace App\Actions;

use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\VAPSampleEntry;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCollectionAccession
{
    private const CORRECTION_FIELDS = [
        'owner_id', 'pack_id', 'temperature_id', 'result_id', 'vehicle_id',
        'comercial_brand', 'du_no', 'term_no', 'container_no', 'temperature_value',
        'recollection', 'obs', 'collected_by_lab', 'expiry_date', 'production_date',
        'collection_date', 'qty', 'collected_qty', 'origin', 'location', 'lot', 'bl',
        'sample_status', 'sampling_plan_ref', 'customer_submitted_info',
    ];

    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly SampleEntryCollectionFlowService $collectionFlow,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(int $labId, int $collectionProductId, int $userId, string $type, array $data): CollectionProduct
    {
        abort_unless(in_array($type, ['direct', 'programmed'], true), 404);

        return DB::transaction(function () use ($labId, $collectionProductId, $userId, $type, $data): CollectionProduct {
            $this->access->operator($userId, $labId, 'edit_'.$type.'_collections');
            $entry = VAPSampleEntry::query()->where('lab_id', $labId)
                ->where('collection_product_id', $collectionProductId)->lockForUpdate()->firstOrFail();
            $this->collectionFlow->sync($entry);
            $record = $this->ownership->collectionAccessionsForLaboratory($labId, $type)
                ->lockForUpdate()->findOrFail($collectionProductId);
            $collection = Collection::query()->lockForUpdate()->findOrFail($record->collection_id);
            abort_unless((int) $collection->customer_id === (int) $entry->customer_id, 404);
            $subject = $collection->collectionable()->lockForUpdate()->firstOrFail();

            foreach (['customer_id', 'warehouse_id', 'product_id', 'collection_id', 'invoice_id'] as $field) {
                if (array_key_exists($field, $data) && (string) $data[$field] !== (string) $record->{$field}) {
                    throw ValidationException::withMessages([$field => 'A identidade do registo não pode ser alterada nesta etapa.']);
                }
            }

            if (! empty($data['owner_id'])) {
                $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $data['owner_id'])
                    ->lockForUpdate()->first();
                $owner = $this->ownership->eligibleUsers($labId)->lockForUpdate()->find($data['owner_id']);

                if (! $membership || ! $owner) {
                    throw ValidationException::withMessages(['owner_id' => 'Escolha um responsável activo e verificado deste laboratório.']);
                }
            }

            $record->update(Arr::only($data, self::CORRECTION_FIELDS));

            $subjectData = array_key_exists('collection_date', $data) ? ['col_date' => $data['collection_date']] : [];

            if ($type === 'programmed') {
                $subjectData += Arr::only($data, ['collection_location', 'vehicle_reference']);
            }

            $subject->update($subjectData);

            if (array_key_exists('collection_date', $data)) {
                $analyses = $this->ownership->analysesForLaboratory($labId)
                    ->whereHas('sample.collection', fn ($codes) => $codes->where('collection_id', $record->id))
                    ->lockForUpdate()->get();

                foreach ($analyses as $analysis) {
                    $analysis->update(['col_date' => $data['collection_date']]);
                }
            }

            if (array_key_exists('collaborations', $data)) {
                $collection->collaborations()->sync(Arr::pluck($data['collaborations'] ?? [], 'collaboration_id'));
            }

            if (array_key_exists('collectionreasons', $data)) {
                $collection->reasons()->sync(Arr::pluck($data['collectionreasons'] ?? [], 'reason_id'));
            }

            $this->collectionFlow->mirrorAccessionMetadata($entry, $data);

            return $record;
        });
    }
}
