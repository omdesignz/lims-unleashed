<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportFailedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Falha na importação')
            ->line('A importação do ficheiro CSV falhou. Verifique o ficheiro e tente novamente.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Falha na importação',
            'message' => 'A importação do ficheiro CSV falhou. Verifique o ficheiro e tente novamente.',
            'sender_id' => $notifiable->id,
            'sender_name' => $notifiable->name,
        ];
    }
}
