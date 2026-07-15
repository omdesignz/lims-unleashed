<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class PortalPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $url)
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
            ->subject(Lang::get('Repor a palavra-passe'))
            ->line(Lang::get('Recebeu esta mensagem porque foi pedida a reposição da palavra-passe da sua conta.'))
            ->action(Lang::get('Repor palavra-passe'), $this->url)
            ->line(Lang::get('Esta ligação para reposição da palavra-passe expira dentro de :count minutos.', [
                'count' => config('auth.passwords.warehouses.expire'),
            ]))
            ->line(Lang::get('Se não pediu a reposição da palavra-passe, não é necessária qualquer acção.'));
    }
}
