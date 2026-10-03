<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\AnalysisReportService;
use App\Support\LaboratoryDossierService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LaboratoryWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_accepted_proposal_becomes_an_actionable_laboratory_dossier(): void
    {
        $user = $this->verifiedAdmin();
        $proposal = $this->acceptedProposal($user);

        $response = $this->actingAs($user)
            ->get(route('laboratory-workflow.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LaboratoryWorkflow/Index')
                ->has('dossiers')
                ->has('stageOptions')
            );

        $dossier = collect(data_get($response->viewData('page'), 'props.dossiers', []))
            ->firstWhere('id', $proposal->id);

        $this->assertNotNull($dossier);
        $this->assertSame('awaiting_samples', data_get($dossier, 'stage.key'));
        $this->assertSame(
            route('vap_samples.index', ['proposal_id' => $proposal->id, 'start' => 1]),
            data_get($dossier, 'primary_action.url')
        );

        $this->actingAs($user)
            ->get(route('vap-proposals.show', $proposal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPProposals/Show')
                ->where('laboratoryDossier.id', $proposal->id)
                ->where('laboratoryDossier.stage.key', 'awaiting_samples')
            );
    }

    public function test_proposal_handoff_prefills_sample_intake_and_progresses_after_receipt(): void
    {
        $user = $this->verifiedAdmin();
        $proposal = $this->acceptedProposal($user);

        $this->actingAs($user)
            ->get(route('vap_samples.index', ['proposal_id' => $proposal->id, 'start' => 1]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAPSamples/Index')
                ->where('entryWorkflowDefaults.open_form', true)
                ->where('entryWorkflowDefaults.proposal.id', $proposal->id)
                ->where('entryWorkflowDefaults.proposal.customer_id', $proposal->customer_id)
                ->where('entryWorkflowDefaults.proposal.warehouse_id', $proposal->warehouse_id)
                ->where('entryWorkflowDefaults.proposal.department_id', $proposal->department_id)
            );

        $sampleEntry = VAPSampleEntry::query()->create([
            'name' => 'Amostra recebida sem escopo técnico',
            'sample_type' => 'ROTINA',
            'status' => 'POR_INICIAR',
            'proposal_id' => $proposal->id,
            'customer_id' => $proposal->customer_id,
            'warehouse_id' => $proposal->warehouse_id,
            'department_id' => $proposal->department_id,
            'lab_id' => $proposal->lab_id,
            'received_by_id' => $user->id,
            'received_by_label' => $user->name,
            'received_at' => now(),
            'sample_year' => now()->year,
            'client_submitted_info' => ['request_origin' => 'client'],
        ]);

        $dossier = app(LaboratoryDossierService::class)->summarize($proposal->fresh());

        $this->assertSame('intake', data_get($dossier, 'stage.key'));
        $this->assertSame(route('vap_samples.show', $sampleEntry), data_get($dossier, 'primary_action.url'));
        $this->assertSame(1, data_get($dossier, 'counts.samples'));
        $this->assertSame(0, data_get($dossier, 'counts.accessioned_samples'));
    }

    public function test_sample_intake_rejects_commercial_lineage_mismatches(): void
    {
        $user = $this->verifiedAdmin();
        $proposal = $this->acceptedProposal($user);
        $otherCustomer = Customer::query()->create(['name' => 'Different customer']);
        $otherWarehouse = Warehouse::query()->create(['name' => 'Different customer site', 'customer_id' => $otherCustomer->id]);

        PersonnelQualification::query()->updateOrCreate([
            'lab_id' => $proposal->lab_id,
            'user_id' => $user->id,
            'capability' => 'sample_intake_validation',
            'department_id' => $proposal->department_id,
        ], [
            'qualified_by_id' => $user->id,
            'authorized_from' => now()->subDay()->toDateString(),
            'authorized_until' => now()->addYear()->toDateString(),
            'training_completed_at' => now()->subDay()->toDateString(),
            'training_reference' => 'DOSSIER-LINEAGE-TEST',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->from(route('vap_samples.index'))
            ->post(route('vap_samples.samples.store'), [
                'name' => 'Amostra com cliente incorreto',
                'sample_type' => 'ROTINA',
                'status' => 'POR_INICIAR',
                'proposal_id' => $proposal->id,
                'customer_id' => $otherCustomer->id,
                'warehouse_id' => $otherWarehouse->id,
                'department_id' => $proposal->department_id,
                'lab_id' => $proposal->lab_id,
                'received_at' => now()->toDateTimeString(),
                'client_submitted_info' => ['request_origin' => 'client'],
            ])
            ->assertRedirect(route('vap_samples.index'))
            ->assertSessionHasErrors('customer_id');

        $this->assertDatabaseMissing('sample_entries', ['name' => 'Amostra com cliente incorreto']);
    }

    public function test_workflow_viewers_cannot_generate_reports_without_release_permission(): void
    {
        $admin = $this->verifiedAdmin();
        $proposal = $this->acceptedProposal($admin);
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo(Permission::findOrCreate('view_samples', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $proposal->lab_id, 'user_id' => $viewer->id]);

        $this->actingAs($viewer)
            ->get(route('laboratory-workflow.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('laboratory-workflow.reports.store', $proposal))
            ->assertForbidden();
    }

    public function test_workflow_excludes_peer_proposals_and_wrongly_linked_peer_samples(): void
    {
        $user = $this->verifiedAdmin();
        $proposal = $this->acceptedProposal($user);
        $peerUser = $this->verifiedAdmin();
        $peerProposal = $this->acceptedProposal($peerUser);
        $localSample = VAPSampleEntry::factory()->create([
            'proposal_id' => $proposal->id, 'lab_id' => $proposal->lab_id,
        ]);
        $peerSample = VAPSampleEntry::factory()->create([
            'proposal_id' => $proposal->id, 'lab_id' => $peerProposal->lab_id,
        ]);
        $peerOwnedSample = VAPSampleEntry::factory()->create([
            'proposal_id' => $peerProposal->id, 'lab_id' => $peerProposal->lab_id,
        ]);

        $this->assertSame([$localSample->id], $proposal->sampleEntries()->pluck('sample_entries.id')->all());
        $eagerProposal = VAPProposal::query()->with('sampleEntries')->findOrFail($proposal->id);
        $this->assertSame([$localSample->id], $eagerProposal->sampleEntries->modelKeys());
        $proposals = VAPProposal::query()->with('sampleEntries')->findMany([$proposal->id, $peerProposal->id])->keyBy('id');
        $this->assertSame([$peerOwnedSample->id], $proposals[$peerProposal->id]->sampleEntries->modelKeys());
        $reportProposal = $proposal->fresh();
        app(AnalysisReportService::class)->ensureForProposal($reportProposal, $user->id);
        $this->assertSame([$localSample->id], $reportProposal->sampleEntries->modelKeys());

        $this->actingAs($user)->get(route('laboratory-workflow.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('dossiers', 1)
                ->where('dossiers.0.id', $proposal->id)
                ->where('dossiers.0.counts.samples', 1)
                ->has('dossiers.0.samples', 1)
                ->where('dossiers.0.samples.0.id', $localSample->id)
                ->where('stats.total', 1)
            );

        $this->post(route('laboratory-workflow.reports.store', $peerProposal))->assertNotFound();
        $this->assertModelExists($peerSample);
    }

    private function verifiedAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function acceptedProposal(User $user): VAPProposal
    {
        $customer = Customer::query()->create(['name' => 'Workflow customer']);
        $warehouse = Warehouse::query()->create(['name' => 'Workflow site '.Str::uuid(), 'customer_id' => $customer->id]);
        $department = Department::factory()->create();
        $template = VAPProposalTemplate::query()->create([
            'name' => 'Laboratory workflow test',
            'content' => '<p>Laboratory workflow</p>',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $proposal = new VAPProposal([
            'proposal_year' => now()->year,
            'service_location' => $warehouse->address ?: $warehouse->name,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'ACCEPTED',
            'details' => [],
            'sub_total' => 100,
            'total' => 100,
            'unique_hash' => (string) Str::uuid(),
            'tolerance_days' => 30,
        ]);
        $proposal->lab_id = DB::table('lab_user')->where('user_id', $user->id)->value('lab_id');
        $proposal->save();

        $proposal->complianceAgreement()->create([
            'confidentiality' => true,
            'impartiality' => true,
            'nondisclosure' => true,
            'acknowledged_at' => now(),
        ]);

        return $proposal->fresh();
    }
}
