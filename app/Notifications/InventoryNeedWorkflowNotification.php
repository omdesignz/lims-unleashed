<?php

namespace App\Notifications;

use App\Models\InventoryNeed;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InventoryNeedWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InventoryNeed $need,
        public string $title,
        public string $message,
        public ?User $sender = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->notificationType(),
            'sender_id' => $this->sender?->id,
            'sender_name' => $this->sender?->name,
            'sender' => $this->sender?->name,
            'need_id' => $this->need->id,
            'need_reference' => $this->need->reference,
            'need_status' => $this->need->status,
            'need_url' => route('vap-inventory.needs.show', $this->need),
        ];
    }

    private function notificationType(): string
    {
        return match ($this->need->status) {
            'approved', 'ordered', 'fulfilled' => 'success',
            'rejected' => 'error',
            default => 'info',
        };
    }
}
