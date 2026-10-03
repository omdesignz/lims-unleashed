<?php

namespace App\Actions;

use App\Models\VAPProposal;
use App\Services\ProposalPdfDocument;
use App\Services\ProposalStaffAccess;
use Illuminate\Support\Facades\DB;

class DownloadStaffProposalPdf
{
    public function __construct(private readonly ProposalStaffAccess $access, private readonly ProposalPdfDocument $document) {}

    /** @return array{content: string, renderer: string, filename: string, fingerprint: string} */
    public function execute(int $labId, int $userId, VAPProposal $snapshot): array
    {
        $proposal = DB::transaction(fn (): VAPProposal => $this->access->lock($labId, $userId, $snapshot, 'view_proposals')['proposal'], 3);
        $rendered = $this->document->render($proposal);
        DB::transaction(function () use ($labId, $userId, $proposal, $rendered): void {
            $current = $this->access->lock($labId, $userId, $proposal, 'view_proposals')['proposal'];
            $this->document->assertUnchanged($current, $rendered['fingerprint']);
        }, 3);

        return $rendered;
    }
}
