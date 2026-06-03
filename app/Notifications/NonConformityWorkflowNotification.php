<?php

namespace App\Notifications;

use App\Models\VAPNonConformity;
use App\Support\WhiteLabelMessageDefaults;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NonConformityWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VAPNonConformity $nonConformity,
        public string $title,
        public string $message
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return (new MailMessage)
            ->subject($defaults->notificationTitle($this->title))
            ->greeting($defaults->mailGreeting())
            ->line($defaults->notificationEmailIntro($this->message))
            ->line(sprintf('NC: %s - %s', $this->nonConformity->nc_number, $this->nonConformity->title))
            ->action('Abrir não conformidade', route('vap_non_conformities.show', $this->nonConformity))
            ->line($defaults->notificationEmailOutro())
            ->salutation($defaults->salutationWithSignature());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => 'non_conformity',
            'url' => route('vap_non_conformities.show', $this->nonConformity),
            'non_conformity_id' => $this->nonConformity->id,
            'nc_number' => $this->nonConformity->nc_number,
            'severity' => $this->nonConformity->severity,
            'status' => $this->nonConformity->status,
        ];
    }
}
