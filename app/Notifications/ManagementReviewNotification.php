<?php

namespace App\Notifications;

use App\Models\ManagementReview;

class ManagementReviewNotification extends GlobalNotification
{
    public function __construct(public ManagementReview $review, string $title, string $message, $sender)
    {
        parent::__construct($title, $message, $sender);
    }

    public function toDatabase($notifiable)
    {
        return array_merge(parent::toDatabase($notifiable), [
            'management_review_id' => $this->review->id,
            'management_review_reference' => $this->review->reference,
            'management_review_status' => $this->review->status,
            'review_date' => optional($this->review->review_date)?->toDateString(),
        ]);
    }
}
