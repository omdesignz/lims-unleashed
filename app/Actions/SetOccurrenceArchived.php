<?php

namespace App\Actions;

use App\Models\Occurrence;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SetOccurrenceArchived
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param list<int> $recordIds */
    public function execute(int $labId, int $userId, array $recordIds, bool $archived): void
    {
        Validator::make(['recordIds' => $recordIds], [
            'recordIds' => ['required', 'array', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();

        DB::transaction(function () use ($labId, $userId, $recordIds, $archived): void {
            $operator = $this->access->operator($userId, $labId, $archived ? 'delete_occurrences' : 'restore_occurrences');
            $records = Occurrence::query()->where('lab_id', $labId)->withTrashed()
                ->whereKey($recordIds)->orderBy('id')->lockForUpdate()->get();
            abort_unless($records->count() === count($recordIds), 404);

            foreach ($records as $record) {
                if ($record->trashed() === $archived) {
                    continue;
                }

                abort_unless($archived ? $record->delete() : $record->restore(), 409);
                activity()->causedBy($operator)->performedOn($record)->withProperties(['lab_id' => $labId])
                    ->log($archived ? 'arquivou a ocorrência' : 'restaurou a ocorrência');
            }
        }, 3);
    }
}
