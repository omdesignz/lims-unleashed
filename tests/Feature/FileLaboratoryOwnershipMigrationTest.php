<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VAPFile;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class FileLaboratoryOwnershipMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retained_documents_prevent_removal_of_laboratory_ownership(): void
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create();
        VAPFile::query()->create([
            'lab_id' => $lab->id,
            'name' => 'procedure.pdf',
            'type' => 'file',
            'modified_at' => now(),
            'created_by' => $user->id,
        ]);
        $migration = require database_path('migrations/2026_09_30_065455_add_laboratory_owner_to_vap_files_table.php');

        try {
            $migration->down();
            $this->fail('Expected rollback to preserve retained laboratory ownership.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot remove laboratory ownership from retained documents.', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('v_files', 'lab_id'));
    }

    public function test_empty_file_schema_can_roll_back_and_reapply_laboratory_ownership(): void
    {
        $this->assertDatabaseCount('v_files', 0);
        $migration = require database_path('migrations/2026_09_30_065455_add_laboratory_owner_to_vap_files_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('v_files', 'lab_id'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('v_files', 'lab_id'));
    }

    public function test_retained_unowned_documents_prevent_automatic_laboratory_assignment(): void
    {
        $this->assertDatabaseCount('v_files', 0);
        $migration = require database_path('migrations/2026_09_30_065455_add_laboratory_owner_to_vap_files_table.php');
        $migration->down();

        $user = User::factory()->create();
        VAPFile::query()->create([
            'name' => 'unassigned.pdf',
            'type' => 'file',
            'modified_at' => now(),
            'created_by' => $user->id,
        ]);

        try {
            $migration->up();
            $this->fail('Expected migration to reject unassigned retained documents.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Assign retained documents to laboratories before adding file ownership.', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('v_files', 'lab_id'));
    }
}
