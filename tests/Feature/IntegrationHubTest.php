<?php

namespace Tests\Feature;

use App\Jobs\DeliverIntegrationWebhook;
use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use App\Models\IntegrationMapping;
use App\Models\IntegrationTransmission;
use App\Models\PersonnelQualification;
use App\Models\Result;
use App\Models\Role;
use App\Models\User;
use App\Services\Integrations\IntegrationPublisher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IntegrationHubTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    public function test_admin_can_open_the_integration_hub(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->get(route('integration-hub.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Integrations/Index')
                ->has('summary.active_connectors')
                ->has('summary.review_queue')
                ->has('adapterCatalog', 7)
                ->where('canManage', true)
                ->where('canImport', true));
    }

    public function test_admin_can_create_an_inbound_connector_with_a_one_time_token_and_mapping(): void
    {
        $response = $this->actingAs($this->verifiedAdmin())
            ->post(route('integration-hub.connectors.store'), [
                'name' => 'Cobas Chemistry Line',
                'direction' => 'inbound',
                'adapter' => 'astm_edge',
                'status' => 'active',
                'configuration' => [
                    'edge_agent_id' => 'lab-edge-chemistry-01',
                    'timeout_seconds' => 10,
                ],
                'event_types' => [],
            ]);

        $response
            ->assertRedirect(route('integration-hub.index'))
            ->assertSessionHas('integration_token', fn (string $token): bool => strlen($token) === 64);

        $connector = IntegrationConnector::query()->where('name', 'Cobas Chemistry Line')->firstOrFail();
        $revealedToken = session('integration_token');

        $this->assertTrue($connector->matchesIngestToken($revealedToken));
        $this->assertNotSame($revealedToken, $connector->ingest_token_hash);
        $this->assertSame('lab-edge-chemistry-01', data_get($connector->configuration, 'edge_agent_id'));
        $this->assertDatabaseHas('integration_mappings', [
            'connector_id' => $connector->id,
            'version' => 1,
            'is_active' => true,
        ]);
    }

    public function test_ingest_requires_the_connector_token_and_is_idempotent(): void
    {
        $result = $this->resultWithMachineReadableCodes();
        $connector = IntegrationConnector::factory()->create();
        $token = $connector->rotateIngestToken();
        IntegrationMapping::factory()->for($connector, 'connector')->create();
        $payload = $this->ingestPayload($result, 'INGEST-IDEMPOTENT-001');

        $this->postJson(route('api.integrations.ingest', $connector), $payload)
            ->assertUnauthorized();

        $this->withToken($token)
            ->postJson(route('api.integrations.ingest', $connector), $payload)
            ->assertAccepted()
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.sample_code', $result->sample->code)
            ->assertJsonPath('data.parameter_code', $result->parameter->code);

        $this->withToken($token)
            ->postJson(route('api.integrations.ingest', $connector), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, IntegrationTransmission::query()
            ->where('connector_id', $connector->id)
            ->where('external_id', 'INGEST-IDEMPOTENT-001')
            ->count());
    }

    public function test_authorized_reviewer_can_import_a_matched_transmission_without_overwriting_results(): void
    {
        $admin = $this->verifiedAdmin();
        $result = $this->resultWithMachineReadableCodes();
        $result->forceFill([
            'inserted_value' => null,
            'inserted_date' => null,
            'inserted_by_id' => null,
            'inserted_by' => null,
        ])->save();
        $departmentId = $result->sample?->analysis?->department_id;

        PersonnelQualification::query()->updateOrCreate([
            'user_id' => $admin->id,
            'capability' => 'insert_results',
            'department_id' => $departmentId,
        ], [
            'qualified_by_id' => $admin->id,
            'authorized_from' => now()->subDay()->toDateString(),
            'authorized_until' => now()->addYear()->toDateString(),
            'training_completed_at' => now()->subDay()->toDateString(),
            'training_reference' => 'INTEGRATION-HUB-REVIEW',
            'is_active' => true,
        ]);

        $connector = IntegrationConnector::factory()->create(['name' => 'ICP-MS 01']);
        $mapping = IntegrationMapping::factory()->for($connector, 'connector')->create();
        $transmission = IntegrationTransmission::factory()->for($connector, 'connector')->create([
            'mapping_id' => $mapping->id,
            'status' => 'matched',
            'matched_sample_id' => $result->sample_id,
            'matched_parameter_id' => $result->parameter_id,
            'matched_result_id' => $result->id,
            'sample_code' => $result->sample->code,
            'parameter_code' => $result->parameter->code,
            'measured_value' => '7.42',
            'measured_unit' => $result->unit_label,
            'normalized_payload' => ['value' => '7.42'],
        ]);

        $this->actingAs($admin)
            ->post(route('integration-hub.transmissions.import', $transmission))
            ->assertRedirect();

        $result->refresh();
        $transmission->refresh();

        $this->assertSame('7.42', $result->inserted_value);
        $this->assertSame($admin->id, $result->inserted_by_id);
        $this->assertSame('individual', $result->insertion_method);
        $this->assertSame($transmission->id, data_get($result->extra_data, 'integration.transmission_id'));
        $this->assertSame('imported', $transmission->status);
        $this->assertSame($admin->id, $transmission->reviewed_by_id);

        $secondTransmission = IntegrationTransmission::factory()->for($connector, 'connector')->create([
            'mapping_id' => $mapping->id,
            'status' => 'matched',
            'matched_result_id' => $result->id,
            'measured_value' => '8.99',
        ]);

        $this->actingAs($admin)
            ->post(route('integration-hub.transmissions.import', $secondTransmission))
            ->assertSessionHasErrors('transmission');

        $this->assertSame('7.42', $result->fresh()->inserted_value);
    }

    public function test_outbound_delivery_is_hmac_signed_and_records_the_response(): void
    {
        Http::fake([
            'https://partner.example/*' => Http::response('', 204),
        ]);
        $signingSecret = 'integration-signing-secret';
        $connector = IntegrationConnector::factory()->create([
            'direction' => 'outbound',
            'configuration' => [
                'endpoint' => 'https://partner.example/v1/results',
                'timeout_seconds' => 10,
            ],
            'credentials' => ['bearer_token' => 'partner-token'],
            'signing_secret' => $signingSecret,
        ]);
        $delivery = IntegrationDelivery::factory()->for($connector, 'connector')->create();

        (new DeliverIntegrationWebhook($delivery))->handle();

        Http::assertSent(function (HttpRequest $request) use ($delivery, $signingSecret): bool {
            $timestamp = $request->header('X-LIMS-Timestamp')[0] ?? '';
            $expectedSignature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), $signingSecret);

            return $request->url() === 'https://partner.example/v1/results'
                && $request->header('X-LIMS-Signature')[0] === $expectedSignature
                && $request->header('Idempotency-Key')[0] === $delivery->idempotency_key
                && $request->header('Authorization')[0] === 'Bearer partner-token';
        });

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(204, $delivery->http_status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->delivered_at);
    }

    public function test_validated_analysis_is_published_to_subscribed_outbound_connectors(): void
    {
        Queue::fake();
        $result = $this->resultWithMachineReadableCodes();
        $connector = IntegrationConnector::factory()->create([
            'direction' => 'outbound',
            'status' => 'active',
            'event_types' => ['lims.analysis.validated'],
        ]);

        $deliveries = app(IntegrationPublisher::class)->publishValidatedAnalysis($result);

        $this->assertCount(1, $deliveries);
        $delivery = $deliveries[0];
        $this->assertSame($connector->id, $delivery->connector_id);
        $this->assertSame('lims.analysis.validated', $delivery->event_type);
        $this->assertSame('sample', $delivery->subject_type);
        $this->assertSame($result->sample_id, $delivery->subject_id);
        $this->assertSame($result->sample->code, data_get($delivery->payload, 'data.sample.code'));
        $this->assertNotEmpty(data_get($delivery->payload, 'data.results'));
        $this->assertNotContains(null, collect(data_get($delivery->payload, 'data.results'))->pluck('approved_at')->all());
        Queue::assertPushed(DeliverIntegrationWebhook::class, 1);
    }

    private function resultWithMachineReadableCodes(): Result
    {
        return Result::query()
            ->with(['sample.analysis', 'parameter'])
            ->whereHas('sample', fn ($query) => $query->whereNotNull('code'))
            ->whereHas('parameter', fn ($query) => $query->whereNotNull('code'))
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function ingestPayload(Result $result, string $externalId): array
    {
        return [
            'external_id' => $externalId,
            'correlation_id' => 'BATCH-2026-07-14',
            'payload' => [
                'message' => ['id' => $externalId],
                'result' => [
                    'sample_code' => $result->sample->code,
                    'parameter_code' => $result->parameter->code,
                    'value' => '7,21',
                    'unit' => $result->unit_label,
                    'measured_at' => now()->toIso8601String(),
                ],
            ],
        ];
    }
}
