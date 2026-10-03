<?php

namespace App\Notifications;

use App\Events\LaboratoryNotificationBroadcasted;
use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LaboratoryBroadcastChannel extends BroadcastChannel
{
    public function send(mixed $notifiable, Notification $notification): mixed
    {
        $message = $this->getData($notifiable, $notification);
        $event = new LaboratoryNotificationBroadcasted(
            $notifiable,
            $notification,
            $message instanceof BroadcastMessage ? $message->data : $message
        );

        if ($message instanceof BroadcastMessage) {
            $event->onConnection($message->connection)->onQueue($message->queue);
        }

        return $this->events->dispatch($event);
    }
}
