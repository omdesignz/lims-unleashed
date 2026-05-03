<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MaintenanceModuleTest extends TestCase
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

    public function test_admin_can_create_and_filter_maintenance_tasks(): void
    {
        $user = $this->verifiedAdmin();
        $equipment = InventoryItem::query()->firstOrFail();
        $supplier = InventoryItemSupplier::query()->firstOrFail();
        $category = MaintenanceCategory::query()->create([
            'name' => 'Calibração periódica',
            'code' => 'CAL-' . uniqid(),
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
        $equipment = InventoryItem::query()->firstOrFail();
        $category = MaintenanceCategory::query()->create([
            'name' => 'Calibração interna',
            'code' => 'CAL_INT',
            'description' => 'Categoria de calibração interna para teste.',
        ]);

        $task = MaintenanceTask::query()->create([
            'name' => 'Calibração interna do equipamento',
            'category_id' => $category->id,
            'equipment_id' => $equipment->id,
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
}
