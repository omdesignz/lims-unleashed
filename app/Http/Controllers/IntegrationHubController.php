<?php

namespace App\Http\Controllers;

use App\Actions\RetryIntegrationDelivery;
use App\Actions\SaveIntegrationConnector;
use App\Http\Requests\StoreIntegrationConnectorRequest;
use App\Http\Requests\StoreIntegrationMappingRequest;
use App\Jobs\DeliverIntegrationWebhook;
use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use App\Models\IntegrationTransmission;
use App\Models\InventoryItem;
use App\Services\Integrations\IntegrationPayloadNormalizer;
use App\Services\Integrations\IntegrationResultImporter;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationHubController extends Controller
{
    public function index(SampleLaboratoryAccess $laboratory): Response
    {
        $this->authorizeView();
        $labId = $laboratory->activeLabId();

        $connectors = IntegrationConnector::query()
            ->where('lab_id', $labId)
            ->with([
                'equipment' => fn (BelongsTo $items): BelongsTo => $items->withTrashed()->forLaboratory($labId)->equipment()
                    ->select(['id', 'name', 'code', 'serial_number', 'deleted_at']),
                'activeMapping',
            ])
            ->withCount(['transmissions', 'deliveries'])
            ->orderByRaw("CASE status WHEN 'error' THEN 0 WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")
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
                'equipment' => $connector->equipment ? $connector->equipment->only(['id', 'name', 'code', 'serial_number']) + [
                    'is_archived' => $connector->equipment->trashed(),
                ] : null,
                'equipment_link_unavailable' => $connector->inventory_item_id !== null && $connector->equipment === null,
                'active_mapping' => $connector->activeMapping,
                'ingest_token_configured' => filled($connector->ingest_token_hash),
                'credentials_configured' => filled($connector->credentials),
                'transmissions_count' => $connector->transmissions_count,
                'deliveries_count' => $connector->deliveries_count,
                'ingest_endpoint' => route('api.integrations.ingest', $connector),
                'heartbeat_endpoint' => route('api.integrations.heartbeat', $connector),
            ]);

        $transmissions = IntegrationTransmission::query()
            ->whereHas('connector', fn ($query) => $query->where('lab_id', $labId))
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
            ->whereHas('connector', fn ($query) => $query->where('lab_id', $labId))
            ->with('connector:id,uuid,name')
            ->latest()
            ->limit(60)
            ->get();

        return Inertia::render('Integrations/Index', [
            'summary' => [
                'active_connectors' => IntegrationConnector::query()->where('lab_id', $labId)->where('status', 'active')->count(),
                'healthy_connectors' => IntegrationConnector::query()->where('lab_id', $labId)->where('status', 'active')->where('health_status', 'healthy')->count(),
                'review_queue' => IntegrationTransmission::query()->whereHas('connector', fn ($query) => $query->where('lab_id', $labId))->where('status', 'matched')->count(),
                'quarantined' => IntegrationTransmission::query()->whereHas('connector', fn ($query) => $query->where('lab_id', $labId))->where('status', 'quarantined')->count(),
                'received_24h' => IntegrationTransmission::query()->whereHas('connector', fn ($query) => $query->where('lab_id', $labId))->where('received_at', '>=', now()->subDay())->count(),
                'delivery_failures' => IntegrationDelivery::query()->whereHas('connector', fn ($query) => $query->where('lab_id', $labId))->whereIn('status', ['retrying', 'failed'])->count(),
            ],
            'connectors' => $connectors,
            'transmissions' => $transmissions,
            'deliveries' => $deliveries,
            'equipmentOptions' => InventoryItem::forLaboratory($labId)
                ->equipment()
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

    public function store(StoreIntegrationConnectorRequest $request, SampleLaboratoryAccess $laboratory, SaveIntegrationConnector $save): RedirectResponse
    {
        $this->authorizeManage();
        $saved = $save->execute($laboratory->activeLabId(), (int) $request->user()->id, $request->validated());

        return to_route('integration-hub.index')
            ->with('integration_token', $saved['token'])
            ->with('integration_connector_uuid', $saved['connector']->uuid)
            ->with('toast', $this->toast('Conector criado', 'O conector foi criado e está pronto para configuração.'));
    }

    public function update(StoreIntegrationConnectorRequest $request, IntegrationConnector $connector, SampleLaboratoryAccess $laboratory, SaveIntegrationConnector $save): RedirectResponse
    {
        $this->authorizeManage();
        $this->authorizeConnectorLab($connector, $laboratory);
        $save->execute($laboratory->activeLabId(), (int) $request->user()->id, $request->validated(), (int) $connector->id);

        return back()->with('toast', $this->toast('Conector actualizado', 'As definições foram guardadas.'));
    }

    public function storeMapping(StoreIntegrationMappingRequest $request, IntegrationConnector $connector, SampleLaboratoryAccess $laboratory): RedirectResponse
    {
        $this->authorizeManage();
        $this->authorizeConnectorLab($connector, $laboratory);

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

    public function testMapping(Request $request, IntegrationConnector $connector, IntegrationPayloadNormalizer $normalizer, SampleLaboratoryAccess $laboratory): JsonResponse
    {
        $this->authorizeView();
        $this->authorizeConnectorLab($connector, $laboratory);
        $validated = $request->validate(['payload' => ['required', 'array']]);

        return response()->json($normalizer->normalize($validated['payload'], $connector, $connector->activeMapping()->first()));
    }

    public function rotateToken(IntegrationConnector $connector, SampleLaboratoryAccess $laboratory): RedirectResponse
    {
        $this->authorizeManage();
        $this->authorizeConnectorLab($connector, $laboratory);
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

    public function test(IntegrationConnector $connector, SampleLaboratoryAccess $laboratory): RedirectResponse
    {
        $this->authorizeManage();
        $this->authorizeConnectorLab($connector, $laboratory);

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
            DeliverIntegrationWebhook::dispatch($delivery)->afterCommit();
        }

        return back()->with('toast', $this->toast('Teste iniciado', 'O estado será actualizado no histórico operacional.'));
    }

    public function import(IntegrationTransmission $transmission, IntegrationResultImporter $importer, SampleLaboratoryAccess $laboratory): RedirectResponse
    {
        $importer->import($transmission, auth()->user(), $laboratory->activeLabId());

        return back()->with('toast', $this->toast('Resultado importado', 'O valor entrou na etapa de inserção e mantém a proveniência do equipamento.'));
    }

    public function reject(Request $request, IntegrationTransmission $transmission, SampleLaboratoryAccess $laboratory): RedirectResponse
    {
        abort_unless($this->canManage() || auth()->user()->can('insert_results'), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $labId = $laboratory->activeLabId();

        DB::transaction(function () use ($transmission, $labId, $validated): void {
            $lockedTransmission = IntegrationTransmission::query()
                ->with('connector')->lockForUpdate()->findOrFail($transmission->id);
            abort_unless((int) $lockedTransmission->connector?->lab_id === $labId, 404);
            abort_unless(in_array($lockedTransmission->status, ['matched', 'quarantined'], true), 422);

            $lockedTransmission->forceFill([
                'status' => 'rejected',
                'reviewed_by_id' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => $validated['reason'],
            ])->save();

            activity()
                ->causedBy(auth()->user())
                ->performedOn($lockedTransmission)
                ->log('Rejeitou uma transmissão recebida pelo Integration Hub.');
        });

        return back()->with('toast', $this->toast('Transmissão rejeitada', 'A decisão e a justificação ficaram registadas.'));
    }

    public function retry(IntegrationDelivery $delivery, SampleLaboratoryAccess $laboratory, RetryIntegrationDelivery $retry): RedirectResponse
    {
        $this->authorizeManage();
        $shouldQueue = $retry->execute($delivery->id, $laboratory->activeLabId());

        if (! $shouldQueue) {
            return back()->with('toast', $this->toast('Reenvio não necessário', 'A entrega já está em curso ou foi concluída.'));
        }

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

    private function authorizeConnectorLab(IntegrationConnector $connector, SampleLaboratoryAccess $laboratory): void
    {
        abort_unless((int) $connector->lab_id === $laboratory->activeLabId(), 404);
    }

    private function canManage(): bool
    {
        return auth()->user()->can('edit_iequipments') || auth()->user()->can('edit_settings');
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
