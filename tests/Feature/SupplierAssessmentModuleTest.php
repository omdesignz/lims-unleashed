<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventorySupplierAssessment;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPNonConformity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupplierAssessmentModuleTest extends TestCase
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

    public function test_admin_can_open_supplier_assessment_module(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('supplier-assessments.index'))
            ->assertOk();
    }

    public function test_admin_can_create_supplier_assessment(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor QA',
            'address' => 'Luanda',
            'currency' => 'AOA',
        ]);

        $this->actingAs($user)->post(route('supplier-assessments.store'), [
            'inventory_item_supplier_id' => $supplier->id,
            'department_id' => $department->id,
            'assessment_date' => now()->toDateString(),
            'next_review_at' => now()->addDays(30)->toDateString(),
            'status' => 'conditional',
            'risk_level' => 'high',
            'delivery_score' => 3,
            'quality_score' => 4,
            'compliance_score' => 2,
            'responsiveness_score' => 3,
            'approved_supplier' => false,
            'is_active' => true,
            'evidence_reference' => 'SUP-9001',
            'gaps' => 'Documentação incompleta.',
            'follow_up_actions' => 'Solicitar ficha técnica atualizada.',
        ])->assertRedirect();

        $this->assertDatabaseHas('inventory_supplier_assessments', [
            'inventory_item_supplier_id' => $supplier->id,
            'status' => 'conditional',
            'risk_level' => 'high',
            'total_score' => 60,
            'evidence_reference' => 'SUP-9001',
        ]);
    }

    public function test_qms_dashboard_surfaces_supplier_assessment_signals(): void
    {
        $user = $this->verifiedAdmin();
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor Crítico',
            'address' => 'Benguela',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $user->id,
            'assessment_date' => now()->subDays(10)->toDateString(),
            'next_review_at' => now()->addDays(12)->toDateString(),
            'status' => 'conditional',
            'risk_level' => 'critical',
            'total_score' => 48,
            'delivery_score' => 2,
            'quality_score' => 2,
            'compliance_score' => 3,
            'responsiveness_score' => 2,
            'approved_supplier' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('qms.index'));
        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertGreaterThanOrEqual(1, data_get($page, 'props.summary.supplier_assessments_due'));
        $this->assertGreaterThanOrEqual(1, data_get($page, 'props.summary.suppliers_high_risk'));
        $this->assertContains(
            'Fornecedor Crítico',
            collect(data_get($page, 'props.dueSupplierAssessments', []))->pluck('supplier.name')->all()
        );
    }

    public function test_purchase_order_creation_is_blocked_for_suspended_supplier(): void
    {
        $user = $this->verifiedAdmin();
        $inventoryItem = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor Suspenso',
            'address' => 'Huíla',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $user->id,
            'assessment_date' => now()->subDays(3)->toDateString(),
            'next_review_at' => now()->addDays(20)->toDateString(),
            'status' => 'suspended',
            'risk_level' => 'high',
            'total_score' => 35,
            'delivery_score' => 2,
            'quality_score' => 2,
            'compliance_score' => 2,
            'responsiveness_score' => 1,
            'approved_supplier' => false,
            'is_active' => true,
        ]);

        $response = $this->from(route('vap-inventory.orders.create'))
            ->actingAs($user)
            ->post(route('vap-inventory.orders.store'), [
                'supplier_id' => $supplier->id,
                'date' => now()->toDateString(),
                'status' => 'PENDING',
                'obs' => 'Teste de bloqueio de fornecedor.',
                'order_items' => [
                    [
                        'item_id' => $inventoryItem->id,
                        'qty' => 1,
                        'warehouse_id' => $warehouse->id,
                        'expected_date' => now()->addDays(7)->toDateString(),
                        'unit_price' => 100,
                        'status' => 'PENDING',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('vap-inventory.orders.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('i_orders', [
            'supplier_id' => $supplier->id,
            'obs' => 'Teste de bloqueio de fornecedor.',
        ]);
    }

    public function test_need_conversion_to_order_is_blocked_for_suspended_supplier(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();
        $inventoryItem = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor Suspenso da Necessidade',
            'address' => 'Namibe',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $user->id,
            'assessment_date' => now()->subDays(1)->toDateString(),
            'next_review_at' => now()->addDays(25)->toDateString(),
            'status' => 'suspended',
            'risk_level' => 'high',
            'total_score' => 32,
            'delivery_score' => 2,
            'quality_score' => 2,
            'compliance_score' => 1,
            'responsiveness_score' => 1,
            'approved_supplier' => false,
            'is_active' => true,
        ]);

        $need = InventoryNeed::query()->create([
            'reference' => 'NEED-BLOCK-001',
            'department_id' => $department->id,
            'requested_by_id' => $user->id,
            'approved_by_id' => $user->id,
            'status' => 'approved',
            'needed_by_date' => now()->addDays(7)->toDateString(),
            'justification' => 'Teste de bloqueio na conversão.',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
        ]);

        InventoryNeedItem::query()->create([
            'inventory_need_id' => $need->id,
            'inventory_item_id' => $inventoryItem->id,
            'warehouse_id' => $warehouse->id,
            'quantity_requested' => 2,
            'quantity_approved' => 2,
            'estimated_unit_price' => 150,
            'status' => 'approved',
        ]);

        $response = $this->from(route('vap-inventory.needs.show', $need))
            ->actingAs($user)
            ->post(route('vap-inventory.needs.convert-to-order', $need), [
                'supplier_id' => $supplier->id,
                'date' => now()->toDateString(),
                'expected_date' => now()->addDays(7)->toDateString(),
                'reference' => 'PED-NC-001',
                'obs' => 'Conversão bloqueada por avaliação.',
            ]);

        $response
            ->assertRedirect(route('vap-inventory.needs.show', $need))
            ->assertSessionHas('error');

        $this->assertNull($need->fresh()->inventory_order_id);
        $this->assertDatabaseMissing('i_orders', [
            'supplier_id' => $supplier->id,
            'reference' => 'PED-NC-001',
        ]);
        $this->assertSame(0, InventoryOrder::query()->where('supplier_id', $supplier->id)->count());
    }

    public function test_order_pages_expose_supplier_assessment_context(): void
    {
        $user = $this->verifiedAdmin();
        $inventoryItem = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor com contexto',
            'address' => 'Luanda Sul',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $user->id,
            'assessment_date' => now()->subDays(2)->toDateString(),
            'next_review_at' => now()->addDays(15)->toDateString(),
            'status' => 'conditional',
            'risk_level' => 'high',
            'total_score' => 61,
            'delivery_score' => 3,
            'quality_score' => 3,
            'compliance_score' => 3,
            'responsiveness_score' => 3,
            'approved_supplier' => false,
            'is_active' => true,
        ]);

        $order = InventoryOrder::query()->create([
            'date' => now()->toDateString(),
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'order_year' => now()->format('Y'),
            'status' => 'PENDING',
            'currency' => 'AOA',
            'total_amount' => 125,
        ]);

        InventoryOrderDetail::query()->create([
            'order_id' => $order->id,
            'item_id' => $inventoryItem->id,
            'qty' => 1,
            'unit_price' => 125,
            'warehouse_id' => $warehouse->id,
            'status' => 'PENDING',
            'currency' => 'AOA',
        ]);

        $indexResponse = $this->actingAs($user)->get(route('vap-inventory.orders.index'));
        $indexResponse->assertOk();
        $indexPage = $indexResponse->viewData('page');
        $indexedOrder = collect(data_get($indexPage, 'props.orders.data', []))
            ->firstWhere('id', $order->id);

        $this->assertIsArray($indexedOrder);
        $this->assertSame('conditional', data_get($indexedOrder, 'supplier.latest_assessment.status'));
        $this->assertSame('high', data_get($indexedOrder, 'supplier.latest_assessment.risk_level'));
        $this->assertSame(0, data_get($indexedOrder, 'reception_non_conformity_summary.open_count'));

        $showResponse = $this->actingAs($user)->get(route('vap-inventory.orders.show', $order));
        $showResponse->assertOk();
        $showPage = $showResponse->viewData('page');

        $this->assertSame('conditional', data_get($showPage, 'props.order.supplier.latest_assessment.status'));
        $this->assertSame('high', data_get($showPage, 'props.order.supplier.latest_assessment.risk_level'));
    }

    public function test_receiving_order_can_register_non_conformity_evidence(): void
    {
        $user = $this->verifiedAdmin();
        $inventoryItem = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor com desvio',
            'address' => 'Cacuaco',
            'currency' => 'AOA',
        ]);

        $order = InventoryOrder::query()->create([
            'date' => now()->toDateString(),
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'order_year' => now()->format('Y'),
            'status' => 'ORDERED',
            'currency' => 'AOA',
            'reference' => 'PO-NC-001',
            'total_amount' => 800,
        ]);

        $orderItem = InventoryOrderDetail::query()->create([
            'order_id' => $order->id,
            'item_id' => $inventoryItem->id,
            'qty' => 4,
            'received_qty' => 0,
            'unit_price' => 200,
            'warehouse_id' => $warehouse->id,
            'status' => 'ORDERED',
            'currency' => 'AOA',
        ]);

        $response = $this->actingAs($user)
            ->post(route('vap-inventory.orders.receive', $order), [
                'items' => [
                    [
                        'id' => $orderItem->id,
                        'received_qty' => 2,
                        'unit_price' => 200,
                    ],
                ],
                'receive_date' => now()->toDateString(),
                'reason' => 'Recepção com desvio visual',
                'notes' => 'Embalagem exterior danificada.',
                'register_non_conformity' => true,
                'non_conformity_title' => 'Dano na recepção do fornecedor',
                'non_conformity_severity' => 'high',
                'non_conformity_description' => 'A embalagem chegou danificada e exige avaliação adicional antes do uso.',
            ]);

        $response
            ->assertRedirect(route('vap-inventory.orders.show', $order))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('i_order_details', [
            'id' => $orderItem->id,
            'received_qty' => 2,
            'status' => 'PARTIALLY_RECEIVED',
        ]);

        $this->assertDatabaseHas('v_non_conformities', [
            'title' => 'Dano na recepção do fornecedor',
            'status' => 'opened',
            'severity' => 'high',
            'category' => 'quality',
            'reported_by_id' => $user->id,
        ]);

        $record = VAPNonConformity::query()->latest('id')->first();

        $this->assertNotNull($record);
        $this->assertStringContainsString('Contexto da recepção', $record->description);
        $this->assertStringContainsString((string) $order->id, (string) $record->evidence);
        $this->assertNotEmpty($record->batch_number);

        $indexResponse = $this->actingAs($user)->get(route('vap-inventory.orders.index'));
        $indexResponse->assertOk();
        $indexPage = $indexResponse->viewData('page');
        $indexedOrder = collect(data_get($indexPage, 'props.orders.data', []))
            ->firstWhere('id', $order->id);

        $this->assertSame(1, data_get($indexedOrder, 'reception_non_conformity_summary.open_count'));
        $this->assertSame('high', data_get($indexedOrder, 'reception_non_conformity_summary.latest_severity'));
    }
}
