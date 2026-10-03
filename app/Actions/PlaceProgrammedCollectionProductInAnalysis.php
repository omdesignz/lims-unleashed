<?php

namespace App\Actions;

use App\Enums\Collections\CollectionProductTrackingStatus;
use App\Events\CollectionProcessed;
use App\Models\Analysis;
use App\Models\Profile;
use App\Models\ProgrammedCollection;
use App\Models\Sample;
use App\Models\VAPSampleEntry;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceProgrammedCollectionProductInAnalysis
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly LaboratoryWorkflowMutationAccess $access,
    ) {}

    public function execute(int $labId, int $collectionProductId, int $programmedCollectionId, int $userId): bool
    {
        return DB::transaction(function () use ($labId, $collectionProductId, $programmedCollectionId, $userId): bool {
            $operator = $this->access->operator($userId, $labId, 'add_analysis');
            $entry = VAPSampleEntry::query()->where('lab_id', $labId)
                ->where('collection_product_id', $collectionProductId)->lockForUpdate()->firstOrFail();
            $collectionProduct = $this->ownership->collectionAccessionsForLaboratory($labId, 'programmed')
                ->lockForUpdate()->findOrFail($collectionProductId);
            $collection = $collectionProduct->collection()->lockForUpdate()->firstOrFail();

            abort_unless($collection->collectionable_type === 'programmed'
                && (int) $collection->collectionable_id === $programmedCollectionId
                && (int) $collection->customer_id === (int) $entry->customer_id, 404);

            $programmedCollection = ProgrammedCollection::query()->lockForUpdate()->findOrFail($programmedCollectionId);
            $code = $this->ownership->labCodesForLaboratory($labId)
                ->where('collection_id', $collectionProductId)->where('codeable_type', 'analysis')
                ->lockForUpdate()->firstOrFail();

            if ($code->samples()->withTrashed()->exists()) {
                return false;
            }

            $collectionProduct->load('product.matrix.profiles.type');
            $profiles = ($collectionProduct->product?->matrix?->profiles ?? collect())
                ->when($entry->department_id, fn ($profiles) => $profiles->filter(
                    fn (Profile $profile): bool => (int) $profile->type?->department_id === (int) $entry->department_id
                ));
            $requestedIds = collect(data_get($entry->client_submitted_info, 'resolved_profile_ids',
                data_get($entry->client_submitted_info, 'requested_profile_ids', [])))
                ->map(fn (mixed $id): int => (int) $id)->unique();

            if ($requestedIds->isNotEmpty() && $requestedIds->diff($profiles->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'client_submitted_info.requested_profile_ids' => 'Os perfis devem corresponder ao produto e ao departamento da amostra.',
                ]);
            }

            $profiles = $profiles->when($requestedIds->isNotEmpty(), fn ($profiles) => $profiles->whereIn('id', $requestedIds))->values();

            if ($profiles->isEmpty() || $profiles->contains(fn (Profile $profile): bool => ! $profile->type?->department_id)) {
                throw ValidationException::withMessages([
                    'product_id' => 'O produto não possui perfis analíticos válidos para esta amostra.',
                ]);
            }

            $customer = $entry->customer()->firstOrFail();
            $sampleIds = [];

            foreach ($profiles as $profile) {
                $sample = Sample::query()->create([
                    'code' => '',
                    'sample_month' => now()->format('y/m'),
                    'cl_id' => $code->id,
                ]);
                $analysis = Analysis::query()->create([
                    'department_id' => $profile->type->department_id,
                    'sample_id' => $sample->id,
                    'profile_id' => $profile->id,
                    'col_date' => $programmedCollection->col_date,
                    'entry_date' => now()->toDateString(),
                    'type_id' => $profile->category_id,
                    'cl_id' => $code->id,
                    'product_id' => $collectionProduct->product_id,
                ]);
                $analysis->codeable()->save($code);
                $sampleIds[] = $sample->id;

                activity()->by($operator)->performedOn($collectionProduct)
                    ->log('colocou em análise a colheita programada CL '.$code->description);
            }

            $programmedCollection->update([
                'placed_analysis' => true,
                'status' => true,
                'entry_date' => now()->toDateString(),
            ]);
            $collectionProduct->update([
                'processed' => true,
                'sample_status' => 'Aceite para análise',
                'progress' => CollectionProductTrackingStatus::PENDING_ANALYSIS,
            ]);
            $entry->update(['client_submitted_info' => array_merge($entry->client_submitted_info ?? [], [
                'linked_lab_code_id' => $code->id,
                'linked_sample_ids' => $sampleIds,
                'linked_collection_type' => 'programmed',
                'requested_profile_ids' => $profiles->pluck('id')->all(),
            ])]);

            broadcast(new CollectionProcessed($operator, $customer, $collectionProduct->id));

            return true;
        });
    }
}
