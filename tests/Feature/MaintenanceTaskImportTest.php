<?php

namespace Tests\Feature;

use App\Actions\ImportMaintenanceTasks;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\MaintenanceTaskImport;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\MaintenanceTaskCsv;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaintenanceTaskImportTest extends TestCase
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
        $this->operator->givePermissionTo(Permission::findOrCreate('add_maintenance_tasks', 'web'));
        $equipmentCategory = ItemCategory::create(['name' => 'Equipment', 'inventory_type' => 'equipment']);
        $this->equipment = InventoryItem::create([
            'lab_id' => $this->lab->id, 'category_id' => $equipmentCategory->id,
            'internal_code' => 'IMPORT-EQ-1', 'name' => 'Local equipment',
        ]);
        $this->category = MaintenanceCategory::create(['name' => 'Calibration', 'code' => 'IMP-CAL']);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    /** @return array<string, mixed> */
    private function row(array $overrides = []): array
    {
        return array_replace([
            'equipment_code' => $this->equipment->internal_code, 'name' => 'Imported calibration',
            'category_id' => $this->category->id, 'due_date' => '2026-12-01',
        ], $overrides);
    }

    /** @param list<array<string, mixed>> $rows */
    private function csv(array $rows): UploadedFile
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array_keys($rows[0]), ';', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, array_values($row), ';', '"', '');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return UploadedFile::fake()->createWithContent('maintenance.csv', $content);
    }

    private function submit(UploadedFile $file, ?string $key = null): TestResponse
    {
        return $this->post(route('maintenancetasks.import.upload'), ['file' => $file, 'request_key' => $key ?? (string) Str::uuid()]);
    }

    public function test_import_creates_pending_tasks_with_canonical_identity_and_a_receipt(): void
    {
        $key = (string) Str::uuid();
        $file = $this->csv([$this->row(['periodicity' => 2, 'periodicity_unit' => 'months', 'result' => '0'])]);
        $this->submit($file, $key)->assertRedirect(route('vap-maintenance.tasks'));
        $task = MaintenanceTask::where('equipment_id', $this->equipment->id)->sole();
        $this->assertFalse($task->is_executed);
        $this->assertTrue($task->is_planned);
        $this->assertSame('0', $task->result);
        $this->assertNull($task->supplier_id);
        $this->assertSame('2027-02-01', $task->next_date->toDateString());
        $this->assertStringStartsWith('IMP-CAL '.now()->year.'/', $task->maintenance_task_no);
        $receipt = MaintenanceTaskImport::findOrFail($key);
        $this->assertSame($this->lab->id, $receipt->lab_id);
        $this->assertSame($this->operator->id, $receipt->user_id);
        $this->assertSame([$task->id], $receipt->task_ids);
        $this->assertSame(1, $receipt->row_count);
    }

    public function test_retry_confirms_the_original_receipt_even_after_task_archiving(): void
    {
        $key = (string) Str::uuid();
        $file = $this->csv([$this->row()]);
        $this->submit($file, $key)->assertRedirect();
        $receipt = MaintenanceTaskImport::findOrFail($key)->getRawOriginal();
        $task = MaintenanceTask::where('equipment_id', $this->equipment->id)->sole();
        $task->delete();
        $this->travel(1)->days();
        $this->submit($file, $key)->assertRedirect();
        $this->assertSame(1, MaintenanceTask::withTrashed()->where('equipment_id', $this->equipment->id)->count());
        $this->assertTrue($task->fresh()->trashed());
        $this->assertSame($receipt, MaintenanceTaskImport::findOrFail($key)->getRawOriginal());
    }

    public function test_reusing_a_key_for_changed_content_is_rejected(): void
    {
        $key = (string) Str::uuid();
        $this->submit($this->csv([$this->row()]), $key)->assertRedirect();
        $this->submit($this->csv([$this->row(['name' => 'Changed content'])]), $key)->assertSessionHasErrors('file');
        $this->assertSame(1, MaintenanceTask::where('equipment_id', $this->equipment->id)->count());
    }

    #[DataProvider('invalidRows')]
    public function test_invalid_rows_reject_the_entire_file(array $invalid): void
    {
        $first = array_replace($this->row(array_fill_keys(array_keys($invalid), null)), $this->row());
        $second = $this->row($invalid);
        $file = $this->csv([$first, $second]);
        $this->submit($file)->assertSessionHasErrors('file');
        $this->assertStringStartsWith('Linha 3:', session('errors')->first('file'));
        $this->assertSame(0, MaintenanceTask::where('equipment_id', $this->equipment->id)->count());
        $this->assertSame(0, MaintenanceTaskImport::count());
    }

    public static function invalidRows(): array
    {
        return [
            'invalid date' => [['due_date' => 'not-a-date']],
            'timestamp instead of calendar date' => [['due_date' => '2026-12-01T00:00:00Z']],
            'invalid boolean' => [['is_planned' => 'false']],
            'external execution without supplier' => [['executed_by_supplier' => '1']],
            'missing recurrence amount' => [['periodicity_unit' => 'months']],
            'unsupported calibration status' => [['calibration_status' => 'completed']],
        ];
    }

    #[DataProvider('forbiddenColumns')]
    public function test_issued_identity_and_completion_columns_are_not_importable(string $column): void
    {
        $this->submit($this->csv([$this->row([$column => '1'])]))->assertSessionHasErrors('file');
        $this->assertSame(0, MaintenanceTaskImport::count());
    }

    public static function forbiddenColumns(): array
    {
        return array_map(fn (string $column): array => [$column], ['lab_id', 'seq', 'maintenance_task_no', 'maintenance_task_year', 'previous_date', 'next_date', 'is_executed', 'equipment_id']);
    }

    public function test_only_local_active_equipment_can_be_resolved(): void
    {
        $peer = VAPLab::factory()->create();
        InventoryItem::create(['lab_id' => $peer->id, 'category_id' => $this->equipment->category_id, 'name' => 'Peer equipment', 'internal_code' => 'PEER-ONLY']);
        $material = InventoryItem::create(['lab_id' => $this->lab->id, 'name' => 'Material', 'internal_code' => 'MATERIAL-ONLY']);
        foreach (['PEER-ONLY', 'MATERIAL-ONLY', 'MISSING'] as $code) {
            $this->submit($this->csv([$this->row(['equipment_code' => $code])]))->assertSessionHasErrors('file');
        }
        $this->equipment->delete();
        $this->submit($this->csv([$this->row()]))->assertSessionHasErrors('file');
        $this->assertSame(0, MaintenanceTaskImport::count());
        $this->assertSame(0, MaintenanceTask::whereIn('equipment_id', [$this->equipment->id, $material->id])->count());
    }

    public function test_same_code_in_a_peer_lab_does_not_change_the_local_match(): void
    {
        $peer = VAPLab::factory()->create();
        $foreign = InventoryItem::create(['lab_id' => $peer->id, 'category_id' => $this->equipment->category_id, 'name' => 'Peer equipment', 'internal_code' => $this->equipment->internal_code]);
        $this->submit($this->csv([$this->row()]))->assertRedirect();
        $this->assertSame(1, MaintenanceTask::where('equipment_id', $this->equipment->id)->count());
        $this->assertSame(0, MaintenanceTask::where('equipment_id', $foreign->id)->count());
    }

    public function test_write_failure_rolls_back_tasks_and_receipt(): void
    {
        $event = 'eloquent.creating: '.MaintenanceTask::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (MaintenanceTask $task): ?bool => $task->name === 'Second task' ? false : null);
        try {
            $this->submit($this->csv([$this->row(), $this->row(['name' => 'Second task'])]))->assertStatus(409);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
        $this->assertSame(0, MaintenanceTask::where('equipment_id', $this->equipment->id)->count());
        $this->assertSame(0, MaintenanceTaskImport::count());
    }

    public function test_receipt_failure_rolls_back_the_created_tasks(): void
    {
        $event = 'eloquent.creating: '.MaintenanceTaskImport::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (): bool => false);
        try {
            $this->submit($this->csv([$this->row()]))->assertStatus(409);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
        $this->assertSame(0, MaintenanceTask::where('equipment_id', $this->equipment->id)->count());
    }

    public function test_reference_page_template_and_upload_require_creation_permission(): void
    {
        $this->operator->revokePermissionTo('add_maintenance_tasks');
        $this->get(route('maintenancetasks.import.form'))->assertForbidden();
        $this->get(route('maintenancetasks.import.template'))->assertForbidden();
        $this->submit($this->csv([$this->row()]))->assertForbidden();
    }

    public function test_impersonation_cannot_open_or_submit_the_import(): void
    {
        $this->withSession(['impersonate' => $this->operator->id]);
        $this->get(route('maintenancetasks.import.form'))->assertForbidden();
        $this->submit($this->csv([$this->row()]))->assertForbidden();
    }

    public function test_action_rechecks_membership_before_replaying_a_receipt(): void
    {
        $file = $this->csv([$this->row()]);
        $key = (string) Str::uuid();
        $this->submit($file, $key)->assertRedirect();
        DB::table('lab_user')->where('lab_id', $this->lab->id)->where('user_id', $this->operator->id)->delete();
        $this->expectException(AuthorizationException::class);
        app(ImportMaintenanceTasks::class)->execute($this->lab->id, $this->operator->id, $file->getRealPath(), $key);
    }

    public function test_receipts_from_another_operator_or_lab_cannot_be_replayed(): void
    {
        $file = $this->csv([$this->row()]);
        $foreign = MaintenanceTaskImport::factory()->create(['file_hash' => hash_file('sha256', $file->getRealPath())]);
        $this->submit($file, $foreign->id)->assertNotFound();
        $this->assertSame(0, MaintenanceTask::where('equipment_id', $this->equipment->id)->count());
    }

    public function test_template_and_reference_data_are_canonical_and_lab_scoped(): void
    {
        InventoryItem::create(['lab_id' => VAPLab::factory()->create()->id, 'category_id' => $this->equipment->category_id, 'name' => 'Peer equipment']);
        $this->get(route('maintenancetasks.import.form'))->assertInertia(fn (Assert $page) => $page
            ->component('VAPMaintenance/Tasks/Import')->has('equipment', 1)->where('equipment.0.id', $this->equipment->id)
            ->where('breadcrumbs.0.url', route('vap-maintenance.tasks'))->where('breadcrumbs.1.title', 'Importar tarefas')
            ->where('maxRows', MaintenanceTaskCsv::MAX_ROWS));
        $response = $this->get(route('maintenancetasks.import.template'))->assertOk();
        $this->assertStringContainsString('equipment_code;', $response->streamedContent());
        $this->assertStringNotContainsString('is_executed', $response->streamedContent());
        $this->assertStringNotContainsString('maintenance_task_no', $response->streamedContent());
    }

    public function test_oversized_empty_malformed_and_non_utf8_files_fail_without_writes(): void
    {
        $this->submit($this->csv(array_fill(0, MaintenanceTaskCsv::MAX_ROWS + 1, $this->row())))->assertSessionHasErrors('file');
        foreach ([
            '', "name;name\nA;B\n", "equipment_code;name;category_id;due_date\nshort;row\n", "name\n\xFF\n",
        ] as $content) {
            $this->submit(UploadedFile::fake()->createWithContent('bad.csv', $content))->assertSessionHasErrors('file');
        }
        $this->submit(UploadedFile::fake()->create('large.csv', 2049, 'text/plain'))->assertSessionHasErrors('file');
        $this->submit(UploadedFile::fake()->createWithContent('bad.pdf', 'name;equipment_code'))->assertSessionHasErrors('file');
        $this->assertSame(0, MaintenanceTaskImport::count());
    }

    public function test_old_batch_progress_endpoints_are_not_accessible(): void
    {
        $this->get('/import-status/any-batch')->assertNotFound();
        $this->get('/maintenance-tasks/import-progress/any-batch')->assertNotFound();
    }

    public function test_completed_import_receipts_are_immutable(): void
    {
        $receipt = MaintenanceTaskImport::factory()->create();
        $this->expectException(LogicException::class);
        $receipt->update(['row_count' => 999]);
    }
}
