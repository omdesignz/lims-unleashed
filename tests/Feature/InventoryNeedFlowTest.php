<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Models\InventoryUnit;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\OperationalNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InventoryNeedFlowTest extends TestCase
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

    private function createDepartment(): Department
    {
        return Department::query()->create([
            'name' => 'Procurement department',
            'code' => fake()->unique()->bothify('PD-####'),
        ]);
    }

    private function createItem(User $user, ?VAPLab $lab = null): InventoryItem
    {
        $unit = InventoryUnit::query()->create([
            'code' => 'mL-'.fake()->numerify('######'),
            'description' => 'Millilitres',
        ]);

        return InventoryItem::query()->create([
            'lab_id' => $lab?->id ?? DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Procurement material',
            'code' => fake()->unique()->bothify('PN-######'),
            'unit_id' => $unit->id,
        ]);
    }

    private function createWarehouse(User $user, ?VAPLab $lab = null): InventoryItemWarehouse
    {
        return InventoryItemWarehouse::query()->create([
            'lab_id' => $lab?->id ?? DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Procurement warehouse',
        ]);
    }

    public function test_need_can_be_submitted_approved_and_converted_into_purchase_order(): void
    {
        $user = $this->verifiedAdmin();
        $department = $this->createDepartment();
        $lab = VAPLab::query()->create([
            'name' => 'Laboratório de Ensaios',
            'code' => fake()->unique()->bothify('LAB-######'),
            'department_id' => $department->id,
        ]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);
        $item = $this->createItem($user, $lab);
        $warehouse = $this->createWarehouse($user, $lab);
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Procurement supplier', 'currency' => 'AOA']);

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
        Notification::assertSentTo($user, OperationalNotification::class);
        $submittedNotification = Notification::sent($user, OperationalNotification::class)->first();
        $this->assertSame('inventory.need.updated', $submittedNotification->payload['key']);
        $this->assertSame($need->reference, $submittedNotification->payload['context']['need_reference']);
        $this->assertSame('submetida', $submittedNotification->payload['context']['status']);
        $this->assertSame($user->id, $submittedNotification->payload['sender_id']);
        $this->assertNotEmpty($submittedNotification->payload['sender_name']);
        $this->assertContains('database', $submittedNotification->via($user));
        $this->assertStringContainsString((string) $need->id, $submittedNotification->payload['action_url']);

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
        $this->assertSame('4.0000', $need->items->first()->quantity_approved);
        Notification::assertSentTo(
            $user,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'inventory.need.updated'
                && $notification->payload['context']['need_reference'] === $need->reference
                && $notification->payload['context']['status'] === 'aprovada'
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
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'inventory.need.updated'
                && $notification->payload['context']['need_reference'] === $need->reference
                && $notification->payload['context']['status'] === 'convertida em pedido'
                && str_contains($notification->payload['message'], (string) $need->inventoryOrder?->reference)
        );
    }

    public function test_fractional_need_approval_rejects_excess_and_converts_the_exact_quantity(): void
    {
        $user = $this->verifiedAdmin();
        $department = $this->createDepartment();
        $item = $this->createItem($user);
        $warehouse = $this->createWarehouse($user);
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Fractional supplier', 'currency' => 'AOA']);
        Notification::fake();

        $this->actingAs($user)->post(route('vap-inventory.needs.store'), [
            'department_id' => $department->id,
            'justification' => 'Quantidade medida de material.',
            'items' => [[
                'inventory_item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'quantity_requested' => '0.3750',
                'estimated_unit_price' => '10.00',
            ]],
        ])->assertRedirect();

        $need = InventoryNeed::query()->latest('id')->firstOrFail();
        $line = $need->items()->sole();
        $this->assertSame('0.3750', $line->quantity_requested);

        $this->post(route('vap-inventory.needs.approve', $need), [
            'items' => [['id' => $line->id, 'quantity_approved' => '0.3751']],
        ])->assertSessionHasErrors('items');
        $this->assertNull($line->fresh()->quantity_approved);

        $this->post(route('vap-inventory.needs.approve', $need), [
            'items' => [['id' => $line->id, 'quantity_approved' => '0.1250']],
        ])->assertRedirect();
        $this->assertSame('0.1250', $line->fresh()->quantity_approved);

        $this->post(route('vap-inventory.needs.convert-to-order', $need), [
            'supplier_id' => $supplier->id,
            'date' => today()->toDateString(),
            'expected_date' => today()->addWeek()->toDateString(),
            'reference' => 'FRACTIONAL-NEED',
        ])->assertRedirect();
        $this->assertSame('0.1250', $need->fresh()->inventoryOrder->items()->sole()->qty);
    }

    public function test_needs_index_and_show_pages_load_for_admin(): void
    {
        $user = $this->verifiedAdmin();
        $item = $this->createItem($user);
        $warehouse = $this->createWarehouse($user);
        $department = $this->createDepartment();
        $need = InventoryNeed::query()->create([
            'reference' => 'NEED-INDEX-001',
            'department_id' => $department->id,
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
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

        $showResponse = $this->actingAs($user)->get(route('vap-inventory.needs.show', $need));
        $showResponse->assertOk();

        $showPage = $showResponse->viewData('page');
        $this->assertSame('Solicitadas', data_get($showPage, 'props.charts.quantity_scope.labels.0'));
        $this->assertSame(1, data_get($showPage, 'props.charts.quantity_scope.series.0'));
        $this->assertSame('Itens', data_get($showPage, 'props.charts.governance_pulse.labels.0'));
    }

    public function test_need_rejection_sends_workflow_notification_to_requester(): void
    {
        $user = $this->verifiedAdmin();
        $department = $this->createDepartment();
        $item = $this->createItem($user);
        $warehouse = $this->createWarehouse($user);

        $need = InventoryNeed::query()->create([
            'reference' => 'NEED-REJECT-001',
            'department_id' => $department->id,
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
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
            OperationalNotification::class,
            function (OperationalNotification $notification) use ($need, $user): bool {
                $payload = $notification->payload;

                return $payload['key'] === 'inventory.need.updated'
                    && $payload['context']['need_reference'] === $need->reference
                    && $payload['context']['status'] === 'rejeitada'
                    && in_array('database', $notification->via($user), true)
                    && str_contains($payload['message'], 'indisponibilidade orçamental');
            }
        );
    }

    public function test_need_pdf_export_returns_a_pdf_response(): void
    {
        $user = $this->verifiedAdmin();
        $department = $this->createDepartment();
        $item = $this->createItem($user);
        $warehouse = $this->createWarehouse($user);

        $need = InventoryNeed::query()->create([
            'reference' => 'NEED-PDF-001',
            'department_id' => $department->id,
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
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
        $this->assertStringStartsWith('%PDF-', (string) $response->baseResponse->getContent());
    }

    public function test_need_submission_rejects_labs_from_another_department(): void
    {
        $user = $this->verifiedAdmin();
        $primaryDepartment = $this->createDepartment();
        $secondaryDepartment = $this->createDepartment();
        $lab = VAPLab::query()->create([
            'name' => 'Laboratório de departamento divergente',
            'code' => fake()->unique()->bothify('LAB-######'),
            'department_id' => $secondaryDepartment->id,
        ]);
        $item = $this->createItem($user);
        $warehouse = $this->createWarehouse($user);

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

    public function test_peer_laboratory_needs_are_absent_from_operational_views_and_actions(): void
    {
        $user = $this->verifiedAdmin();
        $labId = (int) DB::table('lab_user')->where('user_id', $user->id)->value('lab_id');
        $peer = VAPLab::factory()->create();
        $department = $this->createDepartment();
        $local = InventoryNeed::query()->create([
            'reference' => 'NEED-LOCAL-'.fake()->unique()->numerify('######'),
            'department_id' => $department->id,
            'lab_id' => $labId,
            'requested_by_id' => $user->id,
            'status' => 'approved',
            'justification' => 'Local need',
        ]);
        $foreign = InventoryNeed::query()->create([
            'reference' => 'NEED-PEER-'.fake()->unique()->numerify('######'),
            'department_id' => $department->id,
            'lab_id' => $peer->id,
            'requested_by_id' => $user->id,
            'status' => 'approved',
            'justification' => 'Peer need',
        ]);

        $response = $this->actingAs($user)->get(route('vap-inventory.needs.index'));
        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertSame(1, data_get($page, 'props.needs.total'));
        $this->assertSame($local->id, data_get($page, 'props.needs.data.0.id'));
        $this->assertSame(1, data_get($page, 'props.stats.total'));
        $this->assertSame(1, data_get($page, 'props.stats.awaiting_order'));
        $this->assertSame(1, count(data_get($page, 'props.procurementQueue', [])));

        $this->get(route('vap-inventory.needs.show', $foreign))->assertNotFound();
        $this->get(route('vap-inventory.needs.pdf', $foreign))->assertNotFound();
        $this->post(route('vap-inventory.needs.approve', $foreign), [])->assertNotFound();
        $this->post(route('vap-inventory.needs.reject', $foreign), [])->assertNotFound();
        $this->post(route('vap-inventory.needs.convert-to-order', $foreign), [])->assertNotFound();
        $this->assertSame('approved', $foreign->fresh()->status);
    }
}
