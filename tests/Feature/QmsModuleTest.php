<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PersonnelQualification;
use App\Models\ReportStudioTemplate;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPNonConformity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class QmsModuleTest extends TestCase
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

    public function test_admin_can_open_qms_and_related_pages(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)->get(route('qms.index'))->assertOk();
        $this->actingAs($user)->get(route('responsibility-matrix.index'))->assertOk();
        $this->actingAs($user)->get(route('uncertainty-sources.index'))->assertOk();
    }

    public function test_admin_can_create_proposal_report_studio_template(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)->post(route('report-studios.store'), [
            'name' => 'Proposal Studio Premium',
            'studio_type' => 'proposal',
            'renderer' => 'internal',
            'status' => 'active',
            'is_default' => true,
            'theme_preset' => 'corporate',
            'description' => 'Template para propostas multipágina.',
            'layout_schema' => [
                'first_page_header_html' => '<div>Header</div>',
                'default_header_html' => '<div>Repeat</div>',
                'footer_html' => '<div>Footer</div>',
            ],
            'export_settings' => [
                'paper_size' => 'A4',
                'orientation' => 'P',
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('report_studio_templates', [
            'name' => 'Proposal Studio Premium',
            'studio_type' => 'proposal',
            'status' => 'active',
        ]);

        $this->assertNotNull(ReportStudioTemplate::resolveDefaultFor('proposal'));
    }

    public function test_qms_dashboard_surfaces_competence_follow_up_signals(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();

        PersonnelQualification::query()->create([
            'user_id' => $user->id,
            'capability' => 'Verificação de resultados ' . Str::upper(Str::random(5)),
            'department_id' => $department->id,
            'qualified_by_id' => $user->id,
            'authorized_from' => now()->subMonths(10)->toDateString(),
            'authorized_until' => now()->addDays(15)->toDateString(),
            'training_completed_at' => now()->subMonths(11)->toDateString(),
            'training_reference' => 'CERT-QMS-001',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('qms.index'));

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame(1, data_get($page, 'props.summary.expiring_qualifications'));
        $this->assertSame('CERT-QMS-001', data_get($page, 'props.renewalReadyQualifications.0.training_reference'));
        $this->assertSame('overdue', data_get($page, 'props.qualificationFollowUps.0.follow_up_state'));
    }

    public function test_qms_dashboard_surfaces_receiving_non_conformities(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();

        VAPNonConformity::query()->create([
            'department_id' => $department->id,
            'nc_number' => 'NC-REC-0001',
            'title' => 'Desvio na recepção de reagente',
            'description' => 'Material recebido com dano visível.',
            'status' => 'opened',
            'severity' => 'high',
            'category' => 'quality',
            'reported_by' => $user->name,
            'reported_by_id' => $user->id,
            'reported_at' => now(),
            'occurrence_area' => 'procurement_receipt',
            'batch_number' => 'PO-2026-001',
        ]);

        $response = $this->actingAs($user)->get(route('qms.index'));

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame(1, data_get($page, 'props.summary.receiving_non_conformities_open'));
        $this->assertSame('Desvio na recepção de reagente', data_get($page, 'props.receivingNonConformities.0.title'));
    }

    public function test_user_edit_page_shows_competence_monitoring_panel(): void
    {
        $admin = $this->verifiedAdmin();
        $targetUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $department = Department::query()->firstOrFail();

        PersonnelQualification::query()->create([
            'user_id' => $targetUser->id,
            'capability' => 'Leitura cromatográfica',
            'department_id' => $department->id,
            'qualified_by_id' => $admin->id,
            'authorized_from' => now()->subMonths(8)->toDateString(),
            'authorized_until' => now()->addDays(20)->toDateString(),
            'training_completed_at' => now()->subMonths(9)->toDateString(),
            'training_reference' => 'CERT-USER-020',
            'is_active' => true,
            'notes' => 'Agendar reciclagem interna antes da renovação.',
        ]);

        $response = $this->actingAs($admin)->get(route('users.edit', ['user' => $targetUser->id]));

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame(1, data_get($page, 'props.competenceSummary.total'));
        $this->assertSame('CERT-USER-020', data_get($page, 'props.record.personnel_qualifications.0.training_reference'));
        $this->assertSame('Agendar reciclagem interna antes da renovação.', data_get($page, 'props.record.personnel_qualifications.0.notes'));
    }
}
