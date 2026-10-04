<?php

namespace Tests\Feature;

use App\Exports\MaintenanceCalendarExport;
use App\Exports\MaintenanceTasksExport;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\ItemCategory;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceTask;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\MaintenanceTaskQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaintenanceReportingTest extends TestCase
{
    use DatabaseTransactions;

    private User $operator;

    private VAPLab $lab;

    private InventoryItem $equipment;

    private MaintenanceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-04 15:00:00'));
        $this->lab = VAPLab::factory()->create(['name' => 'Reporting laboratory']);
        $this->operator = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $this->lab->id, 'user_id' => $this->operator->id]);
        foreach (['view_maintenance_tasks', 'export_maintenance_tasks', 'edit_maintenance_tasks'] as $permission) {
            $this->operator->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $category = ItemCategory::create(['name' => 'Equipment', 'inventory_type' => 'equipment']);
        $this->equipment = InventoryItem::create(['lab_id' => $this->lab->id, 'category_id' => $category->id, 'name' => 'Balance', 'internal_code' => 'REPORT-EQ']);
        $this->category = MaintenanceCategory::create(['name' => 'Reporting calibration', 'code' => 'REPORT']);
        $this->actingAs($this->operator)->withSession(['active_lab_id' => $this->lab->id]);
    }

    private function task(array $attributes = []): MaintenanceTask
    {
        return MaintenanceTask::create(array_replace([
            'name' => 'Reporting task', 'equipment_id' => $this->equipment->id,
            'category_id' => $this->category->id, 'maintenance_task_year' => 2026,
            'due_date' => '2026-10-04', 'cost' => 0, 'is_executed' => false, 'is_planned' => true,
        ], $attributes));
    }

    public function test_list_dashboard_and_exports_agree_on_today_zero_cost_and_scope(): void
    {
        $today = $this->task(['name' => 'TODAY-ZERO']);
        $this->task(['name' => 'TODAY-PAID', 'cost' => 30]);
        $this->task(['name' => 'OLD-ZERO', 'due_date' => '2026-10-03']);
        $this->task(['name' => 'ARCHIVED'])->delete();
        $peerEquipment = $this->equipment->replicate(['internal_code']);
        $peerEquipment->lab_id = VAPLab::factory()->create()->id;
        $peerEquipment->save();
        $this->task(['name' => 'PEER', 'equipment_id' => $peerEquipment->id]);
        $filters = ['date_to' => '2026-10-04', 'status' => 'upcoming', 'cost_max' => 0];
        foreach (['vap-maintenance.tasks', 'vap-maintenance.dashboard'] as $route) {
            $this->get(route($route, $filters))->assertInertia(fn (Assert $page) => $page
                ->has('tasks.data', 1)->where('tasks.data.0.id', $today->id)
                ->where('tasks.data.0.due_date', '2026-10-04')->where('tasks.data.0.days_until_due', 0)
                ->where('stats.total_tasks', 1)->where('stats.overdue', 0)->where('stats.due_soon', 1)
                ->where('stats.total_cost', 0)->where('stats.due_this_month', 1));
        }
        $this->assertSame([$today->id], (new MaintenanceTasksExport($this->lab->id, $filters))->collection()->pluck('id')->all());
        $calendar = new MaintenanceCalendarExport($this->lab->id, $filters + ['date_from' => '2026-10-04']);
        $this->assertSame(['TODAY-ZERO'], $calendar->collection()->pluck('task_name')->all());
    }

    public function test_every_export_filter_and_summary_applies_before_pagination(): void
    {
        $supplier = InventoryItemSupplier::create(['name' => 'Reporting supplier']);
        $match = $this->task(['name' => 'MATCH-REPORT', 'cost' => 12.50, 'supplier_id' => $supplier->id]);
        $this->task(['name' => 'MATCH-REPORT', 'cost' => 90, 'supplier_id' => $supplier->id]);
        $this->task(['name' => 'OTHER', 'cost' => 12.50]);
        $filters = ['search' => 'MATCH', 'category_id' => $this->category->id, 'equipment_id' => $this->equipment->id,
            'supplier_id' => $supplier->id, 'cost_min' => 12.5, 'cost_max' => 12.5, 'status' => 'planned'];
        $this->assertSame([$match->id], (new MaintenanceTasksExport($this->lab->id, $filters))->collection()->pluck('id')->all());
        $this->get(route('vap-maintenance.tasks', ['per_page' => 1]))->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 1)->where('stats.total_tasks', 3)->where('stats.total_cost', 115));
        $this->get(route('vap-maintenance.tasks', $filters))->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 1)->where('stats.total_cost', 12.5));
    }

    #[DataProvider('invalidFilters')]
    public function test_malformed_report_inputs_fail_validation_without_query_errors(array $filters, string $field): void
    {
        foreach (['vap-maintenance.tasks', 'vap-maintenance.dashboard'] as $route) {
            $this->getJson(route($route, $filters))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->getJson(route('vap-maintenance.export', $filters + ['type' => 'tasks', 'format' => 'csv']))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public static function invalidFilters(): array
    {
        return [
            [['date_from' => '2026-10-10', 'date_to' => '2026-10-01'], 'date_to'],
            [['date_to' => 'not-a-date'], 'date_to'],
            [['date_from' => '2026-10-04T10:00:00Z'], 'date_from'],
            [['cost_min' => 5, 'cost_max' => 0], 'cost_max'],
            [['cost_max' => -1], 'cost_max'],
            [['sort_by' => 'arbitrary_sql'], 'sort_by'],
            [['sort_direction' => 'arbitrary_sql'], 'sort_direction'],
            [['supplier_id' => ['invalid']], 'supplier_id'],
            [['status' => 'unknown'], 'status'],
        ];
    }

    public function test_export_ranges_have_explicit_meaning_and_foreign_task_cannot_expand_the_scope(): void
    {
        $this->task(['name' => 'TODAY']);
        $old = $this->task(['name' => 'OVERDUE', 'due_date' => '2026-10-03']);
        $response = $this->get(route('vap-maintenance.export', ['type' => 'tasks', 'format' => 'csv', 'range' => 'overdue', 'search' => 'TODAY']))->assertOk();
        $csv = file_get_contents($response->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('OVERDUE', $csv);
        $this->assertStringNotContainsString('TODAY', $csv);
        $all = $this->get(route('vap-maintenance.export', ['type' => 'tasks', 'format' => 'csv', 'range' => 'all', 'search' => 'MISSING']))->assertOk();
        $this->assertStringContainsString('TODAY', file_get_contents($all->baseResponse->getFile()->getPathname()));
        $old->delete();
        $this->get(route('vap-maintenance.export', ['type' => 'calendar', 'format' => 'csv', 'task_id' => $old->id, 'range' => 'all']))->assertNotFound();
    }

    #[DataProvider('downloadTypes')]
    public function test_downloads_return_real_documents_with_supported_names(string $type, string $format): void
    {
        $this->task(['name' => 'DOWNLOAD-RECORD']);
        $response = $this->get(route('vap-maintenance.export', [
            'type' => $type, 'format' => $format, 'date_from' => '2026-10-04', 'date_to' => '2026-10-04',
        ]))->assertOk();
        $extension = $format === 'excel' ? 'xlsx' : $format;
        $response->assertDownload('maintenance_'.$type.'_2026-10-04_15-00-00.'.$extension);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        if ($format === 'pdf') {
            $response->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        } else {
            $content = file_get_contents($response->baseResponse->getFile()->getPathname());
            $this->assertNotEmpty($content);
            if ($format === 'excel') {
                $this->assertStringStartsWith('PK', $content);
            } else {
                $this->assertStringContainsString('DOWNLOAD-RECORD', $content);
            }
        }
    }

    public static function downloadTypes(): array
    {
        return [['tasks', 'pdf'], ['tasks', 'csv'], ['tasks', 'excel'], ['calendar', 'pdf'], ['calendar', 'csv'], ['calendar', 'excel']];
    }

    public function test_report_alias_returns_a_pdf_not_json_and_rejects_unimplemented_types(): void
    {
        $this->task(['due_date' => '2026-10-03']);
        $response = $this->get(route('vap-maintenance.report.generate', ['report_type' => 'overdue', 'format' => 'pdf']))->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->getJson(route('vap-maintenance.report.generate', ['report_type' => 'category_summary', 'format' => 'csv']))->assertUnprocessable();
        $this->getJson(route('vap-maintenance.export', ['type' => 'categories', 'format' => 'csv']))->assertUnprocessable();
    }

    public function test_export_permission_and_current_lab_membership_are_enforced(): void
    {
        $this->operator->revokePermissionTo('export_maintenance_tasks');
        $this->get(route('vap-maintenance.export', ['type' => 'tasks', 'format' => 'csv']))->assertForbidden();
        $this->get(route('vap-maintenance.report.generate', ['report_type' => 'overdue', 'format' => 'pdf']))->assertForbidden();
        $this->operator->givePermissionTo('export_maintenance_tasks');
        DB::table('lab_user')->where('user_id', $this->operator->id)->delete();
        $this->get(route('vap-maintenance.export', ['type' => 'tasks', 'format' => 'csv']))->assertForbidden();
    }

    public function test_calendar_date_span_and_removed_options_are_rejected(): void
    {
        foreach ([['date_from' => '2020-01-01', 'date_to' => '2030-01-01'], ['fields' => 'all'], ['archived' => 1], ['filters' => '{}']] as $filters) {
            $this->getJson(route('vap-maintenance.export', $filters + ['type' => 'calendar', 'format' => 'csv']))->assertUnprocessable();
        }
        $this->expectException(ValidationException::class);
        new MaintenanceCalendarExport($this->lab->id, ['date_from' => '2020-01-01', 'date_to' => '2030-01-01']);
    }

    public function test_spreadsheet_text_is_safe_and_archived_categories_do_not_crash_downloads(): void
    {
        $task = $this->task(['name' => '=1+1', 'description' => ' @SUM(1,2)']);
        $this->category->delete();
        $export = new MaintenanceTasksExport($this->lab->id);
        $row = $export->map($export->collection()->sole());
        $this->assertSame("'=1+1", $row[1]);
        $this->assertSame('Reporting calibration', $row[2]);
        $this->assertSame("' @SUM(1,2)", $row[11]);
        $calendar = new MaintenanceCalendarExport($this->lab->id, ['date_from' => '2026-10-04', 'date_to' => '2026-10-04']);
        $this->assertSame("'=1+1", $calendar->map($calendar->collection()->sole())[3]);
        $this->assertStringContainsString('Reporting laboratory', view('exports.maintenance.tasks', [
            'tasks' => $export->collection(), 'filters' => [], 'generated_at' => now(), 'labName' => $this->lab->name,
        ])->render());
    }

    public function test_chart_period_is_a_filtered_creation_cohort_and_costs_are_truthful(): void
    {
        $this->task(['cost' => 12]);
        $this->task(['cost' => 0, 'due_date' => '2026-10-03']);
        $this->task(['cost' => 40])->forceFill(['created_at' => '2026-08-10 12:00:00'])->save();
        $this->getJson(route('vap-maintenance.stats', ['period' => 'month']))->assertOk()
            ->assertJsonPath('status_stats.overdue', 1)->assertJsonPath('status_stats.due_soon', 1)
            ->assertJsonPath('total_cost', 12)->assertJsonPath('avg_cost', 6)
            ->assertJsonPath('highest_cost_category.cost', 12)->assertJsonPath('period_start', '2026-10-01');
        $this->getJson(route('vap-maintenance.stats', ['period' => 'quarter', 'cost_min' => 1]))->assertOk()
            ->assertJsonPath('total_cost', 52)->assertJsonPath('status_stats.overdue', 0)->assertJsonPath('period_start', '2026-08-01');
        $this->getJson(route('vap-maintenance.stats', ['period' => 'unknown']))->assertUnprocessable();
    }

    public function test_oversized_export_fails_instead_of_silently_truncating(): void
    {
        $prototype = $this->task();
        $row = $prototype->getAttributes();
        unset($row['id']);
        $rows = [];
        for ($index = 1; $index <= MaintenanceTaskQuery::MAX_EXPORT_ROWS; $index++) {
            $rows[] = array_replace($row, ['seq' => $index + 1, 'maintenance_task_no' => 'REPORT-BOUND-'.$index]);
        }
        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('maintenance_tasks')->insert($chunk);
        }
        $this->getJson(route('vap-maintenance.export', ['type' => 'tasks', 'format' => 'csv']))->assertUnprocessable()->assertJsonValidationErrors('export');
    }
}
