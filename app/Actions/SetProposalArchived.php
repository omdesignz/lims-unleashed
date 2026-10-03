<?php

namespace App\Actions;

use App\Models\VAPProposal;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SetProposalArchived
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param list<int> $recordIds */
    public function execute(int $labId, int $userId, array $recordIds, bool $archived): int
    {
        Validator::make(['recordIds' => $recordIds], [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();

        return DB::transaction(function () use ($labId, $userId, $recordIds, $archived): int {
            $operator = $this->access->operator($userId, $labId, $archived ? 'delete_proposals' : 'restore_proposals');
            $records = VAPProposal::withoutGlobalScope('proposal_laboratory')->withTrashed()
                ->where('lab_id', $labId)->whereKey($recordIds)->orderBy('id')->lockForUpdate()->get();
            abort_unless($records->count() === count($recordIds), 404);

            if ($archived && $records->contains(fn (VAPProposal $proposal): bool => ! $proposal->trashed()
                && ! in_array($proposal->status, ['PENDING', 'REJECTED'], true))) {
                throw ValidationException::withMessages([
                    'recordIds' => 'Só podem ser arquivadas propostas pendentes ou rejeitadas.',
                ]);
            }

            $changed = 0;
            foreach ($records as $record) {
                if ($record->trashed() === $archived) {
                    continue;
                }

                abort_unless($archived ? $record->delete() : $record->restore(), 409);
                activity()->causedBy($operator)->performedOn($record)->withProperties(['lab_id' => $labId])
                    ->log($archived ? 'arquivou a proposta' : 'restaurou a proposta');
                $changed++;
            }

            return $changed;
        }, 3);
    }
}
