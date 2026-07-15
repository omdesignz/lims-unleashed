<?php

namespace App\Support;

use App\Models\InventorySupplierAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SupplierAssessmentNotifier
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function notifySensitiveAssessment(InventorySupplierAssessment $assessment, User $sender): void
    {
        if (! in_array($assessment->status, ['conditional', 'suspended', 'rejected'], true)
            && ! in_array($assessment->risk_level, ['high', 'critical'], true)) {
            return;
        }

        $this->sendNotification(
            $assessment,
            'Monitorização reforçada.',
            $sender,
            $this->stakeholders(),
            'supplier-assessment-sensitive:'.$assessment->id.':'.$assessment->updated_at?->format('YmdHi')
        );
    }

    public function notifyDueSoon(InventorySupplierAssessment $assessment, User $sender): void
    {
        $this->sendNotification(
            $assessment,
            'Revisão prevista para '.($assessment->next_review_at?->format('d/m/Y') ?? 'data em aberto').'.',
            $sender,
            $this->stakeholders(),
            'supplier-assessment-due-soon:'.$assessment->id.':'.now()->format('Ymd')
        );
    }

    public function notifyOverdue(InventorySupplierAssessment $assessment, User $sender): void
    {
        $this->sendNotification(
            $assessment,
            'O prazo de revisão foi ultrapassado e requer acção imediata.',
            $sender,
            $this->stakeholders(),
            'supplier-assessment-overdue:'.$assessment->id.':'.now()->format('Ymd')
        );
    }

    public function notifyCriticalRisk(InventorySupplierAssessment $assessment, User $sender): void
    {
        $this->sendNotification(
            $assessment,
            'Rever antes de novas aquisições.',
            $sender,
            $this->stakeholders(),
            'supplier-assessment-critical:'.$assessment->id.':'.now()->format('Ymd')
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
        InventorySupplierAssessment $assessment,
        string $detail,
        User $sender,
        Collection $recipients,
        string $cacheKey
    ): void {
        if (! Cache::add('supplier-assessment-notification:'.$cacheKey, true, now()->addHours(12))) {
            return;
        }

        $targets = $recipients
            ->filter()
            ->reject(fn ($recipient) => $recipient instanceof User && $recipient->is($sender))
            ->unique(fn ($recipient) => get_class($recipient).':'.$recipient->getKey())
            ->values();

        if ($targets->isEmpty()) {
            return;
        }

        $this->templates->notify($targets, 'inventory.supplier_assessment', [
            'supplier_name' => $assessment->supplier?->name ?? ('Fornecedor #'.$assessment->inventory_item_supplier_id),
            'status' => $assessment->status,
            'risk_level' => $assessment->risk_level,
            'detail' => $detail,
            'document_url' => route('supplier-assessments.index'),
        ]);
    }

    private function mergeRecipients(EloquentCollection|Collection ...$recipientGroups): Collection
    {
        return collect($recipientGroups)->flatten(1)->filter();
    }
}
