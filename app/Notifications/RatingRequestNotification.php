<?php

namespace App\Notifications;

use App\Models\RatingRequest;
use App\Support\WhiteLabelMessageDefaults;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RatingRequestNotification extends Notification
{
    use Queueable;

    protected $ratingRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(RatingRequest $ratingRequest)
    {
        //
        $this->ratingRequest = $ratingRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return (new MailMessage)
            ->subject($defaults->notificationTitle('Novo pedido de avaliação'))
            ->greeting($defaults->mailGreeting("Olá, {$notifiable->name}."))
            ->line($defaults->notificationEmailIntro('Tem um novo item pendente de avaliação.'))
            ->action('Avaliar agora', url(route('rating.create', [
                'rateableType' => $this->ratingRequest->rateable_type,
                'rateableId' => $this->ratingRequest->rateable_id,
            ])))
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

    public function toDatabase(object $notifiable): array
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return [
            'title' => $defaults->notificationTitle('Novo pedido de avaliação'),
            'rateable_type' => $this->ratingRequest->rateable_type,
            'rateable_id' => $this->ratingRequest->rateable_id,
            'url' => url(route('rating.create', [
                'rateableType' => $this->ratingRequest->rateable_type,
                'rateableId' => $this->ratingRequest->rateable_id,
            ])),
            'message' => $defaults->notificationMessage('Tem um novo item pendente de avaliação.'),
        ];
    }
}
