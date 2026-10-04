<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\ItemCategory;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceModuleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bulk_completion_requires_existing_results_and_is_atomic(): void
    {
        $user = $this->verifiedAdmin();
        [$equipment, $first] = $this->completionFixture($user);
        $second = $first->replicate(['seq', 'maintenance_task_no']);
        $second->result = '   ';
        $second->save();
        $this->actingAs($user)->postJson(route('vap-maintenance.tasks.bulk-update'), [
            'task_ids' => [$first->id, $second->id], 'action' => 'mark_executed',
        ])->assertUnprocessable()->assertJsonValidationErrors('task_ids');
        $this->assertFalse($first->fresh()->is_executed);
        $this->assertFalse($second->fresh()->is_executed);
        $this->assertSame('2026-10-01', $first->fresh()->due_date->toDateString());
        $this->assertNull($equipment->fresh()->last_calibration_date);
    }

    public function test_bulk_completion_preserves_results_and_retries_do_not_advance_dates(): void
    {
        $user = $this->verifiedAdmin();
        [$equipment, $task] = $this->completionFixture($user);
        $data = ['task_ids' => [$task->id], 'action' => 'mark_executed'];
        $this->actingAs($user)->postJson(route('vap-maintenance.tasks.bulk-update'), $data)->assertOk();
        $task->refresh();
        $equipment->refresh();
        $this->assertTrue($task->is_executed);
        $this->assertSame('Conforme: medições registadas.', $task->result);
        $this->assertSame('2026-10-01', $task->previous_date->toDateString());
        $this->assertSame('2027-10-01', $task->due_date->toDateString());
        $this->assertSame('2028-10-01', $task->next_date->toDateString());
        $this->assertSame('2026-10-01', $equipment->last_calibration_date->toDateString());
        $this->assertSame('2027-10-01', $equipment->next_calibration_date->toDateString());
        $before = $task->getRawOriginal();
        $equipmentBefore = $equipment->getRawOriginal();
        $this->travel(1)->days();
        $this->postJson(route('vap-maintenance.tasks.bulk-update'), $data)->assertOk();
        $this->assertSame($before, $task->fresh()->getRawOriginal());
        $this->assertSame($equipmentBefore, $equipment->fresh()->getRawOriginal());
    }

    public function test_bulk_completion_rolls_back_tasks_when_equipment_write_is_vetoed(): void
    {
        $user = $this->verifiedAdmin();
        [$equipment, $task] = $this->completionFixture($user);
        $event = 'eloquent.updating: '.InventoryItem::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (): bool => false);
        try {
            $this->actingAs($user)->postJson(route('vap-maintenance.tasks.bulk-update'), [
                'task_ids' => [$task->id], 'action' => 'mark_executed',
            ])->assertStatus(409);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
        $this->assertFalse($task->fresh()->is_executed);
        $this->assertSame('2026-10-01', $task->fresh()->due_date->toDateString());
        $this->assertNull($equipment->fresh()->last_calibration_date);
    }

    public function test_partial_task_update_preserves_absent_boolean_fields(): void
    {
        $user = $this->verifiedAdmin();
        [, $task] = $this->completionFixture($user);
        $task->update(['is_executed' => true, 'is_planned' => true, 'executed_by_supplier' => true]);
        $this->actingAs($user)->put(route('vap-maintenance.tasks.update', $task), ['name' => 'Corrected name'])->assertRedirect();
        $task->refresh();
        $this->assertTrue($task->is_executed);
        $this->assertTrue($task->is_planned);
        $this->assertTrue($task->executed_by_supplier);
        $this->assertSame('Conforme: medições registadas.', $task->result);
        $this->assertSame('2026-10-01', $task->due_date->toDateString());
    }

    public function test_view_only_member_cannot_complete_tasks_in_bulk(): void
    {
        $admin = $this->verifiedAdmin();
        [$equipment, $task] = $this->completionFixture($admin);
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->givePermissionTo(Permission::findOrCreate('view_maintenance_tasks', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $equipment->lab_id, 'user_id' => $user->id]);
        $this->actingAs($user)->postJson(route('vap-maintenance.tasks.bulk-update'), [
            'task_ids' => [$task->id], 'action' => 'mark_executed',
        ])->assertForbidden();
        $this->assertFalse($task->fresh()->is_executed);
    }

    /** @return array{InventoryItem, MaintenanceTask} */
    private function completionFixture(User $user): array
    {
        $equipment = InventoryItem::create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'), 'name' => 'Calibration equipment',
            'category_id' => ItemCategory::create(['name' => 'Equipment', 'inventory_type' => 'equipment'])->id,
        ]);
        $category = MaintenanceCategory::create(['name' => 'Internal calibration', 'code' => 'CAL_INT']);
        $supplier = InventoryItemSupplier::create(['name' => 'Calibration supplier']);
        $task = MaintenanceTask::create([
            'name' => 'Annual calibration', 'equipment_id' => $equipment->id, 'category_id' => $category->id,
            'supplier_id' => $supplier->id, 'due_date' => '2026-10-01', 'periodicity' => 12,
            'periodicity_unit' => 'months', 'maintenance_task_year' => 2026,
            'result' => 'Conforme: medições registadas.', 'is_executed' => false,
        ]);

        return [$equipment, $task];
    }

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $admin->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $admin;
    }

    public function test_admin_can_create_and_filter_maintenance_tasks(): void
    {
        $user = $this->verifiedAdmin();
        $equipment = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Maintenance equipment',
            'category_id' => ItemCategory::create(['name' => 'Equipment', 'inventory_type' => 'equipment'])->id,
        ]);
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Maintenance supplier']);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Calibração periódica',
            'code' => 'CAL-'.uniqid(),
            'description' => 'Categoria criada durante o teste.',
        ]);

        $this->actingAs($user)->post(route('vap-maintenance.tasks.store'), [
            'name' => 'Calibração semestral do equipamento',
            'category_id' => $category->id,
            'equipment_id' => $equipment->id,
            'supplier_id' => $supplier->id,
            'due_date' => now()->addDays(15)->toDateString(),
            'periodicity' => 6,
            'periodicity_unit' => 'months',
            'cost' => 1450.50,
            'executed_by_supplier' => true,
            'is_planned' => true,
            'acceptance_criteria' => 'Dentro dos limites definidos.',
        ])->assertRedirect(route('vap-maintenance.tasks'));

        /** @var MaintenanceTask $task */
        $task = MaintenanceTask::query()->latest('id')->firstOrFail();

        $this->assertSame($supplier->id, $task->supplier_id);
        $this->assertNotNull($task->next_date);

        $this->actingAs($user)->get(route('vap-maintenance.tasks', [
            'supplier_id' => $supplier->id,
            'cost_min' => 1000,
            'cost_max' => 2000,
        ]))
            ->assertOk();

        $this->assertSame(1, MaintenanceTask::query()
            ->where('supplier_id', $supplier->id)
            ->whereBetween('cost', [1000, 2000])
            ->count());
    }

    public function test_executing_calibration_requires_result_and_updates_equipment_dates(): void
    {
        $user = $this->verifiedAdmin();
        $equipment = InventoryItem::query()->create([
            'lab_id' => DB::table('lab_user')->where('user_id', $user->id)->value('lab_id'),
            'name' => 'Calibration equipment',
            'category_id' => ItemCategory::create(['name' => 'Equipment', 'inventory_type' => 'equipment'])->id,
        ]);
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Calibration supplier']);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Calibração interna',
            'code' => 'CAL_INT',
            'description' => 'Categoria de calibração interna para teste.',
        ]);

        $task = MaintenanceTask::query()->create([
            'name' => 'Calibração interna do equipamento',
            'category_id' => $category->id,
            'equipment_id' => $equipment->id,
            'supplier_id' => $supplier->id,
            'due_date' => now()->toDateString(),
            'periodicity' => 12,
            'periodicity_unit' => 'months',
            'maintenance_task_year' => now()->format('Y'),
            'is_planned' => true,
        ]);

        $this->actingAs($user)->put(route('vap-maintenance.tasks.update', $task), [
            'is_executed' => true,
        ])->assertSessionHasErrors('result');

        $this->actingAs($user)->put(route('vap-maintenance.tasks.update', $task), [
            'is_executed' => true,
            'result' => 'Equipamento conforme e apto para uso.',
        ])->assertRedirect(route('vap-maintenance.tasks.show', $task));

        $task->refresh();
        $equipment->refresh();

        $this->assertTrue($task->is_executed);
        $this->assertNotNull($task->previous_date);
        $this->assertNotNull($task->next_date);
        $this->assertSame($task->previous_date?->toDateString(), $equipment->last_calibration_date?->toDateString());
        $this->assertSame($task->due_date?->toDateString(), $equipment->next_calibration_date?->toDateString());
    }

    public function test_maintenance_tasks_follow_equipment_ownership_and_bulk_actions_fail_closed(): void
    {
        $user = $this->verifiedAdmin();
        $labId = (int) DB::table('lab_user')->where('user_id', $user->id)->value('lab_id');
        $peer = VAPLab::factory()->create();
        $category = MaintenanceCategory::query()->create(['name' => 'Owned calibration', 'code' => 'OWN-CAL']);
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Owned supplier']);
        $localEquipment = InventoryItem::query()->create(['lab_id' => $labId, 'name' => 'Local equipment']);
        $peerEquipment = InventoryItem::query()->create(['lab_id' => $peer->id, 'name' => 'Peer equipment']);
        $localTask = MaintenanceTask::query()->create([
            'name' => 'Local task', 'category_id' => $category->id, 'equipment_id' => $localEquipment->id,
            'supplier_id' => $supplier->id, 'due_date' => today()->toDateString(),
            'maintenance_task_year' => today()->format('Y'),
        ]);
        $peerTask = MaintenanceTask::query()->create([
            'name' => 'Peer task', 'category_id' => $category->id, 'equipment_id' => $peerEquipment->id,
            'supplier_id' => $supplier->id, 'due_date' => today()->toDateString(),
            'maintenance_task_year' => today()->format('Y'),
        ]);

        $this->actingAs($user)->get(route('vap-maintenance.tasks'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPMaintenance/Tasks/Index')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.id', $localTask->id));
        $this->get(route('vap-maintenance.tasks.show', $peerTask))->assertNotFound();
        $this->put(route('vap-maintenance.tasks.update', $peerTask), [
            'name' => 'Changed',
        ])->assertNotFound();
        $this->postJson(route('vap-maintenance.tasks.bulk-update'), [
            'task_ids' => [$localTask->id, $peerTask->id], 'action' => 'delete',
        ])->assertNotFound();
        $this->post(route('vap-maintenance.tasks.store'), [
            'name' => 'Foreign equipment task', 'category_id' => $category->id,
            'equipment_id' => $peerEquipment->id, 'due_date' => today()->toDateString(),
        ])->assertSessionHasErrors('equipment_id');
        $this->assertFalse($localTask->fresh()->trashed());
        $this->assertFalse($peerTask->fresh()->trashed());
    }
}
