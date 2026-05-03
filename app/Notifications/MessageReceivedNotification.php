<?php

namespace App\Notifications;

use App\Models\Message;
use App\Support\WhiteLabelMessageDefaults;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return (new MailMessage)
            ->subject($defaults->notificationTitle('Nova mensagem recebida'))
            ->greeting($defaults->mailGreeting("Olá, {$notifiable->name}."))
            ->line($defaults->notificationEmailIntro('Recebeu uma nova mensagem na plataforma.'))
            ->line($this->message->message)
            ->action('Abrir mensagem', url('/messages/' . $this->message->id))
            ->line($defaults->notificationEmailOutro())
            ->salutation($defaults->salutationWithSignature());
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return [
            'title' => $defaults->notificationTitle('Nova mensagem recebida'),
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toBroadcast(object $notifiable): object
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return new BroadcastMessage([
            'title' => $defaults->notificationTitle('Nova mensagem recebida'),
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
        ]);
    }
}
