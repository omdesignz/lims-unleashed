<?php

namespace App\Support;

use App\Models\CounterAnalysis;
use App\Models\Result;
use App\Models\User;
use App\Models\VAPSampleEntry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LaboratoryWorkflowNotifier
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function notifyCounterAnalysisRequested(Result $result, User $sender): void
    {
        $this->sendOperationalNotification(
            'lab.counter_analysis.requested',
            [
                'parameter_name' => $result->parameter_label ?? ('Parâmetro #'.$result->parameter_id),
                'sample_code' => $result->code_label ?? ('Amostra #'.$result->sample_id),
                'document_url' => route('counteranalysis.index'),
            ],
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission('view_counter_analysis'),
                $this->usersWithPermission('insert_results'),
                collect([$result->sample?->collection?->collection?->warehouse])
            ),
            'counter-analysis-request:'.$result->id
        );
    }

    public function notifySampleCollectionLinked(VAPSampleEntry $sampleEntry, User $sender): void
    {
        if (! $sampleEntry->collectionProduct) {
            return;
        }

        $recipients = $this->mergeRecipients(
            collect([$sampleEntry->warehouse, $sampleEntry->receivedBy]),
            $this->usersWithPermission('view_analysis')
        );

        if (! Cache::add('workflow-notification:linked-sample:'.$sampleEntry->id, true, now()->addHours(6))) {
            return;
        }

        $this->templates->notify($recipients, 'lab.sample.linked', [
            'sample_code' => $sampleEntry->code ?: $sampleEntry->name,
            'collection_code' => $sampleEntry->collectionProduct->code?->code ?? ('#'.$sampleEntry->collection_product_id),
            'parameter_count' => (int) data_get($sampleEntry->client_submitted_info, 'required_parameter_count', 0),
            'document_url' => route('vap_samples.show', $sampleEntry),
        ]);
    }

    public function notifyStaleSample(VAPSampleEntry $sampleEntry, User $sender): void
    {
        $recipients = $this->mergeRecipients(
            collect([$sampleEntry->warehouse, $sampleEntry->receivedBy]),
            $this->usersWithPermission('view_analysis')
        );

        if (! Cache::add('workflow-notification:stale-sample:'.$sampleEntry->id.':'.now()->format('Ymd'), true, now()->addHours(12))) {
            return;
        }

        $this->templates->notify($recipients, 'lab.sample.stale', [
            'sample_code' => $sampleEntry->code ?: $sampleEntry->name,
            'status' => $sampleEntry->status,
            'last_updated_at' => optional($sampleEntry->updated_at)->format('d/m/Y H:i') ?? 'data desconhecida',
            'document_url' => route('vap_samples.show', $sampleEntry),
        ]);
    }

    public function notifyStaleResult(Result $result, string $stage, User $sender): void
    {
        $permission = $stage === 'verify' ? 'verify_results' : 'approve_results';

        $this->sendOperationalNotification(
            'lab.results.stale',
            [
                'sample_code' => $result->code_label ?? ('Amostra #'.$result->sample_id),
                'stage' => $stage === 'verify' ? 'verificação' : 'aprovação',
                'document_url' => route('analysis.index'),
            ],
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission($permission),
                collect([$result->sample?->collection?->collection?->warehouse])
            ),
            'stale-result:'.$stage.':'.$result->id.':'.now()->format('Ymd')
        );
    }

    public function notifyStaleCounterAnalysis(CounterAnalysis $counterAnalysis, User $sender): void
    {
        $result = $counterAnalysis->requested_result;

        $this->sendOperationalNotification(
            'lab.counter_analysis.stale',
            [
                'sample_code' => $result?->code_label ?? ('Amostra #'.$counterAnalysis->sample_id),
                'document_url' => route('counteranalysis.index'),
            ],
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission('view_counter_analysis'),
                collect([$result?->sample?->collection?->collection?->warehouse])
            ),
            'stale-counter-analysis:'.$counterAnalysis->id.':'.now()->format('Ymd')
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

        return $admins->concat($permitted)
            ->unique('id')
            ->values();
    }

    private function sendOperationalNotification(
        string $templateKey,
        array $context,
        User $sender,
        Collection $recipients,
        string $cacheKey
    ): void {
        if (! Cache::add('workflow-notification:'.$cacheKey, true, now()->addHours(12))) {
            return;
        }

        $filteredRecipients = $recipients
            ->filter()
            ->reject(fn ($recipient) => $recipient instanceof User && $recipient->is($sender))
            ->unique(fn ($recipient) => get_class($recipient).':'.$recipient->getKey())
            ->values();

        if ($filteredRecipients->isEmpty()) {
            return;
        }

        $this->templates->notify($filteredRecipients, $templateKey, $context);
    }

    private function mergeRecipients(EloquentCollection|Collection ...$recipientGroups): Collection
    {
        return collect($recipientGroups)
            ->flatten(1)
            ->filter();
    }
}
