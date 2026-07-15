<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Rating;
use App\Models\User;
use App\Models\VAPNonConformity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class QualityModuleNotifier
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function notifyRatingSubmitted(Rating $rating): void
    {
        $this->send(
            $this->qualityStakeholders(),
            'quality.rating.received',
            [
                'channel' => $rating->channel === 'portal' ? 'do portal' : 'interna',
                'rateable_type' => $rating->rateable_type,
                'rateable_id' => $rating->rateable_id,
                'document_url' => route('ratings.index'),
            ],
            'rating-submitted:'.$rating->id
        );
    }

    public function notifyNonConformityCreated(VAPNonConformity $nonConformity): void
    {
        $this->send(
            $this->nonConformityStakeholders($nonConformity),
            'quality.nonconformity.created',
            $this->nonConformityContext($nonConformity),
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

        $this->send(
            $this->nonConformityStakeholders($nonConformity),
            'quality.nonconformity.updated',
            [
                ...$this->nonConformityContext($nonConformity),
                'status' => $becameCritical ? 'crítica' : $nonConformity->status,
            ],
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

    /** @param array<string, scalar|null> $context */
    private function send(Collection $recipients, string $key, array $context, string $cacheKey): void
    {
        if (! Cache::add('quality-module-notification:'.$cacheKey, true, now()->addHours(6))) {
            return;
        }

        $targets = $recipients->filter()->values();

        if ($targets->isEmpty()) {
            return;
        }

        $this->templates->notify($targets, $key, $context);
    }

    /** @return array<string, scalar|null> */
    private function nonConformityContext(VAPNonConformity $nonConformity): array
    {
        return [
            'document_number' => $nonConformity->nc_number,
            'severity' => $nonConformity->severity,
            'status' => $nonConformity->status,
            'document_url' => route('vap_non_conformities.show', $nonConformity),
        ];
    }
}
