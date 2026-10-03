<?php

namespace App\Services;

use App\Models\VAPProposal;

class PublicProposalAccess
{
    public function __construct(private readonly ProposalNotificationOwnership $ownership) {}

    public function read(string $hash): VAPProposal
    {
        abort_unless(filled($hash), 404);
        $proposal = VAPProposal::withoutGlobalScope('proposal_laboratory')->where('unique_hash', $hash)->firstOrFail();

        return $this->current($proposal);
    }

    public function current(VAPProposal $snapshot): VAPProposal
    {
        $proposal = filled($snapshot->unique_hash) ? $this->ownership->resolve($this->ownership->context($snapshot)) : null;
        abort_unless($proposal, 404);

        return $proposal;
    }
}
