<?php

namespace App\Notifications;

use App\Support\WhiteLabelMessageDefaults;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperationalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload)
    {
        $this->afterCommit();
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->payload['channels'] ?? ['database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $defaults = WhiteLabelMessageDefaults::current();
        $mail = (new MailMessage)
            ->subject($this->payload['email_subject'] ?? $this->payload['title'])
            ->greeting($defaults->mailGreeting())
            ->line($this->payload['email_message'] ?? $this->payload['message']);

        if (filled($this->payload['action_url'] ?? null)) {
            $mail->action($this->payload['action_label'] ?: 'Abrir plataforma', $this->payload['action_url']);
        }

        return $mail
            ->line($defaults->notificationEmailOutro())
            ->salutation($defaults->salutationWithSignature());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->databasePayload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->databasePayload()))->onQueue('broadcasts');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->databasePayload();
    }

    /**
     * @return array<string, mixed>
     */
    private function databasePayload(): array
    {
        return collect($this->payload)->only([
            'key', 'category', 'priority', 'title', 'message', 'action_label', 'action_url', 'sender_id', 'sender_name', 'context',
        ])->all();
    }
}
