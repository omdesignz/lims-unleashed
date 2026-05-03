<?php

namespace App\Support;

use App\Models\InventorySupplierAssessment;
use App\Models\User;
use App\Notifications\GlobalNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class SupplierAssessmentNotifier
{
    public function notifySensitiveAssessment(InventorySupplierAssessment $assessment, User $sender): void
    {
        if (! in_array($assessment->status, ['conditional', 'suspended', 'rejected'], true)
            && ! in_array($assessment->risk_level, ['high', 'critical'], true)) {
            return;
        }

        $title = 'Fornecedor sob monitorização reforçada';
        $message = sprintf(
            'O fornecedor %s foi classificado como %s com risco %s.',
            $assessment->supplier?->name ?? ('Fornecedor #' . $assessment->inventory_item_supplier_id),
            $assessment->status,
            $assessment->risk_level
        );

        $this->sendNotification(
            $title,
            $message,
            $sender,
            $this->stakeholders(),
            'supplier-assessment-sensitive:' . $assessment->id . ':' . $assessment->updated_at?->format('YmdHi')
        );
    }

    public function notifyDueSoon(InventorySupplierAssessment $assessment, User $sender): void
    {
        $this->sendNotification(
            'Avaliação de fornecedor próxima da revisão',
            sprintf(
                'A avaliação do fornecedor %s deve ser revista até %s.',
                $assessment->supplier?->name ?? ('Fornecedor #' . $assessment->inventory_item_supplier_id),
                $assessment->next_review_at?->format('d/m/Y') ?? 'data em aberto'
            ),
            $sender,
            $this->stakeholders(),
            'supplier-assessment-due-soon:' . $assessment->id . ':' . now()->format('Ymd')
        );
    }

    public function notifyOverdue(InventorySupplierAssessment $assessment, User $sender): void
    {
        $this->sendNotification(
            'Avaliação de fornecedor vencida',
            sprintf(
                'A avaliação do fornecedor %s ultrapassou o prazo de revisão e requer ação imediata.',
                $assessment->supplier?->name ?? ('Fornecedor #' . $assessment->inventory_item_supplier_id)
            ),
            $sender,
            $this->stakeholders(),
            'supplier-assessment-overdue:' . $assessment->id . ':' . now()->format('Ymd')
        );
    }

    public function notifyCriticalRisk(InventorySupplierAssessment $assessment, User $sender): void
    {
        $this->sendNotification(
            'Fornecedor com risco crítico',
            sprintf(
                'O fornecedor %s permanece com risco crítico e deve ser revisto antes de novas aquisições.',
                $assessment->supplier?->name ?? ('Fornecedor #' . $assessment->inventory_item_supplier_id)
            ),
            $sender,
            $this->stakeholders(),
            'supplier-assessment-critical:' . $assessment->id . ':' . now()->format('Ymd')
        );
    }

    private function stakeholders(): Collection
    {
        return $this->mergeRecipients(
            $this->usersWithPermission('view_isuppliers'),
            $this->usersWithPermission('view_iorders')
        );
    }

    private function usersWithPermission(string $permission): Collection
    {
        $admins = User::query()
            ->role('admin')
            ->whereNotNull('email_verified_at')
            ->get();

        $permitted = User::query()
            ->permission($permission)
            ->whereNotNull('email_verified_at')
            ->get();

        return $admins->concat($permitted)->unique('id')->values();
    }

    private function sendNotification(
        string $title,
        string $message,
        User $sender,
        Collection $recipients,
        string $cacheKey
    ): void {
        if (! Cache::add('supplier-assessment-notification:' . $cacheKey, true, now()->addHours(12))) {
            return;
        }

        $targets = $recipients
            ->filter()
            ->reject(fn ($recipient) => $recipient instanceof User && $recipient->is($sender))
            ->unique(fn ($recipient) => get_class($recipient) . ':' . $recipient->getKey())
            ->values();

        if ($targets->isEmpty()) {
            return;
        }

        Notification::send($targets, new GlobalNotification($title, $message, $sender));
    }

    private function mergeRecipients(EloquentCollection|Collection ...$recipientGroups): Collection
    {
        return collect($recipientGroups)->flatten(1)->filter();
    }
}
