<?php

namespace App\Services\Integrations;

use App\Jobs\DeliverIntegrationWebhook;
use App\Models\Analysis;
use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use App\Models\Result;
use App\Services\IssuedAnalyticalScope;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IntegrationPublisher
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly IssuedAnalyticalScope $issuedScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, IntegrationDelivery>
     */
    private function publish(string $eventType, Model $subject, array $data, int $labId): array
    {
        $deliveries = [];

        IntegrationConnector::query()
            ->where('lab_id', $labId)
            ->outbound()
            ->where('status', 'active')
            ->whereJsonContains('event_types', $eventType)
            ->each(function (IntegrationConnector $connector) use ($eventType, $subject, $data, &$deliveries): void {
                $eventId = (string) Str::uuid();
                $delivery = $connector->deliveries()->create([
                    'event_type' => $eventType,
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                    'idempotency_key' => $eventId,
                    'status' => 'pending',
                    'payload' => [
                        'specversion' => '1.0',
                        'id' => $eventId,
                        'source' => 'lims-unleashed',
                        'type' => $eventType,
                        'subject' => $subject->getMorphClass().'/'.$subject->getKey(),
                        'time' => now()->toIso8601String(),
                        'data' => $data,
                    ],
                ]);

                DeliverIntegrationWebhook::dispatch($delivery)->afterCommit();
                $deliveries[] = $delivery;
            });

        return $deliveries;
    }

    /**
     * @return array<int, IntegrationDelivery>
     */
    public function publishValidatedAnalysis(Result $result): array
    {
        $result->loadMissing([
            'sample.analysis',
            'sample.results',
            'sample.collection.collection.sampleEntry',
        ]);

        $sample = $result->sample;
        $analysis = $sample?->analysis;
        $product = $sample?->collection?->collection;

        if (! $sample || ! $analysis || ! $product) {
            return [];
        }

        $labId = (int) $product->sampleEntry?->lab_id;
        if ($labId <= 0 || ! $this->ownership->samplesForLaboratory($labId)->whereKey($sample->id)->exists()) {
            return [];
        }

        try {
            $issuedParameters = $this->issuedScope->parametersFor($analysis, $product)->keyBy('id');
        } catch (ValidationException) {
            return [];
        }

        $isIssuedResult = fn (Result $item): bool => (int) $item->sample_id === (int) $sample->id
            && (int) $item->code_id === (int) $sample->cl_id
            && (int) $item->collection_id === (int) $product->id
            && (int) $item->product_id === (int) $product->product_id
            && (int) $item->profile_id === (int) $analysis->profile_id
            && $item->resultable_type === (new Analysis)->getMorphClass()
            && (int) $item->resultable_id === (int) $analysis->id
            && $issuedParameters->has((int) $item->parameter_id);

        if (! $isIssuedResult($result) || ! filled($result->approved_date)) {
            return [];
        }

        return $this->publish('lims.analysis.validated', $sample, [
            'sample' => [
                'id' => $sample->id,
                'code' => $sample->code,
                'laboratory_code' => $sample->collection?->code,
            ],
            'results' => $sample->results
                ->filter(fn (Result $item): bool => $isIssuedResult($item) && filled($item->approved_date))
                ->map(fn (Result $item): array => [
                    'id' => $item->id,
                    'parameter_code' => $issuedParameters->get((int) $item->parameter_id)?->code,
                    'parameter_name' => $issuedParameters->get((int) $item->parameter_id)?->name,
                    'value' => $item->approved_value,
                    'unit' => $issuedParameters->get((int) $item->parameter_id)?->pivot?->unit_label,
                    'approved_at' => Carbon::parse((string) $item->approved_date)->toIso8601String(),
                ])->values()->all(),
        ], $labId);
    }
}
