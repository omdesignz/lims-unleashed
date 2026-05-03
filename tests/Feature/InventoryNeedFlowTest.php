<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use App\Notifications\InventoryNeedWorkflowNotification;
use Tests\TestCase;

class InventoryNeedFlowTest extends TestCase
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

    public function test_need_can_be_submitted_approved_and_converted_into_purchase_order(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();
        $lab = VAPLab::query()->first() ?? VAPLab::query()->create([
            'name' => 'Laboratório de Ensaios',
            'department_id' => $department->id,
        ]);
        $item = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->firstOrFail();

        Notification::fake();

        $this->actingAs($user)->post(route('vap-inventory.needs.store'), [
            'department_id' => $department->id,
            'lab_id' => $lab->id,
            'needed_by_date' => now()->addDays(10)->toDateString(),
            'justification' => 'Reposição crítica para manter a operação do laboratório.',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity_requested' => 5,
                    'estimated_unit_price' => 1250,
                    'notes' => 'Entrega prioritária',
                ],
            ],
        ])->assertRedirect();

        /** @var InventoryNeed $need */
        $need = InventoryNeed::query()->with('items')->latest('id')->firstOrFail();

        $this->assertSame('submitted', $need->status);
        $this->assertCount(1, $need->items);
        Notification::assertSentTo(
            $user,
            InventoryNeedWorkflowNotification::class,
            function (InventoryNeedWorkflowNotification $notification, array $channels) use ($need, $user): bool {
                $payload = $notification->toDatabase($user);

                return $notification->need->is($need)
                    && $notification->title === 'Nova necessidade submetida'
                    && in_array('database', $channels, true)
                    && $payload['type'] === 'info'
                    && $payload['sender'] === $user->name
                    && str_contains($payload['need_url'], (string) $need->id);
            }
        );

        $this->actingAs($user)->post(route('vap-inventory.needs.approve', $need), [
            'approval_notes' => 'Aprovado para aquisição imediata.',
            'items' => [
                [
                    'id' => $need->items->first()->id,
                    'quantity_approved' => 4,
                ],
            ],
        ])->assertRedirect();

        $need->refresh();
        $need->load('items');

        $this->assertSame('approved', $need->status);
        $this->assertSame(4, $need->items->first()->quantity_approved);
        Notification::assertSentTo(
            $user,
            InventoryNeedWorkflowNotification::class,
            fn (InventoryNeedWorkflowNotification $notification): bool => $notification->need->is($need)
                && $notification->title === 'Necessidade aprovada'
        );

        $this->actingAs($user)->post(route('vap-inventory.needs.convert-to-order', $need), [
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'reference' => 'AUTO-NEED-ORDER',
            'obs' => 'Gerado pelo teste automático.',
        ])->assertRedirect();

        $need->refresh();
        $need->load('inventoryOrder', 'items');

        $this->assertSame('ordered', $need->status);
        $this->assertNotNull($need->inventory_order_id);
        $this->assertNotNull($need->inventoryOrder);
        $this->assertSame('ordered', $need->items->first()->status);
        Notification::assertSentTo(
            $user,
            InventoryNeedWorkflowNotification::class,
            fn (InventoryNeedWorkflowNotification $notification): bool => $notification->need->is($need)
                && $notification->title === 'Necessidade convertida em pedido'
                && str_contains($notification->message, (string) $need->inventoryOrder?->reference)
        );
    }

    public function test_needs_index_and_show_pages_load_for_admin(): void
    {
        $user = $this->verifiedAdmin();
        $need = InventoryNeed::query()->first();
        $item = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();

        if ($need === null) {
            $department = Department::query()->firstOrFail();
            $need = InventoryNeed::query()->create([
                'reference' => 'NEED-INDEX-001',
                'department_id' => $department->id,
                'requested_by_id' => $user->id,
                'status' => 'approved',
                'needed_by_date' => now()->addDays(4)->toDateString(),
                'justification' => 'Teste da fila de procurement.',
                'submitted_at' => now()->subDay(),
                'approved_at' => now(),
            ]);

            InventoryNeedItem::query()->create([
                'inventory_need_id' => $need->id,
                'inventory_item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'quantity_requested' => 1,
                'quantity_approved' => 1,
                'estimated_unit_price' => 10,
                'status' => 'approved',
            ]);
        }

        $response = $this->actingAs($user)->get(route('vap-inventory.needs.index'));
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertIsArray(data_get($page, 'props.procurementQueue', []));
        $this->assertNotNull(data_get($page, 'props.stats.awaiting_order'));
        $this->assertNotNull(data_get($page, 'props.stats.overdue_procurement'));
        $this->assertSame('Submetidas', data_get($page, 'props.charts.status_overview.labels.0'));
        $this->assertSame('Prontas', data_get($page, 'props.charts.queue_readiness.labels.0'));
        $this->assertSame('Fila procurement', data_get($page, 'props.charts.procurement_pressure.labels.0'));
        $firstQueueEntry = collect(data_get($page, 'props.procurementQueue', []))->first();

        if ($firstQueueEntry) {
            $this->assertArrayHasKey('supplier_readiness', $firstQueueEntry);
            $this->assertArrayHasKey('supplier_summary', $firstQueueEntry);
        }

        if ($need) {
            $showResponse = $this->actingAs($user)->get(route('vap-inventory.needs.show', $need));
            $showResponse->assertOk();

            $showPage = $showResponse->viewData('page');
            $this->assertSame('Solicitado', data_get($showPage, 'props.charts.quantity_scope.labels.0'));
            $this->assertSame('Itens', data_get($showPage, 'props.charts.governance_pulse.labels.0'));
        }
    }

    public function test_need_rejection_sends_workflow_notification_to_requester(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();
        $item = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();

        $need = InventoryNeed::query()->create([
            'reference' => 'NEED-REJECT-001',
            'department_id' => $department->id,
            'requested_by_id' => $user->id,
            'status' => 'submitted',
            'needed_by_date' => now()->addDays(3)->toDateString(),
            'justification' => 'Material sem orçamento aprovado.',
            'submitted_at' => now(),
        ]);

        InventoryNeedItem::query()->create([
            'inventory_need_id' => $need->id,
            'inventory_item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity_requested' => 2,
            'estimated_unit_price' => 55,
            'status' => 'requested',
        ]);

        Notification::fake();

        $this->actingAs($user)->post(route('vap-inventory.needs.reject', $need), [
            'approval_notes' => 'Rejeitada por indisponibilidade orçamental.',
        ])->assertRedirect();

        $need->refresh();

        $this->assertSame('rejected', $need->status);
        Notification::assertSentTo(
            $user,
            InventoryNeedWorkflowNotification::class,
            function (InventoryNeedWorkflowNotification $notification, array $channels) use ($need, $user): bool {
                $payload = $notification->toDatabase($user);

                return $notification->need->is($need)
                    && $notification->title === 'Necessidade rejeitada'
                    && in_array('database', $channels, true)
                    && $payload['type'] === 'error'
                    && str_contains($notification->message, 'indisponibilidade orçamental');
            }
        );
    }

    public function test_need_pdf_export_returns_a_pdf_response(): void
    {
        $user = $this->verifiedAdmin();
        $department = Department::query()->firstOrFail();
        $item = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();

        $need = InventoryNeed::query()->create([
            'reference' => 'NEED-PDF-001',
            'department_id' => $department->id,
            'requested_by_id' => $user->id,
            'status' => 'approved',
            'needed_by_date' => now()->addDays(5)->toDateString(),
            'justification' => 'Exportação PDF premium.',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
            'approved_by_id' => $user->id,
        ]);

        InventoryNeedItem::query()->create([
            'inventory_need_id' => $need->id,
            'inventory_item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity_requested' => 6,
            'quantity_approved' => 4,
            'estimated_unit_price' => 275,
            'status' => 'approved',
            'notes' => 'Gerar no template premium.',
        ]);

        $response = $this->actingAs($user)->get(route('vap-inventory.needs.pdf', $need));

        $response->assertOk();
    }

    public function test_need_submission_rejects_labs_from_another_department(): void
    {
        $user = $this->verifiedAdmin();
        $departments = Department::query()->limit(2)->get();

        if ($departments->count() < 2) {
            $this->markTestSkipped('This test requires at least two departments.');
        }

        $primaryDepartment = $departments->get(0);
        $secondaryDepartment = $departments->get(1);
        $lab = VAPLab::query()->create([
            'name' => 'Laboratório de departamento divergente',
            'department_id' => $secondaryDepartment->id,
        ]);
        $item = InventoryItem::query()->firstOrFail();
        $warehouse = InventoryItemWarehouse::query()->firstOrFail();

        $this->actingAs($user)
            ->from(route('vap-inventory.needs.create'))
            ->post(route('vap-inventory.needs.store'), [
                'department_id' => $primaryDepartment->id,
                'lab_id' => $lab->id,
                'needed_by_date' => now()->addDays(10)->toDateString(),
                'justification' => 'Validação de integridade departamento/laboratório.',
                'items' => [
                    [
                        'inventory_item_id' => $item->id,
                        'warehouse_id' => $warehouse->id,
                        'quantity_requested' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('vap-inventory.needs.create'))
            ->assertSessionHasErrors(['lab_id']);
    }
}
