<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Rating;
use App\Models\User;
use App\Models\VAPNonConformity;
use App\Notifications\NonConformityWorkflowNotification;
use App\Notifications\RatingSubmittedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class QualityModuleNotifier
{
    public function notifyRatingSubmitted(Rating $rating): void
    {
        $this->send(
            $this->qualityStakeholders(),
            new RatingSubmittedNotification($rating),
            'rating-submitted:'.$rating->id
        );
    }

    public function notifyNonConformityCreated(VAPNonConformity $nonConformity): void
    {
        $title = $nonConformity->severity === 'critical'
            ? 'Não conformidade crítica registada'
            : 'Nova não conformidade registada';

        $message = sprintf(
            '%s foi registada com severidade %s e estado %s.',
            $nonConformity->nc_number,
            $nonConformity->severity,
            $nonConformity->status
        );

        $this->send(
            $this->nonConformityStakeholders($nonConformity),
            new NonConformityWorkflowNotification($nonConformity, $title, $message),
            'nc-created:'.$nonConformity->id
        );
    }

    public function notifyNonConformityUpdated(VAPNonConformity $nonConformity, array $before): void
    {
        $statusChanged = ($before['status'] ?? null) !== $nonConformity->status;
        $becameCritical = ($before['severity'] ?? null) !== 'critical' && $nonConformity->severity === 'critical';

        if (! $statusChanged && ! $becameCritical) {
            return;
        }

        $title = $becameCritical ? 'Não conformidade escalada para crítica' : 'Estado de não conformidade atualizado';
        $message = $becameCritical
            ? sprintf('%s foi escalada para severidade crítica.', $nonConformity->nc_number)
            : sprintf('%s mudou de %s para %s.', $nonConformity->nc_number, $before['status'] ?? 'n/a', $nonConformity->status);

        $this->send(
            $this->nonConformityStakeholders($nonConformity),
            new NonConformityWorkflowNotification($nonConformity, $title, $message),
            'nc-updated:'.$nonConformity->id.':'.$nonConformity->updated_at?->format('YmdHi')
        );
    }

    private function nonConformityStakeholders(VAPNonConformity $nonConformity): Collection
    {
        return $this->qualityStakeholders()
            ->concat(collect([
                $nonConformity->assignedToUser,
                $nonConformity->reportedByUser,
            ]))
            ->filter()
            ->unique(fn ($recipient) => get_class($recipient).':'.$recipient->getKey())
            ->values();
    }

    private function qualityStakeholders(): Collection
    {
        $admins = User::query()
            ->role('admin')
            ->whereNotNull('email_verified_at')
            ->get();

        $nonConformityUsers = $this->usersWithPermission('view_vap_non_conformities');
        $occurrenceUsers = $this->usersWithPermission('view_occurrences');

        return $admins
            ->concat($nonConformityUsers)
            ->concat($occurrenceUsers)
            ->whereNotNull('email_verified_at')
            ->unique('id')
            ->values();
    }

    private function usersWithPermission(string $permission): Collection
    {
        if (! Permission::query()->where('name', $permission)->exists()) {
            return collect();
        }

        return User::query()
            ->permission($permission)
            ->whereNotNull('email_verified_at')
            ->get();
    }

    private function send(Collection $recipients, object $notification, string $cacheKey): void
    {
        if (! Cache::add('quality-module-notification:'.$cacheKey, true, now()->addHours(6))) {
            return;
        }

        $targets = $recipients->filter()->values();

        if ($targets->isEmpty()) {
            return;
        }

        Notification::send($targets, $notification);
    }
}
