<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ImportFailedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Import Failed')
            ->line('Your CSV import has failed. Please check and try again.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Import Failed',
            'message' => 'Your CSV import has failed. Please check and try again.',
            'sender_id' => $notifiable->id,
            'sender_name' => $notifiable->name,
        ];
    }
}
