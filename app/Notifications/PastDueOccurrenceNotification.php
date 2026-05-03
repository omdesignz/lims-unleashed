<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Occurrence;

class PastDueOccurrenceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $occurrence;

    /**
     * Create a new notification instance.
     */
    public function __construct(Occurrence $occurrence)
    {
        $this->occurrence = $occurrence;
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
        return (new MailMessage)
                ->subject('Ocorrência em Atraso')
                ->line('A data de implementação da ocorrencia é atrasada.')
                ->line('Nº de Ocorrência: ' . $this->occurrence->occurrence_no)
                ->line('Descrição: ' . $this->occurrence->issue_description) // Assuming your Occurrence model has a 'title' attribute
                ->line('Prazo de Implementação: ' . $this->occurrence->implementation_date)
                ->action('Ver', route('occurrences.show', $this->occurrence->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Ocorrencia em Atraso',
            'message' => 'A data de implementação da ocorrencia ' . $this->occurrence->occurrence_no . ' é atrasada.',
            'link' => route('occurrences.show', $this->occurrence->id),
            'sender_id' => null,
            'sender_name' => 'SISTEMA',
        ];
    }
}
