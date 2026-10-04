<?php

namespace Tests\Feature;

use App\Actions\SaveMaintenanceTask;
use App\Exports\MaintenanceTasksExport;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\ItemCategory;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaintenanceAuthoringTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    private InventoryItem $equipment;

    private MaintenanceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lab = VAPLab::factory()->create();
        $this->operator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        foreach (['view', 'add', 'edit', 'delete', 'export'] as $ability) {
            $this->operator->givePermissionTo(Permission::findOrCreate($ability.'_maintenance_tasks', 'web'));
        }
        $equipmentCategory = ItemCategory::create(['name' => 'Equipment', 'inventory_type' => 'equipment']);
        $this->equipment = InventoryItem::create(['lab_id' => $this->lab->id, 'category_id' => $equipmentCategory->id, 'name' => 'Primary calibration equipment']);
        $this->category = MaintenanceCategory::create(['name' => 'Internal calibration', 'code' => 'CAL_INT']);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'name' => 'Annual calibration', 'equipment_id' => $this->equipment->id,
            'category_id' => $this->category->id, 'due_date' => '2026-10-01',
            'periodicity' => 12, 'periodicity_unit' => 'months',
        ];
    }

    private function task(array $overrides = []): MaintenanceTask
    {
        return MaintenanceTask::create(array_replace($this->payload(), [
            'maintenance_task_year' => 2026, 'result' => 'Conforme', 'is_executed' => false,
            'is_planned' => true, 'executed_by_supplier' => false,
        ], $overrides))->refresh();
    }

    public function test_creation_assigns_identity_and_derives_the_schedule(): void
    {
        $this->post(route('vap-maintenance.tasks.store'), $this->payload())
            ->assertRedirect(route('vap-maintenance.tasks'));
        $task = MaintenanceTask::where('equipment_id', $this->equipment->id)->firstOrFail();
        $this->assertStringStartsWith('CAL_INT '.now()->year.'/', $task->maintenance_task_no);
        $this->assertSame('2027-10-01', $task->next_date->toDateString());
        $this->assertFalse($task->is_executed);
        $this->assertFalse($task->executed_by_supplier);
    }

    public function test_materials_cannot_be_selected_or_submitted_as_maintenance_equipment(): void
    {
        $material = InventoryItem::create([
            'lab_id' => $this->lab->id, 'name' => 'Reagent, not equipment',
            'category_id' => ItemCategory::create(['name' => 'Materials', 'inventory_type' => 'material'])->id,
        ]);
        $this->get(route('vap-maintenance.tasks.create'))->assertInertia(fn (Assert $page) => $page
            ->has('equipment', 1)->where('equipment.0.id', $this->equipment->id));
        $this->post(route('vap-maintenance.tasks.store'), array_replace($this->payload(), ['equipment_id' => $material->id]))
            ->assertSessionHasErrors('equipment_id');
        $task = $this->task();
        $this->put(route('vap-maintenance.tasks.update', $task), ['equipment_id' => $material->id])->assertSessionHasErrors('equipment_id');
        $this->assertSame($this->equipment->id, $task->fresh()->equipment_id);
        $legacy = $this->task(['equipment_id' => $material->id]);
        $this->postJson(route('vap-maintenance.tasks.bulk-update'), ['task_ids' => [$legacy->id], 'action' => 'mark_executed'])
            ->assertNotFound();
        $this->assertFalse($legacy->fresh()->is_executed);
    }

    public function test_individual_completion_uses_existing_result_and_is_retry_safe(): void
    {
        $task = $this->task(['result' => '0']);
        $this->put(route('vap-maintenance.tasks.update', $task), ['is_executed' => true])->assertRedirect();
        $task->refresh();
        $this->assertTrue($task->is_executed);
        $this->assertSame('0', $task->result);
        $this->assertSame('2026-10-01', $task->previous_date->toDateString());
        $this->assertSame('2027-10-01', $task->due_date->toDateString());
        $before = $task->getRawOriginal();
        $equipmentBefore = $this->equipment->fresh()->getRawOriginal();
        $this->travel(1)->days();
        $this->put(route('vap-maintenance.tasks.update', $task), ['is_executed' => true])->assertRedirect();
        $this->assertSame($before, $task->fresh()->getRawOriginal());
        $this->assertSame($equipmentBefore, $this->equipment->fresh()->getRawOriginal());
    }

    public function test_completion_updates_the_newly_selected_equipment(): void
    {
        $task = $this->task();
        $replacement = InventoryItem::create(['lab_id' => $this->lab->id, 'category_id' => $this->equipment->category_id, 'name' => 'Replacement equipment']);
        $this->put(route('vap-maintenance.tasks.update', $task), [
            'equipment_id' => $replacement->id, 'category_id' => $this->category->id,
            'due_date' => '2026-10-05', 'is_executed' => true,
        ])->assertRedirect();
        $this->assertNull($this->equipment->fresh()->last_calibration_date);
        $this->assertSame('2026-10-05', $replacement->fresh()->last_calibration_date->toDateString());
        $this->assertSame('2027-10-05', $replacement->fresh()->next_calibration_date->toDateString());
        $this->assertSame($replacement->id, $task->fresh()->equipment_id);
    }

    public function test_completed_creation_updates_calibration_in_the_same_transaction(): void
    {
        $this->post(route('vap-maintenance.tasks.store'), $this->payload() + [
            'is_executed' => true, 'result' => 'Measurements recorded',
        ])->assertRedirect();
        $task = MaintenanceTask::where('equipment_id', $this->equipment->id)->firstOrFail();
        $this->assertTrue($task->is_executed);
        $this->assertSame('2027-10-01', $this->equipment->fresh()->next_calibration_date->toDateString());
    }

    public function test_completion_failure_rolls_back_metadata_reassignment_and_dates(): void
    {
        $task = $this->task();
        $replacement = InventoryItem::create(['lab_id' => $this->lab->id, 'category_id' => $this->equipment->category_id, 'name' => 'Replacement equipment']);
        $before = $task->getRawOriginal();
        $event = 'eloquent.updating: '.InventoryItem::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (): bool => false);
        try {
            $this->put(route('vap-maintenance.tasks.update', $task), [
                'equipment_id' => $replacement->id, 'result' => 'Revised measurements', 'is_executed' => true,
            ])->assertStatus(409);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
        $this->assertSame($before, $task->fresh()->getRawOriginal());
        $this->assertNull($replacement->fresh()->last_calibration_date);
        $this->assertNull($this->equipment->fresh()->last_calibration_date);
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_input_cannot_partially_change_the_task(array $invalid, string $field): void
    {
        $task = $this->task();
        $before = $task->getRawOriginal();
        $this->put(route('vap-maintenance.tasks.update', $task), $invalid)
            ->assertSessionHasErrors($field);
        $this->assertSame($before, $task->fresh()->getRawOriginal());
    }

    public static function invalidFields(): array
    {
        return [
            'blank result' => [['result' => '  ', 'is_executed' => true], 'result'],
            'forged number' => [['maintenance_task_no' => 'MANUAL-001'], 'maintenance_task_no'],
            'forged year' => [['maintenance_task_year' => 1999], 'maintenance_task_year'],
            'forged sequence' => [['seq' => 999], 'seq'],
            'forged previous date' => [['previous_date' => '2020-01-01'], 'previous_date'],
            'forged next date' => [['next_date' => '2050-01-01'], 'next_date'],
            'forged owner' => [['lab_id' => 1], 'lab_id'],
            'invalid boolean' => [['is_executed' => 'not-a-boolean'], 'is_executed'],
            'missing period unit' => [['periodicity_unit' => null], 'periodicity_unit'],
            'missing period amount' => [['periodicity' => null], 'periodicity'],
            'supplier missing' => [['executed_by_supplier' => true], 'supplier_id'],
        ];
    }

    public function test_schedule_changes_recompute_next_date_and_can_clear_recurrence(): void
    {
        $task = $this->task();
        $this->put(route('vap-maintenance.tasks.update', $task), ['due_date' => '2026-11-10'])->assertRedirect();
        $this->assertSame('2027-11-10', $task->fresh()->next_date->toDateString());
        $this->put(route('vap-maintenance.tasks.update', $task), [
            'periodicity' => null, 'periodicity_unit' => null,
        ])->assertRedirect();
        $this->assertNull($task->fresh()->next_date);
    }

    public function test_peer_equipment_and_tasks_are_inaccessible(): void
    {
        $peer = VAPLab::factory()->create();
        $equipment = InventoryItem::create(['lab_id' => $peer->id, 'name' => 'Peer equipment']);
        $task = $this->task(['equipment_id' => $equipment->id]);
        $this->get(route('vap-maintenance.tasks.show', $task))->assertNotFound();
        $this->get(route('vap-maintenance.tasks.edit', $task))->assertNotFound();
        $this->get(route('vap-maintenance.export', ['type' => 'tasks', 'format' => 'csv', 'task_id' => $task->id]))->assertNotFound();
        $this->put(route('vap-maintenance.tasks.update', $task), ['name' => 'Attempt'])->assertNotFound();
        $this->post(route('vap-maintenance.tasks.store'), array_replace($this->payload(), [
            'equipment_id' => $equipment->id,
        ]))->assertSessionHasErrors('equipment_id');
    }

    public function test_archived_references_cannot_be_used_for_authoring(): void
    {
        $supplier = InventoryItemSupplier::create(['name' => 'Archived supplier']);
        $supplier->delete();
        $this->post(route('vap-maintenance.tasks.store'), $this->payload() + [
            'executed_by_supplier' => true, 'supplier_id' => $supplier->id,
        ])->assertSessionHasErrors('supplier_id');
        $this->category->delete();
        $this->post(route('vap-maintenance.tasks.store'), $this->payload())->assertSessionHasErrors('category_id');
    }

    public function test_issued_sequence_category_cannot_be_replaced(): void
    {
        $task = $this->task();
        $category = MaintenanceCategory::create(['name' => 'Another category', 'code' => 'OTHER']);
        $this->put(route('vap-maintenance.tasks.update', $task), ['category_id' => $category->id])
            ->assertSessionHasErrors('category_id');
        $this->assertSame($this->category->id, $task->fresh()->category_id);
    }

    public function test_view_only_member_cannot_author_or_invoke_operational_endpoints(): void
    {
        $task = $this->task();
        $reader = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $reader->id]);
        $reader->givePermissionTo(Permission::findOrCreate('view_maintenance_tasks', 'web'));
        $this->actingAs($reader);
        $this->get(route('vap-maintenance.tasks'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('can.create', false)->where('can.edit', false)->where('can.delete', false)->where('can.export', false));
        $this->get(route('vap-maintenance.tasks.create'))->assertForbidden();
        $this->get(route('vap-maintenance.tasks.edit', $task))->assertForbidden();
        $this->post(route('vap-maintenance.tasks.store'), $this->payload())->assertForbidden();
        $this->put(route('vap-maintenance.tasks.update', $task), ['obs' => 'Attempt'])->assertForbidden();
        $this->delete(route('vap-maintenance.tasks.destroy', $task))->assertForbidden();
        $this->get(route('vap-maintenance.export', ['type' => 'tasks', 'format' => 'csv']))->assertForbidden();
        $this->post(route('vap-maintenance.tasks.notify-completion', $task))->assertForbidden();
        $this->post(route('vap-maintenance.notifications.send'))->assertForbidden();
        $this->get(route('vap-maintenance.categories'))->assertForbidden();
    }

    public function test_edit_form_and_single_task_export_resolve_only_the_selected_record(): void
    {
        $task = $this->task();
        $this->task(['name' => 'Another task']);
        $this->get(route('vap-maintenance.tasks.edit', $task))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('VAPMaintenance/Tasks/Create')->where('task.id', $task->id)
            ->where('task.due_date', '2026-10-01')->where('today', today()->toDateString()));
        $export = new MaintenanceTasksExport($this->lab->id, ['task_id' => $task->id]);
        $this->assertSame([$task->id], $export->collection()->modelKeys());
    }

    public function test_notes_edit_preserves_date_only_schedule_and_completion_state(): void
    {
        $task = $this->task([
            'is_executed' => true, 'previous_date' => '2026-10-01',
            'due_date' => '2027-10-01', 'next_date' => '2028-10-01',
        ]);
        $this->get(route('vap-maintenance.tasks.edit', $task))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('task.due_date', '2027-10-01'));
        $this->put(route('vap-maintenance.tasks.update', $task), ['obs' => 'Metadata correction', 'due_date' => '2027-10-01'])
            ->assertRedirect();
        $task->refresh();
        $this->assertTrue($task->is_executed);
        $this->assertSame('2026-10-01', $task->previous_date->toDateString());
        $this->assertSame('2027-10-01', $task->due_date->toDateString());
        $this->assertSame('2028-10-01', $task->next_date->toDateString());
    }

    public function test_unfinished_work_cannot_emit_a_completion_notification(): void
    {
        $task = $this->task();
        $this->postJson(route('vap-maintenance.tasks.notify-completion', $task))
            ->assertUnprocessable()->assertJsonValidationErrors('task');
        $this->assertFalse($task->fresh()->is_executed);
    }

    public function test_equipment_history_totals_include_rows_outside_the_preview(): void
    {
        for ($index = 0; $index < 51; $index++) {
            $this->task(['cost' => 2]);
        }
        $this->task(['is_executed' => true, 'cost' => 10, 'previous_date' => '2026-10-01', 'due_date' => '2027-10-01']);
        $peer = VAPLab::factory()->create();
        $peerEquipment = InventoryItem::create(['lab_id' => $peer->id, 'name' => 'Private peer equipment']);
        $this->task(['equipment_id' => $peerEquipment->id, 'cost' => 1000]);

        $this->getJson(route('vap-maintenance.equipment.history', $this->equipment->id))->assertOk()
            ->assertJsonCount(50, 'tasks')->assertJsonPath('stats.total_tasks', 52)
            ->assertJsonPath('stats.executed_tasks', 1)->assertJsonPath('stats.total_cost', 112)
            ->assertJsonPath('stats.last_maintenance', '2026-10-01');
        $this->getJson(route('vap-maintenance.equipment.history', $peerEquipment->id))->assertNotFound();
    }

    public function test_action_rechecks_current_membership_even_with_a_previously_authorized_request(): void
    {
        $task = $this->task();
        DB::table('lab_user')->where('user_id', $this->operator->id)->delete();
        try {
            app(SaveMaintenanceTask::class)->execute($this->operator->id, $this->lab->id, ['obs' => 'Attempt'], $task->id);
            $this->fail('Revoked membership must not authorize a write.');
        } catch (AuthorizationException) {
            $this->assertNull($task->fresh()->obs);
        }
    }

    public function test_impersonation_cannot_mutate_maintenance(): void
    {
        $task = $this->task();
        $this->withSession(['impersonate' => $this->operator->id])
            ->put(route('vap-maintenance.tasks.update', $task), ['obs' => 'Attempt'])->assertForbidden();
        $this->assertNull($task->fresh()->obs);
    }
}
