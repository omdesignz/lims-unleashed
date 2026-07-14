<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIntegrationConnectorRequest;
use App\Http\Requests\StoreIntegrationMappingRequest;
use App\Jobs\DeliverIntegrationWebhook;
use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use App\Models\IntegrationTransmission;
use App\Models\InventoryItem;
use App\Services\Integrations\IntegrationPayloadNormalizer;
use App\Services\Integrations\IntegrationResultImporter;
use App\Support\PersonnelQualificationGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationHubController extends Controller
{
    public function index(): Response
    {
        $this->authorizeView();

        $connectors = IntegrationConnector::query()
            ->with(['equipment:id,name,code,serial_number', 'activeMapping'])
            ->withCount(['transmissions', 'deliveries'])
            ->orderByRaw("FIELD(status, 'error', 'active', 'paused', 'draft')")
            ->orderBy('name')
            ->get()
            ->map(fn (IntegrationConnector $connector): array => [
                'id' => $connector->id,
                'uuid' => $connector->uuid,
                'name' => $connector->name,
                'key' => $connector->key,
                'description' => $connector->description,
                'direction' => $connector->direction,
                'adapter' => $connector->adapter,
                'status' => $connector->status,
                'health_status' => $connector->health_status,
                'health_message' => $connector->health_message,
                'last_seen_at' => $connector->last_seen_at?->toIso8601String(),
                'last_tested_at' => $connector->last_tested_at?->toIso8601String(),
                'configuration' => $connector->configuration ?? [],
                'event_types' => $connector->event_types ?? [],
                'equipment' => $connector->equipment,
                'active_mapping' => $connector->activeMapping,
                'ingest_token_configured' => filled($connector->ingest_token_hash),
                'credentials_configured' => filled($connector->credentials),
                'transmissions_count' => $connector->transmissions_count,
                'deliveries_count' => $connector->deliveries_count,
                'ingest_endpoint' => route('api.integrations.ingest', $connector),
                'heartbeat_endpoint' => route('api.integrations.heartbeat', $connector),
            ]);

        $transmissions = IntegrationTransmission::query()
            ->with([
                'connector:id,uuid,name,adapter',
                'mapping:id,name,version',
                'sample:id,code',
                'parameter:id,name,code',
                'result:id,inserted_value,inserted_date',
                'reviewer:id,name',
            ])
            ->latest('received_at')
            ->limit(100)
            ->get();

        $deliveries = IntegrationDelivery::query()
            ->with('connector:id,uuid,name')
            ->latest()
            ->limit(60)
            ->get();

        return Inertia::render('Integrations/Index', [
            'summary' => [
                'active_connectors' => IntegrationConnector::query()->where('status', 'active')->count(),
                'healthy_connectors' => IntegrationConnector::query()->where('status', 'active')->where('health_status', 'healthy')->count(),
                'review_queue' => IntegrationTransmission::query()->where('status', 'matched')->count(),
                'quarantined' => IntegrationTransmission::query()->where('status', 'quarantined')->count(),
                'received_24h' => IntegrationTransmission::query()->where('received_at', '>=', now()->subDay())->count(),
                'delivery_failures' => IntegrationDelivery::query()->whereIn('status', ['retrying', 'failed'])->count(),
            ],
            'connectors' => $connectors,
            'transmissions' => $transmissions,
            'deliveries' => $deliveries,
            'equipmentOptions' => InventoryItem::query()
                ->where('category_id', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'serial_number'])
                ->map(fn (InventoryItem $item): array => [
                    'value' => $item->id,
                    'label' => $item->name,
                    'meta' => collect([$item->code, $item->serial_number])->filter()->implode(' · '),
                ]),
            'adapterCatalog' => $this->adapterCatalog(),
            'canManage' => $this->canManage(),
            'canImport' => auth()->user()->can('insert_results'),
            'revealedToken' => session('integration_token'),
            'revealedConnectorUuid' => session('integration_connector_uuid'),
        ]);
    }

    public function store(StoreIntegrationConnectorRequest $request): RedirectResponse
    {
        $this->authorizeManage();
        $validated = $request->validated();
        $validated['key'] = $this->uniqueKey($validated['key'] ?? $validated['name']);
        $validated['created_by_id'] = auth()->id();
        $validated['configuration'] = array_filter($validated['configuration'] ?? [], fn (mixed $value): bool => filled($value));
        $validated['credentials'] = array_filter($validated['credentials'] ?? [], fn (mixed $value): bool => filled($value));
        $validated['event_types'] = $validated['event_types'] ?? [];
        $validated['signing_secret'] = Str::random(64);

        $connector = DB::transaction(function () use ($validated): IntegrationConnector {
            $connector = IntegrationConnector::query()->create($validated);

            if (in_array($connector->direction, ['inbound', 'bidirectional'], true)) {
                $connector->mappings()->create([
                    'created_by_id' => auth()->id(),
                    'name' => 'Mapeamento inicial',
                    'version' => 1,
                    'is_active' => true,
                    'field_paths' => [
                        'external_id' => 'message.id',
                        'sample_code' => 'result.sample_code',
                        'parameter_code' => 'result.parameter_code',
                        'value' => 'result.value',
                        'unit' => 'result.unit',
                        'measured_at' => 'result.measured_at',
                    ],
                    'transformations' => [
                        'sample_code' => ['trim', 'uppercase'],
                        'parameter_code' => ['trim', 'uppercase'],
                        'value' => ['trim', 'decimal_comma'],
                    ],
                    'constants' => [],
                ]);
            }

            return $connector;
        });

        $token = in_array($connector->direction, ['inbound', 'bidirectional'], true)
            ? $connector->rotateIngestToken()
            : null;

        activity()
            ->causedBy(auth()->user())
            ->performedOn($connector)
            ->log('Criou um conector no Integration Hub.');

        return to_route('integration-hub.index')
            ->with('integration_token', $token)
            ->with('integration_connector_uuid', $connector->uuid)
            ->with('toast', $this->toast('Conector criado', 'O conector foi criado e está pronto para configuração.'));
    }

    public function update(StoreIntegrationConnectorRequest $request, IntegrationConnector $connector): RedirectResponse
    {
        $this->authorizeManage();
        $validated = $request->validated();
        $validated['key'] = $this->uniqueKey($validated['key'] ?? $validated['name'], $connector);
        $validated['configuration'] = array_filter($validated['configuration'] ?? [], fn (mixed $value): bool => filled($value));
        $newCredentials = array_filter($validated['credentials'] ?? [], fn (mixed $value): bool => filled($value));

        if ($newCredentials === []) {
            unset($validated['credentials']);
        } else {
            $validated['credentials'] = array_merge($connector->credentials ?? [], $newCredentials);
        }

        $connector->update($validated);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($connector)
            ->log('Atualizou a configuração de um conector do Integration Hub.');

        return back()->with('toast', $this->toast('Conector actualizado', 'As definições foram guardadas.'));
    }

    public function storeMapping(StoreIntegrationMappingRequest $request, IntegrationConnector $connector): RedirectResponse
    {
        $this->authorizeManage();

        DB::transaction(function () use ($request, $connector): void {
            $connector->mappings()->update(['is_active' => false]);
            $connector->mappings()->create([
                ...$request->validated(),
                'created_by_id' => auth()->id(),
                'version' => ((int) $connector->mappings()->max('version')) + 1,
                'is_active' => true,
            ]);
        });

        activity()
            ->causedBy(auth()->user())
            ->performedOn($connector)
            ->log('Publicou uma nova versão do mapeamento de dados.');

        return back()->with('toast', $this->toast('Mapeamento publicado', 'A nova versão será usada nas próximas transmissões.'));
    }

    public function testMapping(Request $request, IntegrationConnector $connector, IntegrationPayloadNormalizer $normalizer): JsonResponse
    {
        $this->authorizeView();
        $validated = $request->validate(['payload' => ['required', 'array']]);

        return response()->json($normalizer->normalize($validated['payload'], $connector->activeMapping()->first()));
    }

    public function rotateToken(IntegrationConnector $connector): RedirectResponse
    {
        $this->authorizeManage();
        abort_unless(in_array($connector->direction, ['inbound', 'bidirectional'], true), 422);

        $token = $connector->rotateIngestToken();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($connector)
            ->log('Rodou a credencial de ingestão de um conector.');

        return back()
            ->with('integration_token', $token)
            ->with('integration_connector_uuid', $connector->uuid)
            ->with('toast', $this->toast('Token renovado', 'O token anterior deixou de ser válido.'));
    }

    public function test(IntegrationConnector $connector): RedirectResponse
    {
        $this->authorizeManage();

        if ($connector->direction === 'inbound') {
            $healthy = filled($connector->ingest_token_hash) && $connector->activeMapping()->exists();
            $connector->forceFill([
                'last_tested_at' => now(),
                'health_status' => $healthy ? 'ready' : 'warning',
                'health_message' => $healthy
                    ? 'Credencial e mapeamento válidos; a aguardar o primeiro heartbeat.'
                    : 'Configure uma credencial e um mapeamento activo antes de ligar o equipamento.',
            ])->save();
        } else {
            $eventId = (string) Str::uuid();
            $delivery = $connector->deliveries()->create([
                'event_type' => 'lims.connector.test',
                'idempotency_key' => $eventId,
                'status' => 'pending',
                'payload' => [
                    'specversion' => '1.0',
                    'id' => $eventId,
                    'source' => 'lims-unleashed',
                    'type' => 'lims.connector.test',
                    'time' => now()->toIso8601String(),
                    'data' => ['connector_uuid' => $connector->uuid, 'message' => 'Teste de conectividade da central de integrações'],
                ],
            ]);
            $connector->forceFill(['last_tested_at' => now()])->save();
            DeliverIntegrationWebhook::dispatch($delivery);
        }

        return back()->with('toast', $this->toast('Teste iniciado', 'O estado será actualizado no histórico operacional.'));
    }

    public function import(IntegrationTransmission $transmission, IntegrationResultImporter $importer): RedirectResponse
    {
        abort_if(! auth()->user()->can('insert_results'), 403);
        $transmission->load('result.sample.analysis');
        app(PersonnelQualificationGate::class)->ensure(
            auth()->user(),
            'insert_results',
            $transmission->result?->sample?->analysis?->department_id,
        );
        $importer->import($transmission, auth()->user());

        return back()->with('toast', $this->toast('Resultado importado', 'O valor entrou na etapa de inserção e mantém a proveniência do equipamento.'));
    }

    public function reject(Request $request, IntegrationTransmission $transmission): RedirectResponse
    {
        abort_unless($this->canManage() || auth()->user()->can('insert_results'), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        abort_unless(in_array($transmission->status, ['matched', 'quarantined'], true), 422);

        $transmission->forceFill([
            'status' => 'rejected',
            'reviewed_by_id' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['reason'],
        ])->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transmission)
            ->log('Rejeitou uma transmissão recebida pelo Integration Hub.');

        return back()->with('toast', $this->toast('Transmissão rejeitada', 'A decisão e a justificação ficaram registadas.'));
    }

    public function retry(IntegrationDelivery $delivery): RedirectResponse
    {
        $this->authorizeManage();
        $delivery->forceFill([
            'status' => 'pending',
            'last_error' => null,
            'next_attempt_at' => null,
        ])->save();
        DeliverIntegrationWebhook::dispatch($delivery);

        return back()->with('toast', $this->toast('Reenvio agendado', 'A entrega voltou para a fila de integração.'));
    }

    private function authorizeView(): void
    {
        abort_unless(auth()->user()->can('view_iequipments') || auth()->user()->can('view_settings'), 403);
    }

    private function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403);
    }

    private function canManage(): bool
    {
        return auth()->user()->can('edit_iequipments') || auth()->user()->can('edit_settings');
    }

    private function uniqueKey(string $value, ?IntegrationConnector $ignore = null): string
    {
        $base = Str::slug($value) ?: 'connector';
        $candidate = $base;
        $suffix = 2;

        while (IntegrationConnector::withTrashed()
            ->where('key', $candidate)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /** @return array<int, array<string, mixed>> */
    private function adapterCatalog(): array
    {
        return [
            ['value' => 'rest_json', 'label' => 'REST / JSON', 'transport' => 'Rede', 'edge_required' => false],
            ['value' => 'astm_edge', 'label' => 'ASTM E1381 / E1394', 'transport' => 'Edge relay', 'edge_required' => true],
            ['value' => 'hl7_edge', 'label' => 'HL7 v2 ORU', 'transport' => 'Edge relay', 'edge_required' => true],
            ['value' => 'csv_sftp', 'label' => 'CSV / SFTP', 'transport' => 'Ficheiro', 'edge_required' => false],
            ['value' => 'tcp_edge', 'label' => 'TCP socket', 'transport' => 'Edge relay', 'edge_required' => true],
            ['value' => 'serial_edge', 'label' => 'Serial RS-232', 'transport' => 'Edge relay', 'edge_required' => true],
            ['value' => 'opc_ua_edge', 'label' => 'OPC UA', 'transport' => 'Edge relay', 'edge_required' => true],
        ];
    }

    /** @return array<string, string> */
    private function toast(string $title, string $message): array
    {
        return compact('title', 'message');
    }
}
