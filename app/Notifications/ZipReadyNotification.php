<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ZipReadyNotification extends Notification
{
    use Queueable;

    protected $zipFileName;

    /**
     * Create a new notification instance.
     */
    public function __construct($zipFileName)
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
    public function toMail($notifiable)
    {
        $url = url('/storage/' . $this->zipFileName);

        return (new MailMessage)
                    ->subject('Your ZIP file is ready for download')
                    ->line('The ZIP file containing your selected files is ready.')
                    ->action('Download ZIP', $url)
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'url' => url('/storage/' . $this->zipFileName),
        ];
    }
}