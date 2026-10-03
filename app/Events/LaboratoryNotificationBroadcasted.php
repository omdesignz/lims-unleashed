<?php

namespace App\Events;

use App\Notifications\GlobalNotification;
use App\Notifications\OperationalNotification;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;

class LaboratoryNotificationBroadcasted extends BroadcastNotificationCreated
{
    public function broadcastOn(): array
    {
        if ((! $this->notification instanceof OperationalNotification && ! $this->notification instanceof GlobalNotification)
            || ! $this->notification->shouldSend($this->notifiable, 'broadcast')) {
            return [];
        }

        return parent::broadcastOn();
    }
}
