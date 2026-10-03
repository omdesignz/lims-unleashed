<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceModuleTest extends TestCase
{
    use DatabaseTransactions;

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
