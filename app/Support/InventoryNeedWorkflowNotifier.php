<?php

namespace App\Support;

use App\Models\InventoryNeed;
use App\Models\InventoryOrder;
use App\Models\User;
use App\Notifications\InventoryNeedWorkflowNotification;
use Illuminate\Support\Facades\Notification;

class InventoryNeedWorkflowNotifier
{
    public function submitted(InventoryNeed $need): void
    {
        $this->send(
            $this->procurementRecipients(),
            $need,
            'Nova necessidade submetida',
            "A necessidade {$need->reference} foi submetida e aguarda validação/aprovação."
        );
    }

    public function approved(InventoryNeed $need): void
    {
        $this->send(
            $this->requesterRecipients($need),
            $need,
            'Necessidade aprovada',
            "A necessidade {$need->reference} foi aprovada e está pronta para conversão em pedido de compra."
        );
    }

    public function rejected(InventoryNeed $need): void
    {
        $message = "A necessidade {$need->reference} foi rejeitada.";

        if (filled($need->approval_notes)) {
            $message .= " Motivo: {$need->approval_notes}";
        }

        $this->send(
            $this->requesterRecipients($need),
            $need,
            'Necessidade rejeitada',
            $message
        );
    }

    public function convertedToOrder(InventoryNeed $need, InventoryOrder $order): void
    {
        $this->send(
            $this->stakeholderRecipients($need),
            $need,
            'Necessidade convertida em pedido',
            "A necessidade {$need->reference} foi convertida no pedido {$order->reference}."
        );
    }

    private function send(iterable $recipients, InventoryNeed $need, string $title, string $message): void
    {
        $recipients = collect($recipients)
            ->filter(fn (?User $user) => $user instanceof User)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new InventoryNeedWorkflowNotification($need, $title, $message, auth()->user())
        );
    }

    private function procurementRecipients()
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->where(function ($query) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'admin'))
                    ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->whereIn('name', ['add_iorders', 'edit_iorders']));
            })
            ->get();
    }

    private function requesterRecipients(InventoryNeed $need)
    {
        return collect([$need->requestedBy]);
    }

    private function stakeholderRecipients(InventoryNeed $need)
    {
        return $this->procurementRecipients()
            ->merge([$need->requestedBy, $need->approvedBy]);
    }
}
