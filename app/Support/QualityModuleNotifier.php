<?php

namespace App\Support;

use App\Models\Rating;
use App\Models\User;
use App\Models\VAPNonConformity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QualityModuleNotifier
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function notifyRatingSubmitted(Rating $rating): void
    {
        $this->send(
            $this->ratingStakeholders((int) $rating->lab_id),
            'quality.rating.received',
            [
                'lab_id' => $rating->lab_id,
                'lab_name' => $rating->lab?->name,
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
        return User::query()->where('is_active', true)->whereNotNull('email_verified_at')
            ->whereIn('id', DB::table('lab_user')->where('lab_id', $nonConformity->lab_id)->select('user_id'))
            ->with(['roles.permissions', 'permissions'])->get()
            ->filter(fn (User $recipient): bool => $recipient->can('view_occurrences'))->values();
    }

    public function notifyNonConformityTransition(VAPNonConformity $record, string $requestId): void
    {
        $transition = collect($record->workflow_history ?? [])->firstWhere('request_id', $requestId);
        $status = match ($transition['action'] ?? '') {
            'resolve' => 'resolvida', 'verify' => 'verificada', 'close' => 'encerrada', 'reopen' => 'reaberta', default => $record->status,
        };
        $this->send($this->nonConformityStakeholders($record), 'quality.nonconformity.updated',
            [...$this->nonConformityContext($record), 'status' => $status], 'nc-transition:'.$record->id.':'.$requestId);
    }

    private function ratingStakeholders(int $labId): Collection
    {
        return User::query()->where('is_active', true)->whereNotNull('email_verified_at')
            ->whereIn('id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))
            ->with(['roles.permissions', 'permissions'])->get()
            ->filter(fn (User $recipient): bool => $recipient->can('view_ratings'))->values();
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

        try {
            $this->templates->notify($targets, $key, $context);
        } catch (\Throwable $exception) {
            Cache::forget('quality-module-notification:'.$cacheKey);
            throw $exception;
        }
    }

    /** @return array<string, scalar|null> */
    private function nonConformityContext(VAPNonConformity $nonConformity): array
    {
        return [
            'lab_id' => $nonConformity->lab_id,
            'document_number' => $nonConformity->nc_number,
            'severity' => $nonConformity->severity,
            'status' => $nonConformity->status,
            'document_url' => route('vap_non_conformities.show', $nonConformity),
        ];
    }
}
