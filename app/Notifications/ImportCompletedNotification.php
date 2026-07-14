<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompletedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Importação concluída')
            ->line('A importação do ficheiro CSV foi concluída com sucesso.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Importação concluída',
            'message' => 'A importação do ficheiro CSV foi concluída com sucesso.',
            'sender_id' => $notifiable->id,
            'sender_name' => $notifiable->name,
        ];
    }
}
