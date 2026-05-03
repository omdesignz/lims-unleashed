<?php

namespace App\Notifications;

use App\Models\VAPSampleEntry;

class SampleRetentionDeadlineNotification extends GlobalNotification
{
    public function __construct(
        public VAPSampleEntry $sample,
        string $title,
        string $message,
        $sender
    ) {
        parent::__construct($title, $message, $sender);
    }

    public function toDatabase($notifiable)
    {
        return array_merge(parent::toDatabase($notifiable), [
            'sample_id' => $this->sample->id,
            'sample_code' => $this->sample->code,
            'retention_due_at' => optional($this->sample->retention_due_at)?->toDateString(),
            'retention_status' => $this->sample->retention_status,
        ]);
    }
}
