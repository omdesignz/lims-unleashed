<?php

namespace App\Notifications;

use App\Models\Rating;
use App\Support\WhiteLabelMessageDefaults;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RatingSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Rating $rating) {}

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
        $subject = $defaults->notificationTitle('Nova avaliação recebida');

        return (new MailMessage)
            ->subject($subject)
            ->greeting($defaults->mailGreeting())
            ->line($defaults->notificationEmailIntro('Foi recebida uma nova avaliação para melhoria contínua.'))
            ->line($this->summaryLine())
            ->action('Ver avaliações', route('ratings.index'))
            ->line($defaults->notificationEmailOutro())
            ->salutation($defaults->salutationWithSignature());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Nova avaliação recebida',
            'message' => $this->summaryLine(),
            'type' => 'rating_submitted',
            'url' => route('ratings.index'),
            'rating_id' => $this->rating->id,
            'rateable_type' => $this->rating->rateable_type,
            'rateable_id' => $this->rating->rateable_id,
            'channel' => $this->rating->channel,
        ];
    }

    private function summaryLine(): string
    {
        return sprintf(
            'Avaliação %s registada para %s #%s.',
            $this->rating->channel === 'portal' ? 'do portal' : 'interna',
            $this->rating->rateable_type,
            $this->rating->rateable_id
        );
    }
}
