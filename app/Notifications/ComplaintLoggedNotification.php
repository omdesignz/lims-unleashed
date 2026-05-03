<?php

namespace App\Notifications;

use App\Models\Complaint;

class ComplaintLoggedNotification extends GlobalNotification
{
    public function __construct(public Complaint $complaint, string $title, string $message, $sender)
    {
        parent::__construct($title, $message, $sender);
    }

    public function toDatabase($notifiable)
    {
        return array_merge(parent::toDatabase($notifiable), [
            'complaint_id' => $this->complaint->id,
            'complaint_reference' => $this->complaint->reference,
            'complaint_status' => $this->complaint->status,
        ]);
    }
}
