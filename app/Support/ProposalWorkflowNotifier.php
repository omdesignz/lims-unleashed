<?php

namespace App\Support;

use App\Models\VAPProposal;
use App\Services\ProposalNotificationOwnership;
use Illuminate\Support\Collection;

class ProposalWorkflowNotifier
{
    public function __construct(
        private readonly NotificationTemplateService $templates,
        private readonly ProposalNotificationOwnership $ownership
    ) {}

    public function notifySent(VAPProposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'warehouse', 'user']);

        if ($proposal->warehouse) {
            $this->templates->notify([$proposal->warehouse], 'commercial.proposal.sent_customer', [
                ...$this->ownership->context($proposal),
                'document_number' => $proposal->proposal_number,
                'document_url' => route('vap-proposals.public.show', $proposal->unique_hash),
            ]);
        }

        $this->notifyInternalUsers(
            $proposal,
            'enviada',
            'Disponível para acompanhamento.'
        );
    }

    public function notifyRevised(VAPProposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'warehouse', 'user']);

        if ($proposal->warehouse) {
            $this->notifyPortalCustomer($proposal, 'revista', 'Consulte a versão actualizada no portal.');
        }

        $this->notifyInternalUsers(
            $proposal,
            'revista',
            'Requer acompanhamento comercial.'
        );
    }

    public function notifyAccepted(VAPProposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'warehouse', 'user']);

        $this->notifyInternalUsers(
            $proposal,
            'aceite',
            'O trabalho já pode seguir para execução.'
        );

        if ($proposal->warehouse) {
            $this->notifyPortalCustomer($proposal, 'aceite', 'A aceitação foi registada com sucesso.');
        }
    }

    public function notifyRejected(VAPProposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'warehouse', 'user']);

        $this->notifyInternalUsers(
            $proposal,
            'rejeitada',
            'Reveja os detalhes comerciais antes de avançar.'
        );

        if ($proposal->warehouse) {
            $this->notifyPortalCustomer($proposal, 'rejeitada', 'A rejeição foi registada no portal do cliente.');
        }
    }

    private function notifyInternalUsers(VAPProposal $proposal, string $status, string $detail): void
    {
        $this->templates->notify($this->internalRecipients($proposal), 'commercial.proposal.updated', [
            ...$this->ownership->context($proposal),
            'document_number' => $proposal->proposal_number,
            'status' => $status,
            'detail' => $detail,
            'document_url' => route('vap-proposals.show', $proposal),
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function internalRecipients(VAPProposal $proposal)
    {
        return collect([$proposal->user])
            ->filter()
            ->unique(fn (object $recipient) => get_class($recipient).':'.$recipient->getKey())
            ->values();
    }

    private function notifyPortalCustomer(VAPProposal $proposal, string $status, string $detail): void
    {
        $this->templates->notify([$proposal->warehouse], 'commercial.proposal.updated', [
            ...$this->ownership->context($proposal),
            'document_number' => $proposal->proposal_number,
            'status' => $status,
            'detail' => $detail,
            'document_url' => route('vap-proposals.public.show', $proposal->unique_hash),
        ]);
    }
}
