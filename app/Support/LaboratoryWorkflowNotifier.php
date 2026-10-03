<?php

namespace App\Support;

use App\Models\CounterAnalysis;
use App\Models\Permission;
use App\Models\Result;
use App\Models\User;
use App\Models\VAPSampleEntry;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class LaboratoryWorkflowNotifier
{
    public function __construct(
        private readonly NotificationTemplateService $templates,
        private readonly LaboratoryWorkflowOwnership $ownership
    ) {}

    public function notifyCounterAnalysisRequested(Result $result, User $sender): void
    {
        $entry = $this->ownership->resolve(['result_id' => $result->id]);

        if (! $entry) {
            return;
        }

        $this->sendOperationalNotification(
            'lab.counter_analysis.requested',
            [
                'result_id' => $result->id,
                'parameter_name' => $result->parameter_label ?? ('Parâmetro #'.$result->parameter_id),
                'sample_code' => $result->code_label ?? ('Amostra #'.$result->sample_id),
                'document_url' => route('counteranalysis.index'),
            ],
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission('view_counter_analysis', $entry->lab_id),
                $this->usersWithPermission('insert_results', $entry->lab_id),
                collect([$entry->warehouse])
            ),
            'counter-analysis-request:'.$result->id
        );
    }

    public function notifySampleCollectionLinked(VAPSampleEntry $sampleEntry, User $sender): void
    {
        $sampleEntry = $this->ownership->resolve(['sample_entry_id' => $sampleEntry->id]);

        if (! $sampleEntry?->collectionProduct) {
            return;
        }

        $recipients = $this->mergeRecipients(
            collect([$sampleEntry->warehouse, $sampleEntry->receivedBy]),
            $this->usersWithPermission('view_analysis', $sampleEntry->lab_id)
        );

        $this->sendOperationalNotification('lab.sample.linked', [
            'sample_entry_id' => $sampleEntry->id,
            'sample_code' => $sampleEntry->code ?: $sampleEntry->name,
            'collection_code' => $sampleEntry->collectionProduct->code?->code ?? ('#'.$sampleEntry->collection_product_id),
            'parameter_count' => (int) data_get($sampleEntry->client_submitted_info, 'required_parameter_count', 0),
            'document_url' => route('vap_samples.show', $sampleEntry),
        ], $sender, $recipients, 'linked-sample:'.$sampleEntry->id, false, 6);
    }

    public function notifyStaleSample(VAPSampleEntry $sampleEntry, User $sender): void
    {
        $sampleEntry = $this->ownership->resolve(['sample_entry_id' => $sampleEntry->id]);

        if (! $sampleEntry) {
            return;
        }

        $recipients = $this->mergeRecipients(
            collect([$sampleEntry->warehouse, $sampleEntry->receivedBy]),
            $this->usersWithPermission('view_analysis', $sampleEntry->lab_id)
        );

        $this->sendOperationalNotification('lab.sample.stale', [
            'sample_entry_id' => $sampleEntry->id,
            'sample_code' => $sampleEntry->code ?: $sampleEntry->name,
            'status' => $sampleEntry->status,
            'last_updated_at' => optional($sampleEntry->updated_at)->format('d/m/Y H:i') ?? 'data desconhecida',
            'document_url' => route('vap_samples.show', $sampleEntry),
        ], $sender, $recipients, 'stale-sample:'.$sampleEntry->id.':'.now()->format('Ymd'), false);
    }

    public function notifyStaleResult(Result $result, string $stage, User $sender): void
    {
        $entry = $this->ownership->resolve(['result_id' => $result->id]);

        if (! $entry || ! in_array($stage, ['verify', 'approve'], true)) {
            return;
        }

        $permission = $stage === 'verify' ? 'verify_results' : 'approve_results';

        $this->sendOperationalNotification(
            'lab.results.stale',
            [
                'result_id' => $result->id,
                'sample_code' => $result->code_label ?? ('Amostra #'.$result->sample_id),
                'stage' => $stage === 'verify' ? 'verificação' : 'aprovação',
                'document_url' => route('analysis.index'),
            ],
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission($permission, $entry->lab_id),
                collect([$entry->warehouse])
            ),
            'stale-result:'.$stage.':'.$result->id.':'.now()->format('Ymd')
        );
    }

    public function notifyStaleCounterAnalysis(CounterAnalysis $counterAnalysis, User $sender): void
    {
        $entry = $this->ownership->resolve(['counter_analysis_id' => $counterAnalysis->id]);

        if (! $entry) {
            return;
        }

        $result = $counterAnalysis->requested_result;

        $this->sendOperationalNotification(
            'lab.counter_analysis.stale',
            [
                'counter_analysis_id' => $counterAnalysis->id,
                'sample_code' => $result?->code_label ?? ('Amostra #'.$counterAnalysis->sample_id),
                'document_url' => route('counteranalysis.index'),
            ],
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission('view_counter_analysis', $entry->lab_id),
                collect([$entry->warehouse])
            ),
            'stale-counter-analysis:'.$counterAnalysis->id.':'.now()->format('Ymd')
        );
    }

    private function usersWithPermission(string $permission, int $labId): Collection
    {
        $admins = $this->ownership->eligibleUsers($labId)
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'admin')->where('guard_name', 'web'))
            ->get();

        $permitted = Permission::query()->where('name', $permission)->where('guard_name', 'web')->exists()
            ? $this->ownership->eligibleUsers($labId)->permission($permission)->get()
            : collect();

        return $admins->concat($permitted)
            ->unique('id')
            ->values();
    }

    /** @param array<string, scalar|null> $context */
    private function sendOperationalNotification(
        string $templateKey,
        array $context,
        User $sender,
        Collection $recipients,
        string $cacheKey,
        bool $excludeSender = true,
        int $cacheHours = 12
    ): void {
        $entry = $this->ownership->resolve($context);
        if (! $entry) {
            return;
        }

        $context = [...$context, ...$this->ownership->context($context, $entry), 'actor_name' => $sender->name];
        DB::afterCommit(function () use ($templateKey, $context, $sender, $recipients, $cacheKey, $excludeSender, $cacheHours): void {
            $entry = $this->ownership->resolve($context);
            if (! $entry || ! $this->templates->render($templateKey, $context)) {
                return;
            }

            $filteredRecipients = $recipients
                ->filter()
                ->filter(fn (object $recipient): bool => $this->ownership->canReceive($recipient, $entry))
                ->reject(fn ($recipient) => $excludeSender && $recipient instanceof User && $recipient->is($sender))
                ->unique(fn ($recipient) => get_class($recipient).':'.$recipient->getKey())
                ->values();

            if ($filteredRecipients->isEmpty()) {
                return;
            }

            $cacheKey = 'workflow-notification:lab:'.$entry->lab_id.':'.$cacheKey;
            if (! Cache::add($cacheKey, true, now()->addHours($cacheHours))) {
                return;
            }

            try {
                if ($this->templates->notify($filteredRecipients, $templateKey, $context) === 0) {
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
        return collect($recipientGroups)
            ->flatten(1)
            ->filter();
    }
}
