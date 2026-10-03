<?php

namespace App\Services;

use App\Models\User;
use App\Models\VAPProposal;

class ProposalStaffAccess
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly ProposalNotificationOwnership $ownership
    ) {}

    /** @return array{proposal: VAPProposal, operator: User} */
    public function lock(int $labId, int $userId, VAPProposal $snapshot, string $permission): array
    {
        $operator = $this->access->operator($userId, $labId, $permission);
        $proposal = VAPProposal::withoutGlobalScope('proposal_laboratory')->where('lab_id', $labId)
            ->whereKey($snapshot->getKey())->lockForUpdate()->firstOrFail();
        abort_unless($this->ownership->resolve($this->ownership->context($snapshot)), 404);

        return ['proposal' => $proposal, 'operator' => $operator];
    }
}
