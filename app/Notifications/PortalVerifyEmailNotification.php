<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class PortalVerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(Lang::get('Verificar o endereço de correio electrónico'))
            ->line(Lang::get('Seleccione o botão abaixo para verificar o seu endereço de correio electrónico.'))
            ->action(Lang::get('Verificar endereço'), $this->verificationUrl($notifiable))
            ->line(Lang::get('Se não criou uma conta, não é necessária qualquer acção.'));
    }

    private function verificationUrl(mixed $notifiable): string
    {
        return URL::temporarySignedRoute(
            'portal.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
