<?php

namespace App\Support;

use App\Models\InventorySupplierAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

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
            $this->stakeholders($assessment->lab_id),
            'supplier-assessment-sensitive:'.$assessment->id.':'.$assessment->updated_at?->format('YmdHi')
        );
    }

    public function notifyDueSoon(InventorySupplierAssessment $assessment): void
    {
        $this->sendNotification(
            $assessment,
            'Revisão prevista para '.($assessment->next_review_at?->format('d/m/Y') ?? 'data em aberto').'.',
            null,
            $this->stakeholders($assessment->lab_id),
            'supplier-assessment-due-soon:'.$assessment->id.':'.now()->format('Ymd')
        );
    }

    public function notifyOverdue(InventorySupplierAssessment $assessment): void
    {
        $this->sendNotification(
            $assessment,
            'O prazo de revisão foi ultrapassado e requer acção imediata.',
            null,
            $this->stakeholders($assessment->lab_id),
            'supplier-assessment-overdue:'.$assessment->id.':'.now()->format('Ymd')
        );
    }

    public function notifyCriticalRisk(InventorySupplierAssessment $assessment): void
    {
        $this->sendNotification(
            $assessment,
            'Rever antes de novas aquisições.',
            null,
            $this->stakeholders($assessment->lab_id),
            'supplier-assessment-critical:'.$assessment->id.':'.now()->format('Ymd')
        );
    }

    private function stakeholders(int $labId): Collection
    {
        return $this->mergeRecipients(
            $this->usersWithPermission('view_isuppliers', $labId),
            $this->usersWithPermission('view_iorders', $labId)
        );
    }

    private function usersWithPermission(string $permission, int $labId): Collection
    {
        return User::query()
            ->whereIn('id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))
            ->whereNotNull('email_verified_at')
            ->where('is_active', true)
            ->where(function ($query) use ($permission): void {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'admin')->where('guard_name', 'web'))
                    ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->where('name', $permission)->where('guard_name', 'web'))
                    ->orWhereHas('roles.permissions', fn ($permissionQuery) => $permissionQuery->where('name', $permission)->where('guard_name', 'web'));
            })
            ->get();
    }

    private function sendNotification(
        InventorySupplierAssessment $assessment,
        string $detail,
        ?User $sender,
        Collection $recipients,
        string $cacheKey
    ): void {
        $targets = $recipients
            ->filter()
            ->reject(fn ($recipient) => $recipient instanceof User && $recipient->is($sender))
            ->unique(fn ($recipient) => get_class($recipient).':'.$recipient->getKey())
            ->values();

        if ($targets->isEmpty()) {
            return;
        }

        $context = [
            'lab_id' => $assessment->lab_id,
            'actor_name' => $sender?->name ?? 'Sistema',
            'supplier_name' => $assessment->supplier?->name ?? ('Fornecedor #'.$assessment->inventory_item_supplier_id),
            'status' => $assessment->status,
            'risk_level' => $assessment->risk_level,
            'detail' => $detail,
            'document_url' => route('supplier-assessments.index'),
        ];
        $cacheKey = 'supplier-assessment-notification:lab:'.$assessment->lab_id.':'.$cacheKey;
        DB::afterCommit(function () use ($targets, $context, $cacheKey): void {
            if (! Cache::add($cacheKey, true, now()->addHours(12))) {
                return;
            }

            try {
                if ($this->templates->notify($targets, 'inventory.supplier_assessment', $context) === 0) {
                    Cache::forget($cacheKey);
                }
            } catch (Throwable $exception) {
                Cache::forget($cacheKey);
                throw $exception;
            }
        });
    }

    private function mergeRecipients(EloquentCollection|Collection ...$recipientGroups): Collection
    {
        return collect($recipientGroups)->flatten(1)->filter();
    }
}
