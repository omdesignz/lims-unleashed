<?php

namespace App\Notifications;

use App\Support\WhiteLabelMessageDefaults;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GlobalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $title,
        public ?string $message,
        public object $sender,
        public string $type = 'info',
        public string $priority = 'normal',
        public ?string $actionUrl = null,
        public ?string $actionLabel = null
    ) {
        $this->afterCommit();
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            'database',
            'broadcast',
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return (new MailMessage)
            ->subject($this->title ?: $defaults->notificationTitle())
            ->greeting($defaults->mailGreeting())
            ->line($defaults->notificationEmailIntro())
            ->line($this->message ?: $defaults->notificationMessage())
            ->action('Abrir plataforma', url('/'))
            ->line($defaults->notificationEmailOutro())
            ->salutation($defaults->salutationWithSignature());
    }

    public function toDatabase($notifiable)
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return [
            'title' => $this->title ?: $defaults->notificationTitle(),
            'message' => $this->message ?: $defaults->notificationMessage(),
            'type' => $this->type,
            'priority' => $this->priority,
            'sender_id' => $this->sender->id ?? null,
            'sender_name' => $defaults->senderAlias($this->sender->name ?? null),
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toDatabase($notifiable)))->onQueue('broadcasts');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
