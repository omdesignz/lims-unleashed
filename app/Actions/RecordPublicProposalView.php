<?php

namespace App\Actions;

use App\Models\VAPProposal;
use App\Services\ProposalNotificationOwnership;
use Illuminate\Support\Facades\DB;

class RecordPublicProposalView
{
    public function __construct(private readonly ProposalNotificationOwnership $ownership) {}

    public function execute(string $hash, ?string $clientIp): VAPProposal
    {
        return DB::transaction(function () use ($hash, $clientIp): VAPProposal {
            $proposal = VAPProposal::withoutGlobalScope('proposal_laboratory')->where('unique_hash', $hash)
                ->lockForUpdate()->firstOrFail();
            abort_unless(filled($hash) && $this->ownership->resolve($this->ownership->context($proposal)), 404);
            if ($proposal->status === 'SENT') {
                $proposal->status = 'VIEWED';
                abort_unless($proposal->save(), 409, 'Não foi possível registar a consulta da proposta.');
                $audit = activity()->performedOn($proposal)->withProperties(['client_ip' => $clientIp])->log('viewed_by_client');
                abort_unless($audit?->exists, 409, 'Não foi possível registar o histórico da consulta.');
            }

            return $proposal;
        }, 3);
    }
}
