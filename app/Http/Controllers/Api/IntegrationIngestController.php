<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IngestIntegrationPayloadRequest;
use App\Models\IntegrationConnector;
use App\Models\IntegrationTransmission;
use App\Services\Integrations\IntegrationPayloadNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationIngestController extends Controller
{
    public function store(
        IngestIntegrationPayloadRequest $request,
        IntegrationConnector $connector,
        IntegrationPayloadNormalizer $normalizer,
    ): JsonResponse {
        $this->authenticate($request, $connector);

        if (! $connector->acceptsInbound()) {
            return response()->json([
                'message' => 'Este conector não está activo para a recepção de dados.',
            ], 409);
        }

        $validated = $request->validated();
        $existing = $connector->transmissions()
            ->where('external_id', $validated['external_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'data' => $this->transmissionResponse($existing),
                'duplicate' => true,
            ]);
        }

        $mapping = $connector->activeMapping()->first();
        $rawPayload = json_encode($validated['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $normalization = $normalizer->normalize($validated['payload'], $connector, $mapping);
        $values = $normalization['values'];
        $matches = $normalization['matches'];
        $issues = $normalization['issues'];
        $transmission = $connector->transmissions()->firstOrCreate(
            ['external_id' => $validated['external_id']],
            [
                'mapping_id' => $mapping?->id,
                'matched_sample_id' => data_get($matches, 'sample_id'),
                'matched_parameter_id' => data_get($matches, 'parameter_id'),
                'matched_result_id' => data_get($matches, 'result_id'),
                'correlation_id' => $validated['correlation_id'] ?? null,
                'direction' => 'inbound',
                'status' => $issues === [] ? 'matched' : 'quarantined',
                'checksum' => hash('sha256', $rawPayload),
                'content_type' => $request->header('Content-Type', 'application/json'),
                'raw_payload' => $rawPayload,
                'normalized_payload' => $values,
                'sample_code' => data_get($values, 'sample_code'),
                'parameter_code' => data_get($values, 'parameter_code'),
                'measured_value' => data_get($values, 'value'),
                'measured_unit' => data_get($values, 'unit'),
                'measured_at' => $normalizer->parseMeasuredAt(data_get($values, 'measured_at')),
                'diagnostics' => [
                    'issues' => $issues,
                    'matches' => $matches,
                    'mapping_version' => $mapping?->version,
                ],
                'received_at' => now(),
                'processed_at' => now(),
            ],
        );

        if (! $transmission->wasRecentlyCreated) {
            return response()->json([
                'data' => $this->transmissionResponse($transmission),
                'duplicate' => true,
            ]);
        }

        $connector->forceFill([
            'last_seen_at' => now(),
            'health_status' => 'healthy',
            'health_message' => $issues === []
                ? 'Última transmissão normalizada e correspondida com sucesso.'
                : 'Conectado; a última transmissão requer revisão de correspondência.',
        ])->save();

        return response()->json([
            'data' => $this->transmissionResponse($transmission),
            'duplicate' => false,
        ], 202);
    }

    public function heartbeat(Request $request, IntegrationConnector $connector): JsonResponse
    {
        $this->authenticate($request, $connector);

        if (! $connector->acceptsInbound()) {
            return response()->json(['message' => 'Este conector não está activo para a recepção de dados.'], 409);
        }

        $payload = $request->validate([
            'agent_version' => ['nullable', 'string', 'max:80'],
            'device_serial' => ['nullable', 'string', 'max:120'],
            'queue_depth' => ['nullable', 'integer', 'min:0'],
        ]);

        $connector->forceFill([
            'last_seen_at' => now(),
            'health_status' => 'healthy',
            'health_message' => 'Agente de integração em linha'.(filled($payload['agent_version'] ?? null) ? ' · v'.$payload['agent_version'] : '').'.',
        ])->save();

        return response()->json([
            'status' => 'accepted',
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function authenticate(Request $request, IntegrationConnector $connector): void
    {
        $token = $request->bearerToken();

        abort_unless(filled($token) && $connector->matchesIngestToken((string) $token), 401, 'O token do conector não é válido.');
    }

    /** @return array<string, mixed> */
    private function transmissionResponse(IntegrationTransmission $transmission): array
    {
        return [
            'id' => $transmission->id,
            'external_id' => $transmission->external_id,
            'status' => $transmission->status,
            'sample_code' => $transmission->sample_code,
            'parameter_code' => $transmission->parameter_code,
            'issues' => data_get($transmission->diagnostics, 'issues', []),
            'received_at' => $transmission->received_at?->toIso8601String(),
        ];
    }
}
