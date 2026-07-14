<?php

namespace App\Services\Integrations;

use App\Jobs\DeliverIntegrationWebhook;
use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use App\Models\Result;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class IntegrationPublisher
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<int, IntegrationDelivery>
     */
    public function publish(string $eventType, Model $subject, array $data): array
    {
        $deliveries = [];

        IntegrationConnector::query()
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

                DeliverIntegrationWebhook::dispatch($delivery);
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
            'sample.results.parameter',
            'sample.results.unit',
            'sample.collection',
        ]);

        $sample = $result->sample;

        if (! $sample) {
            return [];
        }

        return $this->publish('lims.analysis.validated', $sample, [
            'sample' => [
                'id' => $sample->id,
                'code' => $sample->code,
                'laboratory_code' => $sample->collection?->code,
            ],
            'results' => $sample->results
                ->filter(fn (Result $item): bool => filled($item->approved_date))
                ->map(fn (Result $item): array => [
                    'id' => $item->id,
                    'parameter_code' => $item->parameter?->code,
                    'parameter_name' => $item->parameter?->name,
                    'value' => $item->approved_value,
                    'unit' => $item->unit?->name ?? $item->unit_label,
                    'approved_at' => Carbon::parse((string) $item->approved_date)->toIso8601String(),
                ])->values()->all(),
        ]);
    }
}
