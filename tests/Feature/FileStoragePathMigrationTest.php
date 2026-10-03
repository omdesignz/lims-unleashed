<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VAPFile;
use App\Models\VAPFileVersion;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class FileStoragePathMigrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retained_file_paths_survive_binary_to_text_conversion(): void
    {
        $user = User::factory()->create();
        $lab = VAPLab::factory()->create();
        $path = 'controlled/relatório-'.fake()->uuid().'.pdf';
        $file = VAPFile::query()->create([
            'lab_id' => $lab->id,
            'name' => 'relatório.pdf',
            'type' => 'file',
            'modified_at' => now(),
            'content' => $path,
            'created_by' => $user->id,
        ]);
        $version = VAPFileVersion::query()->create([
            'file_id' => $file->id,
            'content' => $path,
            'created_by' => $user->id,
        ]);

        foreach (['v_files', 'v_file_versions'] as $table) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN content TYPE bytea USING convert_to(content, 'UTF8')");
            $this->assertSame('bytea', Schema::getColumnType($table, 'content'));
        }

        $migration = require database_path('migrations/2026_09_30_064102_align_file_storage_path_columns.php');
        $migration->up();

        $this->assertSame('text', Schema::getColumnType('v_files', 'content'));
        $this->assertSame('text', Schema::getColumnType('v_file_versions', 'content'));
        $this->assertSame($path, $file->fresh()->content);
        $this->assertSame($path, $version->fresh()->content);

        try {
            $migration->down();
            $this->fail('Expected rollback to preserve retained document paths.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot restore binary file-path columns while documents are retained.', $exception->getMessage());
        }

        $this->assertSame('text', Schema::getColumnType('v_files', 'content'));
    }

    public function test_empty_file_path_schema_can_roll_back_and_reapply(): void
    {
        $this->assertDatabaseCount('v_files', 0);
        $this->assertDatabaseCount('v_file_versions', 0);
        $migration = require database_path('migrations/2026_09_30_064102_align_file_storage_path_columns.php');

        $migration->down();
        $this->assertSame('bytea', Schema::getColumnType('v_files', 'content'));
        $this->assertSame('bytea', Schema::getColumnType('v_file_versions', 'content'));

        $migration->up();
        $this->assertSame('text', Schema::getColumnType('v_files', 'content'));
        $this->assertSame('text', Schema::getColumnType('v_file_versions', 'content'));

        $migration->up();
        $this->assertSame('text', Schema::getColumnType('v_files', 'content'));
    }
}
