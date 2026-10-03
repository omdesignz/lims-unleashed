<?php

namespace App\Actions;

use App\Models\VAPProposal;
use App\Services\ProposalNotificationOwnership;
use App\Services\ProposalPdfArtifacts;
use App\Services\ProposalPdfDocument;
use App\Services\ProposalStaffAccess;
use App\Support\ProposalWorkflowNotifier;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendProposal
{
    public function __construct(
        private readonly ProposalStaffAccess $access,
        private readonly ProposalNotificationOwnership $ownership,
        private readonly ProposalPdfDocument $document,
        private readonly ProposalPdfArtifacts $artifacts,
        private readonly ProposalWorkflowNotifier $notifier
    ) {}

    public function execute(int $labId, int $userId, VAPProposal $snapshot): VAPProposal
    {
        $proposal = DB::transaction(function () use ($labId, $userId, $snapshot): VAPProposal {
            ['proposal' => $proposal] = $this->access->lock($labId, $userId, $snapshot, 'edit_proposals');
            $this->assertSendable($proposal);

            return $proposal;
        }, 3);
        $rendered = $this->document->render($proposal);

        return DB::transaction(function () use ($labId, $userId, $proposal, $rendered): VAPProposal {
            ['proposal' => $current, 'operator' => $operator] = $this->access->lock($labId, $userId, $proposal, 'edit_proposals');
            $this->assertSendable($current);
            $this->document->assertUnchanged($current, $rendered['fingerprint']);
            $previousPath = $current->file_path;
            $path = $this->artifacts->store($current, $rendered['filename'], $rendered['content']);
            $current->fill(['status' => 'SENT', 'file_path' => $path]);
            abort_unless($current->save(), 409, 'Não foi possível registar o envio da proposta.');
            $audit = activity()->performedOn($current)->causedBy($operator)->log('sent');
            abort_unless($audit?->exists, 409, 'Não foi possível registar o histórico do envio.');
            $this->access->lock($labId, $userId, $proposal, 'edit_proposals');
            $context = $this->ownership->context($current);
            $this->artifacts->removeAfterCommit($current->id, $previousPath);
            DB::afterCommit(function () use ($context, $path): void {
                try {
                    $fresh = $this->ownership->resolve($context);
                    if ($fresh && $fresh->status === 'SENT' && $fresh->file_path === $path) {
                        $this->notifier->notifySent($fresh);
                    }
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

            return $current;
        }, 3);
    }

    private function assertSendable(VAPProposal $proposal): void
    {
        abort_unless(in_array($proposal->status, ['PENDING', 'REVISED'], true), 409,
            'A proposta só pode ser enviada quando estiver pendente ou revista.');
        abort_unless(filled($proposal->unique_hash), 409, 'A proposta não tem uma ligação pública válida.');
    }
}
