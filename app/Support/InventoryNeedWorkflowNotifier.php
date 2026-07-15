<?php

namespace App\Support;

use App\Models\InventoryNeed;
use App\Models\InventoryOrder;
use App\Models\User;

class InventoryNeedWorkflowNotifier
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function submitted(InventoryNeed $need): void
    {
        $this->send(
            $this->procurementRecipients(),
            $need,
            'submetida',
            'Aguarda validação e aprovação.'
        );
    }

    public function approved(InventoryNeed $need): void
    {
        $this->send(
            $this->requesterRecipients($need),
            $need,
            'aprovada',
            'Está pronta para conversão em pedido de compra.'
        );
    }

    public function rejected(InventoryNeed $need): void
    {
        $message = '';

        if (filled($need->approval_notes)) {
            $message .= " Motivo: {$need->approval_notes}";
        }

        $this->send(
            $this->requesterRecipients($need),
            $need,
            'rejeitada',
            $message
        );
    }

    public function convertedToOrder(InventoryNeed $need, InventoryOrder $order): void
    {
        $this->send(
            $this->stakeholderRecipients($need),
            $need,
            'convertida em pedido',
            "Pedido associado: {$order->reference}."
        );
    }

    private function send(iterable $recipients, InventoryNeed $need, string $status, string $detail): void
    {
        $recipients = collect($recipients)
            ->filter(fn (?User $user) => $user instanceof User)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->templates->notify($recipients, 'inventory.need.updated', [
            'need_reference' => $need->reference,
            'status' => $status,
            'detail' => $detail,
            'document_url' => route('vap-inventory.needs.show', $need),
        ]);
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
