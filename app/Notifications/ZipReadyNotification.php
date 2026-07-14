<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ZipReadyNotification extends Notification
{
    use Queueable;

    protected string $zipFileName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $zipFileName)
    {
        $this->zipFileName = $zipFileName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/storage/'.$this->zipFileName);

        return (new MailMessage)
            ->subject('O seu ficheiro ZIP está pronto')
            ->line('O ficheiro ZIP com os ficheiros seleccionados está pronto para ser transferido.')
            ->action('Transferir ZIP', $url)
            ->line('Obrigado por utilizar a nossa aplicação.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'url' => url('/storage/'.$this->zipFileName),
        ];
    }
}
