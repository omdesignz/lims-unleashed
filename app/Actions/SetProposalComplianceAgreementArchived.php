<?php

namespace App\Actions;

use App\Models\ProposalComplianceAgreement;
use App\Models\VAPProposal;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SetProposalComplianceAgreementArchived
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
            $parentIds = ProposalComplianceAgreement::withoutGlobalScope('proposal_laboratory')->withTrashed()
                ->whereKey($recordIds)->pluck('proposal_id');
            $parents = VAPProposal::withoutGlobalScope('proposal_laboratory')->withTrashed()
                ->where('lab_id', $labId)->whereKey($parentIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $records = ProposalComplianceAgreement::withoutGlobalScope('proposal_laboratory')->withTrashed()
                ->whereIn('proposal_id', $parents->keys())->whereKey($recordIds)->orderBy('id')->lockForUpdate()->get();
            abort_unless($records->count() === count($recordIds), 404);

            if (! $archived) {
                $restoring = $records->filter(fn (ProposalComplianceAgreement $agreement): bool => $agreement->trashed());
                $restoringParents = $restoring->pluck('proposal_id');
                $hasActiveSibling = ProposalComplianceAgreement::withoutGlobalScope('proposal_laboratory')
                    ->whereIn('proposal_id', $restoringParents)->exists();
                $hasArchivedParent = $restoringParents->contains(fn (int $id): bool => $parents->get($id)->trashed());

                if ($hasArchivedParent || $hasActiveSibling || $restoringParents->unique()->count() !== $restoringParents->count()) {
                    throw ValidationException::withMessages([
                        'recordIds' => 'Restaure primeiro a proposta e confirme que não existe outro acordo activo para a mesma proposta.',
                    ]);
                }
            }

            $changed = 0;
            foreach ($records as $record) {
                if ($record->trashed() === $archived) {
                    continue;
                }

                abort_unless($archived ? $record->delete() : $record->restore(), 409);
                activity()->causedBy($operator)->performedOn($parents->get($record->proposal_id))
                    ->withProperties(['lab_id' => $labId, 'agreement_id' => $record->id])
                    ->log($archived ? 'arquivou o acordo da proposta' : 'restaurou o acordo da proposta');
                $changed++;
            }

            return $changed;
        }, 3);
    }
}
