<?php

namespace Tests\Feature;

use App\Jobs\ImportEquipmentsChunk;
use App\Jobs\ImportMaintenanceTasksChunk;
use App\Models\MaintenanceTask;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryImportLaboratoryTest extends TestCase
{
    use DatabaseTransactions;

    private function member(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), 'last_activity_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        return $user;
    }

    public function test_retired_equipment_transport_and_already_queued_job_cannot_write_any_catalogue_records(): void
    {
        $lab = VAPLab::factory()->create();
        $member = $this->member($lab);
        $member->givePermissionTo(Permission::findOrCreate('add_iequipments', 'web'));
        $this->actingAs($member)->withSession(['active_lab_id' => $lab->id]);
        $before = [];
        foreach (['i_items', 'departments', 'i_suppliers', 'item_categories', 'sequence_counters'] as $table) {
            $before[$table] = DB::table($table)->get()->map(fn (object $row): array => (array) $row)->all();
        }
        $this->get('/equipments/import')->assertNotFound();
        $this->postJson('/equipments/import', [])->assertNotFound();
        $this->get('/equipments/import-progress/retired-batch')->assertNotFound();
        $row = array_fill(0, 18, null);
        $row[0] = 'FORGED-CODE';
        $row[1] = '999';
        $row[5] = 'Unsafe old equipment';
        foreach ([false, true] as $revoked) {
            if ($revoked) {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $member->id)->delete();
            }
            try {
                (new ImportEquipmentsChunk([$row], $lab->id, $member->id))->handle();
                $this->fail('An old queued raw writer must never run.');
            } catch (HttpException $exception) {
                $this->assertSame(410, $exception->getStatusCode());
            }
            foreach ($before as $table => $rows) {
                $this->assertSame($rows, DB::table($table)->get()->map(fn (object $row): array => (array) $row)->all(), $table);
            }
        }
    }

    public function test_retired_maintenance_job_cannot_bypass_canonical_import_validation(): void
    {
        $lab = VAPLab::factory()->create();
        $member = $this->member($lab);
        $before = MaintenanceTask::query()->count();
        $row = array_fill(0, 16, null);
        $row[0] = 'FORGED-EQUIPMENT';
        $row[8] = today()->toDateString();
        try {
            (new ImportMaintenanceTasksChunk([$row], $lab->id, $member->id))->handle();
            $this->fail('Retired jobs must not create maintenance records.');
        } catch (HttpException $exception) {
            $this->assertSame(410, $exception->getStatusCode());
        }
        $this->assertSame($before, MaintenanceTask::query()->count());
    }
}
