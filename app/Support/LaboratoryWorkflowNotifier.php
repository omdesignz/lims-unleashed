<?php

namespace App\Support;

use App\Models\CounterAnalysis;
use App\Models\Result;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPSampleEntry;
use App\Notifications\GlobalNotification;
use App\Notifications\SampleTrackingNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class LaboratoryWorkflowNotifier
{
    public function notifyResultsInserted(Result $result, User $sender): void
    {
        $title = 'Resultados aguardam verificação';
        $message = sprintf(
            'Os resultados da amostra %s foram inseridos e aguardam verificação.',
            $result->code_label ?? ('Amostra #' . $result->sample_id)
        );

        $this->sendOperationalNotification(
            $title,
            $message,
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission('verify_results'),
                collect([$result->sample?->collection?->collection?->warehouse])
            ),
            'results-verified:' . $result->sample_id . ':' . now()->format('YmdHi')
        );
    }

    public function notifyResultsVerified(Result $result, User $sender): void
    {
        $title = 'Resultados aguardam aprovação';
        $message = sprintf(
            'Os resultados da amostra %s foram verificados e aguardam aprovação final.',
            $result->code_label ?? ('Amostra #' . $result->sample_id)
        );

        $this->sendOperationalNotification(
            $title,
            $message,
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission('approve_results'),
                collect([$result->sample?->collection?->collection?->warehouse])
            ),
            'results-approved:' . $result->sample_id . ':' . now()->format('YmdHi')
        );
    }

    public function notifyResultsApproved(Result $result, User $sender): void
    {
        $title = 'Resultados aprovados';
        $message = sprintf(
            'Os resultados da amostra %s foram aprovados e podem seguir para emissão de relatório/certificado.',
            $result->code_label ?? ('Amostra #' . $result->sample_id)
        );

        $this->sendOperationalNotification(
            $title,
            $message,
            $sender,
            $this->mergeRecipients(
                collect([$result->sample?->collection?->collection?->warehouse]),
                $this->usersWithPermission('view_qualitycertificates'),
                collect([$result->inserted_by, $result->verified_by, $result->approved_by])
            ),
            'results-finalized:' . $result->sample_id . ':' . now()->format('YmdHi')
        );
    }

    public function notifyCounterAnalysisRequested(Result $result, User $sender): void
    {
        $title = 'Contra-análise solicitada';
        $message = sprintf(
            'Foi solicitada uma contra-análise para o parâmetro %s da amostra %s.',
            $result->parameter_label ?? ('Parâmetro #' . $result->parameter_id),
            $result->code_label ?? ('Amostra #' . $result->sample_id)
        );

        $this->sendOperationalNotification(
            $title,
            $message,
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission('view_counter_analysis'),
                $this->usersWithPermission('insert_results'),
                collect([$result->sample?->collection?->collection?->warehouse])
            ),
            'counter-analysis-request:' . $result->id
        );
    }

    public function notifySampleCollectionLinked(VAPSampleEntry $sampleEntry, User $sender): void
    {
        if (! $sampleEntry->collectionProduct) {
            return;
        }

        $requiredParameterCount = (int) data_get($sampleEntry->client_submitted_info, 'required_parameter_count', 0);
        $title = 'Pedido convertido no fluxo laboratorial';
        $message = sprintf(
            'A amostra %s foi validada e integrada na colheita/lote %s do fluxo normal com %d parâmetros previstos.',
            $sampleEntry->code ?: $sampleEntry->name,
            $sampleEntry->collectionProduct->code?->code ?? ('#' . $sampleEntry->collection_product_id),
            $requiredParameterCount
        );

        $recipients = $this->mergeRecipients(
            collect([$sampleEntry->warehouse, $sampleEntry->receivedBy]),
            $this->usersWithPermission('view_analysis')
        );

        if (! Cache::add('workflow-notification:linked-sample:' . $sampleEntry->id, true, now()->addHours(6))) {
            return;
        }

        Notification::send($recipients, new SampleTrackingNotification($sampleEntry, $title, $message, $sender));
    }

    public function notifyStaleSample(VAPSampleEntry $sampleEntry, User $sender): void
    {
        $title = 'Amostra sem avanço há demasiado tempo';
        $message = sprintf(
            'A amostra %s continua em %s desde %s e precisa de ação.',
            $sampleEntry->code ?: $sampleEntry->name,
            $sampleEntry->status,
            optional($sampleEntry->updated_at)->format('d/m/Y H:i') ?? 'data desconhecida'
        );

        $recipients = $this->mergeRecipients(
            collect([$sampleEntry->warehouse, $sampleEntry->receivedBy]),
            $this->usersWithPermission('view_analysis')
        );

        if (! Cache::add('workflow-notification:stale-sample:' . $sampleEntry->id . ':' . now()->format('Ymd'), true, now()->addHours(12))) {
            return;
        }

        Notification::send($recipients, new SampleTrackingNotification($sampleEntry, $title, $message, $sender));
    }

    public function notifyStaleResult(Result $result, string $stage, User $sender): void
    {
        $title = $stage === 'verify'
            ? 'Resultados pendentes de verificação'
            : 'Resultados pendentes de aprovação';

        $message = $stage === 'verify'
            ? sprintf('A amostra %s tem resultados inseridos há demasiado tempo sem verificação.', $result->code_label ?? ('Amostra #' . $result->sample_id))
            : sprintf('A amostra %s tem resultados verificados há demasiado tempo sem aprovação.', $result->code_label ?? ('Amostra #' . $result->sample_id));

        $permission = $stage === 'verify' ? 'verify_results' : 'approve_results';

        $this->sendOperationalNotification(
            $title,
            $message,
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission($permission),
                collect([$result->sample?->collection?->collection?->warehouse])
            ),
            'stale-result:' . $stage . ':' . $result->id . ':' . now()->format('Ymd')
        );
    }

    public function notifyStaleCounterAnalysis(CounterAnalysis $counterAnalysis, User $sender): void
    {
        $result = $counterAnalysis->requested_result;

        $this->sendOperationalNotification(
            'Contra-análise sem avanço',
            sprintf(
                'A contra-análise da amostra %s continua pendente e precisa de acompanhamento.',
                $result?->code_label ?? ('Amostra #' . $counterAnalysis->sample_id)
            ),
            $sender,
            $this->mergeRecipients(
                $this->usersWithPermission('view_counter_analysis'),
                collect([$result?->sample?->collection?->collection?->warehouse])
            ),
            'stale-counter-analysis:' . $counterAnalysis->id . ':' . now()->format('Ymd')
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
        string $title,
        string $message,
        User $sender,
        Collection $recipients,
        string $cacheKey
    ): void {
        if (! Cache::add('workflow-notification:' . $cacheKey, true, now()->addHours(12))) {
            return;
        }

        $filteredRecipients = $recipients
            ->filter()
            ->reject(fn ($recipient) => $recipient instanceof User && $recipient->is($sender))
            ->unique(fn ($recipient) => get_class($recipient) . ':' . $recipient->getKey())
            ->values();

        if ($filteredRecipients->isEmpty()) {
            return;
        }

        Notification::send($filteredRecipients, new GlobalNotification($title, $message, $sender));
    }

    private function mergeRecipients(EloquentCollection|Collection ...$recipientGroups): Collection
    {
        return collect($recipientGroups)
            ->flatten(1)
            ->filter();
    }
}
