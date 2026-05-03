<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ImportCompletedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Import Completed')
            ->line('Your CSV import has completed successfully.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Import Completed',
            'message' => 'Your CSV import has completed successfully.',
            'sender_id' => $notifiable->id,
            'sender_name' => $notifiable->name,
        ];
    }
}
