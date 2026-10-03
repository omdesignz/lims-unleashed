<?php

namespace Tests\Feature;

use App\Actions\PrepareSampleEntryPayload;
use App\Jobs\DeliverIntegrationWebhook;
use App\Models\AnalysisCategory;
use App\Models\Customer;
use App\Models\Department;
use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use App\Models\IntegrationMapping;
use App\Models\IntegrationTransmission;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Result;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Services\Integrations\IntegrationPublisher;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IntegrationHubTest extends TestCase
{
    use DatabaseTransactions;

    private ?User $admin = null;

    private ?VAPLab $lab = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'null']);
    }

    private function verifiedAdmin(): User
    {
        if ($this->admin) {
            return $this->admin;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $this->admin->assignRole(Role::findOrCreate('admin', 'web'));
        foreach (['view_settings', 'edit_settings', 'insert_results'] as $permission) {
            $this->admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->admin->id]);

        return $this->admin;
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

    public function test_hub_lists_only_the_active_laboratorys_connectors_and_activity(): void
    {
        $admin = $this->verifiedAdmin();
        $own = IntegrationConnector::factory()->create(['lab_id' => $this->lab->id]);
        $other = IntegrationConnector::factory()->create(['lab_id' => VAPLab::factory()->create()->id]);
        IntegrationTransmission::factory()->for($own, 'connector')->create(['status' => 'matched']);
        IntegrationTransmission::factory()->for($other, 'connector')->create(['status' => 'matched']);
        IntegrationDelivery::factory()->for($own, 'connector')->create(['status' => 'failed']);
        IntegrationDelivery::factory()->for($other, 'connector')->create(['status' => 'failed']);

        $this->actingAs($admin)->get(route('integration-hub.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Integrations/Index')
                ->has('connectors', 1)
                ->where('connectors.0.uuid', $own->uuid)
                ->has('transmissions', 1)
                ->has('deliveries', 1)
                ->where('summary.active_connectors', 1)
                ->where('summary.review_queue', 1)
                ->where('summary.delivery_failures', 1));
    }

    public function test_admin_cannot_manage_another_laboratorys_connector_or_activity(): void
    {
        $admin = $this->verifiedAdmin();
        $connector = IntegrationConnector::factory()->create(['lab_id' => VAPLab::factory()->create()->id]);
        $transmission = IntegrationTransmission::factory()->for($connector, 'connector')->create(['status' => 'matched']);
        $delivery = IntegrationDelivery::factory()->for($connector, 'connector')->create(['event_type' => 'lims.connector.test']);
        $mapping = IntegrationMapping::factory()->for($connector, 'connector')->create();
        $originalToken = $connector->ingest_token_hash;

        $this->actingAs($admin)->put(route('integration-hub.connectors.update', $connector), [
            'name' => 'Attempted takeover',
            'direction' => 'inbound',
            'adapter' => 'rest_json',
            'status' => 'active',
        ])->assertNotFound();
        $this->post(route('integration-hub.mappings.store', $connector), [
            'name' => 'Attempted mapping',
            'field_paths' => ['sample_code' => 'result.sample', 'parameter_code' => 'result.parameter', 'value' => 'result.value'],
        ])->assertNotFound();
        $this->postJson(route('integration-hub.mappings.test', $connector), ['payload' => ['result' => ['sample' => 'A']]])
            ->assertNotFound();
        $this->post(route('integration-hub.connectors.rotate-token', $connector))->assertNotFound();
        $this->post(route('integration-hub.connectors.test', $connector))->assertNotFound();
        $this->post(route('integration-hub.transmissions.reject', $transmission), ['reason' => 'Not my lab'])->assertNotFound();
        $this->post(route('integration-hub.deliveries.retry', $delivery))->assertNotFound();

        $this->assertSame($originalToken, $connector->fresh()->ingest_token_hash);
        $this->assertSame('matched', $transmission->fresh()->status);
        $this->assertSame(1, $connector->mappings()->count());
        $this->assertSame($mapping->id, $connector->mappings()->firstOrFail()->id);
    }

    public function test_admin_can_create_an_inbound_connector_with_a_one_time_token_and_mapping(): void
    {
        $admin = $this->verifiedAdmin();
        $response = $this->actingAs($admin)
            ->post(route('integration-hub.connectors.store'), [
                'lab_id' => VAPLab::factory()->create()->id,
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
        $this->assertSame($this->lab->id, $connector->lab_id);
        $this->assertNotSame($revealedToken, $connector->ingest_token_hash);
        $this->assertSame('lab-edge-chemistry-01', data_get($connector->configuration, 'edge_agent_id'));
        $this->assertDatabaseHas('integration_mappings', [
            'connector_id' => $connector->id,
            'version' => 1,
            'is_active' => true,
        ]);
    }

    public function test_connector_update_cannot_reassign_its_owning_laboratory(): void
    {
        $admin = $this->verifiedAdmin();
        $connector = IntegrationConnector::factory()->create(['lab_id' => $this->lab->id]);

        $this->actingAs($admin)->put(route('integration-hub.connectors.update', $connector), [
            'lab_id' => VAPLab::factory()->create()->id,
            'name' => 'Updated local connector',
            'direction' => 'inbound',
            'adapter' => 'rest_json',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertSame($this->lab->id, $connector->fresh()->lab_id);
        $this->assertSame('Updated local connector', $connector->fresh()->name);
    }

    public function test_ingest_requires_the_connector_token_and_is_idempotent(): void
    {
        $result = $this->resultWithMachineReadableCodes(false);
        $connector = IntegrationConnector::factory()->create(['lab_id' => $this->lab->id]);
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

    public function test_inbound_connector_does_not_match_another_laboratorys_sample(): void
    {
        $this->verifiedAdmin();
        $otherResult = $this->resultWithMachineReadableCodes(false, VAPLab::factory()->create());
        $connector = IntegrationConnector::factory()->create(['lab_id' => $this->lab->id]);
        $token = $connector->rotateIngestToken();
        IntegrationMapping::factory()->for($connector, 'connector')->create();

        $this->withToken($token)
            ->postJson(route('api.integrations.ingest', $connector), $this->ingestPayload($otherResult, 'OTHER-LAB-RESULT'))
            ->assertAccepted()
            ->assertJsonPath('data.status', 'quarantined');

        $transmission = $connector->transmissions()->firstOrFail();
        $this->assertNull($transmission->matched_sample_id);
        $this->assertNull($transmission->matched_parameter_id);
        $this->assertNull($transmission->matched_result_id);
        $this->assertNull(data_get($transmission->diagnostics, 'matches.sample_id'));
    }

    public function test_inbound_matching_uses_the_issued_parameter_code_after_catalogue_rename(): void
    {
        $result = $this->resultWithMachineReadableCodes(false);
        $issuedCode = $result->parameter->code;
        $result->parameter->update(['code' => 'RENAMED-'.fake()->unique()->bothify('########')]);
        $connector = IntegrationConnector::factory()->create(['lab_id' => $this->lab->id]);
        $token = $connector->rotateIngestToken();
        IntegrationMapping::factory()->for($connector, 'connector')->create();
        $payload = $this->ingestPayload($result, 'ISSUED-CODE-RESULT');
        $payload['payload']['result']['parameter_code'] = $issuedCode;

        $this->withToken($token)
            ->postJson(route('api.integrations.ingest', $connector), $payload)
            ->assertAccepted()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.parameter_code', $issuedCode);

        $this->assertSame($result->id, $connector->transmissions()->firstOrFail()->matched_result_id);
    }

    public function test_authorized_reviewer_can_import_a_matched_transmission_without_overwriting_results(): void
    {
        $admin = $this->verifiedAdmin();
        $result = $this->resultWithMachineReadableCodes(false);
        $departmentId = $result->sample?->analysis?->department_id;

        PersonnelQualification::query()->updateOrCreate([
            'lab_id' => $this->lab->id,
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

        $connector = IntegrationConnector::factory()->create(['lab_id' => $this->lab->id, 'name' => 'ICP-MS 01']);
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
        $this->assertNotNull($result->sample->analysis->fresh()->init_date);
        $this->assertSame('EN_PROGRESO', $result->sample->collection->collection->sampleEntry->fresh()->status);

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

    public function test_reviewer_cannot_import_a_result_owned_by_another_laboratory(): void
    {
        $result = $this->resultWithMachineReadableCodes(false);
        $transmission = $this->matchedTransmission($result);
        $otherLab = VAPLab::factory()->create();
        $reviewer = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $reviewer->givePermissionTo(Permission::findOrCreate('insert_results', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $otherLab->id, 'user_id' => $reviewer->id]);
        $this->qualifyForInsertion($reviewer, $result);

        $this->actingAs($reviewer)->withSession(['active_lab_id' => $otherLab->id])
            ->post(route('integration-hub.transmissions.import', $transmission))
            ->assertNotFound();

        $this->assertNull($result->fresh()->inserted_value);
        $this->assertSame('matched', $transmission->fresh()->status);
    }

    public function test_import_rejects_a_connector_owned_by_another_laboratory(): void
    {
        $reviewer = $this->verifiedAdmin();
        $result = $this->resultWithMachineReadableCodes(false);
        $this->qualifyForInsertion($reviewer, $result);
        $transmission = $this->matchedTransmission($result);
        $transmission->connector->forceFill(['lab_id' => VAPLab::factory()->create()->id])->save();

        $this->actingAs($reviewer)
            ->post(route('integration-hub.transmissions.import', $transmission))
            ->assertNotFound();

        $this->assertNull($result->fresh()->inserted_value);
        $this->assertSame('matched', $transmission->fresh()->status);
    }

    public function test_import_rejects_a_transmission_pointing_at_a_different_sample(): void
    {
        $reviewer = $this->verifiedAdmin();
        $result = $this->resultWithMachineReadableCodes(false);
        $otherResult = $this->resultWithMachineReadableCodes(false);
        $this->qualifyForInsertion($reviewer, $result);
        $transmission = $this->matchedTransmission($result, ['matched_sample_id' => $otherResult->sample_id]);

        $this->actingAs($reviewer)
            ->post(route('integration-hub.transmissions.import', $transmission))
            ->assertSessionHasErrors('transmission');

        $this->assertNull($result->fresh()->inserted_value);
        $this->assertSame('matched', $transmission->fresh()->status);
    }

    public function test_import_rejects_wrong_units_and_non_numeric_quantitative_values(): void
    {
        $reviewer = $this->verifiedAdmin();
        $result = $this->resultWithMachineReadableCodes(false);
        $this->qualifyForInsertion($reviewer, $result);

        foreach ([['measured_unit' => 'g/L'], ['measured_value' => 'not-a-number']] as $invalid) {
            $transmission = $this->matchedTransmission($result, $invalid);
            $this->actingAs($reviewer)
                ->post(route('integration-hub.transmissions.import', $transmission))
                ->assertSessionHasErrors('transmission');
            $this->assertSame('matched', $transmission->fresh()->status);
            $this->assertNull($result->fresh()->inserted_value);
        }
    }

    public function test_import_rejects_a_parameter_added_after_sample_issuance(): void
    {
        $reviewer = $this->verifiedAdmin();
        $result = $this->resultWithMachineReadableCodes(false);
        $this->qualifyForInsertion($reviewer, $result);
        $lateParameter = Parameter::query()->create([
            'name' => 'Late integration parameter',
            'code' => 'LATE-'.fake()->unique()->bothify('########'),
            'active' => true,
        ]);
        $result->sample->analysis->profile->parameters()->attach($lateParameter, ['unit_id' => $result->unit_id, 'unit_label' => $result->unit_label]);
        $lateResult = $result->replicate();
        $lateResult->forceFill(['parameter_id' => $lateParameter->id, 'parameter_label' => $lateParameter->name])->save();
        $transmission = $this->matchedTransmission($lateResult);

        $this->actingAs($reviewer)
            ->post(route('integration-hub.transmissions.import', $transmission))
            ->assertSessionHasErrors('transmission');

        $this->assertNull($lateResult->fresh()->inserted_value);
        $this->assertSame('matched', $transmission->fresh()->status);
    }

    public function test_rejected_transmission_cannot_be_imported_afterwards(): void
    {
        $reviewer = $this->verifiedAdmin();
        $result = $this->resultWithMachineReadableCodes(false);
        $transmission = $this->matchedTransmission($result);

        $this->actingAs($reviewer)
            ->post(route('integration-hub.transmissions.reject', $transmission), ['reason' => 'Invalid instrument run'])
            ->assertRedirect();
        $this->post(route('integration-hub.transmissions.import', $transmission))
            ->assertSessionHasErrors('transmission');

        $this->assertSame('rejected', $transmission->fresh()->status);
        $this->assertNull($result->fresh()->inserted_value);
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
        $delivery = IntegrationDelivery::factory()->for($connector, 'connector')->create(['event_type' => 'lims.connector.test']);

        app()->call([(new DeliverIntegrationWebhook($delivery)), 'handle']);

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

    public function test_manual_retry_only_queues_failed_or_retrying_deliveries_once(): void
    {
        Queue::fake();
        $admin = $this->verifiedAdmin();
        $connector = IntegrationConnector::factory()->create(['lab_id' => $this->lab->id]);
        $delivery = IntegrationDelivery::factory()->for($connector, 'connector')->create([
            'event_type' => 'lims.connector.test',
            'status' => 'failed',
        ]);

        $this->actingAs($admin)->post(route('integration-hub.deliveries.retry', $delivery))->assertRedirect();
        $this->assertSame('pending', $delivery->fresh()->status);
        Queue::assertPushed(DeliverIntegrationWebhook::class, 1);
        Queue::assertPushed(DeliverIntegrationWebhook::class, fn (DeliverIntegrationWebhook $job): bool => $job->afterCommit === true);

        $this->post(route('integration-hub.deliveries.retry', $delivery))->assertRedirect();
        $this->assertSame('pending', $delivery->fresh()->status);
        Queue::assertPushed(DeliverIntegrationWebhook::class, 1);

        $delivery->forceFill(['status' => 'delivered', 'delivered_at' => now()])->save();
        $this->post(route('integration-hub.deliveries.retry', $delivery))->assertRedirect();
        $this->assertSame('delivered', $delivery->fresh()->status);
        Queue::assertPushed(DeliverIntegrationWebhook::class, 1);
    }

    public function test_stale_delivery_job_does_not_resend_or_reopen_a_delivered_record(): void
    {
        Http::fake();
        $connector = IntegrationConnector::factory()->create();
        $delivery = IntegrationDelivery::factory()->for($connector, 'connector')->create([
            'event_type' => 'lims.connector.test',
            'status' => 'delivered',
            'attempts' => 1,
            'delivered_at' => now(),
        ]);
        $job = new DeliverIntegrationWebhook($delivery);

        app()->call([$job, 'handle']);
        $job->failed(new RuntimeException('Stale queued failure'));

        Http::assertNothingSent();
        $this->assertSame('delivered', $delivery->fresh()->status);
        $this->assertSame(1, $delivery->fresh()->attempts);
        $this->assertNotNull($delivery->fresh()->delivered_at);
    }

    public function test_stale_delivery_job_does_not_send_a_terminal_failure(): void
    {
        Http::fake();
        $connector = IntegrationConnector::factory()->create();
        $delivery = IntegrationDelivery::factory()->for($connector, 'connector')->create([
            'event_type' => 'lims.connector.test',
            'status' => 'failed',
            'attempts' => 5,
        ]);

        app()->call([(new DeliverIntegrationWebhook($delivery)), 'handle']);

        Http::assertNothingSent();
        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertSame(5, $delivery->fresh()->attempts);
    }

    public function test_queued_delivery_does_not_send_a_sample_to_another_laboratorys_connector(): void
    {
        Http::fake();
        $result = $this->resultWithMachineReadableCodes();
        $connector = IntegrationConnector::factory()->create([
            'lab_id' => VAPLab::factory()->create()->id,
            'direction' => 'outbound',
            'status' => 'active',
            'event_types' => ['lims.analysis.validated'],
            'configuration' => ['endpoint' => 'https://partner.example/v1/results'],
        ]);
        $delivery = IntegrationDelivery::factory()->for($connector, 'connector')->create([
            'event_type' => 'lims.analysis.validated',
            'subject_type' => $result->sample->getMorphClass(),
            'subject_id' => $result->sample_id,
        ]);

        try {
            app()->call([(new DeliverIntegrationWebhook($delivery)), 'handle']);
            $this->fail('The cross-laboratory delivery should fail before sending.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('laboratório', $exception->getMessage());
        }

        Http::assertNothingSent();
        $this->assertSame('pending', $delivery->fresh()->status);
    }

    public function test_queued_delivery_sends_a_validated_sample_to_its_own_laboratorys_connector(): void
    {
        Queue::fake();
        Http::fake(['https://partner.example/*' => Http::response('', 204)]);
        $result = $this->resultWithMachineReadableCodes();
        IntegrationConnector::factory()->create([
            'lab_id' => $this->lab->id,
            'direction' => 'outbound',
            'status' => 'active',
            'event_types' => ['lims.analysis.validated'],
            'configuration' => ['endpoint' => 'https://partner.example/v1/results'],
        ]);

        $deliveries = app(IntegrationPublisher::class)->publishValidatedAnalysis($result);

        $this->assertCount(1, $deliveries);
        app()->call([(new DeliverIntegrationWebhook($deliveries[0])), 'handle']);
        Http::assertSentCount(1);
        $this->assertSame('delivered', $deliveries[0]->fresh()->status);
    }

    public function test_validated_analysis_is_published_to_subscribed_outbound_connectors(): void
    {
        Queue::fake();
        $result = $this->resultWithMachineReadableCodes();
        $issuedCode = $result->parameter->code;
        $issuedName = $result->parameter->name;
        $result->parameter->update([
            'code' => 'RENAMED-'.fake()->unique()->bothify('########'),
            'name' => 'Renamed catalogue parameter',
        ]);
        $connector = IntegrationConnector::factory()->create([
            'lab_id' => $this->lab->id,
            'direction' => 'outbound',
            'status' => 'active',
            'event_types' => ['lims.analysis.validated'],
        ]);
        $otherConnector = IntegrationConnector::factory()->create([
            'lab_id' => VAPLab::factory()->create()->id,
            'direction' => 'outbound',
            'status' => 'active',
            'event_types' => ['lims.analysis.validated'],
        ]);

        $deliveries = app(IntegrationPublisher::class)->publishValidatedAnalysis($result);

        $this->assertCount(1, $deliveries);
        $delivery = $deliveries[0];
        $this->assertSame($connector->id, $delivery->connector_id);
        $this->assertSame(0, $otherConnector->deliveries()->count());
        $this->assertSame('lims.analysis.validated', $delivery->event_type);
        $this->assertSame('sample', $delivery->subject_type);
        $this->assertSame($result->sample_id, $delivery->subject_id);
        $this->assertSame($result->sample->code, data_get($delivery->payload, 'data.sample.code'));
        $this->assertNotEmpty(data_get($delivery->payload, 'data.results'));
        $this->assertSame($issuedCode, data_get($delivery->payload, 'data.results.0.parameter_code'));
        $this->assertSame($issuedName, data_get($delivery->payload, 'data.results.0.parameter_name'));
        $this->assertSame($result->unit_label, data_get($delivery->payload, 'data.results.0.unit'));
        $this->assertNotContains(null, collect(data_get($delivery->payload, 'data.results'))->pluck('approved_at')->all());
        Queue::assertPushed(DeliverIntegrationWebhook::class, 1);
        Queue::assertPushed(DeliverIntegrationWebhook::class, fn (DeliverIntegrationWebhook $job): bool => $job->afterCommit === true);
    }

    public function test_outbound_payload_excludes_parameters_added_after_sample_issuance(): void
    {
        Queue::fake();
        $result = $this->resultWithMachineReadableCodes();
        $lateParameter = Parameter::query()->create([
            'name' => 'Late outbound parameter',
            'code' => 'LATE-'.fake()->unique()->bothify('########'),
            'active' => true,
        ]);
        $result->sample->analysis->profile->parameters()->attach($lateParameter, [
            'unit_id' => $result->unit_id,
            'unit_label' => $result->unit_label,
        ]);
        $lateResult = $result->replicate();
        $lateResult->forceFill(['parameter_id' => $lateParameter->id, 'parameter_label' => $lateParameter->name])->save();
        IntegrationConnector::factory()->create([
            'lab_id' => $this->lab->id,
            'direction' => 'outbound',
            'status' => 'active',
            'event_types' => ['lims.analysis.validated'],
        ]);

        $deliveries = app(IntegrationPublisher::class)->publishValidatedAnalysis($result);

        $this->assertCount(1, $deliveries);
        $this->assertSame([$result->id], collect(data_get($deliveries[0]->payload, 'data.results'))->pluck('id')->all());
        $this->assertSame([], app(IntegrationPublisher::class)->publishValidatedAnalysis($lateResult));
        Queue::assertPushed(DeliverIntegrationWebhook::class, 1);
    }

    private function resultWithMachineReadableCodes(bool $approved = true, ?VAPLab $lab = null): Result
    {
        $this->verifiedAdmin();
        $lab ??= $this->lab;
        $customer = Customer::query()->create(['name' => 'Integration customer '.fake()->uuid()]);
        $warehouse = Warehouse::query()->create(['name' => 'Integration site '.fake()->uuid(), 'customer_id' => $customer->id]);
        $department = Department::factory()->create();
        $category = AnalysisCategory::query()->create([
            'name' => 'Integration category '.fake()->uuid(),
            'code' => fake()->uuid(),
            'department_id' => $department->id,
        ]);
        $matrix = Matrix::query()->create(['code' => 'INTEGRATION-'.fake()->unique()->bothify('########')]);
        $profile = Profile::query()->create([
            'name' => 'Integration profile '.fake()->uuid(),
            'code' => 'INTEGRATION-'.fake()->unique()->bothify('########'),
            'category_id' => $category->id,
        ]);
        $matrix->profiles()->attach($profile);
        $parameter = Parameter::query()->create([
            'name' => 'Integration parameter '.fake()->uuid(),
            'code' => 'INTEGRATION-'.fake()->unique()->bothify('########'),
            'active' => true,
        ]);
        $unit = Unit::query()->firstOrCreate(['code' => 'mg/L']);
        $profile->parameters()->attach($parameter, ['unit_id' => $unit->id, 'unit_label' => $unit->code]);
        $product = Product::query()->create(['name' => 'Integration product', 'matrix_id' => $matrix->id]);
        $intake = app(PrepareSampleEntryPayload::class)->execute([
            'lab_id' => $lab->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'department_id' => $department->id,
            'client_submitted_info' => [
                'request_origin' => 'internal',
                'collection_type' => 'direct',
                'product_id' => $product->id,
                'requested_profile_ids' => [$profile->id],
            ],
        ], null);
        $entry = VAPSampleEntry::factory()->create([...$intake, 'lab_id' => $lab->id]);
        $accession = app(SampleEntryCollectionFlowService::class)->sync($entry);
        $code = $accession->code;
        $sample = $code->samples()->firstOrFail();
        $analysis = $sample->analysis;

        return Result::query()->create([
            'sample_id' => $sample->id,
            'code_id' => $code->id,
            'collection_id' => $accession->id,
            'product_id' => $product->id,
            'profile_id' => $profile->id,
            'parameter_id' => $parameter->id,
            'parameter_label' => $parameter->name,
            'unit_id' => $unit->id,
            'unit_label' => $unit->code,
            'resultable_id' => $analysis->id,
            'resultable_type' => $analysis->getMorphClass(),
            'inserted_value' => $approved ? '7.42' : null,
            'inserted_date' => $approved ? now()->subDays(2) : null,
            'verified_value' => $approved ? '7.42' : null,
            'verified_date' => $approved ? now()->subDay() : null,
            'approved_value' => $approved ? '7.42' : null,
            'approved_date' => $approved ? now() : null,
        ])->load(['sample.analysis', 'parameter']);
    }

    /** @param array<string, mixed> $attributes */
    private function matchedTransmission(Result $result, array $attributes = []): IntegrationTransmission
    {
        $connector = IntegrationConnector::factory()->create(['lab_id' => $this->lab->id]);
        $mapping = IntegrationMapping::factory()->for($connector, 'connector')->create();

        return IntegrationTransmission::factory()->for($connector, 'connector')->create([
            'mapping_id' => $mapping->id,
            'status' => 'matched',
            'matched_sample_id' => $result->sample_id,
            'matched_parameter_id' => $result->parameter_id,
            'matched_result_id' => $result->id,
            'sample_code' => $result->sample->code,
            'parameter_code' => $result->parameter->code,
            'measured_value' => '7.42',
            'measured_unit' => $result->unit_label,
            ...$attributes,
        ]);
    }

    private function qualifyForInsertion(User $reviewer, Result $result): void
    {
        PersonnelQualification::query()->updateOrCreate([
            'lab_id' => $this->lab->id,
            'user_id' => $reviewer->id,
            'capability' => 'insert_results',
            'department_id' => $result->sample->analysis->department_id,
        ], [
            'qualified_by_id' => $this->verifiedAdmin()->id,
            'authorized_from' => now()->subDay()->toDateString(),
            'authorized_until' => now()->addYear()->toDateString(),
            'training_completed_at' => now()->subDay()->toDateString(),
            'training_reference' => 'INTEGRATION-HUB-REVIEW',
            'is_active' => true,
        ]);
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
