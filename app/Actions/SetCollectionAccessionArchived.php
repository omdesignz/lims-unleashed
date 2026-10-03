<?php

namespace App\Actions;

use App\Models\VAPSampleEntry;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Support\Facades\DB;

class SetCollectionAccessionArchived
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly LaboratoryWorkflowMutationAccess $access,
    ) {}

    /** @param list<int> $recordIds */
    public function execute(int $labId, int $userId, string $type, array $recordIds, bool $archived): int
    {
        abort_unless(in_array($type, ['direct', 'programmed'], true) && $recordIds !== [], 404);

        return DB::transaction(function () use ($labId, $userId, $type, $recordIds, $archived): int {
            $operator = $this->access->operator($userId, $labId, ($archived ? 'delete_' : 'restore_').$type.'_collections');
            $recordIds = array_values(array_unique($recordIds));
            sort($recordIds);
            $entries = VAPSampleEntry::withTrashed()->where('lab_id', $labId)
                ->whereIn('collection_product_id', $recordIds)->orderBy('id')->lockForUpdate()->get();
            $records = $this->ownership->collectionAccessionsForLaboratory($labId, $type, includeArchivedEntries: true)
                ->withTrashed()
                ->whereIn('collection_product.id', $recordIds)->orderBy('id')->lockForUpdate()->get();

            abort_unless($records->count() === count($recordIds) && $entries->count() === count($recordIds), 404);
            $changed = 0;

            foreach ($records as $record) {
                $entry = $entries->where('collection_product_id', $record->id);
                abort_unless($entry->count() === 1, 404);
                $entry = $entry->first();

                if ($record->trashed() === $archived && $entry->trashed() === $archived) {
                    continue;
                }

                if ($archived) {
                    $record->deleteQuietly();
                    $entry->deleteQuietly();
                } else {
                    $record->restoreQuietly();
                    $entry->restoreQuietly();
                }

                activity()->causedBy($operator)->performedOn($record)
                    ->withProperties(['lab_id' => $labId, 'sample_entry_id' => $entry->id])
                    ->log($archived ? 'arquivou a colheita e a entrada de amostra' : 'restaurou a colheita e a entrada de amostra');
                $changed++;
            }

            return $changed;
        });
    }
}
