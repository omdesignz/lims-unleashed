<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\InventorySupplierAssessment;
use App\Models\InventoryUnit;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\OperationalNotification;
use App\Support\SupplierAssessmentNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\TestCase;

class SupplierAssessmentLaboratoryBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_assessment_reads_and_mutations_stay_in_the_active_laboratory(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->member($lab);
        $supplier = $this->supplier();
        $localAssessment = $this->assessment($lab, $supplier, $user, 'high');
        $peerAssessment = $this->assessment($peer, $supplier, $user, 'critical');

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        $index = $this->get(route('supplier-assessments.index'));
        $index->assertOk();
        $this->assertCount(1, data_get($index->viewData('page'), 'props.assessments'));
        $this->assertSame($localAssessment->id, data_get($index->viewData('page'), 'props.assessments.0.id'));

        $dashboard = $this->get(route('qms.index'));
        $dashboard->assertOk();
        $this->assertSame(1, data_get($dashboard->viewData('page'), 'props.summary.suppliers_high_risk'));
        $this->assertSame($localAssessment->id, data_get($dashboard->viewData('page'), 'props.dueSupplierAssessments.0.id'));

        $this->put(route('supplier-assessments.update', $peerAssessment), [
            'inventory_item_supplier_id' => $supplier->id,
            'assessment_date' => now()->toDateString(),
            'status' => 'approved',
            'risk_level' => 'low',
        ])->assertNotFound();
        $this->delete(route('supplier-assessments.destroy', $peerAssessment))->assertNotFound();
        $this->assertDatabaseHas('inventory_supplier_assessments', ['id' => $peerAssessment->id, 'lab_id' => $peer->id, 'deleted_at' => null]);

        Notification::fake();
        $this->post(route('supplier-assessments.store'), [
            'lab_id' => $peer->id,
            'inventory_item_supplier_id' => $supplier->id,
            'assessment_date' => now()->toDateString(),
            'status' => 'approved',
            'risk_level' => 'low',
        ])->assertRedirect();

        $this->assertDatabaseHas('inventory_supplier_assessments', [
            'inventory_item_supplier_id' => $supplier->id,
            'lab_id' => $lab->id,
            'status' => 'approved',
        ]);
    }

    public function test_other_laboratory_assessments_do_not_block_local_procurement(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->member($lab);
        $supplier = $this->supplier();
        $this->assessment($peer, $supplier, $user, 'critical', 'suspended');

        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        $order = $this->get(route('vap-inventory.orders.create'));
        $order->assertOk();
        $orderSupplier = collect(data_get($order->viewData('page'), 'props.suppliers'))->firstWhere('id', $supplier->id);
        $this->assertNull($orderSupplier['latest_assessment'] ?? null);

        $need = $this->get(route('vap-inventory.needs.create'));
        $need->assertOk();
        $needSupplier = collect(data_get($need->viewData('page'), 'props.suppliers'))->firstWhere('id', $supplier->id);
        $this->assertNull($needSupplier['latest_assessment'] ?? null);

        $unit = InventoryUnit::query()->create([
            'code' => fake()->unique()->bothify('UN-######'),
            'description' => 'Unit',
        ]);
        $item = InventoryItem::query()->create([
            'lab_id' => $lab->id,
            'unit_id' => $unit->id,
            'name' => 'Local material',
            'code' => fake()->unique()->bothify('QA-######'),
        ]);
        $warehouse = InventoryItemWarehouse::query()->create([
            'lab_id' => $lab->id,
            'name' => 'Local warehouse',
        ]);

        $this->post(route('vap-inventory.orders.store'), [
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'status' => 'PENDING',
            'obs' => 'Foreign assessment must not block this laboratory.',
            'order_items' => [[
                'item_id' => $item->id,
                'qty' => 1,
                'warehouse_id' => $warehouse->id,
                'expected_date' => now()->addDays(7)->toDateString(),
                'unit_price' => 100,
                'status' => 'PENDING',
            ]],
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertSame(1, InventoryOrder::query()->where('lab_id', $lab->id)->where('supplier_id', $supplier->id)->count());
    }

    public function test_assessment_notifications_reach_only_owning_laboratory_members(): void
    {
        Notification::fake();
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $sender = $this->member($lab);
        $localRecipient = $this->member($lab);
        $peerRecipient = $this->member($peer);
        $assessment = $this->assessment($lab, $this->supplier(), $sender, 'critical');

        app(SupplierAssessmentNotifier::class)->notifySensitiveAssessment($assessment, $sender);

        Notification::assertSentTo($localRecipient, OperationalNotification::class);
        Notification::assertNotSentTo($peerRecipient, OperationalNotification::class);
    }

    public function test_assessment_owner_cannot_be_changed_after_creation(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $assessment = $this->assessment($lab, $this->supplier(), $this->member($lab), 'low');

        $this->expectException(LogicException::class);
        $assessment->update(['lab_id' => $peer->id]);
    }

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    private function supplier(): InventoryItemSupplier
    {
        return InventoryItemSupplier::query()->create([
            'name' => 'Supplier '.fake()->unique()->bothify('QA-######'),
            'currency' => 'AOA',
        ]);
    }

    private function assessment(VAPLab $lab, InventoryItemSupplier $supplier, User $assessor, string $riskLevel, string $status = 'conditional'): InventorySupplierAssessment
    {
        return InventorySupplierAssessment::query()->create([
            'lab_id' => $lab->id,
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $assessor->id,
            'assessment_date' => now()->subDay()->toDateString(),
            'next_review_at' => now()->addDays(7)->toDateString(),
            'status' => $status,
            'risk_level' => $riskLevel,
            'approved_supplier' => false,
            'is_active' => true,
        ]);
    }
}
