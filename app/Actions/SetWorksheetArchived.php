<?php

namespace App\Actions;

use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorksheetAccess;
use Illuminate\Support\Facades\DB;

class SetWorksheetArchived
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly LaboratoryWorksheetAccess $worksheets,
    ) {}

    /** @param list<int> $recordIds */
    public function execute(int $labId, int $userId, array $recordIds, bool $archived): int
    {
        abort_unless($recordIds !== [], 404);

        return DB::transaction(function () use ($labId, $userId, $recordIds, $archived): int {
            $operator = $this->access->operator($userId, $labId, $archived ? 'delete_worksheets' : 'restore_worksheets');
            $recordIds = array_values(array_unique($recordIds));
            sort($recordIds);
            $records = [];
            foreach ($recordIds as $id) {
                $records[] = $this->worksheets->lockWorksheet($labId, $id, withTrashed: true);
            }

            $changed = 0;
            foreach ($records as $worksheet) {
                if ($worksheet->trashed() === $archived) {
                    continue;
                }

                abort_unless($archived ? $worksheet->delete() : $worksheet->restore(), 409,
                    'A alteração foi cancelada. Nenhuma folha de trabalho do lote foi modificada.');
                activity()->causedBy($operator)->performedOn($worksheet)->withProperties(['lab_id' => $labId])
                    ->log($archived ? 'arquivou a folha de trabalho' : 'restaurou a folha de trabalho');
                $changed++;
            }

            return $changed;
        }, 3);
    }
}
