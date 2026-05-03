<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryNeed;
use App\Models\InventorySupplierAssessment;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPNonConformity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExecutiveDashboardSuppliersTest extends TestCase
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

    public function test_dashboard_surfaces_supplier_watchlist_and_kpis(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor Executivo',
            'address' => 'Luanda',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
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

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertGreaterThanOrEqual(1, data_get($page, 'props.executive.kpis.4.value'));
        $this->assertGreaterThanOrEqual(1, data_get($page, 'props.executive.kpis.5.value'));
        $this->assertGreaterThanOrEqual(1, data_get($page, 'props.executive.kpis.6.value'));
        $this->assertGreaterThanOrEqual(1, data_get($page, 'props.executive.kpis.7.value'));
        $this->assertNotEmpty(data_get($page, 'props.executive.charts.throughput.categories'));
        $this->assertCount(3, data_get($page, 'props.executive.charts.throughput.series'));
        $this->assertContains('Risco elevado', data_get($page, 'props.executive.charts.supplier_risk.labels', []));
        $this->assertContains('Compra bloqueada', data_get($page, 'props.executive.charts.procurement_readiness.labels', []));
        $this->assertContains('Crítica', data_get($page, 'props.executive.charts.receiving_nc_severity.labels', []));
        $this->assertContains(
            'Fornecedor Executivo',
            collect(data_get($page, 'props.executive.supplier_watchlist', []))->pluck('supplier_name')->all()
        );
        $this->assertContains(
            'NEED-EXEC-001',
            collect(data_get($page, 'props.executive.procurement_queue', []))->pluck('reference')->all()
        );
        $queueEntry = collect(data_get($page, 'props.executive.procurement_queue', []))
            ->firstWhere('reference', 'NEED-EXEC-001');

        $this->assertIsArray($queueEntry);
        $this->assertArrayHasKey('supplier_readiness', $queueEntry);
        $this->assertArrayHasKey('supplier_summary', $queueEntry);
        $this->assertContains(
            'Recepção com desvio executivo',
            collect(data_get($page, 'props.executive.receiving_non_conformities', []))->pluck('title')->all()
        );
    }
}
