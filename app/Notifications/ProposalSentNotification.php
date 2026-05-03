<?php

namespace App\Notifications;

use App\Models\Proposal;
use App\Models\VAPProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposalSentNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Proposal|VAPProposal $proposal)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->proposal->loadMissing(['customer']);

        return (new MailMessage)
            ->subject("Proposta {$this->proposal->proposal_number}")
            ->greeting("Olá {$this->proposal->customer->name},")
            ->line('A sua proposta laboratorial foi preparada e está disponível para consulta.')
            ->action('Ver proposta', route('vap-proposals.public.show', $this->proposal->unique_hash))
            ->line('Se aceitar os termos, o laboratório poderá validar o pedido e iniciar o fluxo operacional.')
            ->line('Obrigado por confiar nos nossos serviços laboratoriais.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Proposta {$this->proposal->proposal_number}",
            'message' => 'A proposta está disponível para revisão e aceitação no portal.',
            'proposal_id' => $this->proposal->id,
            'proposal_hash' => $this->proposal->unique_hash,
            'status' => $this->proposal->status,
        ];
    }
}
