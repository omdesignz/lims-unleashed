<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VAPLab;
use App\Models\Worksheet;
use Database\Seeders\WorksheetPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class WorksheetOwnershipMigrationTest extends TestCase
{
    use DatabaseTransactions;

    private function migration(): object
    {
        return require database_path('migrations/2026_09_28_180826_add_laboratory_ownership_to_worksheets_table.php');
    }

    public function test_empty_schema_rollback_replay_and_repeated_up_preserve_constraints(): void
    {
        $this->assertSame(0, DB::table('worksheets')->count());
        $migration = $this->migration();
        $migration->down();
        $this->assertFalse(Schema::hasColumn('worksheets', 'lab_id'));
        $this->assertFalse(Schema::hasColumn('worksheets', 'analysis_id'));
        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('worksheets', 'lab_id'));
        $this->assertTrue(Schema::hasColumn('worksheets', 'analysis_id'));
        $this->assertTrue(Schema::hasIndex('worksheets', 'worksheets_lab_visibility_index'));
        $this->assertTrue(Schema::hasIndex('worksheets', 'worksheets_analysis_id_unique', 'unique'));
    }

    public function test_rollback_refuses_to_remove_retained_workbook_ownership(): void
    {
        $worksheet = Worksheet::query()->create([
            'name' => 'Retained worksheet', 'lab_id' => VAPLab::factory()->create()->id,
            'user_id' => User::factory()->create()->id, 'worksheets' => ['sheets' => [['data' => [['retained evidence']]]]],
        ]);
        $before = $worksheet->fresh()->getAttributes();
        try {
            $this->migration()->down();
            $this->fail('The migration removed ownership from a retained worksheet.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('retained worksheets', $exception->getMessage());
        }
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
        $this->assertTrue(Schema::hasColumn('worksheets', 'lab_id'));
        $this->migration()->up();
        $this->assertSame($before, $worksheet->fresh()->getAttributes());
    }

    public function test_unknown_existing_records_are_preserved_and_never_assigned_a_guessed_lab(): void
    {
        $this->migration()->down();
        $id = DB::table('worksheets')->insertGetId([
            'name' => 'Unreviewed workbook', 'user_id' => User::factory()->create()->id,
            'worksheets' => json_encode(['sheets' => [['data' => [['original evidence']]]]], JSON_THROW_ON_ERROR),
        ]);
        $before = DB::table('worksheets')->find($id);
        try {
            $this->migration()->up();
            $this->fail('An unreviewed existing worksheet was assigned a laboratory.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ownership must be reviewed', $exception->getMessage());
        }
        $this->assertEquals($before, DB::table('worksheets')->find($id));
        $this->assertFalse(Schema::hasColumn('worksheets', 'lab_id'));
    }

    public function test_permission_registration_is_idempotent_and_grants_no_user_access(): void
    {
        $operator = User::factory()->create();
        $before = $operator->getAllPermissions()->pluck('name')->all();
        $this->seed(WorksheetPermissionSeeder::class);
        $this->seed(WorksheetPermissionSeeder::class);
        $this->assertSame(5, DB::table('permissions')->where('guard_name', 'web')->whereIn('name', [
            'view_worksheets', 'add_worksheets', 'edit_worksheets', 'delete_worksheets', 'restore_worksheets',
        ])->count());
        $this->assertSame($before, $operator->fresh()->getAllPermissions()->pluck('name')->all());
    }
}
