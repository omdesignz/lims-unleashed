<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\Customer;
use App\Models\Department;
use App\Models\PersonnelQualification;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InternalQualityControlSampleFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for internal QC testing.');

        return $admin;
    }

    private function qualifyUser(User $user, Department $department): void
    {
        PersonnelQualification::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'capability' => 'sample_intake_validation',
                'department_id' => $department->id,
            ],
            [
                'qualified_by_id' => $user->id,
                'authorized_from' => now()->subDay()->toDateString(),
                'authorized_until' => now()->addYear()->toDateString(),
                'training_completed_at' => now()->subDay()->toDateString(),
                'training_reference' => 'INTERNAL-QC-SAMPLE',
                'is_active' => true,
            ]
        );
    }

    public function test_sample_entry_exposes_internal_raw_material_quality_control_path(): void
    {
        $this->actingAs($this->verifiedAdmin())
            ->get(route('vap_samples.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPSamples/Index')
                ->where('internalQualityControlPath.sample_type', 'MATERIA_PRIMA')
                ->where('internalQualityControlPath.request_origin', 'internal')
                ->where('internalQualityControlPath.requires_proposal', false)
                ->has('internalQualityControlPath.presets.microbiology')
                ->has('internalQualityControlPath.presets.chemistry')
                ->has('internalQualityControlPath.workflow')
            );
    }

    public function test_internal_raw_material_quality_control_sample_enters_normal_analysis_flow(): void
    {
        $user = $this->verifiedAdmin();
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type', 'matrix.profiles.parameters'])
            ->firstOrFail();

        /** @var Profile $profile */
        $profile = $product->matrix->profiles->first();
        $department = Department::query()->findOrFail($profile->type->department_id);
        $customer = Customer::query()->firstOrFail();
        $warehouse = Warehouse::query()->where('customer_id', $customer->id)->first() ?: Warehouse::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $this->qualifyUser($user, $department);

        /** @var GeneralSettings $settings */
        $settings = app(GeneralSettings::class);
        $originalMode = $settings->app_operation_mode;
        $code = 'MP-CQ-'.Str::upper(Str::random(6));

        try {
            $settings->app_operation_mode = 'hybrid';
            $settings->save();

            $this->actingAs($user)
                ->post(route('vap_samples.samples.store'), [
                    'name' => 'Matéria-prima para CQ interno',
                    'code' => $code,
                    'sample_type' => 'MATERIA_PRIMA',
                    'customer_id' => $customer->id,
                    'lab_id' => $lab->id,
                    'department_id' => $department->id,
                    'warehouse_id' => $warehouse->id,
                    'received_at' => now()->toDateTimeString(),
                    'requested_services' => 'Controlo interno de matéria-prima',
                    'status' => 'EN_PROGRESO',
                    'analysis_start_date' => now()->toDateTimeString(),
                    'client_submitted_info' => [
                        'request_origin' => 'internal',
                        'product_id' => $product->id,
                        'matrix_id' => $product->matrix_id,
                        'requested_profile_ids' => [$profile->id],
                        'quality_control_purpose' => 'raw_material_release',
                        'analysis_discipline' => 'microbiology',
                        'material_category' => 'raw_material',
                        'qc_decision' => 'hold_until_release',
                        'lot' => 'LOT-CQ-001',
                        'batch' => 'BATCH-CQ-001',
                        'supplier_name' => 'Fornecedor CQ',
                    ],
                ])
                ->assertRedirect()
                ->assertSessionHas('type', 'success');

            $sample = VAPSampleEntry::query()->where('code', $code)->firstOrFail();
            $sampleInfo = $sample->client_submitted_info;

            $this->assertSame('internal', data_get($sampleInfo, 'request_origin'));
            $this->assertSame('internal_quality_control', data_get($sampleInfo, 'quality_control_path.procedure_type'));
            $this->assertSame('microbiology', data_get($sampleInfo, 'quality_control_path.discipline'));
            $this->assertTrue((bool) data_get($sampleInfo, 'quality_control_path.follows_normal_analysis_flow'));
            $this->assertFalse((bool) data_get($sampleInfo, 'quality_control_path.requires_proposal'));
            $this->assertSame(120, $sample->retention_period_days);
            $this->assertNotNull($sample->collection_product_id);
            $this->assertNotEmpty(data_get($sampleInfo, 'linked_sample_ids', []));

            $this->assertTrue(
                Analysis::query()
                    ->where('profile_id', $profile->id)
                    ->where('product_id', $product->id)
                    ->whereIn('sample_id', data_get($sampleInfo, 'linked_sample_ids', []))
                    ->exists()
            );

            $reportResponse = $this->actingAs($user)
                ->get(route('vap_samples.reports', ['sample_scope' => 'internal_qc', 'status' => 'EN_PROGRESO']));

            $reportResponse
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('VAPSamples/Reports')
                    ->where('filters.sample_scope', 'internal_qc')
                    ->where('summary.internal_qc_samples', data_get($reportResponse->viewData('page'), 'props.internalQualityControl.total'))
                    ->has('internalQualityControl.samples')
                );

            $reportSamples = collect(data_get($reportResponse->viewData('page'), 'props.internalQualityControl.samples', []));
            $reportedSample = $reportSamples->firstWhere('code', $code);

            $this->assertNotNull($reportedSample, 'Expected the internal raw material QC sample to appear in the dedicated report cut.');
            $this->assertTrue((bool) data_get($reportedSample, 'quality_control.is_internal_qc'));
            $this->assertSame('microbiology', data_get($reportedSample, 'quality_control.discipline'));

            $this->actingAs($user)
                ->get(route('vap_samples.show', $sample))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('VAPSamples/Show')
                    ->where('workflowSummary.quality_control_release.applies', true)
                    ->where('workflowSummary.quality_control_release.status', 'pending_results')
                    ->where('workflowSummary.quality_control_release.decision', 'hold_until_release')
                    ->where('workflowSummary.quality_control_release.requires_full_approval', true)
                    ->has('workflowSummary.quality_control_release.totals')
                );

            $this->actingAs($user)
                ->from(route('vap_samples.show', $sample))
                ->patch(route('vap_samples.samples.internal-quality-control-decision', $sample), [
                    'decision' => 'released',
                    'notes' => 'Tentativa de liberação antes dos resultados.',
                ])
                ->assertRedirect(route('vap_samples.show', $sample))
                ->assertSessionHasErrors('decision');

            $this->actingAs($user)
                ->from(route('vap_samples.show', $sample))
                ->patch(route('vap_samples.samples.internal-quality-control-decision', $sample), [
                    'decision' => 'quarantined',
                    'notes' => 'Lote retido até conclusão da investigação técnica.',
                ])
                ->assertRedirect(route('vap_samples.show', $sample))
                ->assertSessionHas('type', 'success');

            $sample->refresh();

            $this->assertSame('quarantined', data_get($sample->client_submitted_info, 'qc_release.decision'));
            $this->assertSame('Em quarentena', data_get($sample->client_submitted_info, 'qc_release.label'));
            $this->assertSame('Lote retido até conclusão da investigação técnica.', data_get($sample->client_submitted_info, 'qc_release.notes'));
            $this->assertNotEmpty(data_get($sample->client_submitted_info, 'qc_release.decided_at'));
            $this->assertCount(1, data_get($sample->client_submitted_info, 'qc_release_history', []));

            $releaseReportResponse = $this->actingAs($user)
                ->get(route('vap_samples.reports', [
                    'sample_scope' => 'internal_qc',
                    'qc_release' => 'quarantined',
                ]));

            $releaseReportResponse
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('VAPSamples/Reports')
                    ->where('filters.qc_release', 'quarantined')
                    ->has('internalQualityControl.by_release_decision')
                    ->has('internalQualityControl.samples')
                );

            $releaseReportSamples = collect(data_get($releaseReportResponse->viewData('page'), 'props.internalQualityControl.samples', []));
            $releaseReportedSample = $releaseReportSamples->firstWhere('code', $code);

            $this->assertNotNull($releaseReportedSample, 'Expected the quarantined internal QC sample to appear in the release decision report cut.');
            $this->assertSame('quarantined', data_get($releaseReportedSample, 'quality_control.final_decision'));
            $this->assertSame('Em quarentena', data_get($releaseReportedSample, 'quality_control.final_decision_label'));

            $exportResponse = $this->actingAs($user)
                ->get(route('vap_samples.samples.export', [
                    'sample_scope' => 'internal_qc',
                    'qc_release' => 'quarantined',
                ]));

            $exportResponse->assertOk();
            $exportCsv = $exportResponse->streamedContent();

            $this->assertStringContainsString('QC Final Decision', $exportCsv);
            $this->assertStringContainsString('Em quarentena', $exportCsv);
            $this->assertStringContainsString('BATCH-CQ-001', $exportCsv);
        } finally {
            $settings->app_operation_mode = $originalMode;
            $settings->save();
        }
    }
}
