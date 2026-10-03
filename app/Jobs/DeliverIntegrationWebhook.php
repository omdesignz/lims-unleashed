<?php

namespace App\Jobs;

use App\Models\IntegrationDelivery;
use App\Models\Sample;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DeliverIntegrationWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 35;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600, 1800];

    public function __construct(public readonly IntegrationDelivery $delivery) {}

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('integration-delivery-'.$this->delivery->id))->expireAfter(60)];
    }

    public function handle(LaboratoryWorkflowOwnership $ownership): void
    {
        $delivery = $this->delivery->fresh(['connector', 'subject']);

        if (in_array($delivery?->status, ['delivered', 'failed'], true)) {
            return;
        }

        $connector = $delivery?->connector;
        $endpoint = data_get($connector?->configuration, 'endpoint');

        if (! $delivery || ! $connector || ! filled($endpoint)) {
            throw new RuntimeException('A entrega da integração não tem um ponto de acesso configurado.');
        }

        if ($delivery->event_type !== 'lims.connector.test') {
            $sample = $delivery->subject;
            if (! $sample instanceof Sample
                || ! $connector->publishes($delivery->event_type)
                || ! $ownership->samplesForLaboratory((int) $connector->lab_id)->whereKey($sample->id)->exists()) {
                throw new RuntimeException('A entrega não pertence ao laboratório deste conector.');
            }
        }

        $body = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, (string) $connector->signing_secret);
        $headers = [
            'Idempotency-Key' => $delivery->idempotency_key,
            'User-Agent' => 'LIMS-Unleashed-Integration-Hub/1.0',
            'X-LIMS-Event' => $delivery->event_type,
            'X-LIMS-Signature' => 'sha256='.$signature,
            'X-LIMS-Timestamp' => $timestamp,
        ];
        $bearerToken = data_get($connector->credentials, 'bearer_token');
        $request = Http::timeout((int) data_get($connector->configuration, 'timeout_seconds', 10))
            ->connectTimeout(5)
            ->acceptJson()
            ->withHeaders($headers)
            ->withBody($body, 'application/json');

        if (filled($bearerToken)) {
            $request = $request->withToken((string) $bearerToken);
        }

        $delivery->forceFill([
            'status' => 'processing',
            'attempts' => $delivery->attempts + 1,
            'next_attempt_at' => null,
        ])->save();

        try {
            $response = $request->post((string) $endpoint);
            $delivery->forceFill([
                'http_status' => $response->status(),
                'response_excerpt' => Str::limit($response->body(), 2000),
                'last_error' => $response->successful() ? null : 'O endpoint respondeu com HTTP '.$response->status().'.',
                'status' => $response->successful() ? 'delivered' : 'retrying',
                'delivered_at' => $response->successful() ? now() : null,
                'next_attempt_at' => $response->successful() ? null : now()->addSeconds($this->retryDelay($delivery->attempts)),
            ])->save();

            if (! $response->successful()) {
                throw new RuntimeException('Integration endpoint returned HTTP '.$response->status().'.');
            }
        } catch (Throwable $exception) {
            if ($delivery->status !== 'retrying') {
                $delivery->forceFill([
                    'status' => 'retrying',
                    'last_error' => Str::limit($exception->getMessage(), 2000),
                    'next_attempt_at' => now()->addSeconds($this->retryDelay($delivery->attempts)),
                ])->save();
            }

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        IntegrationDelivery::query()
            ->whereKey($this->delivery->getKey())
            ->where('status', '!=', 'delivered')
            ->update([
                'status' => 'failed',
                'last_error' => Str::limit($exception?->getMessage() ?? 'A entrega esgotou as tentativas configuradas.', 2000),
                'next_attempt_at' => null,
            ]);
    }

    private function retryDelay(int $attempt): int
    {
        return $this->backoff[min(max($attempt - 1, 0), count($this->backoff) - 1)];
    }
}
