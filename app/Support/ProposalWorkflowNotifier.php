<?php

namespace App\Support;

use App\Models\VAPProposal;
use App\Notifications\GlobalNotification;
use App\Notifications\ProposalSentNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\Notifiable;

class ProposalWorkflowNotifier
{
    public function notifySent(VAPProposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'warehouse', 'user']);

        if ($proposal->warehouse) {
            rescue(function () use ($proposal): void {
                $proposal->warehouse->notify(new ProposalSentNotification($proposal));
            }, report: false);
        }

        $this->notifyInternalUsers(
            $proposal,
            'Proposta enviada',
            "A proposta {$proposal->proposal_number} foi enviada ao cliente e está disponível para acompanhamento."
        );
    }

    public function notifyRevised(VAPProposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'warehouse', 'user']);

        if ($proposal->warehouse) {
            $this->notifyDatabase(
                $proposal->warehouse,
                $proposal->user ?? $proposal->warehouse,
                'Proposta revista',
                "A proposta {$proposal->proposal_number} foi revista. Consulte a versão atualizada no portal."
            );
        }

        $this->notifyInternalUsers(
            $proposal,
            'Proposta revista',
            "A proposta {$proposal->proposal_number} foi revista e requer acompanhamento comercial."
        );
    }

    public function notifyAccepted(VAPProposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'warehouse', 'user']);

        $sender = $proposal->warehouse ?? $proposal->user;

        $this->notifyInternalUsers(
            $proposal,
            'Proposta aceite',
            "O cliente aceitou a proposta {$proposal->proposal_number}. O trabalho já pode seguir para execução."
        );

        if ($proposal->warehouse) {
            $this->notifyDatabase(
                $proposal->warehouse,
                $sender,
                'Aceitação registada',
                "A aceitação da proposta {$proposal->proposal_number} foi registada com sucesso."
            );
        }
    }

    public function notifyRejected(VAPProposal $proposal): void
    {
        $proposal->loadMissing(['customer', 'warehouse', 'user']);

        $sender = $proposal->warehouse ?? $proposal->user;

        $this->notifyInternalUsers(
            $proposal,
            'Proposta rejeitada',
            "O cliente rejeitou a proposta {$proposal->proposal_number}. Reveja os detalhes comerciais antes de avançar."
        );

        if ($proposal->warehouse) {
            $this->notifyDatabase(
                $proposal->warehouse,
                $sender,
                'Rejeição registada',
                "A rejeição da proposta {$proposal->proposal_number} foi registada no portal do cliente."
            );
        }
    }

    private function notifyInternalUsers(VAPProposal $proposal, string $title, string $message): void
    {
        foreach ($this->internalRecipients($proposal) as $recipient) {
            $sender = $proposal->warehouse ?? $proposal->user ?? $recipient;
            $this->notifyDatabase($recipient, $sender, $title, $message);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function internalRecipients(VAPProposal $proposal)
    {
        return collect([$proposal->user])
            ->filter()
            ->unique(fn (object $recipient) => get_class($recipient) . ':' . $recipient->getKey())
            ->values();
    }

    private function notifyDatabase(object $recipient, object $sender, string $title, string $message): void
    {
        if (! in_array(Notifiable::class, class_uses_recursive($recipient), true)) {
            return;
        }

        rescue(function () use ($recipient, $sender, $title, $message): void {
            $recipient->notify(new GlobalNotification($title, $message, $sender));
        }, report: false);
    }
}
