<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryNeed;
use App\Models\InventorySupplierAssessment;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExecutiveDashboardSuppliersTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    public function test_current_workbenches_surface_procurement_and_quality_signals(): void
    {
        $user = $this->verifiedAdmin();
        $labId = DB::table('lab_user')->where('user_id', $user->id)->value('lab_id');
        $department = Department::query()->create(['name' => 'Executive procurement '.Str::random(8)]);
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor Executivo',
            'address' => 'Luanda',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
            'lab_id' => $labId,
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $user->id,
            'assessment_date' => now()->subDays(5)->toDateString(),
            'next_review_at' => now()->addDays(10)->toDateString(),
            'status' => 'conditional',
            'risk_level' => 'high',
            'total_score' => 58,
            'delivery_score' => 3,
            'quality_score' => 3,
            'compliance_score' => 3,
            'responsiveness_score' => 2,
            'approved_supplier' => false,
            'is_active' => true,
        ]);

        InventoryNeed::query()->create([
            'lab_id' => $labId,
            'reference' => 'NEED-EXEC-001',
            'department_id' => $department->id,
            'requested_by_id' => $user->id,
            'status' => 'approved',
            'needed_by_date' => now()->addDays(2)->toDateString(),
            'justification' => 'Aguardar conversão em pedido.',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
        ]);

        VAPNonConformity::query()->create([
            'lab_id' => $labId,
            'department_id' => $department->id,
            'nc_number' => 'NC-EXEC-001',
            'title' => 'Recepção com desvio executivo',
            'description' => 'Ocorrência aberta no recebimento.',
            'status' => 'opened',
            'severity' => 'critical',
            'category' => 'quality',
            'reported_by' => $user->name,
            'reported_by_id' => $user->id,
            'reported_at' => now(),
            'occurrence_area' => 'procurement_receipt',
            'batch_number' => 'PO-EXEC-001',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('LaboratoryWorkbench'));

        $qms = $this->get(route('qms.index'));
        $qms->assertOk();
        $quality = $qms->viewData('page');
        $this->assertSame(1, data_get($quality, 'props.summary.suppliers_high_risk'));
        $this->assertSame(1, data_get($quality, 'props.summary.receiving_non_conformities_open'));
        $this->assertSame('Fornecedor Executivo', data_get($quality, 'props.dueSupplierAssessments.0.supplier.name'));
        $this->assertSame('Recepção com desvio executivo', data_get($quality, 'props.receivingNonConformities.0.title'));

        $procurement = $this->get(route('vap-inventory.needs.index'));
        $procurement->assertOk();
        $this->assertSame('NEED-EXEC-001', data_get($procurement->viewData('page'), 'props.procurementQueue.0.reference'));
    }
}
