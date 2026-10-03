<?php

namespace App\Actions;

use App\Models\CollectionProduct;
use App\Models\LabCode;
use App\Models\Sample;
use App\Models\VAPSampleEntry;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Support\Facades\DB;

class SetAnalysisArchived
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly LaboratoryWorkflowMutationAccess $access,
    ) {}

    /** @param list<int> $recordIds */
    public function execute(int $labId, int $userId, array $recordIds, bool $archived): int
    {
        abort_unless($recordIds !== [], 404);

        return DB::transaction(function () use ($labId, $userId, $recordIds, $archived): int {
            $operator = $this->access->operator($userId, $labId, $archived ? 'delete_analysis' : 'restore_analysis');
            $recordIds = array_values(array_unique($recordIds));
            sort($recordIds);
            $records = $this->ownership->analysesForLaboratory($labId)->withTrashed()
                ->whereIn('analysis.id', $recordIds)->orderBy('analysis.id')->lockForUpdate()->get();
            abort_unless($records->count() === count($recordIds), 404);

            foreach ($records as $record) {
                $sample = Sample::query()->lockForUpdate()->findOrFail($record->sample_id);
                $code = LabCode::query()->lockForUpdate()->findOrFail($sample->cl_id);
                $product = CollectionProduct::query()->lockForUpdate()->findOrFail($code->collection_id);
                VAPSampleEntry::query()->where('lab_id', $labId)->where('collection_product_id', $product->id)
                    ->lockForUpdate()->firstOrFail();
                abort_unless($this->ownership->analysesForLaboratory($labId)->withTrashed()->whereKey($record->id)->exists(), 404);
            }

            $changed = 0;

            foreach ($records as $record) {
                if ($record->trashed() === $archived) {
                    continue;
                }

                abort_unless($archived ? $record->delete() : $record->restore(), 409,
                    'A alteração da análise foi cancelada. Nenhuma análise do lote foi modificada.');

                activity()->causedBy($operator)->performedOn($record)
                    ->withProperties(['lab_id' => $labId, 'sample_id' => $record->sample_id, 'lab_code_id' => $record->cl_id])
                    ->log($archived ? 'arquivou a análise' : 'restaurou a análise');
                $changed++;
            }

            return $changed;
        }, 3);
    }
}
