<?php

namespace Tests\Feature;

use App\Actions\UpdateMaintenanceTaskLifecycle;
use App\Http\Resources\MaintenanceTaskResource;
use App\Models\InventoryItem;
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

class MaintenanceTaskLifecycleTest extends TestCase
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
        foreach (['view', 'edit', 'delete', 'restore'] as $ability) {
            $this->operator->givePermissionTo(Permission::findOrCreate($ability.'_maintenance_tasks', 'web'));
        }
        $equipmentCategory = ItemCategory::create(['name' => 'Equipment', 'inventory_type' => 'equipment']);
        $this->equipment = InventoryItem::create(['lab_id' => $this->lab->id, 'category_id' => $equipmentCategory->id, 'name' => 'Lifecycle equipment']);
        $this->category = MaintenanceCategory::create(['name' => 'Maintenance', 'code' => 'LIFECYCLE']);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function task(array $overrides = []): MaintenanceTask
    {
        return MaintenanceTask::create(array_replace([
            'name' => 'Monthly check', 'equipment_id' => $this->equipment->id,
            'category_id' => $this->category->id, 'due_date' => '2026-10-01',
            'next_date' => '2026-11-01', 'periodicity' => 1, 'periodicity_unit' => 'months',
            'maintenance_task_year' => 2026, 'is_executed' => false,
            'is_planned' => true, 'executed_by_supplier' => false, 'result' => '0',
        ], $overrides))->refresh();
    }

    public function test_rescheduling_recomputes_recurrence_and_preserves_evidence(): void
    {
        $task = $this->task(['previous_date' => '2026-09-01']);
        $number = $task->maintenance_task_no;
        $this->postJson(route('vap-maintenance.tasks.bulk-update'), [
            'action' => 'reschedule', 'task_ids' => [$task->id], 'new_date' => '2026-12-02',
        ])->assertOk()->assertJsonPath('success', true);
        $task->refresh();
        $this->assertSame('2026-12-02', $task->due_date->toDateString());
        $this->assertSame('2027-01-02', $task->next_date->toDateString());
        $this->assertSame('2026-09-01', $task->previous_date->toDateString());
        $this->assertSame('0', $task->result);
        $this->assertSame($number, $task->maintenance_task_no);
    }

    public function test_single_archive_and_restore_are_retry_safe_and_preserve_evidence(): void
    {
        $task = $this->task(['is_executed' => true, 'previous_date' => '2026-09-01']);
        $before = $task->getRawOriginal();
        $this->delete(route('vap-maintenance.tasks.destroy', $task))->assertRedirect();
        $archived = $task->fresh()->getRawOriginal();
        $this->travel(1)->days();
        $this->delete(route('vap-maintenance.tasks.destroy', $task))->assertRedirect();
        $this->assertSame($archived, $task->fresh()->getRawOriginal());
        $this->post(route('vap-maintenance.tasks.restore', $task))->assertRedirect();
        $restored = $task->fresh()->getRawOriginal();
        $this->travel(1)->days();
        $this->post(route('vap-maintenance.tasks.restore', $task))->assertRedirect();
        $this->assertSame($restored, $task->fresh()->getRawOriginal());
        unset($before['updated_at'], $restored['updated_at']);
        $this->assertSame($before, $restored);
    }

    #[DataProvider('actions')]
    public function test_mixed_laboratory_batches_never_partially_mutate(string $action): void
    {
        $local = $this->task();
        $peerEquipment = InventoryItem::create(['lab_id' => VAPLab::factory()->create()->id, 'name' => 'Peer equipment']);
        $peer = $this->task(['equipment_id' => $peerEquipment->id]);
        if ($action === 'restore') {
            $local->delete();
            $peer->delete();
        }
        $before = $local->fresh()->getRawOriginal();
        $this->postJson(route('vap-maintenance.tasks.bulk-update'), [
            'action' => $action, 'task_ids' => [$local->id, $peer->id], 'new_date' => '2026-12-02',
        ])->assertNotFound();
        $this->assertSame($before, $local->fresh()->getRawOriginal());
    }

    #[DataProvider('actions')]
    public function test_write_veto_rolls_back_every_task(string $action): void
    {
        $first = $this->task();
        $second = $this->task();
        if ($action === 'restore') {
            $first->delete();
            $second->delete();
        }
        $before = [$first->fresh()->getRawOriginal(), $second->fresh()->getRawOriginal()];
        $event = 'eloquent.'.match ($action) {
            'delete' => 'deleting', 'restore' => 'restoring', default => 'updating'
        }.': '.MaintenanceTask::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (MaintenanceTask $task): ?bool => $task->id === $second->id ? false : null);
        try {
            $this->postJson(route('vap-maintenance.tasks.bulk-update'), [
                'action' => $action, 'task_ids' => [$second->id, $first->id], 'new_date' => '2026-12-02',
            ])->assertStatus(409);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
        $this->assertSame($before, [$first->fresh()->getRawOriginal(), $second->fresh()->getRawOriginal()]);
    }

    #[DataProvider('actions')]
    public function test_action_rechecks_revoked_membership(string $action): void
    {
        $task = $this->task();
        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->delete();
        $this->expectException(AuthorizationException::class);
        app(UpdateMaintenanceTaskLifecycle::class)->execute($this->operator->id, $this->lab->id, [$task->id], $action, '2026-12-02');
    }

    public static function actions(): array
    {
        return [['reschedule'], ['delete'], ['restore']];
    }

    public function test_archive_and_restore_use_separate_abilities_and_reject_impersonation(): void
    {
        $task = $this->task();
        $this->operator->revokePermissionTo('delete_maintenance_tasks');
        $this->delete(route('vap-maintenance.tasks.destroy', $task))->assertForbidden();
        $this->postJson(route('vap-maintenance.tasks.bulk-update'), ['action' => 'delete', 'task_ids' => [$task->id]])->assertForbidden();
        $task->delete();
        $this->operator->revokePermissionTo('restore_maintenance_tasks');
        $this->post(route('vap-maintenance.tasks.restore', $task))->assertForbidden();
        $this->postJson(route('vap-maintenance.tasks.bulk-update'), ['action' => 'restore', 'task_ids' => [$task->id]])->assertForbidden();
        $this->operator->givePermissionTo('delete_maintenance_tasks', 'restore_maintenance_tasks');
        $this->withSession(['impersonate' => $this->operator->id]);
        $this->delete(route('vap-maintenance.tasks.destroy', $task))->assertForbidden();
        $this->post(route('vap-maintenance.tasks.restore', $task))->assertForbidden();
    }

    public function test_archived_list_is_lab_scoped_and_search_cannot_escape_the_scope(): void
    {
        $active = $this->task(['name' => 'Active task']);
        $archived = $this->task(['name' => 'Visible archived task']);
        $archived->delete();
        $peerEquipment = InventoryItem::create(['lab_id' => VAPLab::factory()->create()->id, 'name' => 'Peer equipment']);
        $peer = $this->task(['name' => 'Peer archived task', 'equipment_id' => $peerEquipment->id]);
        $peer->delete();
        $this->get(route('vap-maintenance.tasks', ['archived' => 1]))->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 1)->where('tasks.data.0.id', $archived->id)->where('can.restore', true));
        $this->get(route('vap-maintenance.tasks'))->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 1)->where('tasks.data.0.id', $active->id));
        $this->get(route('vap-maintenance.tasks', ['archived' => 1, 'search' => $peer->maintenance_task_no]))
            ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 0));
    }

    public function test_invalid_bulk_input_does_not_change_records(): void
    {
        $task = $this->task();
        foreach ([
            [['task_ids' => [$task->id, $task->id]], 'task_ids.0'],
            [['task_ids' => []], 'task_ids'],
            [['new_date' => 'invalid'], 'new_date'],
            [['new_date' => '2026-12-02T23:00:00Z'], 'new_date'],
            [['send_notification' => true], 'send_notification'],
        ] as [$invalid, $field]) {
            $this->postJson(route('vap-maintenance.tasks.bulk-update'), array_replace([
                'action' => 'reschedule', 'task_ids' => [$task->id], 'new_date' => '2026-12-02',
            ], $invalid))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertSame('2026-10-01', $task->fresh()->due_date->toDateString());
    }

    public function test_listing_rejects_invalid_sort_and_unbounded_page_size(): void
    {
        $this->getJson(route('vap-maintenance.tasks', ['sort_by' => 'private_column', 'per_page' => 100000]))
            ->assertUnprocessable()->assertJsonValidationErrors(['sort_by', 'per_page']);
        $this->get(route('vap-maintenance.tasks', ['date_to' => '2026-12-31']))->assertOk();
    }

    public function test_archived_task_cannot_be_rescheduled(): void
    {
        $task = $this->task();
        $task->delete();
        $this->postJson(route('vap-maintenance.tasks.bulk-update'), [
            'action' => 'reschedule', 'task_ids' => [$task->id], 'new_date' => '2026-12-02',
        ])->assertNotFound();
        $this->assertSame('2026-10-01', $task->fresh()->due_date->toDateString());
    }

    public function test_legacy_reads_redirect_only_after_lab_and_ability_checks(): void
    {
        $task = $this->task();
        $this->get(route('maintenancetasks.index', ['filter' => 'trashed']))
            ->assertRedirect(route('vap-maintenance.tasks', ['archived' => 1]));
        $this->get(route('maintenancetasks.edit', $task))->assertRedirect(route('vap-maintenance.tasks.edit', $task));
        $this->get(route('maintenancetasks.show', $task))->assertRedirect(route('vap-maintenance.tasks.show', $task));
        $peerEquipment = InventoryItem::create(['lab_id' => VAPLab::factory()->create()->id, 'name' => 'Peer equipment']);
        $peer = $this->task(['equipment_id' => $peerEquipment->id]);
        $this->get(route('maintenancetasks.edit', $peer))->assertNotFound();
        $this->get(route('maintenancetasks.show', $peer))->assertNotFound();
        $this->operator->revokePermissionTo('view_maintenance_tasks');
        $this->get(route('maintenancetasks.show', $task))->assertForbidden();
    }

    public function test_legacy_write_and_get_mutation_paths_are_retired(): void
    {
        $task = $this->task();
        $before = $task->getRawOriginal();
        $this->post('/maintenancetasks', ['name' => 'Bypass'])->assertStatus(405);
        $this->put('/maintenancetasks/'.$task->id, ['result' => 'Bypass'])->assertNotFound();
        $this->get('/maintenancetasks/destroy?recordIds[]='.$task->id)->assertNotFound();
        $this->get('/maintenancetasks/restore?recordIds[]='.$task->id)->assertNotFound();
        $this->assertSame($before, $task->fresh()->getRawOriginal());
    }

    public function test_embedded_equipment_history_links_to_canonical_workflows_and_handles_internal_work(): void
    {
        $task = $this->task(['supplier_id' => null])->load(['equipment', 'category', 'supplier']);
        $resource = (new MaintenanceTaskResource($task))->resolve();
        $this->assertNull($resource['supplier']);
        $this->assertSame($this->equipment->name, $resource['equipment']);
        $this->assertSame(route('vap-maintenance.tasks.edit', $task), $resource['links']['edit_path']);
        $this->assertFalse($resource['action_capabilities']['delete']);
        $this->assertFalse($resource['action_capabilities']['restore']);
        $this->assertArrayNotHasKey('delete_path', $resource['links']);
    }
}
