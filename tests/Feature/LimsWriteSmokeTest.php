<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\Formula;
use App\Models\LabCode;
use App\Models\NormativeWorkProcedure;
use App\Models\PackagingCategory;
use App\Models\Parameter;
use App\Models\CounterAnalysis;
use App\Models\CollectionProduct;
use App\Models\Protocol;
use App\Models\QualityCertificate;
use App\Models\QualityCertificateRevision;
use App\Models\CustomerRequest;
use App\Models\Complaint;
use App\Models\ManagementReview;
use App\Models\PersonnelQualification;
use App\Models\Proposal;
use App\Models\ProposalTemplate;
use App\Models\Product;
use App\Models\Profile;
use App\Models\ResultCategory;
use App\Models\ReagentConsumption;
use App\Models\Result;
use App\Models\Role;
use App\Models\Sample;
use App\Models\Standard;
use App\Models\TaxExemption;
use App\Models\TaxType;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleDiscard;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Models\AnalysisCategory;
use App\Models\Worksheet;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LimsWriteSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private const PNG_SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sX6lz4AAAAASUVORK5CYII=';

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for write smoke testing.');

        return $admin;
    }

    private function captureResponseOutput(callable $callback): array
    {
        ob_start();

        try {
            $response = $callback();
            $output = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return [$response, (string) $output];
    }

    private function qualifyUser(User $user, array $capabilities, ?Department $department = null): void
    {
        foreach ($capabilities as $capability) {
            PersonnelQualification::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'capability' => $capability,
                    'department_id' => $department?->id,
                ],
                [
                    'qualified_by_id' => $user->id,
                    'authorized_from' => now()->subDay()->toDateString(),
                    'authorized_until' => now()->addYear()->toDateString(),
                    'training_completed_at' => now()->subDay()->toDateString(),
                    'training_reference' => 'SMOKE-' . strtoupper($capability),
                    'is_active' => true,
                ]
            );
        }
    }

    private function acceptedProposal(Customer $customer, Warehouse $warehouse, Department $department, User $user): Proposal
    {
        $existing = Proposal::query()
            ->accepted()
            ->where('customer_id', $customer->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $template = ProposalTemplate::query()->first();

        if (! $template) {
            $template = new ProposalTemplate();
            $template->forceFill([
                'name' => 'Smoke Proposal Template',
                'content' => '<p>Smoke proposal content</p>',
                'user_id' => $user->id,
                'category' => 'general',
            ])->save();
            $template->refresh();
        }

        return Proposal::query()->create([
            'proposal_year' => now()->year,
            'service_location' => 'Smoke Workflow Validation',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'department_id' => $department->id,
            'template_id' => $template->id,
            'status' => 'ACCEPTED',
            'details' => [],
            'is_original' => true,
            'sub_total' => 0,
            'total' => 0,
            'tolerance_days' => 0,
            'user_id' => $user->id,
        ]);
    }

    public function test_verified_admin_can_create_and_update_sample_intake(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $packaging = PackagingCategory::query()->firstOrFail();
        $this->qualifyUser($user, ['sample_intake_validation'], $department);
        $code = 'SMK-' . Str::upper(Str::random(8));

        $storePayload = [
            'name' => 'Codex Smoke Sample',
            'code' => $code,
            'sample_type' => 'ROTINA',
            'customer_id' => $customer->id,
            'lab_id' => $lab->id,
            'department_id' => $department->id,
            'warehouse_id' => $warehouse->id,
            'packaging_id' => $packaging->id,
            'received_at' => now()->toDateTimeString(),
            'requested_services' => 'Smoke intake validation',
            'obs' => 'Created by transactional smoke test',
            'status' => 'POR_INICIAR',
            'collected_by_lab' => false,
        ];

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), $storePayload)
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $sample = VAPSampleEntry::query()->where('code', $code)->first();
        $this->assertNotNull($sample, 'Expected the smoke-created sample to exist.');

        $this->assertDatabaseHas('sample_entries', [
            'id' => $sample->id,
            'name' => 'Codex Smoke Sample',
            'status' => 'POR_INICIAR',
            'customer_id' => $customer->id,
        ]);

        $updatePayload = array_merge($storePayload, [
            'name' => 'Codex Smoke Sample Updated',
            'proposal_id' => $this->acceptedProposal($customer, $warehouse, $department, $user)->id,
            'status' => 'EN_PROGRESO',
            'obs' => 'Updated by transactional smoke test',
            'analysis_start_date' => now()->toDateTimeString(),
        ]);

        $this->actingAs($user)
            ->put(route('vap_samples.samples.update', $sample), $updatePayload)
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('sample_entries', [
            'id' => $sample->id,
            'name' => 'Codex Smoke Sample Updated',
            'status' => 'EN_PROGRESO',
            'obs' => 'Updated by transactional smoke test',
        ]);
    }

    public function test_verified_admin_can_create_sample_intake_with_generated_code(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Generated Code Smoke Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'requested_services' => 'Auto-code validation',
                'status' => 'POR_INICIAR',
                'collected_by_lab' => true,
                'collected_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $sample = VAPSampleEntry::query()
            ->where('name', 'Generated Code Smoke Sample')
            ->latest('id')
            ->first();

        $this->assertNotNull($sample, 'Expected the auto-coded smoke sample to exist.');
        $this->assertMatchesRegularExpression(
            '/^SMP-\d{4}-ROT-\d{5}$/',
            (string) $sample->code,
            'Expected the sample code to be auto-generated using the documented format.'
        );
        $this->assertNotNull($sample->received_by_id);
        $this->assertSame($user->id, $sample->received_by_id);
    }

    public function test_validated_sample_intake_can_attach_to_the_normal_collection_and_analysis_flow(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $packaging = PackagingCategory::query()->firstOrFail();
        $product = Product::query()->whereHas('matrix.profiles')->firstOrFail();
        $profileIds = $product->matrix->profiles->pluck('id')->take(2)->values();

        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Linked Flow Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'packaging_id' => $packaging->id,
                'received_at' => now()->toDateTimeString(),
                'requested_services' => $profileIds->all(),
                'status' => 'POR_INICIAR',
                'collected_by_lab' => true,
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'packaging_id' => $packaging->id,
                    'requested_profile_ids' => $profileIds->all(),
                    'quantity' => '1',
                    'lot' => 'LINK-' . now()->format('His'),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $sampleEntry = VAPSampleEntry::query()
            ->where('name', 'Linked Flow Sample')
            ->latest('id')
            ->first();

        $this->assertNotNull($sampleEntry);
        $this->assertNotNull($sampleEntry->collection_product_id);

        $collectionProduct = CollectionProduct::query()
            ->with('code.samples.analysis')
            ->find($sampleEntry->collection_product_id);

        $this->assertNotNull($collectionProduct);
        $this->assertSame($product->id, $collectionProduct->product_id);
        $this->assertCount($profileIds->count(), $collectionProduct->code->samples);
        $this->assertGreaterThan(0, $collectionProduct->code->analysis()->count());
    }

    public function test_sample_intake_persists_conditioning_and_parameter_scope_snapshot(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        $profile = $product->matrix->profiles
            ->firstWhere('type.department_id', $product->matrix->profiles->first()?->type?->department_id);

        $this->assertNotNull($profile, 'Expected a product matrix profile with a department.');

        $department = Department::query()->findOrFail($profile->type->department_id);
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Scoped Intake Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                    'conditioning_status' => 'restricted',
                    'packaging_condition' => 'Secondary seal damp but intact',
                    'temperature_condition' => '2-8 C on arrival',
                    'integrity_observations' => 'Container identified and intact.',
                    'chain_of_custody_notes' => 'Delivered by courier with cold box.',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $sample = VAPSampleEntry::query()
            ->where('name', 'Scoped Intake Sample')
            ->latest('id')
            ->first();

        $this->assertNotNull($sample);
        $this->assertSame('restricted', data_get($sample->client_submitted_info, 'conditioning_status'));
        $this->assertSame([$profile->id], data_get($sample->client_submitted_info, 'resolved_profile_ids'));
        $this->assertGreaterThan(0, (int) data_get($sample->client_submitted_info, 'required_parameter_count'));
        $this->assertNotEmpty(data_get($sample->client_submitted_info, 'required_parameters'));

        $this->actingAs($user)
            ->get(route('vap_samples.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPSamples/Index')
                ->has('charts.intake_trend.categories', 7)
                ->has('charts.intake_trend.series', 1)
                ->where('charts.lifecycle_status.labels.0', 'Por iniciar')
                ->where('charts.retention_pressure.labels.2', 'Retenção vencida')
                ->where('samples.0.client_submitted_info.conditioning_status', data_get($sample->client_submitted_info, 'conditioning_status'))
            );
    }

    public function test_sample_intake_rejects_profiles_outside_the_selected_product_matrix(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type'])
            ->firstOrFail();

        $validProfile = $product->matrix->profiles->first();
        $this->assertNotNull($validProfile);

        $invalidProfile = Profile::query()
            ->whereKeyNot($validProfile->id)
            ->whereDoesntHave('matrixes', fn ($query) => $query->whereKey($product->matrix_id))
            ->firstOrFail();

        $department = Department::query()->findOrFail($validProfile->type->department_id);
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->from(route('vap_samples.index'))
            ->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Invalid Scope Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$validProfile->id, $invalidProfile->id],
                ],
            ])
            ->assertRedirect(route('vap_samples.index'))
            ->assertSessionHasErrors('client_submitted_info.requested_profile_ids');

        $this->assertDatabaseMissing('sample_entries', [
            'name' => 'Invalid Scope Sample',
        ]);
    }

    public function test_analysis_workflow_exposes_reception_scope_and_conditioning_snapshot(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        $profile = $product->matrix->profiles->first();
        $this->assertNotNull($profile);

        $department = Department::query()->findOrFail($profile->type->department_id);
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Analysis Scope Audit Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                    'conditioning_status' => 'accepted',
                    'packaging_condition' => 'Tamper-evident seal intact',
                    'temperature_condition' => 'Ambient at reception',
                    'integrity_observations' => 'Labels legible and container intact.',
                    'chain_of_custody_notes' => 'Transferred directly from courier to reception.',
                ],
            ])
            ->assertRedirect();

        $sampleEntry = VAPSampleEntry::query()
            ->where('name', 'Analysis Scope Audit Sample')
            ->latest('id')
            ->firstOrFail();

        $analysis = CollectionProduct::query()
            ->with('code.samples.analysis')
            ->findOrFail($sampleEntry->collection_product_id)
            ->code
            ->samples
            ->first()
            ?->analysis;

        $this->assertNotNull($analysis);

        $this->actingAs($user)
            ->get(route('analysis.edit', $analysis))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Analysis/ResultsWorkflow')
                ->where('scope_audit.conditioning_status', 'accepted')
                ->where('scope_audit.expected_count', $profile->parameters->unique('id')->count())
                ->where('scope_audit.reception_count', $profile->parameters->unique('id')->count())
                ->where('scope_audit.packaging_condition', 'Tamper-evident seal intact')
            );
    }

    public function test_result_submission_rejects_parameters_outside_the_analysis_profile(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        $profile = $product->matrix->profiles->first();
        $this->assertNotNull($profile);
        $department = Department::query()->findOrFail($profile->type->department_id);

        $this->qualifyUser($user, ['sample_intake_validation', 'insert_results'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Invalid Result Scope Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                ],
            ])
            ->assertRedirect();

        $sampleEntry = VAPSampleEntry::query()
            ->where('name', 'Invalid Result Scope Sample')
            ->latest('id')
            ->firstOrFail();

        $analysis = CollectionProduct::query()
            ->with('code.samples.analysis')
            ->findOrFail($sampleEntry->collection_product_id)
            ->code
            ->samples
            ->first()
            ?->analysis;

        $this->assertNotNull($analysis);

        $resultsPayload = $this->actingAs($user)
            ->getJson(route('results.getDefaultResultsData', [
                'action' => 'analyze',
                'sample_id' => $analysis->sample_id,
            ]))
            ->assertOk()
            ->json();

        $this->assertNotEmpty($resultsPayload);

        $invalidParameter = Parameter::query()
            ->whereKeyNot($profile->parameters->pluck('id'))
            ->firstOrFail();

        $resultsPayload[0]['parameter_id']['value'] = $invalidParameter->id;
        $resultsPayload[0]['parameter_id']['label'] = $invalidParameter->name;
        $resultsPayload[0]['parameter_label'] = $invalidParameter->name;

        $this->from(route('analysis.edit', $analysis))
            ->actingAs($user)
            ->post(route('results.store'), [
                'action' => 'analyze',
                'sample_id' => [
                    'value' => $analysis->sample_id,
                    'label' => (string) $analysis->sample_id,
                ],
                'results' => $resultsPayload,
            ])
            ->assertRedirect(route('analysis.edit', $analysis))
            ->assertSessionHasErrors('results');
    }

    public function test_default_results_payload_exposes_calculation_control_metadata(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $unit = Unit::query()->firstOrFail();
        $protocol = Protocol::query()->firstOrFail();
        $nwp = NormativeWorkProcedure::query()->firstOrFail();
        $standard = Standard::query()->firstOrFail();
        $resultCategory = ResultCategory::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        $profile = $product->matrix->profiles->first();
        $this->assertNotNull($profile);

        $department = Department::query()->findOrFail($profile->type->department_id);
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $suffix = Str::upper(Str::random(6));
        $calculatedParameter = Parameter::query()->create([
            'name' => 'Payload Calc Parameter ' . $suffix,
            'code' => 'PCP-' . $suffix,
            'description' => 'Calculated parameter for results payload smoke test.',
            'price' => 0,
            'charge_tax' => false,
            'withhold_tax' => false,
            'active' => true,
            'tax_percentage' => 0,
            'optimal_analysis_time' => '24h',
            'requires_calculation' => true,
            'formula_expression' => '({DENSITY} * {MASS}) / {VOLUME}',
            'calculation_parameters' => ['DENSITY', 'MASS', 'VOLUME'],
            'decimal_places' => 3,
            'result_type' => 'quantitative',
            'result_is_qualitative' => false,
        ]);

        $profile->parameters()->attach($calculatedParameter->id, [
            'unit_id' => $unit->id,
            'unit_label' => $unit->code,
            'protocol_id' => $protocol->id,
            'protocol_label' => $protocol->code,
            'nwp_id' => $nwp->id,
            'nwp_label' => $nwp->code,
            'standard_id' => $standard->id,
            'standard_label' => $standard->code,
            'category_id' => $resultCategory->id,
            'category_label' => $resultCategory->name,
            'min_ref_value' => 0,
            'max_ref_value' => 100,
            'dilutions' => json_encode([]),
            'extra_data' => json_encode([]),
            'count' => true,
            'ref_val_origin' => 'payload-test',
        ]);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Calculation Payload Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                ],
            ])
            ->assertRedirect();

        $sampleEntry = VAPSampleEntry::query()
            ->where('name', 'Calculation Payload Sample')
            ->latest('id')
            ->firstOrFail();

        $analysis = CollectionProduct::query()
            ->with('code.samples.analysis')
            ->findOrFail($sampleEntry->collection_product_id)
            ->code
            ->samples
            ->first()
            ?->analysis;

        $this->assertNotNull($analysis);

        $payload = $this->actingAs($user)
            ->getJson(route('results.getDefaultResultsData', [
                'action' => 'analyze',
                'sample_id' => $analysis->sample_id,
            ]))
            ->assertOk()
            ->json();

        $calculatedPayload = collect($payload)->firstWhere('parameter_id.value', $calculatedParameter->id);

        $this->assertNotNull($calculatedPayload);
        $this->assertTrue((bool) data_get($calculatedPayload, 'requires_calculation'));
        $this->assertSame($calculatedParameter->code, data_get($calculatedPayload, 'parameter_id.code'));
        $this->assertSame(
            ['DENSITY', 'MASS', 'VOLUME'],
            data_get($calculatedPayload, 'calculation_parameters')
        );
        $this->assertSame('({DENSITY} * {MASS}) / {VOLUME}', data_get($calculatedPayload, 'formula_expression'));
    }

    public function test_counter_analysis_results_payload_exposes_calculation_control_metadata(): void
    {
        $user = $this->verifiedAdmin();
        $result = Result::query()
            ->with(['sample.analysis.profile.parameters', 'parameter.formula', 'code'])
            ->where('requested_counter_analysis', false)
            ->whereHas('sample.analysis')
            ->whereDoesntHave('counter_analysis')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('counteranalysis.store'), [
                'result_id' => $result->id,
            ])
            ->assertRedirect(route('analysis.index'));

        $counterAnalysis = CounterAnalysis::query()
            ->where('result_id', $result->id)
            ->latest('id')
            ->firstOrFail();

        $payload = $this->actingAs($user)
            ->getJson(route('results.getCounterAnalysisDefaultResultsData', [
                'action' => 'analyze',
                'sample_id' => $counterAnalysis->sample_id,
            ]))
            ->assertOk()
            ->json();

        $counterAnalysisResult = collect($payload)->firstWhere('parameter_id.value', $result->parameter_id);

        $this->assertNotNull($counterAnalysisResult);
        $this->assertSame($result->parameter->code, data_get($counterAnalysisResult, 'parameter_id.code'));
        $this->assertSame($result->parameter->name, data_get($counterAnalysisResult, 'parameter_id.name'));
        $this->assertSame($result->parameter->result_type, data_get($counterAnalysisResult, 'result_type'));
        $this->assertSame($result->parameter->formula_expression, data_get($counterAnalysisResult, 'formula_expression'));
        $this->assertSame($result->parameter->calculation_parameters, data_get($counterAnalysisResult, 'calculation_parameters'));
        $this->assertSame($result->parameter->requires_calculation, data_get($counterAnalysisResult, 'requires_calculation'));
    }

    public function test_analysis_can_seed_a_controlled_scope_worksheet(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        $profile = $product->matrix->profiles->first();
        $this->assertNotNull($profile);
        $department = Department::query()->findOrFail($profile->type->department_id);
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Worksheet Draft Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                    'conditioning_status' => 'accepted',
                ],
            ])
            ->assertRedirect();

        $sampleEntry = VAPSampleEntry::query()
            ->where('name', 'Worksheet Draft Sample')
            ->latest('id')
            ->firstOrFail();

        $analysis = CollectionProduct::query()
            ->with('code.samples.analysis')
            ->findOrFail($sampleEntry->collection_product_id)
            ->code
            ->samples
            ->first()
            ?->analysis;

        $this->assertNotNull($analysis);

        $response = $this->actingAs($user)
            ->post(route('analysis.worksheet-draft', $analysis));

        $worksheet = Worksheet::query()
            ->where('worksheets->analysis_id', $analysis->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($worksheet);
        $response->assertRedirect(route('worksheets.show', $worksheet));
        $this->assertSame($analysis->sample_id, data_get($worksheet->worksheets, 'sample_id'));
        $this->assertSame('analysis_scope', data_get($worksheet->worksheets, 'generated_from'));
        $this->assertSame('Pendente', data_get($worksheet->worksheets, 'scope_control.status_label'));
        $this->assertSame($profile->parameters->unique('id')->count(), data_get($worksheet->worksheets, 'scope_control.expected_count'));
        $this->assertSame($profile->parameters->unique('id')->count(), data_get($worksheet->worksheets, 'scope_control.missing_count'));
        $this->assertNotEmpty(data_get($worksheet->worksheets, 'sheets.0.data'));

        $this->actingAs($user)
            ->get(route('worksheets.show', $worksheet))
            ->assertOk();
    }

    public function test_direct_collection_show_exposes_controlled_scope_snapshot(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        $profile = $product->matrix->profiles->first();
        $this->assertNotNull($profile);
        $department = Department::query()->findOrFail($profile->type->department_id);
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Collection Scope Snapshot Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                    'conditioning_status' => 'restricted',
                    'packaging_condition' => 'Seal broken but sample accepted',
                ],
            ])
            ->assertRedirect();

        $sampleEntry = VAPSampleEntry::query()
            ->where('name', 'Collection Scope Snapshot Sample')
            ->latest('id')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('directcollections.show', $sampleEntry->collection_product_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DirectCollections/Show')
                ->where('record.data.scope_control.conditioning_status', 'restricted')
                ->where('record.data.scope_control.required_parameter_count', $profile->parameters->unique('id')->count())
                ->where('record.data.scope_control.packaging_condition', 'Seal broken but sample accepted')
            );
    }

    public function test_direct_collection_index_exposes_scope_control_for_triage(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        $profile = $product->matrix->profiles->first();
        $this->assertNotNull($profile);
        $department = Department::query()->findOrFail($profile->type->department_id);
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Collection Index Scope Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                    'conditioning_status' => 'restricted',
                    'packaging_condition' => 'Index panel requires review',
                ],
            ])
            ->assertRedirect();

        $sampleEntry = VAPSampleEntry::query()
            ->where('name', 'Collection Index Scope Sample')
            ->latest('id')
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('directcollections.index', ['category' => 'pending', 'per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('DirectCollections/Index'));

        $record = collect(data_get($response->viewData('page'), 'props.record.data', []))
            ->firstWhere('id', $sampleEntry->collection_product_id);

        $this->assertNotNull($record);
        $this->assertSame('restricted', data_get($record, 'scope_control.conditioning_status'));
        $this->assertSame('Index panel requires review', data_get($record, 'scope_control.packaging_condition'));
        $this->assertSame($profile->parameters->unique('id')->count(), data_get($record, 'scope_control.required_parameter_count'));
    }

    public function test_sample_tracking_notification_carries_scope_links_for_technicians(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        $profile = $product->matrix->profiles->first();
        $this->assertNotNull($profile);
        $department = Department::query()->findOrFail($profile->type->department_id);
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $initialNotifications = $warehouse->notifications()->count();

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Notification Scope Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'status' => 'POR_INICIAR',
                'client_submitted_info' => [
                    'request_origin' => 'internal',
                    'product_id' => $product->id,
                    'matrix_id' => $product->matrix_id,
                    'requested_profile_ids' => [$profile->id],
                    'conditioning_status' => 'accepted',
                ],
            ])
            ->assertRedirect();

        $sampleEntry = VAPSampleEntry::query()
            ->where('name', 'Notification Scope Sample')
            ->latest('id')
            ->firstOrFail();

        $warehouse->refresh();
        $this->assertGreaterThan($initialNotifications, $warehouse->notifications()->count());

        $notification = $warehouse->notifications()->latest()->first();

        $this->assertSame($sampleEntry->id, data_get($notification->data, 'sample_id'));
        $this->assertSame('accepted', data_get($notification->data, 'conditioning_status'));
        $this->assertSame(
            data_get($sampleEntry->client_submitted_info, 'required_parameter_count'),
            data_get($notification->data, 'required_parameter_count')
        );
        $this->assertNotNull(data_get($notification->data, 'collection_url'));
        $this->assertNotNull(data_get($notification->data, 'analysis_url'));
    }

    public function test_profile_catalog_rejects_duplicate_or_inactive_parameters(): void
    {
        $user = $this->verifiedAdmin();
        $category = AnalysisCategory::query()->whereNotNull('department_id')->firstOrFail();
        $parameter = Parameter::query()->where('active', false)->first();

        if (! $parameter) {
            $parameter = Parameter::query()->where('active', true)->firstOrFail();
            $parameter->update(['active' => false]);
        }

        $unit = Unit::query()->firstOrFail();
        $protocol = Protocol::query()->firstOrFail();
        $nwp = NormativeWorkProcedure::query()->firstOrFail();
        $standard = Standard::query()->firstOrFail();
        $resultCategory = ResultCategory::query()->firstOrFail();

        $response = $this->from(route('profiles.create'))
            ->actingAs($user)
            ->post(route('profiles.store'), [
                'name' => 'Invalid Controlled Scope Profile',
                'code' => 'PRF-' . Str::upper(Str::random(6)),
                'description' => 'Should be rejected because of duplicate inactive parameters.',
                'category_id' => [
                    'value' => $category->id,
                    'label' => $category->code,
                ],
                'parameters' => [
                    [
                        'parameter_id' => ['value' => $parameter->id, 'label' => $parameter->name],
                        'unit_id' => ['value' => $unit->id, 'label' => $unit->code],
                        'protocol_id' => ['value' => $protocol->id, 'label' => $protocol->code],
                        'nwp_id' => ['value' => $nwp->id, 'label' => $nwp->code],
                        'standard_id' => ['value' => $standard->id, 'label' => $standard->code],
                        'count' => true,
                        'formula_id' => ['value' => null, 'label' => null],
                        'category_id' => ['value' => $resultCategory->id, 'label' => $resultCategory->name],
                        'min_ref_value' => '10',
                        'max_ref_value' => '5',
                        'dilutions' => [],
                        'extra_data' => [],
                        'optimal_analysis_time' => null,
                        'ref_val_origin' => 'catalog-test',
                    ],
                    [
                        'parameter_id' => ['value' => $parameter->id, 'label' => $parameter->name],
                        'unit_id' => ['value' => $unit->id, 'label' => $unit->code],
                        'protocol_id' => ['value' => $protocol->id, 'label' => $protocol->code],
                        'nwp_id' => ['value' => $nwp->id, 'label' => $nwp->code],
                        'standard_id' => ['value' => $standard->id, 'label' => $standard->code],
                        'count' => true,
                        'formula_id' => ['value' => null, 'label' => null],
                        'category_id' => ['value' => $resultCategory->id, 'label' => $resultCategory->name],
                        'min_ref_value' => '1',
                        'max_ref_value' => '2',
                        'dilutions' => [],
                        'extra_data' => [],
                        'optimal_analysis_time' => null,
                        'ref_val_origin' => 'catalog-test',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('profiles.create'))
            ->assertSessionHasErrors(['parameters', 'parameters.0.max_ref_value']);

        $this->assertDatabaseMissing('profiles', [
            'name' => 'Invalid Controlled Scope Profile',
        ]);
    }

    public function test_matrix_catalog_rejects_profiles_from_multiple_departments(): void
    {
        $user = $this->verifiedAdmin();
        $taxType = TaxType::query()->first();
        $unit = Unit::query()->firstOrFail();
        $protocol = Protocol::query()->firstOrFail();
        $nwp = NormativeWorkProcedure::query()->firstOrFail();
        $standard = Standard::query()->firstOrFail();
        $resultCategory = ResultCategory::query()->firstOrFail();
        $parameter = Parameter::query()->where('active', true)->firstOrFail();

        if (! $taxType) {
            $taxType = TaxType::query()->create([
                'name' => 'IVA Smoke',
                'percent' => 14,
                'compound_tax' => false,
                'collective_tax' => false,
                'description' => 'Smoke validation tax type',
                'user_id' => $user->id,
            ]);
        }

        $profiles = Profile::query()
            ->with(['type:id,department_id,name', 'parameters:id'])
            ->whereHas('parameters')
            ->whereHas('type', fn ($query) => $query->whereNotNull('department_id'))
            ->get()
            ->groupBy(fn (Profile $profile) => (int) $profile->type->department_id)
            ->filter(fn ($group) => $group->isNotEmpty());

        if ($profiles->keys()->count() < 2) {
            $sourceProfile = $profiles->flatten()->firstOrFail();
            $alternateDepartment = Department::query()
                ->whereKeyNot($sourceProfile->type->department_id)
                ->firstOrFail();

            $analysisCategory = AnalysisCategory::query()->create([
                'name' => 'Smoke Alt Category',
                'code' => 'SMK-ALT-' . Str::upper(Str::random(4)),
                'description' => 'Alternate department category for matrix validation smoke test',
                'department_id' => $alternateDepartment->id,
            ]);

            $alternateProfile = Profile::query()->create([
                'name' => 'Smoke Alt Profile',
                'code' => 'SMK-PROF-' . Str::upper(Str::random(4)),
                'description' => 'Alternate department profile for matrix validation smoke test',
                'price' => 0,
                'category_id' => $analysisCategory->id,
            ]);

            $alternateProfile->parameters()->attach($parameter->id, [
                'unit_id' => $unit->id,
                'unit_label' => $unit->code,
                'protocol_id' => $protocol->id,
                'protocol_label' => $protocol->code,
                'nwp_id' => $nwp->id,
                'nwp_label' => $nwp->code,
                'standard_id' => $standard->id,
                'standard_label' => $standard->code,
                'category_id' => $resultCategory->id,
                'category_label' => $resultCategory->name,
                'min_ref_value' => 0,
                'max_ref_value' => 1,
                'dilutions' => json_encode([]),
                'extra_data' => json_encode([]),
                'count' => true,
            ]);

            $profiles = Profile::query()
                ->with(['type:id,department_id,name', 'parameters:id'])
                ->whereHas('parameters')
                ->whereHas('type', fn ($query) => $query->whereNotNull('department_id'))
                ->get()
                ->groupBy(fn (Profile $profile) => (int) $profile->type->department_id)
                ->filter(fn ($group) => $group->isNotEmpty());
        }

        $firstProfile = $profiles->shift()->first();
        $secondProfile = $profiles->shift()->first();

        $response = $this->from(route('matrixes.create'))
            ->actingAs($user)
            ->post(route('matrixes.store'), [
                'code' => 'MAT-' . Str::upper(Str::random(6)),
                'description' => 'Should be rejected for mixed departments.',
                'price' => 0,
                'fixed_price' => 0,
                'charge_tax' => true,
                'withhold_tax' => false,
                'tax_id' => [
                    'value' => $taxType->id,
                    'label' => $taxType->name,
                    'percent' => $taxType->percent,
                ],
                'profiles' => [
                    [
                        'profile_id' => [
                            'value' => $firstProfile->id,
                            'label' => $firstProfile->name,
                        ],
                    ],
                    [
                        'profile_id' => [
                            'value' => $secondProfile->id,
                            'label' => $secondProfile->name,
                        ],
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('matrixes.create'))
            ->assertSessionHasErrors('profiles');

        $this->assertDatabaseMissing('matrixes', [
            'description' => 'Should be rejected for mixed departments.',
        ]);
    }

    public function test_parameter_catalog_rejects_inconsistent_calculated_parameter_definitions(): void
    {
        $user = $this->verifiedAdmin();
        $exemption = TaxExemption::query()->firstOrFail();
        $suffix = Str::upper(Str::random(6));

        $formula = Formula::query()->create([
            'name' => 'Scope Calc Formula ' . $suffix,
            'code' => 'SCF-' . $suffix,
            'description' => 'Formula for parameter governance validation.',
            'formula_expression' => '({density} * {mass}) / {volume}',
            'expression' => '(density * mass) / volume',
            'variables' => [
                ['name' => 'density', 'label' => 'Density', 'type' => 'number', 'unit' => 'g/mL'],
                ['name' => 'mass', 'label' => 'Mass', 'type' => 'number', 'unit' => 'g'],
                ['name' => 'volume', 'label' => 'Volume', 'type' => 'number', 'unit' => 'mL'],
            ],
            'category' => 'custom',
            'output_unit' => 'g/mL',
            'decimal_places' => 4,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('parameters.index'))
            ->actingAs($user)
            ->post(route('parameters.store'), [
                'name' => 'Invalid Calculated Parameter ' . $suffix,
                'code' => 'ICP-' . $suffix,
                'description' => 'Should be rejected for inconsistent calculation metadata.',
                'price' => 1000,
                'charge_tax' => false,
                'withhold_tax' => false,
                'active' => true,
                'optimal_analysis_time' => '48h',
                'result_is_qualitative' => true,
                'requires_calculation' => true,
                'formula_id' => $formula->id,
                'formula_expression' => '({density} * {mass}) / {volume}',
                'calculation_parameters' => ['density', 'mass'],
                'decimal_places' => 3,
                'exemption_id' => [
                    'value' => $exemption->id,
                    'label' => $exemption->code,
                ],
            ]);

        $response
            ->assertRedirect(route('parameters.index'))
            ->assertSessionHasErrors(['requires_calculation', 'calculation_parameters']);

        $this->assertDatabaseMissing('parameters', [
            'name' => 'Invalid Calculated Parameter ' . $suffix,
        ]);
    }

    public function test_sample_cannot_start_analysis_without_an_accepted_proposal(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $response = $this->from(route('vap_samples.index'))
            ->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Blocked Smoke Sample',
                'code' => 'SMK-BLOCK-' . Str::upper(Str::random(5)),
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'requested_services' => 'Blocked workflow validation',
                'status' => 'EN_PROGRESO',
                'analysis_start_date' => now()->toDateTimeString(),
                'collected_by_lab' => false,
            ]);

        $response->assertRedirect(route('vap_samples.index'));

        $this->assertDatabaseMissing('sample_entries', [
            'name' => 'Blocked Smoke Sample',
        ]);
    }

    public function test_internal_sample_can_start_analysis_without_client_proposal_in_hybrid_mode(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        /** @var GeneralSettings $settings */
        $settings = app(GeneralSettings::class);
        $originalMode = $settings->app_operation_mode;
        $code = 'INT-' . Str::upper(Str::random(8));

        try {
            $settings->app_operation_mode = 'hybrid';
            $settings->save();

            $this->actingAs($user)
                ->post(route('vap_samples.samples.store'), [
                    'name' => 'Internal Process Validation Sample',
                    'code' => $code,
                    'sample_type' => 'INTERNO',
                    'customer_id' => $customer->id,
                    'lab_id' => $lab->id,
                    'department_id' => $department->id,
                    'warehouse_id' => $warehouse->id,
                    'received_at' => now()->toDateTimeString(),
                    'requested_services' => 'Internal method verification',
                    'status' => 'EN_PROGRESO',
                    'analysis_start_date' => now()->toDateTimeString(),
                    'client_submitted_info' => [
                        'request_origin' => 'internal',
                    ],
                ])
                ->assertRedirect()
                ->assertSessionHas('type', 'success');

            $this->assertDatabaseHas('sample_entries', [
                'code' => $code,
                'status' => 'EN_PROGRESO',
            ]);
        } finally {
            $settings->app_operation_mode = $originalMode;
            $settings->save();
        }
    }

    public function test_sample_can_start_analysis_when_linked_to_an_accepted_proposal(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $this->qualifyUser($user, ['sample_intake_validation'], $department);
        $proposal = $this->acceptedProposal($customer, $warehouse, $department, $user);
        $code = 'SMK-EXEC-' . Str::upper(Str::random(5));

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Accepted Proposal Smoke Sample',
                'code' => $code,
                'sample_type' => 'ROTINA',
                'proposal_id' => $proposal->id,
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'requested_services' => 'Execution workflow validation',
                'status' => 'EN_PROGRESO',
                'analysis_start_date' => now()->toDateTimeString(),
                'collected_by_lab' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('sample_entries', [
            'code' => $code,
            'proposal_id' => $proposal->id,
            'status' => 'EN_PROGRESO',
        ]);
    }

    public function test_portal_analysis_request_can_be_validated_into_a_sample_and_notifies_tracking_recipients(): void
    {
        $user = $this->verifiedAdmin();
        $warehouse = Warehouse::query()->whereNotNull('customer_id')->firstOrFail();
        $customer = Customer::query()->findOrFail($warehouse->customer_id);
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $portalRequest = CustomerRequest::query()->create([
            'reference' => 'REQ-' . Str::upper(Str::random(6)),
            'title' => 'Portal prefilling smoke request',
            'request_type' => 'analysis_request',
            'status' => 'pending',
            'priority' => 'high',
            'preferred_date' => now()->addDay(),
            'submitted_at' => now(),
            'description' => 'Portal request ready for lab validation.',
            'contact' => $warehouse->primary_phone ?: '900000321',
            'email' => $warehouse->email ?: ('portal.' . $warehouse->id . '@lims-unleashed.test'),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'answered' => false,
            'extra_data' => [
                'sample_name' => 'Portal Prefilled Sample',
                'matrix' => 'Água',
                'requested_profiles' => [],
            ],
        ]);

        $initialNotifications = $warehouse->notifications()->count();

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Portal Prefilled Sample',
                'sample_type' => 'ROTINA',
                'portal_request_id' => $portalRequest->id,
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'requested_services' => 'Portal validation flow',
                'status' => 'POR_INICIAR',
                'collected_by_lab' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $sample = VAPSampleEntry::query()
            ->where('customer_request_id', $portalRequest->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($sample, 'Expected the portal request to create a linked sample entry.');

        $portalRequest->refresh();
        $warehouse->refresh();

        $this->assertSame('in_progress', $portalRequest->status);
        $this->assertSame($sample->id, data_get($portalRequest->extra_data, 'validated_sample_entry_id'));
        $this->assertSame($portalRequest->reference, data_get($sample->client_submitted_info, 'request_reference'));
        $this->assertGreaterThan($initialNotifications, $warehouse->notifications()->count());
        $this->assertSame(
            $sample->id,
            data_get($warehouse->notifications()->latest()->first(), 'data.sample_id')
        );
    }

    public function test_verified_admin_can_record_sample_discard(): void
    {
        $user = $this->verifiedAdmin();
        $sample = VAPSampleEntry::query()
            ->whereIn('status', ['COMPLETADO', 'CANCELADO'])
            ->firstOrFail();

        $payload = [
            'sample_id' => $sample->id,
            'discard_method' => 'Incineração',
            'qty' => '2',
            'discarded_at' => now()->toDateTimeString(),
            'lab_id' => $sample->lab_id,
            'department_id' => $sample->department_id,
        ];

        $this->actingAs($user)
            ->post(route('vap_samples.discards.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('type', 'success');

        $discard = VAPSampleDiscard::query()
            ->where('sample_id', $sample->id)
            ->where('discard_method', 'Incineração')
            ->latest('id')
            ->first();

        $this->assertNotNull($discard, 'Expected the smoke-created discard record to exist.');

        $this->assertDatabaseHas('sample_discards', [
            'id' => $discard->id,
            'sample_id' => $sample->id,
            'discard_method' => 'Incineração',
            'discarded_by_id' => $user->id,
        ]);
    }

    public function test_verified_admin_can_export_collection_parameter_sheets(): void
    {
        $user = $this->verifiedAdmin();
        $directCollection = CollectionProduct::query()
            ->whereRelation('collection', 'collectionable_type', 'direct')
            ->first();
        $programmedCollection = CollectionProduct::query()
            ->whereRelation('collection', 'collectionable_type', 'programmed')
            ->first();

        $this->assertNotNull($directCollection, 'Expected at least one direct collection product for export smoke testing.');
        $this->assertNotNull($programmedCollection, 'Expected at least one programmed collection product for export smoke testing.');

        $directResponse = $this->actingAs($user)
            ->get(route('directcollections.exportParametersToAnalyzeSheet', [
                'recordIds' => [$directCollection->id],
            ]));

        $directResponse->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $directResponse->headers->get('content-type'),
        );

        $programmedResponse = $this->actingAs($user)
            ->get(route('programmedcollections.exportParametersToAnalyzeSheet', [
                'recordIds' => [$programmedCollection->id],
            ]));

        $programmedResponse->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $programmedResponse->headers->get('content-type'),
        );
    }

    public function test_verified_admin_cannot_discard_sample_that_is_not_completed_or_canceled(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Non-discardable Smoke Sample',
                'code' => 'SMK-NODISC-' . Str::upper(Str::random(4)),
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'requested_services' => 'Discard eligibility validation',
                'status' => 'POR_INICIAR',
                'collected_by_lab' => false,
            ])
            ->assertRedirect();

        $sample = VAPSampleEntry::query()
            ->where('name', 'Non-discardable Smoke Sample')
            ->latest('id')
            ->firstOrFail();

        $existingDiscardCount = VAPSampleDiscard::query()
            ->where('sample_id', $sample->id)
            ->count();

        $this->actingAs($user)
            ->post(route('vap_samples.discards.store'), [
                'sample_id' => $sample->id,
                'discard_method' => 'Incineração',
                'qty' => '1',
                'discarded_at' => now()->toDateTimeString(),
                'lab_id' => $sample->lab_id,
                'department_id' => $sample->department_id,
            ])
            ->assertRedirect()
            ->assertSessionHas('type', 'error');

        $this->assertSame(
            $existingDiscardCount,
            VAPSampleDiscard::query()->where('sample_id', $sample->id)->count(),
            'Expected discard creation to be blocked for ineligible sample statuses.'
        );
    }

    public function test_verified_admin_can_create_and_update_quality_certificate(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $labCode = LabCode::query()->firstOrFail();

        $storePayload = [
            'customer_id' => ['value' => $customer->id],
            'warehouse_id' => ['value' => $warehouse->id],
            'cl_id' => ['value' => $labCode->id],
            'invoice_id' => null,
            'obs' => 'Smoke certificate create',
            'status' => true,
        ];

        $this->actingAs($user)
            ->post(route('qualitycertificates.store'), $storePayload)
            ->assertRedirect();

        $certificate = QualityCertificate::query()
            ->where('customer_id', $customer->id)
            ->where('cl_id', $labCode->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('obs', 'Smoke certificate create')
            ->latest('id')
            ->first();

        $this->assertNotNull($certificate, 'Expected the smoke-created quality certificate to exist.');

        $this->assertDatabaseHas('quality_certificates', [
            'id' => $certificate->id,
            'customer_id' => $customer->id,
            'cl_id' => $labCode->id,
            'warehouse_id' => $warehouse->id,
            'obs' => 'Smoke certificate create',
            'status' => 1,
        ]);

        $updatePayload = [
            'customer_id' => ['value' => $customer->id],
            'warehouse_id' => ['value' => $warehouse->id],
            'cl_id' => ['value' => $labCode->id],
            'invoice_id' => null,
            'obs' => 'Smoke certificate updated',
            'status' => false,
        ];

        $this->actingAs($user)
            ->put(route('qualitycertificates.update', $certificate), $updatePayload)
            ->assertRedirect();

        $this->assertDatabaseHas('quality_certificates', [
            'id' => $certificate->id,
            'obs' => 'Smoke certificate updated',
            'status' => 0,
        ]);
    }

    public function test_verified_admin_can_approve_quality_certificate_with_signature(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $labCode = LabCode::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('qualitycertificates.store'), [
                'customer_id' => ['value' => $customer->id],
                'warehouse_id' => ['value' => $warehouse->id],
                'cl_id' => ['value' => $labCode->id],
                'invoice_id' => null,
                'obs' => 'Smoke certificate approval target',
                'status' => true,
            ])
            ->assertRedirect();

        $certificate = QualityCertificate::query()
            ->where('customer_id', $customer->id)
            ->where('cl_id', $labCode->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('obs', 'Smoke certificate approval target')
            ->latest('id')
            ->first();

        $this->assertNotNull($certificate, 'Expected the approval target certificate to exist.');

        $this->actingAs($user)
            ->post(route('qualitycertificates.approve', $certificate), [
                'signature' => self::PNG_SIGNATURE,
            ])
            ->assertRedirect(route('qualitycertificates.show', $certificate));

        $certificate->refresh();

        $this->assertSame($user->id, $certificate->validated_by_id);
        $this->assertSame($user->name, $certificate->validated_by);
        $this->assertNotNull($certificate->validated_at);
        $this->assertNotNull(
            $certificate->getFirstMedia('validation_signature'),
            'Expected approval to persist the validator signature media.'
        );
    }

    public function test_verified_admin_can_create_update_and_adjust_inventory(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();

        $storePayload = [
            'qty_available' => 10,
            'min_stock_level' => 3,
            'reorder_point' => 2,
            'warehouse_id' => ['value' => $warehouse->id],
            'item_id' => ['value' => $item->id],
            'name' => 'Smoke Inventory Entry',
            'category_id' => $item->category_id,
        ];

        $this->actingAs($user)
            ->post(route('inventory.store'), $storePayload)
            ->assertRedirect();

        $inventory = Inventory::query()
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('qty_available', 10)
            ->latest('id')
            ->first();

        $this->assertNotNull($inventory, 'Expected the smoke-created inventory record to exist.');

        $this->assertDatabaseHas('inventory', [
            'id' => $inventory->id,
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_available' => 10,
            'min_stock_level' => 3,
            'reorder_point' => 2,
        ]);

        $updatePayload = [
            'qty_available' => 12,
            'min_stock_level' => 4,
            'reorder_point' => 3,
            'warehouse_id' => ['value' => $warehouse->id],
            'item_id' => ['value' => $item->id],
            'name' => 'Smoke Inventory Entry Updated',
            'category_id' => $item->category_id,
        ];

        $this->actingAs($user)
            ->put(route('inventory.update', $inventory), $updatePayload)
            ->assertRedirect();

        $inventory->refresh();
        $this->assertSame(12, $inventory->qty_available);
        $this->assertSame(4, $inventory->min_stock_level);
        $this->assertSame(3, $inventory->reorder_point);

        $this->actingAs($user)
            ->post(route('inventory.increment', $inventory), ['qty' => 3])
            ->assertRedirect();

        $inventory->refresh();
        $this->assertSame(15, $inventory->qty_available);

        $this->actingAs($user)
            ->post(route('inventory.decrement', $inventory), ['qty' => 4])
            ->assertRedirect();

        $inventory->refresh();
        $this->assertSame(11, $inventory->qty_available);
    }

    public function test_inventory_decrement_below_minimum_creates_low_stock_notification(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->where('category_id', '!=', 2)->first() ?? InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('inventory.store'), [
                'qty_available' => 5,
                'min_stock_level' => 4,
                'reorder_point' => 3,
                'warehouse_id' => ['value' => $warehouse->id],
                'item_id' => ['value' => $item->id],
                'name' => 'Smoke Low Stock Entry',
                'category_id' => $item->category_id,
            ])
            ->assertRedirect();

        $inventory = Inventory::query()
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('qty_available', 5)
            ->latest('id')
            ->first();

        $this->assertNotNull($inventory, 'Expected the low-stock smoke inventory record to exist.');

        $initialNotifications = $user->notifications()->count();

        $this->actingAs($user)
            ->post(route('inventory.decrement', $inventory), ['qty' => 2])
            ->assertRedirect();

        $inventory->refresh();
        $user->refresh();

        $this->assertSame(3, $inventory->qty_available);
        $this->assertGreaterThan(
            $initialNotifications,
            $user->notifications()->count(),
            'Expected a database notification to be created when stock drops below the minimum level.'
        );

        $notification = $user->notifications()->latest()->first();

        $this->assertStringContainsString('Alerta de Estoque Baixo', (string) data_get($notification, 'data.title'));
        $this->assertStringContainsString((string) $item->name, (string) data_get($notification, 'data.message'));
    }

    public function test_verified_admin_can_generate_vap_sample_and_discard_artifacts(): void
    {
        $user = $this->verifiedAdmin();
        $sample = VAPSampleEntry::query()
            ->with(['customer', 'lab', 'department', 'warehouse'])
            ->whereNotNull('code')
            ->firstOrFail();

        [$samplePdf, $samplePdfOutput] = $this->captureResponseOutput(
            fn () => $this->actingAs($user)->get(route('vap_samples.samples.pdf', $sample))
        );

        $samplePdf->assertOk();
        $this->assertStringStartsWith('%PDF-', $samplePdfOutput);

        $sampleExport = $this->actingAs($user)
            ->get(route('vap_samples.samples.export', ['status' => $sample->status]));

        $sampleExport->assertOk();
        $this->assertStringContainsString('text/csv', (string) $sampleExport->headers->get('content-type'));
        $this->assertStringContainsString('Code', $sampleExport->streamedContent());

        $discardableSample = VAPSampleEntry::query()
            ->whereIn('status', ['COMPLETADO', 'CANCELADO'])
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('vap_samples.discards.store'), [
                'sample_id' => $discardableSample->id,
                'discard_method' => 'Autoclave',
                'qty' => '1',
                'discarded_at' => now()->toDateTimeString(),
                'lab_id' => $discardableSample->lab_id,
                'department_id' => $discardableSample->department_id,
            ])
            ->assertRedirect();

        $discard = VAPSampleDiscard::query()
            ->where('sample_id', $discardableSample->id)
            ->where('discard_method', 'Autoclave')
            ->latest('id')
            ->first();

        $this->assertNotNull($discard, 'Expected the discard artifact target to exist.');

        [$discardPdf, $discardPdfOutput] = $this->captureResponseOutput(
            fn () => $this->actingAs($user)->get(route('vap_samples.discards.pdf', $discard))
        );

        $discardPdf->assertOk();
        $this->assertStringStartsWith('%PDF-', $discardPdfOutput);

        $discardExport = $this->actingAs($user)
            ->get(route('vap_samples.discards.export', ['method' => 'Autoclave']));

        $discardExport->assertOk();
        $this->assertStringContainsString('text/csv', (string) $discardExport->headers->get('content-type'));
        $this->assertStringContainsString('Discard Method', $discardExport->streamedContent());
    }

    public function test_verified_admin_can_create_and_export_iso_revision_history(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $labCode = LabCode::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('qualitycertificates.store'), [
                'customer_id' => ['value' => $customer->id],
                'warehouse_id' => ['value' => $warehouse->id],
                'cl_id' => ['value' => $labCode->id],
                'invoice_id' => null,
                'obs' => 'ISO revision smoke target',
                'status' => true,
            ])
            ->assertRedirect();

        $certificate = QualityCertificate::query()
            ->where('customer_id', $customer->id)
            ->where('cl_id', $labCode->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('obs', 'ISO revision smoke target')
            ->latest('id')
            ->first();

        $this->assertNotNull($certificate, 'Expected the ISO revision smoke target certificate to exist.');

        $this->actingAs($user)
            ->post(route('qualitycertificates.iso-revisions.store', $certificate), [
                'change_type' => 'UPDATED',
                'change_reason' => 'Smoke test revision update for ISO traceability.',
                'iso_section' => '8.9.1',
                'risk_assessment' => 'LOW',
                'approved_by_id' => $user->id,
                'fields' => [
                    'obs' => 'ISO revision smoke target updated',
                ],
            ])
            ->assertRedirect(route('qualitycertificates.iso-revisions.index', $certificate));

        $certificate->refresh();

        $revision = QualityCertificateRevision::query()
            ->where('quality_certificate_id', $certificate->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($revision, 'Expected an ISO revision record to be created.');
        $this->assertTrue($revision->is_current);
        $this->assertSame('UPDATED', $revision->change_type);
        $this->assertSame('ISO revision smoke target updated', $certificate->obs);

        $snapshot = $this->actingAs($user)
            ->get(route('qualitycertificates.iso-revisions.snapshot', [$certificate, $revision]));

        $snapshot->assertOk()
            ->assertJsonPath('revision.id', $revision->id)
            ->assertJsonPath('certificate.id', $certificate->id);

        [$historyExport, $historyExportOutput] = $this->captureResponseOutput(
            fn () => $this->actingAs($user)->get(route('qualitycertificates.iso-revisions.export', $certificate))
        );

        $historyExport->assertOk();
        $this->assertStringStartsWith('%PDF-', $historyExportOutput);
    }

    public function test_counter_analysis_request_creates_a_dedicated_sample(): void
    {
        $user = $this->verifiedAdmin();
        $result = Result::query()
            ->with(['sample.analysis', 'code'])
            ->where('requested_counter_analysis', false)
            ->whereHas('sample.analysis')
            ->whereDoesntHave('counter_analysis')
            ->firstOrFail();

        $originalSampleId = $result->sample_id;

        $this->actingAs($user)
            ->post(route('counteranalysis.store'), [
                'result_id' => $result->id,
            ])
            ->assertRedirect(route('analysis.index'));

        $counterAnalysis = CounterAnalysis::query()
            ->where('result_id', $result->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($counterAnalysis, 'Expected the counter-analysis request to create a record.');
        $this->assertNotSame($originalSampleId, $counterAnalysis->sample_id, 'Expected counter-analysis to use a dedicated sample.');
        $this->assertSame($result->parameter_id, $counterAnalysis->parameter_id);
        $this->assertSame($result->sample->analysis->id, $counterAnalysis->analysis_id);
        $this->assertSame($originalSampleId, data_get($counterAnalysis->extra_data, 'source_sample_id'));

        $result->refresh();
        $this->assertTrue($result->requested_counter_analysis);
    }

    public function test_duplicate_inventory_adjustment_request_is_blocked(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();

        $inventory = Inventory::query()->updateOrCreate(
            [
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'qty_available' => 10,
                'min_stock_level' => 1,
                'reorder_point' => 1,
                'category_id' => $item->category_id,
                'status' => 'AVAILABLE',
            ]
        );

        $payload = [
            'warehouse_id' => $warehouse->id,
            'adjustment_type' => 'add',
            'quantity' => 5,
            'reason' => 'Smoke duplicate guard',
            'notes' => 'First request should win.',
        ];

        $this->actingAs($user)
            ->postJson(route('vap-inventory.items.adjust-stock', $item), $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->postJson(route('vap-inventory.items.adjust-stock', $item), $payload)
            ->assertStatus(429);

        $inventory->refresh();
        $this->assertSame(15.0, (float) $inventory->qty_available);
    }

    public function test_destroying_consumption_restores_stock_and_deletes_the_exact_transaction(): void
    {
        $user = $this->verifiedAdmin();
        $item = InventoryItem::query()->reagents()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();

        $inventory = Inventory::query()->updateOrCreate(
            [
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'qty_available' => 10,
                'min_stock_level' => 1,
                'reorder_point' => 1,
                'category_id' => $item->category_id,
                'status' => 'AVAILABLE',
            ]
        );

        $this->actingAs($user)
            ->postJson(route('vap-inventory.reagents.consume', $item), [
                'warehouse_id' => $warehouse->id,
                'quantity_used' => 2,
                'used_by' => 'Codex Smoke',
                'remarks' => 'Precise transaction rollback smoke test',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $consumption = ReagentConsumption::query()
            ->where('reagent_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('used_by', 'Codex Smoke')
            ->latest('id')
            ->first();

        $this->assertNotNull($consumption, 'Expected the reagent consumption record to be created.');
        $this->assertNotNull($consumption->inventory_transaction_id, 'Expected the consumption to track its inventory transaction.');
        $this->assertDatabaseHas('itransactions', [
            'id' => $consumption->inventory_transaction_id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user)
            ->delete(route('vap-inventory.reagents.consumption.destroy', $consumption))
            ->assertRedirect(route('vap-inventory.reagents.consumption.index'));

        $inventory->refresh();

        $this->assertSame(10.0, (float) $inventory->qty_available);
        $this->assertDatabaseMissing('reagent_consumption', [
            'id' => $consumption->id,
        ]);
        $this->assertSoftDeleted('itransactions', [
            'id' => $consumption->inventory_transaction_id,
        ]);
        $this->assertNull(InventoryTransaction::query()->find($consumption->inventory_transaction_id));
    }

    public function test_sample_intake_assigns_retention_schedule_for_qualified_personnel(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();

        $this->qualifyUser($user, ['sample_intake_validation'], $department);

        $this->actingAs($user)
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Retention Controlled Sample',
                'sample_type' => 'ROTINA',
                'customer_id' => $customer->id,
                'lab_id' => $lab->id,
                'department_id' => $department->id,
                'warehouse_id' => $warehouse->id,
                'received_at' => now()->toDateTimeString(),
                'requested_services' => 'Retention schedule validation',
                'retention_period_days' => 45,
            ])
            ->assertRedirect();

        $sample = VAPSampleEntry::query()->where('name', 'Retention Controlled Sample')->latest('id')->first();

        $this->assertNotNull($sample);
        $this->assertSame(45, $sample->retention_period_days);
        $this->assertNotNull($sample->retention_due_at);
        $this->assertSame('active', $sample->retention_status);
    }

    public function test_result_approval_requires_signature_when_user_has_no_saved_signature(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();
        $this->qualifyUser($user, ['approve_results'], $department);
        $user->clearMediaCollection('signature');

        $existing = Result::query()->firstOrFail();

        $response = $this->from(route('analysis.index', ['category' => 'approve']))
            ->actingAs($user)
            ->post(route('results.store'), [
                'action' => 'approve',
                'sample_id' => ['value' => $existing->sample_id, 'label' => (string) $existing->sample_id],
                'results' => [[
                    'result_id' => $existing->id,
                    'approved_by' => $user->name,
                    'approved_by_id' => $user->id,
                    'verified_by_id' => $existing->verified_by_id ?? $user->id,
                    'approved_date' => null,
                    'verified_date' => now()->toDateTimeString(),
                    'approved_value' => is_numeric($existing->verified_value ?? $existing->inserted_value) ? (string) ($existing->verified_value ?? $existing->inserted_value) : '1.0',
                    'collection_id' => $existing->collection_id,
                    'count' => (bool) $existing->count,
                    'inserted_by' => $existing->inserted_by ?? $user->name,
                    'inserted_by_id' => $existing->inserted_by_id ?? $user->id,
                    'inserted_date' => now()->toDateTimeString(),
                    'inserted_value' => is_numeric($existing->inserted_value) ? (string) $existing->inserted_value : '1.0',
                    'verified_by' => $existing->verified_by ?? $user->name,
                    'verified_value' => is_numeric($existing->verified_value ?? $existing->inserted_value) ? (string) ($existing->verified_value ?? $existing->inserted_value) : '1.0',
                    'matrix_id' => $existing->matrix_id,
                    'max_ref_value' => $existing->max_ref_value,
                    'min_ref_value' => $existing->min_ref_value,
                    'parameter_id' => ['value' => $existing->parameter_id, 'label' => $existing->parameter_label],
                    'product_id' => ['value' => $existing->product_id, 'label' => $existing->product_label],
                    'protocol_id' => ['value' => $existing->protocol_id, 'label' => $existing->protocol_label],
                    'profile_id' => $existing->profile_id,
                    'unit_id' => ['value' => $existing->unit_id, 'label' => $existing->unit_label],
                    'standard_id' => ['value' => $existing->standard_id, 'label' => $existing->standard_label],
                    'code_id' => ['value' => $existing->code_id, 'label' => $existing->code_label],
                    'nwp_id' => ['value' => $existing->nwp_id, 'label' => $existing->nwp_label],
                    'requested_counter_analysis' => false,
                    'sample_id' => $existing->sample_id,
                    'status' => (bool) $existing->status,
                    'type_id' => ['value' => $existing->type_id, 'label' => $existing->category_label],
                    'category_label' => $existing->category_label,
                    'uncertainty_value' => '0.05',
                    'sumC' => 0,
                    'volume' => 1,
                    'n1' => 0,
                    'n2' => 0,
                    'dilution' => 0,
                    'd1' => 0,
                    'd2' => 0,
                    'cfu1' => 0,
                    'cfu2' => 0,
                    'is_calculated' => false,
                    'is_override' => false,
                    'calculation_metadata' => null,
                ]],
            ]);

        $response->assertSessionHasErrors('signature');
    }

    public function test_result_verification_rejects_partial_scope_submission(): void
    {
        $user = $this->verifiedAdmin();
        $sample = Sample::query()
            ->with(['analysis.department', 'analysis.profile.parameters', 'results'])
            ->has('results', '>=', 2)
            ->whereHas('analysis.profile.parameters')
            ->firstOrFail();

        $department = $sample->analysis?->department;
        $this->assertNotNull($department);
        $this->qualifyUser($user, ['verify_results'], $department);

        $payload = $this->actingAs($user)
            ->getJson(route('results.getDefaultResultsData', [
                'action' => 'verify',
                'sample_id' => $sample->id,
            ]))
            ->assertOk()
            ->json();

        $this->assertGreaterThanOrEqual(2, count($payload));

        array_pop($payload);

        $this->from(route('analysis.index', ['category' => 'verify']))
            ->actingAs($user)
            ->post(route('results.store'), [
                'action' => 'verify',
                'signature' => 'workflow-signature',
                'sample_id' => ['value' => $sample->id, 'label' => (string) $sample->id],
                'results' => $payload,
            ])
            ->assertRedirect(route('analysis.index', ['category' => 'verify']))
            ->assertSessionHasErrors('results');
    }

    public function test_result_approval_rejects_partial_scope_submission(): void
    {
        $user = $this->verifiedAdmin();
        $sample = Sample::query()
            ->with(['analysis.department', 'analysis.profile.parameters', 'results'])
            ->has('results', '>=', 2)
            ->whereHas('analysis.profile.parameters')
            ->firstOrFail();

        $department = $sample->analysis?->department;
        $this->assertNotNull($department);
        $this->qualifyUser($user, ['approve_results'], $department);

        $payload = $this->actingAs($user)
            ->getJson(route('results.getDefaultResultsData', [
                'action' => 'approve',
                'sample_id' => $sample->id,
            ]))
            ->assertOk()
            ->json();

        $this->assertGreaterThanOrEqual(2, count($payload));

        array_pop($payload);

        $this->from(route('analysis.index', ['category' => 'approve']))
            ->actingAs($user)
            ->post(route('results.store'), [
                'action' => 'approve',
                'signature' => 'workflow-signature',
                'sample_id' => ['value' => $sample->id, 'label' => (string) $sample->id],
                'results' => $payload,
            ])
            ->assertRedirect(route('analysis.index', ['category' => 'approve']))
            ->assertSessionHasErrors('results');
    }

    public function test_complaints_and_management_reviews_are_auditable_records(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('complaints.store'), [
                'title' => 'Portal response delay',
                'description' => 'Customer reported excessive delay in processing urgent samples.',
                'severity' => 'high',
                'confidentiality_level' => 'internal',
                'reported_by_name' => 'Smoke Auditor',
                'reported_by_email' => 'smoke@example.test',
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'assigned_to_id' => $user->id,
            ])
            ->assertRedirect();

        $complaint = Complaint::query()->where('title', 'Portal response delay')->latest('id')->first();
        $this->assertNotNull($complaint);
        $this->assertSame('open', $complaint->status);
        $this->assertNotNull($complaint->reference);

        $this->actingAs($user)
            ->put(route('complaints.update', $complaint), [
                'status' => 'resolved',
                'assigned_to_id' => $user->id,
                'root_cause' => 'Queue backlog',
                'corrective_action' => 'Redistributed workload',
                'follow_up_notes' => 'Customer informed',
            ])
            ->assertRedirect();

        $complaint->refresh();
        $this->assertSame('resolved', $complaint->status);
        $this->assertNotNull($complaint->resolved_at);

        $this->actingAs($user)
            ->post(route('management-reviews.store'), [
                'review_date' => now()->addWeek()->toDateString(),
                'scope' => 'Quarterly ISO review',
                'summary' => 'Pending',
                'decisions' => 'Initial agenda',
                'risks_and_opportunities' => 'Backlog on urgent samples',
                'improvements' => 'Improve staffing plan',
                'conducted_by_id' => $user->id,
            ])
            ->assertRedirect();

        $review = ManagementReview::query()->where('scope', 'Quarterly ISO review')->latest('id')->first();
        $this->assertNotNull($review);
        $this->assertSame('planned', $review->status);

        $this->actingAs($user)
            ->put(route('management-reviews.update', $review), [
                'status' => 'completed',
                'summary' => 'Review completed with action plan.',
                'decisions' => 'Hire temporary analyst and rebalance intake.',
                'risks_and_opportunities' => 'Reduce turnaround risk and improve customer confidence.',
                'improvements' => 'Weekly capacity review.',
                'approved_by_id' => $user->id,
            ])
            ->assertRedirect();

        $review->refresh();
        $this->assertSame('completed', $review->status);
        $this->assertNotNull($review->approved_at);
    }
}
